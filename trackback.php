<?php
// $Id: trackback.php,v 1.24 2005/05/20 00:07:01 nao-pon Exp $
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
	return get_pgid_by_name($page);
}

// TrackBack Ping ID からページ名を取得
function tb_id2page($tb_id)
{
	$page = strip_bracket(get_pgname_by_id($tb_id));
	
	return ($page)? $page : FALSE;
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
	global $script,$trackback,$update_ping_to,$page_title,$interwiki,$notb_plugin,$h_excerpt,$auto_template_name,$update_ping_to,$defaultpage;
	global $trackback_encoding,$vars;
	
	$page = strip_bracket($page);

	// 処理しない場合
	if ((!$trackback && !$update_ping_to) || 
		($page == $interwiki) || 
		(preg_match("/".preg_quote("/$auto_template_name","/")."(_m)?$/",$page))
		)
	{
		return;
	}
	
	$filename = CACHE_DIR.encode($page).".tbf";
	
	if (!$data)
	{
		// ファイルに一時保管
		touch($filename);
		//if ($fp = fopen($filename,'wb'))
		//{
		//	fputs($fp,XOOPS_URL."/modules/pukiwiki/ping.php?p=".rawurlencode($page)."&t=".$vars['is_rsstop']);
		//	fclose($fp);
		//}
		
		// 非同期モードで別スレッド処理 Blokking=0,Retry=5,接続タイムアウト=30,ソケットタイムアウト=0(指定しない)
		$ret = http_request(
		XOOPS_URL."/modules/pukiwiki/ping.php?p=".rawurlencode($page)."&t=".$vars['is_rsstop']
		,'GET','',array(),HTTP_REQUEST_URL_REDIRECT_MAX,0,5,30,0);
		
		return;
	}
	
	//error_reporting(E_ALL);
	
	// 送信済みエントリーを取得
	$ping_filename = TRACKBACK_DIR.tb_get_id($page).".ping";
	$to_pukiwiki = $sended = array();
	$sended_count = 0;
	// 改行文字を削除
	foreach(@file($ping_filename) as $line)
	{
		//Pukiwikiはスタックされないので送信済みに加えない
		if (strpos($line,"?plugin=tb&tb_id=") !== false || //PukiWiki
			strpos($line,"/modules/pukiwiki/") !== false   //PukiWikiMod 全般
			)
			$to_pukiwiki[] = trim($line);
		else
			$sended[] = trim($line);
		
		$sended_count++;
	}
	
	//set_time_limit(0); // 処理実行時間制限(php.ini オプション max_execution_time )
	
	// convert_html() 変換結果の <a> タグから URL 抽出
	preg_match_all('#href="(https?://[^"]+)"#',$data,$links,PREG_PATTERN_ORDER);

	// 自ホスト(XOOPS_WIKI_URLで始まるurl)を除く
	$links = preg_grep("/^(?!".preg_quote(XOOPS_WIKI_HOST.XOOPS_WIKI_URL,'/').")./",$links[1]);
	
	// convert_html() 変換結果から Ping 送出  URL 抽出
	preg_match_all('#<!--__PINGSEND__"(https?://[^"]+)"-->#',$data,$pings,PREG_PATTERN_ORDER);
	
	// 自ホスト($scriptで始まるurl)を除く
	$pings = preg_grep("/^(?!".preg_quote(XOOPS_WIKI_HOST.$script,'/')."\?)./",$pings[1]);
	
	$pings = str_replace("&amp;","&",$pings);
	$pings = array_unique($pings);
	
	$title = preg_replace("/\/[0-9\-]+$/","",$page);//末尾の数字とハイフンは除く
	$up_page = (strpos($page,"/")) ? preg_replace("/(.+)\/[^\/]+/","$1",strip_bracket($page)) : $defaultpage;
	if (!is_page($up_page)) $up_page = $defaultpage;
	
	if ($h_excerpt) $title .= "/".$h_excerpt;
	
	$myurl = XOOPS_WIKI_HOST.get_url_by_name($page);
	$xml_myurl = XOOPS_WIKI_HOST.get_url_by_name($up_page);
	
	$xml_title = $page_title."/".$up_page;
	
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
	
	// 送信文字コード
	$_send_encoding = SOURCE_ENCODING;
	$data = mb_strimwidth(preg_replace("/[\r\n]/",' ',$data),0,255,'...');
	
	if ($trackback_encoding && $trackback_encoding != SOURCE_ENCODING)
	{
		$title = mb_convert_encoding($title,$trackback_encoding,SOURCE_ENCODING);
		$page_title = mb_convert_encoding($page_title,$trackback_encoding,SOURCE_ENCODING);
		$data = mb_convert_encoding($data,$trackback_encoding,SOURCE_ENCODING);
		$_send_encoding = $trackback_encoding;
	}
	// 自文書の情報
	$putdata = array(
		'title'     => $title,
		'url'       => $myurl,
		'excerpt'   => $data,
		'blog_name' => $page_title,
		'charset'   => $_send_encoding // 送信側文字コード(未既定)
	);
	
	// Ping送信制限値をチェック
	if ($trackback > $sended_count)
	{
		if (is_array($links) && count($links) != 0)
		{
			foreach ($links as $link)
			{
				set_time_limit(120); // 処理実行時間を長めに再設定
				touch($filename); // 判定ファイルをtouch
				
				// URL から TrackBack ID を取得する
				$tb_id = trim(str_replace("&amp;","&",tb_get_url($link)));
				
				//tb_debug_output($tb_id);
				
				if (empty($tb_id) || in_array($tb_id,$sended)) // TrackBack に対応していないか送信済み
				{
					continue;
				}
				$result = http_request($tb_id,'POST','',$putdata,3,TRUE,3,10,30);
				if ($result['rc'] === 200 || strpos($result['data'],"<error>0</error>") !== false)
				{
					$sended[] = $tb_id;
					$sended_count++;
					// とりあえず送信済みにスタックする
					tb_sended_save_temp($tb_id,$ping_filename);
				}
				// FIXME: エラー処理を行っても、じゃ、どうする？だしなぁ...
				if ($trackback <= $sended_count) break;
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
	
	// 更新通知Ping送信指定先追加
	$pings = array_merge($pings,preg_split("/\s+/",trim($update_ping_to)));
	$pings = array_unique($pings);

	if (is_array($pings) && count($pings) != 0)
	{
		// Ping 指定先へ送信
		foreach ($pings as $tb_id)
		{
			set_time_limit(120); // 処理実行時間を長めに再設定
			touch($filename); // 判定ファイルをtouch

			$done = false;
			// XML RPC Ping を打ってみる
			$result = http_request($tb_id,'POST',"Content-Type: text/xml\r\n",$rpcdata,3,TRUE,3,10,30);
			if ($result['rc'] === 200)
			{
				// 超手抜きなチェック
				if (strpos($result['data'],"<boolean>0</boolean>") !== false)
					$done = true;
				else
				{
					set_time_limit(120); // 処理実行時間を長めに再設定
					//Track Back Ping
					$result = http_request($tb_id,'POST','',$putdata,3,TRUE,3,10,30);
					// 超手抜きなチェック
					if (strpos($result['data'],"<error>0</error>") !== false)
						$done = true;
				}
			}
			
			/*
			tb_debug_output
			(
				$tb_id."\n"
				.$result['query']."\n"
				.$result['rc']."\n"
				.$result['header']."\n"
				.$result['data']."\n"
			);
			*/
			
			if ($done)
			{
				$sended[] = $tb_id;
				// とりあえず送信済みにスタックする
				tb_sended_save_temp($tb_id,$ping_filename);
			}
		}
	}
	
	if ($sended){
		// 送信済みエントリを記録
		$sended = array_unique($sended);
		$fp = fopen($ping_filename,'wb');
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
	$self = XOOPS_WIKI_HOST.getenv('REQUEST_URI');
	$dcdate = substr_replace(get_date('Y-m-d\TH:i:sO',$time),':',-2,0);
	$tb_url = tb_get_my_tb_url($tb_id);
	// dc:date="$dcdate"
	
	return <<<EOD
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
 <rdf:Description
   rdf:about="$self"
   dc:identifier="$self"
   dc:title="$page"
   trackback:ping="$tb_url"
   dc:creator="$creator"
   dc:date="$dcdate" />
</rdf:RDF>
-->
EOD;
}

function tb_get_my_tb_url($pid)
{
	global $use_static_url;
	if ($use_static_url)
		return XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/tb/".$pid;
	else
		return XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/index.php?pwm_ping=".$pid;
}

// 文書をGETし、埋め込まれたTrackBack Ping urlを取得
function tb_get_url($url)
{
	static $ng_host = array();
	
	$parse_url = parse_url($url);
	
	// 5回抽出に失敗した host はパスする。
	if (!isset($ng_host[$parse_url['host']])) $ng_host[$parse_url['host']] = 0;
	if ($ng_host[$parse_url['host']] > 5) return '';
	
	// プロキシを経由する必要があるホストにはpingを送信しない
	if (empty($parse_url['host']) or via_proxy($parse_url['host']))
	{
		return '';
	}
	
	$data = http_request($url,'GET','',array(),HTTP_REQUEST_URL_REDIRECT_MAX,TRUE,3,10,60);
	
	if ($data['rc'] !== 200)
	{
		$ng_host[$parse_url['host']]++;
		return '';
	}
	
	if (!preg_match_all('#<rdf:RDF[^>]*>(.*?)</rdf:RDF>#si',$data['data'],$matches,PREG_PATTERN_ORDER))
	{
		$ng_host[$parse_url['host']]++;
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

// トラックバック一覧 Body取得
function tb_get_tb_body($tb_id,$page=FALSE)
{
	global $script;
	global $_tb_title,$_tb_header,$_tb_entry,$_tb_refer,$_tb_date;
	global $_tb_header_Excerpt,$_tb_header_Weblog,$_tb_header_Tracked;
	global $X_admin;
	
	if ($page) $tb_id = tb_get_id($tb_id);
	
	$data = tb_get_db($tb_id);
	
	$tb_body = '';
	foreach ($data as $x)
	{
		list ($time,$url,$title,$excerpt,$blog_name,$dum,$dum,$ip) = $x;
		if ($title == '')
		{
			$title = 'no title';
		}
		$time = date($_tb_date, $time + LOCALZONE); // May 2, 2003 11:25 AM
		$del_form_tag = ($X_admin)? "<input type=\"checkbox\" name=\"delete_check[]\" value=\"$url\"/>" : "";
		$ip_tag = ($X_admin)? "<br />\n  <strong>IP:</strong> $ip" : "";
		$tb_body .= <<<EOD
<div class="trackback-body">
 <span class="trackback-post">
  $del_form_tag
  <a href="$url" target="new">$title</a><br />
  <strong>$_tb_header_Excerpt</strong> $excerpt<br />
  <strong>$_tb_header_Weblog</strong> $blog_name<br />
  <strong>$_tb_header_Tracked</strong> $time$ip_tag
 </span>
</div>
EOD;
	}
	
	if ($X_admin && $tb_body)
	{
		$tb_body = <<<EOD
<form method="post" action="$script">
<input type="hidden" name="plugin" value="tb" />
<input type="hidden" name="__mode" value="view" />
<input type="hidden" name="tb_id" value="$tb_id" />
<input type="hidden" name="cmd" value="delete" />
<input type="submit" value="チェックしたものを削除" />
$tb_body
</form>
EOD;
	}
	
	return $tb_body;

}

function tb_debug_output($str)
{
	$data = "----------\n".date("D M j G:i:s T Y")."\n----------\n";
	$data .= $str."\n\n";
	$filename = CACHE_DIR."tb_debug.txt";
	$fp = fopen($filename,'a+');
	fputs($fp,$data."\n");
	fclose($fp);
}

// Ping送信処理中にとりあえず送信済み相手先を記録する
function tb_sended_save_temp($dat,$ping_filename)
{
	$fp = fopen($ping_filename,'ab');
	flock($fp,LOCK_EX);
	fwrite($fp,$dat."\n");
	flock($fp,LOCK_UN);
	fclose($fp);
}
?>