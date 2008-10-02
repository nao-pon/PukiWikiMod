<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ud_plain.php,v 1.4 2008/10/02 08:37:54 nao-pon Exp $
/////////////////////////////////////////////////

include 'initialize.php';

//XOOPS設定読み込み
include("../../mainfile.php");

// プログラムファイル読み込み
require("func.php");
require("file.php");
require("plugin.php");
require("template.php");
require("convert_html.php");
require("html.php");
require("backup.php");
require("rss.php");
require('make_link.php');
require('config.php');
require('link.php');
require('proxy.php');
require('db_func.php');
require('trackback.php');

require("init.php");
/////////////////////////////
$h_excerpt = "";

//$page = strip_bracket(mb_convert_encoding(trim($arg),SOURCE_ENCODING,"AUTO"));
$page = strip_bracket(trim($arg));

$vars["page"] = add_bracket($page);
$get["page"] = $post["page"] = $vars["page"];

$filename = CACHE_DIR.encode($page).".udp";

if (file_exists($filename)) {
	unlink($filename);
	if (is_page($vars["page"]))
	{
		$pgid = get_pgid_by_name($vars["page"]);
		$query = "SELECT count(*) FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_plain")." WHERE pgid = $pgid";
		$result = $xoopsDB->query($query);
		list($count) = mysql_fetch_row($result);
		if (! $count) {
			$action = 'insert';
		} else {
			$action = 'update';
		}
		// plane_text DB を更新
		if(!plain_db_write($vars["page"], $action))
			echo "error!";
	}
}
exit();
?>