<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ping.php,v 1.10 2006/04/06 13:32:15 nao-pon Exp $
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

$page = strip_bracket($get['p']);
$vars["page"] = add_bracket($page);
$get["page"] = $post["page"] = $vars["page"];

$is_rsstop = (empty($get['t']))? 0 : 1;

if ($is_rsstop)
	$up_page = strip_bracket($vars["page"]);
else
{
	if (strpos($vars["page"],"/") === FALSE)
		$up_page = "";
	else
		$up_page = preg_replace("/(.+)\/[^\/]+/","$1",strip_bracket($vars["page"]));
}
$up_page = ($up_page && is_page($up_page))? "&p=".get_pgid_by_name($up_page) : "";

$rss_url = XOOPS_URL.XOOPS_WIKI_URL.'/?cmd=rss10&content=s'.$up_page;

$filename = CACHE_DIR.encode($page).".tbf";

if (file_exists($filename))
{
	//常にゲストモード
	$X_admin = $X_uid = 0;
	
	// 存在しないページ or ゲスト閲覧権限なし
	if (!is_page(add_bracket($page)) || !check_readable($page,FALSE,FALSE))
	{
		unlink($filename); // 判定ファイルを削除
		exit;
	}
	
	sleep(3); // ページが表示されるまでちょっと待つ
	
	// 実行時間を長めに設定
	@set_time_limit(120);
	
	//sleep(mt_rand(40,60)); //適当に遅延させる
	
	//キャッシュ作成 (ページ)
	convert_html($page,false,true);
	
	//キャッシュ作成 (RSS)
	http_request($rss_url);
	
	sleep(2);
	
	//ソースを取得
	$data = get_source($page);
	
	//処理しないプラグインを削除
	if ($notb_plugin)
	{
		// 念のため quote
		$notb_plugin = preg_quote($notb_plugin,"/");
		// 正規表現形式へ
		$notb_plugin = str_replace(",","|",$notb_plugin);
		
		// 該当プラグインを削除
		$data = preg_replace("/#($notb_plugin)(\(((?!#[^(]+\()(?!\),).)*\))?/","",$data);
	}

	$data = join("",$data);
	//delete_page_info($data);
	$data = convert_html($data);

	tb_send($page,$data);
	
	unlink($filename); // 判定ファイルを削除
}

exit;
?>