<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: mc_refrash.php,v 1.1 2005/02/23 00:16:41 nao-pon Exp $
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

//error_reporting(E_ALL);

$data = (isset($_POST['mc_refresh']))? explode(" ",$_POST['mc_refresh']) : array();
$page = add_bracket($post['tgt_page']);

$done = 0;
//$done_data = array();
foreach($data as $uri)
{
	// 無効なURIはパス
	if (!preg_match("#^\?plugin=(aws|google|gimage|showrss|newsclip)&pmode=refresh#",$uri)) continue;
	
	// 実行時間を長めに設定
	set_time_limit(120);
	
	// データ更新 同期モードで順番に
	$rc = http_request(
	$script.$uri
	,'GET','',array(),HTTP_REQUEST_URL_REDIRECT_MAX,1,3);
	
	if ($rc['rc'] == 200)
	{
		$done = 1;
		//$done_data[] = $script.$uri;
	}
}

if ($done)
{
	$filename = CACHE_DIR.encode(strip_bracket($page)).".udp";

	if (file_exists($filename))
	{
		unlink($filename);
		if (is_page($page))
		{
			// plane_text DB を更新
			plain_db_write($page,"update");
		}

	}
	/*
	$data = $page."\n----\n".join("\n\n",$done_data);
	
	$c_file = "./debug.txt";
	$fp = fopen($c_file, "ab");
	fwrite($fp, date("Y/m/d H:i")."\n".$data."\n----\n\n");
	fclose($fp);
	*/
}
exit();
?>