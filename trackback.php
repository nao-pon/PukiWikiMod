<?php
// $Id: trackback.php,v 1.24 2005/05/20 00:07:01 nao-pon Exp $
/*
 * PukiWiki TrackBack �ץ����
 * (C) 2003, Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
 * License: GPL
 *
 * http://localhost/pukiwiki/pukiwiki.php?FrontPage �����Τ˻��ꤷ�ʤ���
 * TrackBack ID �μ����ϤǤ��ʤ�
 *
 * tb_get_id($page)       TrackBack Ping ID�����
 * tb_id2page($tb_id)     TrackBack Ping ID ����ڡ���̾�����
 * tb_get_filename($page) TrackBack Ping �ǡ����ե�����̾�����
 * tb_count($page)        TrackBack Ping �ǡ����Ŀ�����  // pukiwiki.skin.LANG.php
 * tb_send($page,$data)   TrackBack Ping ����  // file.php
 * tb_delete($page)       TrackBack Ping �ǡ������  // edit.inc.php
 * tb_get($file,$key=1)   TrackBack Ping �ǡ�������
 * tb_get_rdf($page)      ʸ����������ि���rdf��ǡ��������� // pukiwiki.php
 * tb_get_url($url)       ʸ���GET���������ޤ줿TrackBack Ping URL�����
 * class TrackBack_XML    XML����TrackBack Ping ID��������륯�饹
 * == Referer �б�ʬ ==
 * ref_save($page)        Referer �ǡ�����¸(����) // pukiwiki.php
 */

if (!defined('TRACKBACK_DIR'))
{
	define('TRACKBACK_DIR','./trackback/');
}

// TrackBack Ping ID�����
function tb_get_id($page)
{
	return get_pgid_by_name($page);
}

// TrackBack Ping ID ����ڡ���̾�����
function tb_id2page($tb_id)
{
	$page = strip_bracket(get_pgname_by_id($tb_id));
	
	return ($page)? $page : FALSE;
}

// TrackBack Ping �ǡ����ե�����̾�����
function tb_get_filename($page,$ext='.txt')
{
	return TRACKBACK_DIR.tb_get_id($page).$ext;
}

// TrackBack Ping �ǡ����Ŀ�����
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

// TrackBack Ping ����
function tb_send($page,$data="")
{
	global $script,$trackback,$update_ping_to,$page_title,$interwiki,$notb_plugin,$h_excerpt,$auto_template_name,$update_ping_to,$defaultpage;
	global $trackback_encoding,$vars;
	
	$page = strip_bracket($page);

	// �������ʤ����
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
		// �ե�����˰���ݴ�
		touch($filename);
		//if ($fp = fopen($filename,'wb'))
		//{
		//	fputs($fp,XOOPS_URL."/modules/pukiwiki/ping.php?p=".rawurlencode($page)."&t=".$vars['is_rsstop']);
		//	fclose($fp);
		//}
		
		// ��Ʊ���⡼�ɤ��̥���åɽ��� Blokking=0,Retry=5,��³�����ॢ����=30,�����åȥ����ॢ����=0(���ꤷ�ʤ�)
		$ret = http_request(
		XOOPS_URL."/modules/pukiwiki/ping.php?p=".rawurlencode($page)."&t=".$vars['is_rsstop']
		,'GET','',array(),HTTP_REQUEST_URL_REDIRECT_MAX,0,5,30,0);
		
		return;
	}
	
	//error_reporting(E_ALL);
	
	// �����Ѥߥ���ȥ꡼�����
	$ping_filename = TRACKBACK_DIR.tb_get_id($page).".ping";
	$to_pukiwiki = $sended = array();
	$sended_count = 0;
	// ����ʸ������
	foreach(@file($ping_filename) as $line)
	{
		//Pukiwiki�ϥ����å�����ʤ��Τ������Ѥߤ˲ä��ʤ�
		if (strpos($line,"?plugin=tb&tb_id=") !== false || //PukiWiki
			strpos($line,"/modules/pukiwiki/") !== false   //PukiWikiMod ����
			)
			$to_pukiwiki[] = trim($line);
		else
			$sended[] = trim($line);
		
		$sended_count++;
	}
	
	//set_time_limit(0); // �����¹Ի�������(php.ini ���ץ���� max_execution_time )
	
	// convert_html() �Ѵ���̤� <a> �������� URL ���
	preg_match_all('#href="(https?://[^"]+)"#',$data,$links,PREG_PATTERN_ORDER);

	// ���ۥ���(XOOPS_WIKI_URL�ǻϤޤ�url)�����
	$links = preg_grep("/^(?!".preg_quote(XOOPS_WIKI_HOST.XOOPS_WIKI_URL,'/').")./",$links[1]);
	
	// convert_html() �Ѵ���̤��� Ping ����  URL ���
	preg_match_all('#<!--__PINGSEND__"(https?://[^"]+)"-->#',$data,$pings,PREG_PATTERN_ORDER);
	
	// ���ۥ���($script�ǻϤޤ�url)�����
	$pings = preg_grep("/^(?!".preg_quote(XOOPS_WIKI_HOST.$script,'/')."\?)./",$pings[1]);
	
	$pings = str_replace("&amp;","&",$pings);
	$pings = array_unique($pings);
	
	$title = preg_replace("/\/[0-9\-]+$/","",$page);//�����ο����ȥϥ��ե�Ͻ���
	$up_page = (strpos($page,"/")) ? preg_replace("/(.+)\/[^\/]+/","$1",strip_bracket($page)) : $defaultpage;
	if (!is_page($up_page)) $up_page = $defaultpage;
	
	if ($h_excerpt) $title .= "/".$h_excerpt;
	
	$myurl = XOOPS_WIKI_HOST.get_url_by_name($page);
	$xml_myurl = XOOPS_WIKI_HOST.get_url_by_name($up_page);
	
	$xml_title = $page_title."/".$up_page;
	
	$data = trim(strip_htmltag($data));
	$data = preg_replace("/^".preg_quote($h_excerpt,"/")."/","",$data);
	
	// RPC Ping �Υǡ�����
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
	
	// ����ʸ��������
	$_send_encoding = SOURCE_ENCODING;
	$data = mb_strimwidth(preg_replace("/[\r\n]/",' ',$data),0,255,'...');
	
	if ($trackback_encoding && $trackback_encoding != SOURCE_ENCODING)
	{
		$title = mb_convert_encoding($title,$trackback_encoding,SOURCE_ENCODING);
		$page_title = mb_convert_encoding($page_title,$trackback_encoding,SOURCE_ENCODING);
		$data = mb_convert_encoding($data,$trackback_encoding,SOURCE_ENCODING);
		$_send_encoding = $trackback_encoding;
	}
	// ��ʸ��ξ���
	$putdata = array(
		'title'     => $title,
		'url'       => $myurl,
		'excerpt'   => $data,
		'blog_name' => $page_title,
		'charset'   => $_send_encoding // ����¦ʸ��������(̤����)
	);
	
	// Ping���������ͤ�����å�
	if ($trackback > $sended_count)
	{
		if (is_array($links) && count($links) != 0)
		{
			foreach ($links as $link)
			{
				set_time_limit(120); // �����¹Ի��֤�Ĺ��˺�����
				touch($filename); // Ƚ��ե������touch
				
				// URL ���� TrackBack ID ���������
				$tb_id = trim(str_replace("&amp;","&",tb_get_url($link)));
				
				//tb_debug_output($tb_id);
				
				if (empty($tb_id) || in_array($tb_id,$sended)) // TrackBack ���б����Ƥ��ʤ��������Ѥ�
				{
					continue;
				}
				$result = http_request($tb_id,'POST','',$putdata,3,TRUE,3,10,30);
				if ($result['rc'] === 200 || strpos($result['data'],"<error>0</error>") !== false)
				{
					$sended[] = $tb_id;
					$sended_count++;
					// �Ȥꤢ���������Ѥߤ˥����å�����
					tb_sended_save_temp($tb_id,$ping_filename);
				}
				// FIXME: ���顼������ԤäƤ⡢���㡢�ɤ����롩�����ʤ�...
				if ($trackback <= $sended_count) break;
			}
		}
		
		// �����ѤߤΥ���ȥ꡼����
		$pings = array_diff($pings,$sended);
	}
	else
	{
		//����Ping�����ͤ�Ķ���Ƥ�����ϡ�PukiWiki���ƤΤ�����
		$pings = $to_pukiwiki;
	}
	
	// ��������Ping�����������ɲ�
	$pings = array_merge($pings,preg_split("/\s+/",trim($update_ping_to)));
	$pings = array_unique($pings);

	if (is_array($pings) && count($pings) != 0)
	{
		// Ping �����������
		foreach ($pings as $tb_id)
		{
			set_time_limit(120); // �����¹Ի��֤�Ĺ��˺�����
			touch($filename); // Ƚ��ե������touch

			$done = false;
			// XML RPC Ping ���ǤäƤߤ�
			$result = http_request($tb_id,'POST',"Content-Type: text/xml\r\n",$rpcdata,3,TRUE,3,10,30);
			if ($result['rc'] === 200)
			{
				// Ķ��ȴ���ʥ����å�
				if (strpos($result['data'],"<boolean>0</boolean>") !== false)
					$done = true;
				else
				{
					set_time_limit(120); // �����¹Ի��֤�Ĺ��˺�����
					//Track Back Ping
					$result = http_request($tb_id,'POST','',$putdata,3,TRUE,3,10,30);
					// Ķ��ȴ���ʥ����å�
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
				// �Ȥꤢ���������Ѥߤ˥����å�����
				tb_sended_save_temp($tb_id,$ping_filename);
			}
		}
	}
	
	if ($sended){
		// �����Ѥߥ���ȥ��Ͽ
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

// TrackBack Ping �ǡ������
function tb_delete($page)
{
	global $xoopsDB;
	$page = addslashes(strip_bracket($page));
	$where = " WHERE page_name='$page'";
	
	$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_tb")." $where;";
	$results=$xoopsDB->queryF($query);
}

// TrackBack Ping �ǡ�������
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
// TrackBack Ping �ǡ�������
function tb_get($file,$key=1)
{
	$tb_id = str_replace(TRACKBACK_DIR,"",$file);
	$tb_id = str_replace(".txt","",$tb_id);
	return tb_get_db($tb_id,$key);
}

// ʸ����� trackback:ping �������ि��Υǡ���������
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

// ʸ���GET���������ޤ줿TrackBack Ping url�����
function tb_get_url($url)
{
	static $ng_host = array();
	
	$parse_url = parse_url($url);
	
	// 5����Ф˼��Ԥ��� host �ϥѥ����롣
	if (!isset($ng_host[$parse_url['host']])) $ng_host[$parse_url['host']] = 0;
	if ($ng_host[$parse_url['host']] > 5) return '';
	
	// �ץ������ͳ����ɬ�פ�����ۥ��Ȥˤ�ping���������ʤ�
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

// �����ޤ줿�ǡ������� TrackBack Ping url��������륯�饹
class TrackBack_XML
{
	var $url;
	var $tb_url;
	var $tb_url_nc;
	
	function parse($buf,$url)
	{
		// �����
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

// Referer �ǡ�����¸(����)
function ref_save($page)
{
	global $referer;
	
	if (!$referer or empty($_SERVER['HTTP_REFERER']))
	{
		return;
	}
	
	$url = $_SERVER['HTTP_REFERER'];
	
	// URI ��������ɾ��
	// ����������ξ��Ͻ������ʤ�
	$parse_url = parse_url($url);
	if (empty($parse_url['host']) or $parse_url['host'] == $_SERVER['HTTP_HOST'])
	{
		return;
	}
	
	// TRACKBACK_DIR ��¸�ߤȽ񤭹��߲�ǽ���γ�ǧ
	if (!is_dir(TRACKBACK_DIR))
	{
		die(TRACKBACK_DIR.': No such directory');
	}
	if (!is_writable(TRACKBACK_DIR))
	{
		die(TRACKBACK_DIR.': Permission denied');
	}
	
	// Referer �Υǡ����򹹿�
	if (ereg("[,\"\n\r]",$url))
	{
		$url = '"'.str_replace('"', '""', $url).'"';
	}
	$filename = tb_get_filename($page,'.ref');
	$data = tb_get($filename,3);
	if (!array_key_exists($url,$data))
	{
		// 0:�ǽ���������, 1:�����Ͽ����, 2:���ȥ�����, 3:Referer �إå�, 4:���Ѳ��ݥե饰(1��ͭ��)
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

// �����Ѥ�PING����������
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

// �ȥ�å��Хå����� Body����
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
<input type="submit" value="�����å�������Τ���" />
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

// Ping����������ˤȤꤢ���������Ѥ�������Ͽ����
function tb_sended_save_temp($dat,$ping_filename)
{
	$fp = fopen($ping_filename,'ab');
	flock($fp,LOCK_EX);
	fwrite($fp,$dat."\n");
	flock($fp,LOCK_UN);
	fclose($fp);
}
?>