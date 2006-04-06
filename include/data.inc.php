<?php

// PukiWikiMod ディレクトリ名
$dir_name = basename( dirname( dirname( __FILE__ ) ));
$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);

eval( '

function '.$dir_name.'_new($limit=0, $offset=0)
{
	return pukiwiki_new_base("'.$dir_name.'", "'.$dir_num.'", $limit, $offset);
}

' ) ;


if (!function_exists('pukiwiki_new_base'))
{

function pukiwiki_new_base($dir_name, $dir_num, $limit=0, $offset=0)
{
	$xoopsDB =& Database::getInstance();
	$comment = array();
	
	include (XOOPS_ROOT_PATH."/modules/".$dir_name."/cache/config.php");
	$use_static_url = (string)$use_static_url;
	$trackback = (int)$trackback;

	if (!empty($pukiwiki_dirs['short_url']))
	{
		$dir = (string)$pukiwiki_dirs['short_url'];
	}
	else
	{
		$dir = "modules/".$dir_name;
	}
	
	$SQL = "SELECT p.id, p.lastediter, p.name as pname, p.title, p.editedtime , c.count, t.plain FROM ".
		$xoopsDB->prefix("pukiwikimod".$dir_num."_pginfo")." p LEFT JOIN ".
		$xoopsDB->prefix("pukiwikimod".$dir_num."_count")." c ON p.name=c.name ".
		"LEFT JOIN ".$xoopsDB->prefix("pukiwikimod".$dir_num."_plain")." t ON t.pgid=p.id ".
		"WHERE p.vaids=\"&all\" AND p.name NOT LIKE \":%\" AND p.editedtime != 0 ".
		"ORDER BY p.editedtime DESC";
	
	if ( !$query = $xoopsDB->query($SQL,$limit,$offset) ) echo "Error! $SQL (pukiwiki)";
	
	$tb_tag = "-";
	$key = 0;
	while ( $result = $xoopsDB->fetchArray($query) )
	{
		$u_page_name = preg_replace("/\/[^\/]+$/","",$result['pname']);
		
		if ($use_static_url == 3)
		{
			$page = XOOPS_URL."/".$dir_name."+._".$result['id'].".htm";
			$u_page = XOOPS_URL."/".$dir_name."+._".b_WhatsNew_pukiwiki_get_pgid_by_name($u_page_name,$dir_num).".htm";
		}
		else if ($use_static_url == 2)
		{
			$page = XOOPS_URL."/".$dir_name."+index.pgid+_".$result['id'].".htm";
			$u_page = XOOPS_URL."/".$dir_name."+index.pgid+_".b_WhatsNew_pukiwiki_get_pgid_by_name($u_page_name,$dir_num).".htm";
		}
		else if ($use_static_url)
		{
			$page = XOOPS_URL."/".$dir."/".$result['id'].".html";
			$u_page = XOOPS_URL."/".$dir."/".b_WhatsNew_pukiwiki_get_pgid_by_name($u_page_name,$dir_num).".html";
		}
		else
		{
			$page = XOOPS_URL."/".$dir."/?".rawurlencode($result['pname']);
			$u_page = XOOPS_URL."/".$dir."/?".rawurlencode($u_page_name);
		}
		
		$tb = $com = 0;
		if ($trackback)
		{
			$t_query = "SELECT count(*) FROM ".$xoopsDB->prefix("pukiwikimod".$dir_num."_tb")." WHERE page_name='".addslashes($result['pname'])."';";
			$t_result=$xoopsDB->query($t_query);
			$tb = mysql_result($t_result,0);
		}
		// Page Comments
		$m_obj = XoopsModule::getByDirname($dir_name);
		$com = xoops_comment_count($m_obj->mid(),$result['id']);
		$reply = $tb + $com;
		
		// 新規追加行
		$addfile = XOOPS_ROOT_PATH."/modules/".$dir_name."/diff/"."add_".$result['id'].".cgi";
		$addtext = "";
		if (file_exists($addfile))
		{
			$addtext = trim(join('',file($addfile)));
			if ($addtext)
			{
				//$addtext = "<p>".strip_tags(str_replace(array("\r","\n"),"",$addtext))."</p>&#182;";
				$addtext = "<p>".trim(strip_tags($addtext))."</p>&#182;";
				//$addtext = $addtext."&#182;";
			}
		}
		
		$comment[$key]['id'] = $result['id'];
		$comment[$key]['cat_link'] = $u_page;
		$comment[$key]['cat_name'] = $u_page_name;
		$comment[$key]['link'] = $page;
		$comment[$key]['title'] = ($result['title'])? str_replace(array("<",">"),array("&lt;","&gt;"),$result['title']) : "+ no title +";
		$comment[$key]['uid'] = $result['lastediter'];
		$comment[$key]['hits'] = $result['count'];
		$comment[$key]['replies'] = $reply;
		$comment[$key]['time'] = $result['editedtime'];
		$comment[$key]['description'] = $addtext."<p>".str_replace(array("<",">"),array("&lt;","&gt;"),$result['plain'])."</p>";
		$key++;
	}
	return $comment;
}

//ページ名からページIDを求める
function b_WhatsNew_pukiwiki_get_pgid_by_name($page,$dir_num)
{
	$xoopsDB =& Database::getInstance();
	static $page_id = array();
	$page = addslashes($page);
	if (!empty($page_id[$dir_num+1][$page])) return $page_id[$dir_num+1][$page];
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".$dir_num."_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return 0;
	$ret = mysql_fetch_row($res);
	$page_id[$dir_num+1][$page] = $ret[0];
	return $ret[0];
}

}
?>