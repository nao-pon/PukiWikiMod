<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ping.php,v 1.1 2003/10/31 12:22:59 nao-pon Exp $
/////////////////////////////////////////////////

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

$page = strip_bracket(mb_convert_encoding(trim($arg),SOURCE_ENCODING,"AUTO"));

$vars["page"] = add_bracket($page);
$get["page"] = $post["page"] = $vars["page"];

$filename = CACHE_DIR.encode($page).".tbf";

if (file_exists($filename))
{
	unlink($filename);
	tb_send($page,true);
}

header("Content-Type: image/gif");
readfile('image/transparent.gif');

exit;
?>