<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ud_plain.php,v 1.2 2006/04/06 13:32:15 nao-pon Exp $
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

if (file_exists($filename))
{
	unlink($filename);
	if (is_page($page))
	{
		// plane_text DB を更新
		if(!plain_db_write($page,"update"))
			echo "error!";
	}

}

header("Content-Type: image/gif");
readfile('image/transparent.gif');

exit;
?>