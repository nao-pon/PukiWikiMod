<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: mc_refrash.php,v 1.3 2005/03/23 14:16:29 nao-pon Exp $
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
//$debug = CACHE_DIR."mc_cache.debug.txt";
$debug = "";

// 動作中フラグ ON
touch(P_CACHE_DIR."mc_refresh_run.flg");

$data = (isset($post['mc_refresh']))? explode(" ",$post['mc_refresh']) : array();
$page = add_bracket($post['tgt_page']);

$done = 0;
$done_data = array();
foreach($data as $uri)
{
	// 無効なURIはパス
	if (!preg_match("#^\?plugin=(aws|google|gimage|showrss|newsclip)&pmode=refresh#",$uri)) continue;
	
	// 実行時間を長めに設定
	set_time_limit(120);
	
	// データ更新 同期モードで順番に
	$rc = http_request(
	XOOPS_WIKI_HOST.$script.$uri
	,'GET','',array(),HTTP_REQUEST_URL_REDIRECT_MAX,1,3);

	
	if ($rc['rc'] == 200)
	{
		$done = 1;
		$done_data[] = $script.$uri;
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

}

if ($debug)
{
	$data = $page."($done)\n----\n".join("\n\n",$done_data);
	
	$fp = fopen($debug, "ab");
	fwrite($fp, date("Y/m/d H:i:s")."\n".$data."\n----\n\n");
	fclose($fp);
}

// 動作中フラグ OFF
unlink(P_CACHE_DIR."mc_refresh_run.flg");

exit();
?>