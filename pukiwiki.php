<?php
// pukiwiki.php - Yet another WikiWikiWeb clone.
//
// PukiWiki 1.3.* 
//  Copyright (C) 2002 by PukiWiki Developers Team
//  http://pukiwiki.org/
//
// PukiWiki 1.3 (Base)
//  Copyright (C) 2001,2002 by sng.
//  <sng@factage.com>
//  http://factage.com/sng/pukiwiki/
//
// Special thanks
//  YukiWiki by Hiroshi Yuki
//  <hyuki@hyuki.com>
//  http://www.hyuki.com/yukiwiki/
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// $Id: pukiwiki.php,v 1.65 2005/02/23 00:16:41 nao-pon Exp $
/////////////////////////////////////////////////
// Protectorのチェックを回避する
if (
	(isset($_GET['plugin']) && $_GET['plugin']=='showrss' && isset($_GET['pmode']) && $_GET['pmode']=='refresh') ||
	(isset($_GET['plugin']) && $_GET['plugin']=='newsclip' && isset($_GET['pmode']) && $_GET['pmode']=='refresh') ||
	(isset($_GET['plugin']) && $_GET['plugin']=='aws' && isset($_GET['pmode']) && $_GET['pmode']=='refresh') ||
	(isset($_GET['plugin']) && $_GET['plugin']=='google' && isset($_GET['pmode']) && $_GET['pmode']=='refresh') ||
	(isset($_GET['plugin']) && $_GET['plugin']=='gimage' && isset($_GET['pmode']) && $_GET['pmode']=='refresh')
	)
{
	$_SERVER['REMOTE_ADDR'] = "";
}

//XOOPS設定読み込み
include("../../mainfile.php");
global $xoopsUser,$xoopsDB,$xoopsConfig;

/////////////////////////////////////////////////
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
if (!extension_loaded('mbstring'))
{
	require('mbstring.php');
}
require("init.php");

/////////////////////////////////////////////////
// メイン処理

// 一覧の表示
if (arg_check("list")) $vars["plugin"] = "list";

// ファイル名一覧の表示
elseif (arg_check("filelist"))
{
	$vars['plugin'] = "attach";
	$vars['pcmd'] = "list";
}
// RecentChenges の表示
elseif ($arg === $whatsnew)
{
	$vars["plugin"] = "recentchanges";
	$vars['cmd']  = '';
	$vars['page'] = '';
}

// Plug-in action
if(!empty($vars["plugin"]))
{
	if (!$retvars = do_plugin_action($vars["plugin"]))
		$retvars = array('msg'=>"Action plugin '{$vars['plugin']}' is not installed.",'body'=>"Action plugin '{$vars['plugin']}' is not installed.");;
	
	$title = strip_bracket($vars["refer"]);
	$page = make_search($vars["refer"]);
	
	if(!empty($retvars["msg"]))
	{
		$title =  str_replace("$1",$title,$retvars["msg"]);
		$page =  str_replace("$1",$page,$retvars["msg"]);
	}
	
	if(!empty($retvars["body"]))
	{
		$body = $retvars["body"];
	}
	else
	{
		if (!empty($retvars['redirect']))
			$pg_link_url = $retvars['redirect'];
		else
		{
			if ($use_static_url)
				$pg_link_url = XOOPS_WIKI_URL."/".get_pgid_by_name($vars["refer"]).".html";
			else
				$pg_link_url = "$script?".rawurlencode(strip_bracket($vars["refer"]));
		}
		redirect_header($pg_link_url,1,$title);
		exit();
	}
}

// 編集不可能なページを編集しようとしたとき
else if((arg_check("add") || arg_check("edit") || arg_check("preview") || $post["preview"] || $post["write"]) && (!check_editable($vars["page"],FALSE,FALSE) || $vars["page"] == "" || !$anon_writable))
{
	$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_cannotedit);
	$page = str_replace('$1',make_search($vars["page"]),$_title_cannotedit);

}
// 追加
else if(arg_check("add"))
{
	$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_add);
	$page = str_replace('$1',make_search($get["page"]),$_title_add);
	$body = "<ul>\n";
	$body .= "<li>$_msg_add</li>\n";
	$body .= "</ul>\n";
	$body .= edit_form("",$get["page"],true);
}
// 編集
else if(arg_check("edit"))
{
	$freeze_check = "";
	
	if(!is_page($get["page"]))
		$up_freeze_info = get_freezed_uppage($get["page"]);
	else
		$up_freeze_info = array("",0,null,null,0);
		
	if ($up_freeze_info[0]) $freeze_check = "checked ";

	$postdata = @join("",get_source($get["page"]));
	if ((!WIKI_ALLOW_NEWPAGE || $up_freeze_info[4]) && !$postdata){
		$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),_MD_PUKIWIKI_NO_AUTH);
		$page = str_replace('$1',make_search($vars["page"]),_MD_PUKIWIKI_NO_AUTH);
		$vars["page"] = "";
	} else {
		if($postdata == '') {
			$postdata = auto_template($get["page"]);
			$author_uid = $X_uid;
		}  
		$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_edit);
		$page = str_replace('$1',make_search($get["page"]),$_title_edit);
		//echo sprintf("%01.03f",getmicrotime() - MUTIME)."<br />";
		$body = edit_form($postdata,$get["page"],0,$up_freeze_info[3],$up_freeze_info[2],$freeze_check);
		//echo sprintf("%01.03f",getmicrotime() - MUTIME)."<br />";
		
		///// ParaEdit /////
		if ($vars['id']){
			// 改行コードを \n に統一
			$body = preg_replace("/((\x0D\x0A)|(\x0D)|(\x0A))/", "\n", $body);
			
			// <textarea name="msg" ...> 前後で分割
			$lines = array();
			$textareas = array(); // 0: whole, 1: before msg, 2: textarea tag, 3: msg 4: after msg
			preg_match("/^(.*?)(<textarea .*?>)(.*?)(<\/textarea>.*)$/is", $body, $textareas);
			
			// $vars[msg] を分割
			$msg_before; $msg_now; $msg_after; // 編集行とその前後
			$part = $vars['id'];
			$index_num = 0;
			$is_first_line = 1;
			$is_in_table = 0;
			foreach (split ("\n", $textareas[3]) as $line) {
				if (preg_match("/^\*{1,6}/", $line) && !$is_in_table) {
					$index_num++;
				}
				if (!$is_first_line) { $line = "\n$line"; } else { $is_first_line = 0; }
				if ($index_num < $part) {
					$msg_before .= $line;
				} else if ($index_num == $part) {
					$msg_now .= $line;
				} else if ($index_num > $part) {
					$msg_after .= $line;
				}
				if (preg_match("/-(>|&gt;)(\n|$)/",$line)) {
					$is_in_table = 1;
				} else {
					$is_in_table = 0;
				}
			}
			
			// 微調整 (silly!)
			$msg_before = preg_replace("/^\n/", "", $msg_before);
			if ($msg_before) { $msg_before .= "\n"; }
			
			// 改行コードを書換え
			$msg_before = preg_replace("/\n/", _PARAEDIT_SEPARATE_STR, $msg_before);
			$msg_after  = preg_replace("/\n/", _PARAEDIT_SEPARATE_STR, $msg_after);
			
			// 結合
			$body = $textareas[1]
				. '<input type=hidden name="msg_before" value="' . $msg_before . '">' . "\n"
				. '<input type=hidden name="msg_after"  value="' . $msg_after  . '">' . "\n"
				. $textareas[2]  . $msg_now . $textareas[4];

			// ヘルプ表示 : リンク書き換え
			$body = preg_replace("/(cmd=edit&amp;help=true)/", "plugin=paraedit&amp;parnum=$vars[parnum]&$1&amp;refer=" . rawurlencode($vars[page]), $body);
		}
	}
}
// プレビュー
else if(arg_check("preview") || $post["preview"] || $post["template"])
{
	// 新規作成時にプレビューした時 pgid を振る
	check_pginfo($vars['page']);
	
	if(is_uploaded_file($_FILES['attach_file']['tmp_name'])){
		//添付ファイルあり
		include_once (PLUGIN_DIR.'attach.inc.php');

		$file = $_FILES['attach_file'];
		$attachname = basename(str_replace("\\","/",$file['name']));
		$filename = preg_replace('/\..+$/','', $attachname,1);

		//すでに存在した場合、 ファイル名に'_0','_1',...を付けて回避(姑息)
		$count = '_0';
		while (file_exists(UPLOAD_DIR.encode($vars['refer']).'_'.encode($attachname))) {
			$attachname = preg_replace('/^[^\.]+/',$filename.$count++,$file['name']);
		}
		
		$file['name'] = $attachname;
		
		if (!exist_plugin('attach') or !function_exists('attach_upload')){
			return array('msg'=>'attach.inc.php not found or not correct version.');
		}
		
		$copyright = (isset($post['copyright']))? TRUE : FALSE ;
		$attach_msg = attach_upload($file,$vars['refer'],TRUE,$copyright);
		
		// ソース中の空ref()にファイル名をセット
		$post["msg"] = preg_replace("/ref\((,[^)]*)?\)/","ref(".$attachname."$1)",$post["msg"]);
		unset($file,$attachname,$filename);
	}
	
	if($post["template"] && page_exists($post["template_page"]))
	{
		$post["msg"] = @join("",get_source($post["template_page"]));
		//ページ情報削除
		delete_page_info($post["msg"],TRUE);
	}
	else
	{
		//ページ情報削除
		delete_page_info($post["msg"]);
	}
	
	$freeze_check = ($post["freeze"])? "checked " : "";
	$unvisible_check = ($post["unvisible"])? "checked " : "";
	
	//整形済みブロックの | を &#x7c; に置換
	$post["msg"] = rep_for_pre($post["msg"]);
	
	//改行有効 by nao-pon
	if($post["enter_enable"]) {
		$post["msg"] = auto_br($post["msg"]);
	}
	
	//ページ名に自動リンク by nao-pon
	if ($post["auto_bra_enable"]) {
		$post["msg"] = auto_braket($post["msg"],$post["page"]);
	}
	//nao-pon
	
	$postdata_input = $post["msg"];

	if($post["add"])
	{
		if($post["add_top"])
		{
			$postdata  = $post["msg"];
			$postdata .= "\n\n";
			$postdata .= @join("",get_source($post["page"]));
		}
		else
		{
			$postdata  = @join("",get_source($post["page"]));
			$postdata .= "\n\n";
			$postdata .= $post["msg"];
		}
	}
	else
	{
		$postdata = $post["msg"];
	}

	$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_preview);
	$page = str_replace('$1',make_search($post["page"]),$_title_preview);

	$body = (isset($attach_msg['msg']))? str_replace("$1",strip_bracket($post["page"]),$attach_msg['msg'])."<br />" : "";
	$body .= "$_msg_preview<br />\n";
	if($postdata == "") $body .= "<strong>$_msg_preview_delete</strong><br />\n";
	else                $body .= "<br />\n";

	if($postdata != "")
	{
		//if(file_exists(PLUGIN_DIR."attach.inc.php") && $is_read)
		if(file_exists(PLUGIN_DIR."attach.inc.php"))
		{
			require_once(PLUGIN_DIR."attach.inc.php");
			
			//削除リンクを表示させないため退避
			$X_uid_tmp = $X_uid;
			$X_admin_tmp = $X_admin;
			$X_admin = $X_uid = 0;
			$attaches = attach_filelist();
			
			if ($attaches) $attaches = "<hr />".$attaches;
			
			$X_uid = $X_uid_tmp;
			$X_admin = $X_admin_tmp;
		}

		$postdata = convert_html($postdata);
		
		$body .= "<div style=\"width:100%;background-color:$preview_color\">\n"
			.$postdata.$attaches
			."\n</div>\n"
			."<hr />\n";
	}
	
	$body .= edit_form($postdata_input,$vars["page"],0,$vars["gids"],$vars["aids"]);
}
// 書き込みもしくは追加もしくはコメントの挿入
else if($post["write"] || ($_SERVER['REQUEST_METHOD'] == "POST" && arg_check("write")))
{
	// ParaEdit
	// 改行代替文字列を \n に変換
	$post["msg_before"] = str_replace(_PARAEDIT_SEPARATE_STR, "\n", $post["msg_before"]);
	$post["msg_after"]  = str_replace(_PARAEDIT_SEPARATE_STR, "\n", $post["msg_after"]);
	// 連結
	$post["msg"] = $post["msg_before"].$post["msg"].$post["msg_after"];

	if(is_uploaded_file($HTTP_POST_FILES["attach_file"]["tmp_name"])){
		// とりあえず pgid を振る(添付ファイル処理用)
		check_pginfo($vars['page']);
		
		//添付ファイルあり
		include_once (PLUGIN_DIR.'attach.inc.php');

		$file = $_FILES['attach_file'];
		$attachname = basename(str_replace("\\","/",$file['name']));
		$filename = preg_replace('/\..+$/','', $attachname,1);

		//すでに存在した場合、 ファイル名に'_0','_1',...を付けて回避(姑息)
		$count = '_0';
		while (file_exists(UPLOAD_DIR.encode($vars['refer']).'_'.encode($attachname))) {
			$attachname = preg_replace('/^[^\.]+/',$filename.$count++,$file['name']);
		}
		
		$file['name'] = $attachname;
		
		if (!exist_plugin('attach') or !function_exists('attach_upload')){
			return array('msg'=>'attach.inc.php not found or not correct version.');
		}
		
		$copyright = (isset($post['copyright']))? TRUE : FALSE ;
		$attach_msg = attach_upload($file,$vars['refer'],TRUE,$copyright);
		
		// ソース中の空ref()にファイル名をセット
		$post["msg"] = preg_replace("/ref\((,[^)]*)?\)/","ref(".$attachname."$1)",$post["msg"]);
		unset($file,$attachname,$filename);
	}

	//編集以前のページデータを取得
	if(is_page($post["page"]))
		$oldpostdata = join("",get_source($post["page"]));
	else
		$oldpostdata = "\n";

	// ページ情報を削除
	delete_page_info($post["msg"]);
	
	//フォームデータを信用して素通ししてしまうのは問題があるので
	//以前のデータが凍結されていて今回凍結解除する場合のチェック
	$freeze_org = $body = "";
	$checkpostdata=$oldpostdata;
	
	// 編集権限
	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
	{
		if (!X_uid) 
		{	//非ログインユーザーは凍結されたページを編集できるはずがない
			$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_cannotedit);
			$page = str_replace('$1',make_search($vars["page"]),$_title_cannotedit);
		}
		// 元の凍結情報を記憶
		$freeze_org = $arg[0];
		
		$checkpostdata = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
	}
	
	// 閲覧権限
	if (preg_match("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
	{
		// 元の編集権限を記憶
		$unvisible_org = $arg[0];
		
		$checkpostdata = preg_replace("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
	}
	
	// ページ作成者 元ページに記載があればその値を取得
	$author_uid = $X_uid;
	if (preg_match("/^\/\/ author:([0-9]+)($|\n)/",$checkpostdata,$arg))
		$author_uid = $arg[1];
	// 管理者権限はフォームデーターで上書き
	if ($X_admin)
		$author_uid = $post["f_author_uid"];
	
	unset($checkpostdata);
	
	if (!$body){
		//エラーメッセージがセットされていなければ保存実行
		
		$postdata_input = $post["msg"];

		//素の状態のポストデータは空か？
		$is_empty = (trim($post["msg"]) == "")? true : false;

		if (!$is_empty)
		{
			//整形済みブロックの | を &#x7c; に置換
			$post["msg"] = rep_for_pre($post["msg"]);
			
			//改行有効 by nao-pon
			if($post["enter_enable"]) {
				$post["msg"] = auto_br($post["msg"]);
			}
			
			//ページ名に自動リンク by nao-pon
			if ($post["auto_bra_enable"]) {
				$post["msg"] = auto_braket($post["msg"],$post["page"]);
			}
			//nao-pon

			if($post["add"])
			{
				$postdata = get_source($post["page"]);
				
				// ページ情報を削除
				delete_page_info($postdata);
				
				if($post["add_top"])
					$postdata  = $post["msg"]."\n\n".$postdata;
				else
					$postdata  = $postdata."\n\n".$post["msg"];
			}
			else
			{
				$postdata = $post["msg"];
			}

		}

		$oldpagesrc = join("",get_source($post["page"]));
		$old_md5 = md5($oldpagesrc);
		// ページ情報を削除
		delete_page_info($oldpagesrc);
		
		if($old_md5 != $post["digest"])
		{ //更新の衝突
			$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_collided);
			$page = str_replace('$1',make_search($post["page"]),$_title_collided);
			$post["digest"] = $old_md5;
			
			unset ($create_uid);
			if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$oldpagesrc,$arg)) {
				$create_uid = $arg[1];
				$freeze_check = "checked ";
				$oldpagesrc = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$oldpagesrc);
			}
			
			//保存されたページと編集内容との差分を得る
			list($postdata_input,$auto) = do_update_diff($oldpagesrc,$postdata_input);
			
			if($auto) {
			  $body = $_msg_collided_auto."\n";
			}
			else {
			  $body = $_msg_collided."\n";
			}
			$vars['preview'] = TRUE;
			$body .= edit_form($postdata_input,$vars["page"],0,$vars["gids"],$vars["aids"],$freeze_check);
		}
		else
		{
			if (!$is_empty)
			{
				//ページ情報付加　今のところページ製作者のみ
				if (is_page($post["page"]))
					$postdata = "// author:".$author_uid."\n".$postdata;
				else
					$postdata = "// author:".$X_uid."\n".$postdata;

				//閲覧権限
				if ((!$X_admin && ($X_uid != $author_uid || !$X_uid)) || !$read_auth)
				{
					$postdata = $unvisible_org.$postdata;
					$unvisible_aid = $unvisible_gid = "";
					$post["unvisible"] = "";
				}
				else
				{
					if ($post["unvisible"]){
						// ゲストグループが選択されていたら他を無効に
						if (in_array("3",$post["v_gids"]))
						{
							$unvisible_gid = "3";
							$unvisible_aid = "all";
						}
						else
						{
							$unvisible_gid = implode(",",$post["v_gids"]);
							$unvisible_aid = implode(",",$post["v_aids"]);
						}
						
						$unvisible_uid = ($author_uid == 0)? $post["f_create_uid"] : $author_uid ;
						$postdata = "#unvisible\tuid:".$unvisible_uid."\taid:".$unvisible_aid."\tgid:".$unvisible_gid."\n".$postdata;
					}
					else
						$post["unvisible"] = false;
				}

				//編集権限
				if ((!$X_admin && $X_uid != $author_uid || !$X_uid) || !$function_freeze)
				{
					$postdata = $freeze_org.$postdata;
					$freeze_aid=$freeze_gid="";
					$post["freeze"]="";
				}
				else
				{
					if ($post["freeze"])
					{
						$freeze_gid = implode(",",$post["gids"]);
						$freeze_aid = implode(",",$post["aids"]);
						
						$freeze_uid = ($author_uid == 0)? $post["f_create_uid"] : $author_uid ;
						$postdata = "#freeze\tuid:".$freeze_uid."\taid:".$freeze_aid."\tgid:".$freeze_gid."\n".$postdata;
					}
					else
						$post["freeze"] = false;
				}
			}
			
			// とりあえずページIDを記憶
			$pgid = get_pgid_by_name($post["page"]);
			
			// ページの出力
			page_write($post["page"],$postdata,$post['notimestamp'],$freeze_aid,$freeze_gid,$unvisible_aid,$unvisible_gid,$post["freeze"],$post["unvisible"]);
			
			// ページ読みを更新
			if ($pagereading_enable  && !empty($post['c_page_reading']) && isset($post['f_page_reading']) && ($X_admin || ($X_uid && $X_uid == $author_uid)))
			{
				put_reading($post['page'],$post['f_page_reading']);
			}
			
			if($postdata)
			{
				$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_updated);
				
				// 新規作成時はIDが入っていないので
				if (!$pgid) $pgid = get_pgid_by_name($post["page"]);
				
				if ($use_static_url)
					$pg_link_url = XOOPS_WIKI_URL."/".$pgid.".html";
				else
					$pg_link_url = "$script?".rawurlencode(strip_bracket($post["page"]));
				
				redirect_header($pg_link_url,1,$title);
				exit();
			}
			else
			{
				$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_deleted);
				$page = str_replace('$1',make_search($post["page"]),$_title_deleted);
				$body = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_deleted);
				if ($X_admin)
				{
					// 添付ファイル・カウンタファイル削除のリンク表示
					$body .= "\n<hr />\n<a href=\"$script?plugin=filesdel&amp;tgt=".encode($post['page'])."&amp;_pgid=".$pgid."\">$_msg_filesdel</a>";
				}
				// TrackBackデータ削除
				tb_delete($post["page"]);
			}
		}
	}
}
// 凍結
else if(arg_check("freeze") && $vars["page"] && $function_freeze)
{
	if(is_freeze($vars["page"]))
	{
		$title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_isfreezed);
		$page = str_replace('$1',make_search($vars["page"]),$_title_isfreezed);
		$body = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_isfreezed);
	}
	else if(md5($post["pass"]) == $adminpass)
	{
		$postdata = get_source($post["page"]);
		$postdata = join("",$postdata);
		$postdata = "#freeze\n".$postdata;

		file_write(DATA_DIR,$vars["page"],$postdata);

		$title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_freezed);
		$page = str_replace('$1',make_search($vars["page"]),$_title_freezed);
		$postdata = join("",get_source($vars["page"]));
		$postdata = convert_html($postdata);

		$body = $postdata;
	}
	else
	{
		$title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_freeze);
		$page = str_replace('$1',make_search($vars["page"]),$_title_freeze);

		$body.= "<br />\n";
		
		if($post["pass"])
			$body .= "<strong>$_msg_invalidpass</strong><br />\n";
		else
			$body.= "$_msg_freezing<br />\n";
		
		$body.= "<form action=\"$script?cmd=freeze\" method=\"post\">\n";
		$body.= "<div>\n";
		$body.= "<input type=\"hidden\" name=\"page\" value=\"".htmlspecialchars($vars["page"])."\" />\n";
		$body.= "<input type=\"password\" name=\"pass\" size=\"12\" />\n";
		$body.= "<input type=\"submit\" name=\"ok\" value=\"$_btn_freeze\" />\n";
		$body.= "</div>\n";
		$body.= "</form>\n";
	}
}
//凍結の解除
else if(arg_check("unfreeze") && $vars["page"] && $function_freeze)
{
	if(!is_freeze($vars["page"]))
	{
		$title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_isunfreezed);
		$page = str_replace('$1',make_search($vars["page"]),$_title_isunfreezed);
		$body = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_isunfreezed);
	}
	else if(md5($post["pass"]) == $adminpass)
	{
		$postdata = get_source($post["page"]);
		array_shift($postdata);
		$postdata = join("",$postdata);

		file_write(DATA_DIR,$vars["page"],$postdata);

		$title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_unfreezed);
		$page = str_replace('$1',make_search($vars["page"]),$_title_unfreezed);
		
		$postdata = join("",get_source($vars["page"]));
		$postdata = convert_html($postdata);
		
		$body = $postdata;
	}
	else
	{
		$title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_unfreeze);
		$page = str_replace('$1',make_search($vars["page"]),$_title_unfreeze);

		$body.= "<br />\n";

		if($post["pass"])
			$body .= "<strong>$_msg_invalidpass</strong><br />\n";
		else
			$body.= "$_msg_unfreezing<br />\n";

		$body.= "<form action=\"$script?cmd=unfreeze\" method=\"post\">\n";
		$body.= "<div>\n";
		$body.= "<input type=\"hidden\" name=\"page\" value=\"".htmlspecialchars($vars["page"])."\" />\n";
		$body.= "<input type=\"password\" name=\"pass\" size=\"12\" />\n";
		$body.= "<input type=\"submit\" name=\"ok\" value=\"$_btn_unfreeze\" />\n";
		$body.= "</div>\n";
		$body.= "</form>\n";
	}
}
// 差分の表示
else if(arg_check("diff"))
{
	$pagename = htmlspecialchars(strip_bracket($get["page"]));
	if(!is_page($get["page"]))
	{
		$title = htmlspecialchars($pagename);
		$page = make_search($vars["page"]);
		$body = $_msg_notfound;
	}
	elseif (!check_readable($get["page"],false,false))
	{
		$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),_MD_PUKIWIKI_NO_VISIBLE);
		$page = str_replace('$1',make_search($vars["page"]),_MD_PUKIWIKI_NO_VISIBLE);
		$vars["page"] = "";
	}
	else
	{
		$link = str_replace('$1',"<a href=\"$script?".rawurlencode($get["page"])."\">$pagename</a>",$_msg_goto);
		
		$body =  "<ul>\n"
			."<li>$_msg_addline</li>\n"
			."<li>$_msg_delline</li>\n"
			."<li>$link</li>\n"
			."</ul>\n"
			."$hr\n";

		if(!file_exists(DIFF_DIR.encode($get["page"]).".txt") && is_page($get["page"]))
		{
			$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_diff);
			$page = str_replace('$1',make_search($get["page"]),$_title_diff);

			$diffdata = htmlspecialchars(join("",get_source($get["page"])));
			$body .= "<pre style=\"color=:blue\">\n"
				.$diffdata
				."\n"
				."</pre>\n";
		}
		else if(file_exists(DIFF_DIR.encode($get["page"]).".txt"))
		{
			$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_diff);
			$page = str_replace('$1',make_search($get["page"]),$_title_diff);

			$diffdata = file(DIFF_DIR.encode($get["page"]).".txt");
			for ($i = 0; $i < count($diffdata); $i++) { $diffdata[$i] = htmlspecialchars($diffdata[$i]); }
			$diffdata = preg_replace("/^(\-)(.*)/","<span class=\"diff_removed\"> $2</span>",$diffdata);
			$diffdata = preg_replace("/^(\+)(.*)/","<span class=\"diff_added\"> $2</span>",$diffdata);
			
			$body .= "<div class=\"wiki_source\">\n"
				.nl2br(join("",$diffdata))
				."\n"
				."</div>\n";
		}
	}
}
// 検索
else if(arg_check("search"))
{
	// LANG=='ja'で、mb_convert_kanaが使える場合はmb_convert_kanaを使用
	$convert_kana = create_function('$str,$option',
		(LANG == 'ja' and function_exists('mb_convert_kana')) ?
			'return mb_convert_kana($str,$option);' : 'return $str;'
	);

	$vars["word"] = $convert_kana($vars["word"],"KVas");

	if($vars["word"])
	{
		$title = $page = str_replace('$1',htmlspecialchars($vars["word"]),$_title_result);
	}
	else
	{
		$page = $title = $_title_search;
	}

	if($vars["word"])
		$body = do_search($vars["word"],$vars["type"]);
	else
		$body = "<br />\n$_msg_searching";

	if($vars["type"]=="AND" || !$vars["type"]) $and_check = "checked=\"checked\"";
	else if($vars["type"]=="OR")               $or_check = "checked=\"checked\"";

	$body .= "<form action=\"$script?cmd=search\" method=\"post\">\n"
		."<div>\n"
		."<input type=\"text\" name=\"word\" size=\"20\" value=\"".htmlspecialchars($vars["word"])."\" />\n"
		."<input type=\"radio\" name=\"type\" value=\"AND\" $and_check />$_btn_and\n"
		."<input type=\"radio\" name=\"type\" value=\"OR\" $or_check />$_btn_or\n"
		."&nbsp;<input type=\"submit\" value=\"$_btn_search\" />\n"
		."</div>\n"
		."</form>\n";
}
// バックアップ
else if($do_backup && arg_check("backup"))
{
	if(!is_numeric($get["age"])) {
		unset($get["age"]);
	}
	
	if (!$get["page"] || check_readable($get["page"],false,false))
	{

		if($get["page"] && $get["age"] && (file_exists(BACKUP_DIR.encode($get["page"]).".txt") || file_exists(BACKUP_DIR.encode($get["page"]).".gz")))
		{
			$pagename = htmlspecialchars(strip_bracket($get["page"]));
			$body =  "<ul>\n";

			$body .= "<li><a href=\"$script?cmd=backup\">$_msg_backuplist</a></li>\n";

			if(!arg_check("backup_diff") && is_page($get["page"]))
			{
	 			$link = str_replace('$1',"<a href=\"$script?cmd=backup_diff&amp;page=".rawurlencode($get["page"])."&amp;age=".htmlspecialchars($get["age"])."\">$_msg_diff</a>",$_msg_view);
				$body .= "<li>$link</li>\n";
			}
			if(!arg_check("backup_nowdiff") && is_page($get["page"]))
			{
	 			$link = str_replace('$1',"<a href=\"$script?cmd=backup_nowdiff&amp;page=".rawurlencode($get["page"])."&amp;age=".htmlspecialchars($get["age"])."\">$_msg_nowdiff</a>",$_msg_view);
				$body .= "<li>$link</li>\n";
			}
			if(!arg_check("backup_source"))
			{
	 			$link = str_replace('$1',"<a href=\"$script?cmd=backup_source&amp;page=".rawurlencode($get["page"])."&amp;age=".htmlspecialchars($get["age"])."\">$_msg_source</a>",$_msg_view);
				$body .= "<li>$link</li>\n";
			}
			if(arg_check("backup_diff") || arg_check("backup_source") || arg_check("backup_nowdiff"))
			{
	 			$link = str_replace('$1',"<a href=\"$script?cmd=backup&amp;page=".rawurlencode($get["page"])."&amp;age=".htmlspecialchars($get["age"])."\">$_msg_backup</a>",$_msg_view);
				$body .= "<li>$link</li>\n";
			}
			
			if(is_page($get["page"]))
			{
				$link = str_replace('$1',"<a href=\"$script?".rawurlencode($get["page"])."\">".htmlspecialchars($pagename)."</a>",$_msg_goto);
				$body .=  "<li>$link\n";
			}
			else
			{
				$link = str_replace('$1',htmlspecialchars($pagename),$_msg_deleleted);
				$body .=  "<li>$link\n";
			}

			$backups = array();
			$backups = get_backup_info(encode($get["page"]).".txt");
			if(count($backups)) $body .= "<ul>\n";
			foreach($backups as $key => $val)
			{
				$ins_date = date($date_format,$val);
				$ins_time = date($time_format,$val);
				$ins_week = "(".$weeklabels[date("w",$val)].")";
				$backupdate = "($ins_date $ins_week $ins_time)";
				if($key != $get["age"])
	 				$body .= "<li><a href=\"$script?cmd=$get[cmd]&amp;page=".rawurlencode($get["page"])."&amp;age=$key\">$key $backupdate</a></li>\n";
				else
					$body .= "<li><em>$key $backupdate</em></li>\n";
			}
			if(count($backups)) $body .= "</ul>\n";
			$body .= "</li>\n";
			
			if(arg_check("backup_diff"))
			{
				$title = str_replace('$1',htmlspecialchars($pagename),$_title_backupdiff)."(No.".htmlspecialchars($get["age"]).")";
				$page = str_replace('$1',make_search($get["page"]),$_title_backupdiff)."(No.".htmlspecialchars($get["age"]).")";
				
				$backupdata = @join("",get_backup($get["age"]-1,encode($get["page"]).".txt"));
				$postdata = @join("",get_backup($get["age"],encode($get["page"]).".txt"));
				$diffdata = split("\n",do_diff($backupdata,$postdata));
				$backupdata = htmlspecialchars($backupdata);
			}
			else if(arg_check("backup_nowdiff"))
			{
				$title = str_replace('$1',$pagename,$_title_backupnowdiff)."(No.".htmlspecialchars($get["age"]).")";
				$page = str_replace('$1',make_search($get["page"]),$_title_backupnowdiff)."(No.".htmlspecialchars($get["age"]).")";
				
				$backupdata = @join("",get_backup($get["age"],encode($get["page"]).".txt"));
				$postdata = @join("",get_source($get["page"]));
				$diffdata = split("\n",do_diff($backupdata,$postdata));
				$backupdata = htmlspecialchars($backupdata);

			}
			else if(arg_check("backup_source"))
			{
				$title = str_replace('$1',$pagename,$_title_backupsource)."(No.".htmlspecialchars($get["age"]).")";
				$page = str_replace('$1',make_search($get["page"]),$_title_backupsource)."(No.".htmlspecialchars($get["age"]).")";
				$backupdata = join("",get_backup($get["age"],encode($get["page"]).".txt"));
				delete_page_info($backupdata);
				$backupdata = htmlspecialchars($backupdata);
				
				$body.="</ul>\n<form><textarea cols=\"80\" rows=\"20\">\n$backupdata</textarea></form>\n";
			}
			else
			{
				$title = str_replace('$1',$pagename,$_title_backup)."(No.".htmlspecialchars($get["age"]).")";
				$page = str_replace('$1',make_search($get["page"]),$_title_backup)."(No.".htmlspecialchars($get["age"]).")";
				$backupdata = join("",get_backup($get["age"],encode($get["page"]).".txt"));
				$backupdata = convert_html($backupdata);
				$body .= "</ul>\n"
					."$hr\n";
				$body .= $backupdata;
			}
			
			if(arg_check("backup_diff") || arg_check("backup_nowdiff"))
			{
	                  for ($i = 0; $i < count($diffdata); $i++) { $diffdata[$i] = htmlspecialchars($diffdata[$i]); }
				$diffdata = preg_replace("/^(\-)(.*)/","<span class=\"diff_removed\"> $2</span>",$diffdata);
				$diffdata = preg_replace("/^(\+)(.*)/","<span class=\"diff_added\"> $2</span>",$diffdata);

				$body .= "</ul><br /><ul>\n"
					."<li>$_msg_addline</li>\n"
					."<li>$_msg_delline</li>\n"
					."</ul>\n"
					."$hr\n"
					."<pre>\n".join("\n",$diffdata)."</pre>\n";
			}
		}
		else if($get["page"] && (file_exists(BACKUP_DIR.encode($get["page"]).".txt") || file_exists(BACKUP_DIR.encode($get["page"]).".gz")))
		{
			$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_pagebackuplist);
			$page = str_replace('$1',make_search($get["page"]),$_title_pagebackuplist);
			$body = get_backup_list($get["page"]);
		}
		else
		{
			$page = $title = $_title_backuplist;
			$body = get_backup_list();
		}
	}
	else
	{
		//閲覧権限なし
		$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),_MD_PUKIWIKI_NO_VISIBLE);
		$page = str_replace('$1',make_search($vars["page"]),_MD_PUKIWIKI_NO_VISIBLE);
		$vars["page"] = "";
	}
}
// ヘルプの表示
else if(arg_check("help"))
{
	$title = $page = "ヘルプ";
	$body = catrule();
}
// MD5パスワードへの変換
else if($vars["md5"])
{
	$title = $page = "Make password of MD5";
	$body = "$vars[md5] : ".md5($vars["md5"]);
}
else if(arg_check("rss"))
{
	if(empty($vars['page']) && !empty($vars['p']))
	{
		$vars['page'] = get_pgname_by_id($vars['p']);
	}
	if(!arg_check("rss10"))
		catrss(1,$vars['page'],false,$vars['count']);
	else
		catrss(2,$vars['page'],$vars['content'],$vars['count']);
	die();
}
// ページの表示とInterWikiNameの解釈
else if((arg_check("read") && $vars["page"] != "") || (!arg_check("read") && $arg != "" && $vars["page"] == ""))
{
	// ページ名がWikiNameでなく、BracketNameでなければBracketNameとして解釈
	if(!preg_match("/^(($WikiName)|($BracketName)|($InterWikiName))$/",$get["page"]))
	{
		$vars["page"] = add_bracket($vars["page"]);
		$get["page"] = $post["page"] = $vars["page"];
	}

	// WikiName、BracketNameが示すページを表示
	if(is_page($get["page"]))
	{
		if (check_readable($get["page"],false,false))
		{
			if (isset($get['com_mode']))
			{
				$noattach = 1;
				$page_comment_mode = "*ページコメント表示モード\n\n-ページコメント表示モードのため本文(ページ内容)を表示していません。\n-本文を表示するには、[[こちら>__PAGE__]]へアクセスしてください。";
				
				$postdata = convert_html(str_replace("__PAGE__",strip_bracket($vars['page']),$page_comment_mode));
				
			}
			else
				$postdata = convert_html($get["page"],false,true);
				//$pcon = new pukiwiki_converter();
				//$postdata = $pcon->convert_page($get["page"]);

				//$postdata ="The server is being maintained now. Please access it after a while.<br>現在サーバーをメンテナンス中です。しばらくしてからアクセスしてください。";

			$title = htmlspecialchars(strip_bracket($get["page"]));
			$page = make_search($get["page"]);
			$body = tb_get_rdf($vars['page'])."\n";
			
			//PlainTXT DB 更新の必要がある場合
			if (file_exists(CACHE_DIR.encode(strip_bracket($get["page"])).".udp"))
			{
				// 非同期でモードでデータ更新
				http_request(
				XOOPS_URL."/modules/pukiwiki/ud_plain.php?".rawurlencode(strip_bracket($vars["page"]))
				,'GET','',array(),HTTP_REQUEST_URL_REDIRECT_MAX,0);
			}
			
			//モジュール用キャッシュデータの更新
			if (!empty($vars['mc_refresh']))
			{
				$vars['mc_refresh'] = join(" ",array_values($vars['mc_refresh']));
				// 非同期でモードでデータ更新
				http_request(
				XOOPS_WIKI_URL."/mc_refrash.php"
				,'POST','',array('mc_refresh'=>$vars['mc_refresh'],'tgt_page'=>$vars['page']),HTTP_REQUEST_URL_REDIRECT_MAX,0,3);
			}
			
			$body .= $postdata;
			header_lastmod($vars["page"]);
			$show_comments = true;
		}
		else
		{
			$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),_MD_PUKIWIKI_NO_VISIBLE);
			$page = str_replace('$1',make_search($vars["page"]),_MD_PUKIWIKI_NO_VISIBLE);
			$vars["page"] = "";
		}
	}
	else if(preg_match("/($InterWikiName)/",$get["page"],$match))
	{
	// InterWikiNameの判別とページの表示
		$interwikis = open_interwikiname_list();
		
		if(!$interwikis[$match[2]]["url"])
		{
			$title = $page = $_title_invalidiwn;
			$body = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),str_replace('$2',"<a href=\"$script?InterWikiName\">InterWikiName</a>",$_msg_invalidiwn));
		}
		else
		{
			// 文字エンコーディング
			if($interwikis[$match[2]]["opt"] == "yw")
			{
				// YukiWiki系
				if(!preg_match("/$WikiName/",$match[3]))
					$match[3] = "[[".mb_convert_encoding($match[3],"SJIS","EUC-JP")."]]";
			}
			else if($interwikis[$match[2]]["opt"] == "moin")
			{
				// moin系
				if(function_exists("mb_convert_encoding"))
				{
					$match[3] = rawurlencode($match[3]);
					$match[3] = str_replace("%","_",$match[3]);
				}
				else
					$not_mb = 1;
			}
			else if($interwikis[$match[2]]["opt"] == "" || $interwikis[$match[2]]["opt"] == "std")
			{
				// 内部文字エンコーディングのままURLエンコード
				$match[3] = rawurlencode($match[3]);
			}
			else if($interwikis[$match[2]]["opt"] == "asis" || $interwikis[$match[2]]["opt"] == "raw")
			{
				// URLエンコードしない
				$match[3] = $match[3];
			}
			else if($interwikis[$match[2]]["opt"] != "")
			{
				// エイリアスの変換
				if($interwikis[$match[2]]["opt"] == "sjis")
					$interwikis[$match[2]]["opt"] = "SJIS";
				else if($interwikis[$match[2]]["opt"] == "euc")
					$interwikis[$match[2]]["opt"] = "EUC-JP";
				else if($interwikis[$match[2]]["opt"] == "utf8")
					$interwikis[$match[2]]["opt"] = "UTF-8";

				// その他、指定された文字コードへエンコードしてURLエンコード
				if(function_exists("mb_convert_encoding"))
					$match[3] = rawurlencode(mb_convert_encoding($match[3],$interwikis[$match[2]]["opt"],"EUC-JP"));
				else
					$not_mb = 1;
			}

			if(strpos($interwikis[$match[2]]["url"],'$1') !== FALSE)
				$url = str_replace('$1',$match[3],$interwikis[$match[2]]["url"]);
			else
				$url = $interwikis[$match[2]]["url"] . $match[3];

			if($not_mb)
			{
				$title = $page = "Not support mb_jstring.";
				$body = "This server's PHP does not have \"mb_jstring\" module.Cannot convert encoding.";
			}
			else
			{
				header("Location: $url");
				die();
			}
		}
	}
	// WikiName、BracketNameが見つからず、InterWikiNameでもない場合
	else
	{
		$up_freeze_info = get_freezed_uppage($vars["page"]);
		if ($up_freeze_info[0]) $defvalue_freeze = 1;
		
		if (!WIKI_ALLOW_NEWPAGE || !check_readable($vars["page"],false,false) || $up_freeze_info[4])
		{
			$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),_MD_PUKIWIKI_NO_AUTH);
			$page = str_replace('$1',make_search($vars["page"]),_MD_PUKIWIKI_NO_AUTH);
			$vars["page"] = "";
		}
		else
		{
			if(preg_match("/^(($BracketName)|($WikiName))$/",$get["page"]))
			{
				//echo $up_freeze_info[3][0].",".$up_freeze_info[2][0];
				$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_edit);
				$page = str_replace('$1',make_search($get["page"]),$_title_edit);
				if (!empty($vars['template_page']))
					$template = auto_template($vars['template_page'],true);
				else
					$template = auto_template($get["page"]);
				$author_uid = $X_uid;
				$freeze_check = ($defvalue_freeze)? "checked " : "";
				$body = edit_form($template,$get["page"],0,$up_freeze_info[3],$up_freeze_info[2],$freeze_check);
				//$body = edit_form($template,$get["page"]);
				$vars["cmd"] = "edit";
			}
			else
			{
				$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_invalidwn);
				$body = $page = str_replace('$1',make_search($get["page"]), str_replace('$2','WikiName',$_msg_invalidiwn));
				$vars["page"] = "";
				$template = '';
			}
		}
	}
}

if (empty($vars['xoops_block']))
{
	// <title>にページ名をプラス
	$xoops_pagetitle = $xoopsModule->name();
	$xoops_pagetitle = $title."-".$xoops_pagetitle;
	if ($h_excerpt) $xoops_pagetitle = $h_excerpt."-".$xoops_pagetitle;
	// XOOPS 1 用 XOOPS/include/functions.php の改造が必要
	global $xoops_mod_add_title,$xoops_mod_add_header;
	$xoops_mod_add_title = $xoops_pagetitle;
	//<link>タグを追加
	if (is_page($vars["page"]))
	{
		if ($vars['is_rsstop'])
			$up_page = strip_bracket($vars["page"]);
		else
		{
			if (strpos($vars["page"],"/") === FALSE)
				$up_page = "";
			else
				$up_page = preg_replace("/(.+)\/[^\/]+/","$1",strip_bracket($vars["page"]));
		}
		$up_page = ($up_page && is_page($up_page))? "/".get_pgid_by_name($up_page) : "";
		
		$rss_url = XOOPS_URL.'/modules/pukiwiki/index.php/rss10/s'.$up_page;
		
		$xoops_mod_add_header = '
<link rel="index" href="'.XOOPS_URL.'/modules/pukiwiki/index.php?cmd=list" />
<link rel="contents" href="'.XOOPS_URL.'/modules/pukiwiki/index.php?plugin=map" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="'.$rss_url.'" />
	';
		$xoops_mod_add_header .= get_header_link_tag_by_name($vars["page"]);
	}

	// CSS タグ設定
	$xoops_mod_add_header .= '<link rel="stylesheet" href="skin/trackback.css" type="text/css" media="screen" charset="shift_jis">'."\n";
	if (WIKI_THEME_CSS)
	{
		$xoops_mod_add_header .= '<link rel="stylesheet" href="'.WIKI_THEME_CSS.'" type="text/css" media="screen" charset="shift_jis">'."\n";
	}
	else
	{
		$xoops_mod_add_header .= '<link rel="stylesheet" href="skin/default.ja.css" type="text/css" media="screen" charset="shift_jis">'."\n";
	}
	if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css"))
	{
		$xoops_mod_add_header .= '<link rel="stylesheet" href="cache/css.css" type="text/css" media="screen" charset="shift_jis">'."\n";
	}
	// ページ用の .css
	$xoops_mod_add_header .= get_page_css_tag($vars["page"]);
	
	// XOOPSヘッダ
	include("header.php");
	
	// <title>にページ名をプラス
	// XOOPS 2 用
	global $xoopsTpl;
	if ($xoopsTpl)
	{
		$xoopsTpl->assign("xoops_pagetitle",$xoops_pagetitle);
		$xoopsTpl->assign("xoops_module_header",$xoops_mod_add_header);
		//Ads表示済みフラグ
		$xoopsTpl->assign("ads_shown",$wiki_ads_shown);
		$wiki_head_keywords = array_unique($wiki_head_keywords);
		if (count($wiki_head_keywords))
		{
			$xoopsTpl->assign("xoops_meta_keywords",implode(',',$wiki_head_keywords).",".$xoopsTpl->get_template_vars("xoops_meta_keywords"));
		}
		// desciption
		$xoopsTpl->assign("xoops_meta_description",mb_strcut(preg_replace("/\s+/","",strip_tags($body)),0,200));
	}
	else
	{
		$body = $xoops_mod_add_header."\n".$body;
	}
}
// ** 出力処理 **
catbody($title,$page,$body);
unset($title,$page,$body);//一応開放してみる


if (empty($vars['xoops_block']))
{
	// XOOPSテンプレート
	$xoopsOption['template_main'] = 'pukiwiki_index.html';
	$xoopsTpl->assign('body', ob_get_contents());
	ob_end_clean();

	// XOOPSコメント
	if ($use_xoops_comments && $show_comments)
	{
		$HTTP_GET_VARS['pgid'] = $_GET['pgid'] = $pgid;
		
		// ゲスト投稿権限
		$xoopsModuleConfig['com_anonpost'] = 1;
		
		$xoopsTpl->assign('show_comments', true);
		$xoopsTpl->assign('comments_title', 'ページコメント');
		include_once XOOPS_ROOT_PATH.'/include/comment_view.php';
	}
}


// XOOPSフッタ
if (empty($vars['xoops_block'])) include(XOOPS_ROOT_PATH."/footer.php");
// ** 終了 **
?>