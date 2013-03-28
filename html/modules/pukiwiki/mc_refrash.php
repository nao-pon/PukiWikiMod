<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: mc_refrash.php,v 1.6 2006/06/08 04:59:33 nao-pon Exp $
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

//error_reporting(E_ALL);
//$debug = CACHE_DIR."mc_cache.debug.txt";
$debug = "";

// 動作中フラグ ON
touch(P_CACHE_DIR."mc_refresh_run.flg");

// 動作リスト取得
$page = add_bracket($vars['tgt_page']);
$pgid = get_pgid_by_name($page);
$_datafile = P_CACHE_DIR.$pgid.".mcr";
$data = file($_datafile);
unlink($_datafile);
touch($_datafile);

// 古いキャッシュの削除
$gabegge_cycle = 24; // 24h
$gabegge_limit = 30; // 30d

if (!file_exists(CACHE_DIR."pcache.gab") || filemtime(CACHE_DIR."pcache.gab") + $gabegge_cycle * 3600 < time())
{
	if ($dir = opendir(P_CACHE_DIR))
	{
		touch(CACHE_DIR."pcache.gab");
		while (false !== ($file = readdir($dir)))
		{
			if (substr($file,0,1) == "." || "index.html") { continue; }
			$file = P_CACHE_DIR.$file;
			if (filemtime($file) + $gabegge_limit * 86400 < time())
			{
				unlink($file);
			}
		}
		closedir($dir); 
	}
}

$done = 0;
$done_data = array();
foreach($data as $uri)
{
	$uri = trim($uri);
	// 無効なURIはパス
	if (!preg_match("#^\?plugin=(aws|google|gimage|showrss|newsclip|yahoo)&pmode=refresh#",$uri)) continue;
	
	// 実行時間を長めに設定
	@set_time_limit(120);
	
	// データ更新 同期モードで順番に
	$rc = pkwk_http_request(
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
	@unlink(CACHE_DIR.encode(strip_bracket($page)).".udp");
	
	if (is_page($page))
	{
		// plane_text DB を更新
		plain_db_write($page,"update");
		// ページHTMLキャッシュを削除
		delete_page_html($page,"html");
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