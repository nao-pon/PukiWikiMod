<?php
// $Id: trackback.php,v 1.5 2004/01/27 14:30:47 nao-pon Exp $
/*
 * PukiWiki TrackBack プログラム
 * (C) 2003, Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
 * License: GPL
 *
 * http://localhost/pukiwiki/pukiwiki.php?FrontPage と明確に指定しないと
 * TrackBack ID の取得はできない
 *
 * tb_get_id($page)       TrackBack Ping IDを取得
 * tb_id2page($tb_id)     TrackBack Ping ID からページ名を取得
 * tb_get_filename($page) TrackBack Ping データファイル名を取得
 * tb_count($page)        TrackBack Ping データ個数取得  // pukiwiki.skin.LANG.php
 * tb_send($page,$data)   TrackBack Ping 送信  // file.php
 * tb_delete($page)       TrackBack Ping データ削除  // edit.inc.php
 * tb_get($file,$key=1)   TrackBack Ping データ入力
 * tb_get_rdf($page)      文章中に埋め込むためのrdfをデータを生成 // pukiwiki.php
 * tb_get_url($url)       文書をGETし、埋め込まれたTrackBack Ping URLを取得
 * class TrackBack_XML    XMLからTrackBack Ping IDを取得するクラス
 * == Referer 対応分 ==
 * ref_save($page)        Referer データ保存(更新) // pukiwiki.php
 */

if (!defined('TRACKBACK_DIR'))
{
	define('TRACKBACK_DIR','./trackback/');
}

// TrackBack Ping IDを取得
function tb_get_id($page)
{
	return md5(strip_bracket($page));
}

// TrackBack Ping ID からページ名を取得
function tb_id2page($tb_id)
{
	static $pages,$cache = array();
	
	if (array_key_exists($tb_id,$cache))
	{
		return $cache[$tb_id];
	}
	if (!isset($pages))
	{
		$pages = get_existpages();
	}
	foreach ($pages as $page)
	{
		$page = strip_bracket($page);
		$_tb_id = tb_get_id($page);
		$cache[$_tb_id] = $page;
		unset($pages[$page]);
		if ($_tb_id == $tb_id)
		{
			return $page;
		}
	}
	return FALSE; // 見つからない場合
}

// TrackBack Ping データファイル名を取得
function tb_get_filename($page,$ext='.txt')
{
	return TRACKBACK_DIR.tb_get_id($page).$ext;
}

// TrackBack Ping データ個数取得
function tb_count($page,$ext='.txt')
{
	if ($ext == ".txt")
	{
		global $xoopsDB;
		
		$page = strip_bracket($page);
		
		$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_tb")." WHERE page_name='".addslashes($page)."';";
		$results=$xoopsDB->query($query);

		return mysql_num_rows($results);
	}
	else
	{
		$filename = tb_get_filename($page,$ext);
		return file_exists($filename) ? count(file($filename)) : 0;
	}
}

// TrackBack Ping 送信
function tb_send($page,$data="")
{
	global $script,$trackback,$page_title,$interwiki,$notb_plugin,$h_excerpt,$auto_template_name;
	
	$page = strip_bracket($page);
	
	if (!$trackback || 
		($page == $interwiki) || 
		(preg_match("/".preg_quote("/$auto_template_name","/")."(_m)?$/",$page))
		)
	{
		return;
	}
	
	
	if (!$data)
	{
		// ファイルに一時保管
		$filename = CACHE_DIR.encode($page).".tbf";
		if (!($fp = fopen($filename,'w')))
		{
			return;
		}
		fclose($fp);
		return;
	}
	
	// 送信済みエントリーを取得
	$ping_filename = TRACKBACK_DIR.tb_get_id($page).".ping";
	$to_pukiwiki = $sended = array();
	$sended_count = 0;
	// 改行文字を削除
	foreach(@file($ping_filename) as $line)
	{
		//Pukiwikiはスタックされないので送信済みに加えない
		if (strpos($line,"?plugin=tb&tb_id=") === false || //PukiWiki
			strpos($line,"?pwm_ping=") === false)          //PukiWikiMod Ver 0.08b4以降
			$sended[] = trim($line);
		else
			$to_pukiwiki[] = trim($line);
		$sended_count++;
	}
	
	set_time_limit(0); // 処理実行時間制限(php.ini オプション max_execution_time )
	
	// convert_html() 変換結果の <a> タグから URL 抽出
	preg_match_all('#href="(https?://[^"]+)"#',$data,$links,PREG_PATTERN_ORDER);

	// 自ホスト($scriptで始まるurl)を除く
	$links = preg_grep("/^(?!".preg_quote($script,'/')."\?)./",$links[1]);
	
	// convert_html() 変換結果から Ping 送出  URL 抽出
	preg_match_all('#<!--__PINGSEND__"(https?://[^"]+)"-->#',$data,$pings,PREG_PATTERN_ORDER);
	
	// 自ホスト($scriptで始まるurl)を除く
	$pings = preg_grep("/^(?!".preg_quote($script,'/')."\?)./",$pings[1]);
	
	$pings = str_replace("&amp;","&",$pings);
	$pings = array_unique($pings);
	
	$xml_title = $title = preg_replace("/\/[0-9\-]+$/","",$page);//末尾の数字とハイフンは除く
	if ($h_excerpt) $title .= "/".$h_excerpt;
	
	$myurl = $script."?pgid=".get_pgid_by_name($page);
	$xml_myurl = $script."?".rawurlencode($xml_title);
	$xml_title = $page_title."/".$xml_title;
	
	$data = trim(strip_htmltag($data));
	$data = preg_replace("/^".preg_quote($h_excerpt,"/")."/","",$data);
	
	// RPC Ping のデーター
	$rpcdata = '<?xml version="1.0"?>
<methodCall>
  <methodName>weblogUpdates.ping</methodName>
  <params>
    <param>
      <value>'.mb_convert_encoding($xml_title,"UTF-8",SOURCE_ENCODING).'</value>
    </param>
    <param>
      <value>'.$xml_myurl.'</value>
    </param>
  </params>
</methodCall>';
	
	// 自文書の情報
	$putdata = array(
		'title'     => $title,
		'url'       => $myurl,
		'excerpt'   => mb_strimwidth(preg_replace("/[\r\n]/",' ',$data),0,255,'...'),
		'blog_name' => $page_title,
		'charset'   => SOURCE_ENCODING // 送信側文字コード(未既定)
	);
	
	// Ping送信制限値をチェック
	if ($trackback > $sended_count)
	{
		if (is_array($links) && count($links) != 0)
		{
			foreach ($links as $link)
			{
				// URL から TrackBack ID を取得する
				$tb_id = trim(str_replace("&amp;","&",tb_get_url($link)));
				if (empty($tb_id) || in_array($tb_id,$sended)) // TrackBack に対応していない
				{
					continue;
				}
				$result = http_request($tb_id,'POST','',$putdata);
				//echo htmlspecialchars($tb_id).":auto<hr />";
				if ($result['rc'] === 200)
					$sended[] = $tb_id;

				// FIXME: エラー処理を行っても、じゃ、どうする？だしなぁ...
			}
		}
		
		// 送信済みのエントリーを削除
		$pings = array_diff($pings,$sended);
	}
	else
	{
		//送信Ping制限値を超えている場合は、PukiWiki宛てのみ送信
		$pings = $to_pukiwiki;
	}

	if (is_array($pings) && count($pings) != 0)
	{
		// Ping 指定先へ送信
		foreach ($pings as $tb_id)
		{
			$result = http_request($tb_id,'POST','',$putdata);
/*
			echo htmlspecialchars($tb_id).":ping<hr />";
			echo $result['query']."<hr />";
			echo $result['rc']."<hr />";
			echo $result['header']."<hr />";
			echo $result['data']."<hr />";
*/
			$done = false;
			// 超手抜きなチェック
			if (strpos($result['data'],"<error>0</error>") !== false)
				$done = true;
			else
			{
				// XML RPC Ping を打ってみる
				$result = http_request($tb_id,'POST',"Content-Type: text/xml\r\n",$rpcdata);
				// 超手抜きなチェック
				if (strpos($result['data'],"<boolean>0</boolean>") !== false)
					$done = true;
			}
			
			if ($done)
				$sended[] = $tb_id;
		}
	}
	
	if ($sended){
		// 送信済みエントリを記録
		$fp = fopen($ping_filename,'w');
		flock($fp,LOCK_EX);
		foreach ($sended as $entry)
		{
			fwrite($fp,$entry."\n");
		}
		flock($fp,LOCK_UN);
		fclose($fp);
	}

}

// TrackBack Ping データ削除
function tb_delete($page)
{
	global $xoopsDB;
	$page = addslashes(strip_bracket($page));
	$where = " WHERE page_name='$page'";
	
	$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_tb")." $where;";
	$results=$xoopsDB->queryF($query);

/*
	$filename = tb_get_filename($page);
	if (file_exists($filename))
	{
		@unlink($filename);
	}
*/
}

// TrackBack Ping データ入力
function tb_get_db($tb_id,$key=1)
{
	global $xoopsDB;
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_tb")." WHERE tb_id='$tb_id' ORDER BY last_time DESC;";
	$results=$xoopsDB->query($query);
	//echo $query;
	
	if (!mysql_num_rows($results)) return array();

	$result = array();
	while ($data = mysql_fetch_array($results))
	{
		$result[$data[$key]] = $data;
	}
	return $result;
}
// TrackBack Ping データ入力
function tb_get($file,$key=1)
{
	$tb_id = str_replace(TRACKBACK_DIR,"",$file);
	$tb_id = str_replace(".txt","",$tb_id);
	return tb_get_db($tb_id,$key);
/*
	if (!file_exists($file))
	{
		return array();
	}
	
	$result = array();
	$fp = @fopen($file,'r');
	flock($fp,LOCK_EX);
	while ($data = @fgetcsv($fp,8192,','))
	{
		// $data[$key] = URL
		$result[$data[$key]] = $data;
	}
	flock($fp,LOCK_UN);
	fclose ($fp);
	
	return $result;
*/
}

// 文章中に trackback:ping を埋め込むためのデータを生成
function tb_get_rdf($page)
{
	global $script,$trackback;
	
	if (!$trackback)
	{
		return '';
	}
	$page = strip_bracket($page);
	$r_page = rawurlencode($page);
	$creator = get_pg_auther_name(add_bracket($page));
	$tb_id = tb_get_id($page);
	//$self = (getenv('SERVER_PORT')==443?'https://':('http://')).getenv('SERVER_NAME').(getenv('SERVER_PORT')==80?'':(':'.getenv('SERVER_PORT'))).getenv('SERVER_NAME').getenv('REQUEST_URI');
	$self = (getenv('SERVER_PORT')==443?'https://':('http://')).getenv('SERVER_NAME').(getenv('SERVER_PORT')==80?'':(':'.getenv('SERVER_PORT'))).getenv('REQUEST_URI');
	$dcdate = substr_replace(get_date('Y-m-d\TH:i:sO',$time),':',-2,0);
	// dc:date="$dcdate"
	
	return <<<EOD
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
 <rdf:Description
   rdf:about="$script?$r_page"
   dc:identifier="$self"
   dc:title="$page"
   trackback:ping="$script?pwm_ping=$tb_id"
   dc:creator="$creator"
   dc:date="$dcdate" />
</rdf:RDF>
-->
EOD;
}

// 文書をGETし、埋め込まれたTrackBack Ping urlを取得
function tb_get_url($url)
{
	// プロキシを経由する必要があるホストにはpingを送信しない
	$parse_url = parse_url($url);
	if (empty($parse_url['host']) or via_proxy($parse_url['host']))
	{
		return '';
	}
	
	$data = http_request($url);
	
	if ($data['rc'] !== 200)
	{
		return '';
	}
	
	if (!preg_match_all('#<rdf:RDF[^>]*>(.*?)</rdf:RDF>#si',$data['data'],$matches,PREG_PATTERN_ORDER))
	{
		return '';
	}
	
	$tb_urls = array();
	$obj = new TrackBack_XML();
	foreach ($matches[1] as $body)
	{
		list($tb_url,$tb_url_nc) = $obj->parse($body,$url);
		if ($tb_url !== FALSE)
			return $tb_url;
		if ($tb_url_nc !== FALSE)
			$tb_urls[] = $tb_url_nc;
	}
	if (count($tb_urls) == 1)
		return $tb_urls[0];
	
	return '';
}

// 埋め込まれたデータから TrackBack Ping urlを取得するクラス
class TrackBack_XML
{
	var $url;
	var $tb_url;
	var $tb_url_nc;
	
	function parse($buf,$url)
	{
		// 初期化
		$this->url = $url;
		$this->tb_url = FALSE;
		$this->tb_url_nc = FALSE;
		
		$xml_parser = xml_parser_create();
		if ($xml_parser === FALSE)
		{
			return FALSE;
		}
		xml_set_element_handler($xml_parser,array(&$this,'start_element'),array(&$this,'end_element'));
		
		if (!xml_parse($xml_parser,$buf,TRUE))
		{
/*			die(sprintf('XML error: %s at line %d in %s',
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser),
				$buf
			));
*/
			return FALSE;
		}
		return array($this->tb_url,$this->tb_url_nc);
	}
	function start_element($parser,$name,$attrs)
	{
		if ($name !== 'RDF:DESCRIPTION')
		{
			return;
		}
		
		$about = $url = $tb_url = '';
		foreach ($attrs as $key=>$value)
		{
			switch ($key)
			{
				case 'RDF:ABOUT':
					$about = $value;
					break;
				case 'DC:IDENTIFER':
				case 'DC:IDENTIFIER':
					$url = $value;
					break;
				case 'TRACKBACK:PING':
					$tb_url = $value;
					break;
			}
		}
		
		if ($about == $this->url or $url == $this->url)
		{
			$this->tb_url = $tb_url;
		}
		if ($tb_url) $this->tb_url_nc = $tb_url;
	}
	function end_element($parser,$name)
	{
		// do nothing
	}
}

// Referer データ保存(更新)
function ref_save($page)
{
	global $referer;
	
	if (!$referer or empty($_SERVER['HTTP_REFERER']))
	{
		return;
	}
	
	$url = $_SERVER['HTTP_REFERER'];
	
	// URI の妥当性評価
	// 自サイト内の場合は処理しない
	$parse_url = parse_url($url);
	if (empty($parse_url['host']) or $parse_url['host'] == $_SERVER['HTTP_HOST'])
	{
		return;
	}
	
	// TRACKBACK_DIR の存在と書き込み可能かの確認
	if (!is_dir(TRACKBACK_DIR))
	{
		die(TRACKBACK_DIR.': No such directory');
	}
	if (!is_writable(TRACKBACK_DIR))
	{
		die(TRACKBACK_DIR.': Permission denied');
	}
	
	// Referer のデータを更新
	if (ereg("[,\"\n\r]",$url))
	{
		$url = '"'.str_replace('"', '""', $url).'"';
	}
	$filename = tb_get_filename($page,'.ref');
	$data = tb_get($filename,3);
	if (!array_key_exists($url,$data))
	{
		// 0:最終更新日時, 1:初回登録日時, 2:参照カウンタ, 3:Referer ヘッダ, 4:利用可否フラグ(1は有効)
		$data[$url] = array(UTIME,UTIME,0,$url,1);
	}
	$data[$url][0] = UTIME;
	$data[$url][2]++;
	
	if (!($fp = fopen($filename,'w')))
	{
		return 1;
	}
	flock($fp, LOCK_EX);
	foreach ($data as $line)
	{
		fwrite($fp,join(',',$line)."\n");
	}
	flock($fp, LOCK_UN);
	fclose($fp);
	
	return 0;
}

// 送信済みPING一覧を得る
function get_sended_pings($tb_id)
{
	$ping_filename = TRACKBACK_DIR.$tb_id.".ping";
	$sended = (file_exists($ping_filename))? @file($ping_filename) : "";

	if ($sended)
	{
		$tag = "<ul>\n";
		$count = 0;
		foreach($sended as $entry)
		{
			$entry = trim($entry);
			$tag .= "<li>".$entry."\n";
			if (strpos($entry,"?") === false)
			{
				//if (preg_match("/^(.*)\/([^\/]+)/",$entry,$arg))
				//	$entry = $arg[1]."?entry_id=".$arg[2];
			}
			else
				$tag .= str_replace("&","&amp;"," [<a href=\"".$entry."&__mode=view\">View</a>]");
			$count ++;
		}
		$tag .= "</ul>\n";
		$ret['tag'] = $tag;
		$ret['count'] = $count;
	}
	else
	{
		$ret['tag'] = "";
		$ret['count'] = 0;
	}
	return $ret;
}
?>