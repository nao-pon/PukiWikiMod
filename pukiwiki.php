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
// $Id: pukiwiki.php,v 1.5 2003/07/02 00:56:44 nao-pon Exp $
/////////////////////////////////////////////////
//XOOPS�إå�
//include("header.php");
//$xoopsOption['show_rblock'] =0;
//include(XOOPS_ROOT_PATH."/header.php");
//OpenTable();
//include_once(XOOPS_ROOT_PATH."/class/xoopsformloader.php");
/////////////////////////////////////////////////
// �ץ����ե������ɤ߹���
require("func.php");
require("file.php");
require("plugin.php");
require("template.php");
require("html.php");
require("backup.php");
require("rss.php");
require('make_link.php');


/////////////////////////////////////////////////
// �ץ����ե������ɤ߹���
require("init.php");

// XOOPS�ǡ����ɤ߹���
// $anon_writable:�Խ���ǽ(Yes:1 No:0)
// $X_uid:XOOPS�桼����ID
// $X_admin:PukiWiki�⥸�塼�������(Yes:1 No:0)
// 
global $xoopsUser,$xoopsDB;

$X_admin =0;
$X_uid =0;
if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname("pukiwiki");
	if ( $xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		$X_admin = 1;
	}
	$X_uid = $xoopsUser->uid();
}
/////////////////////////////////////////////////
// �Խ����¥��å�
if ($X_admin || ($wiki_writable === 0) || (($X_uid && ($wiki_writable < 2)))) {
	$anon_writable = 1;
} else {
	$anon_writable = 0;
}	
/////////////////////////////////////////////////
// �᡼�����Τ�ͭ�� or ̵��
if ($wiki_mail_sw === 2 || ($wiki_mail_sw === 1 && (!$X_admin))) {
	define("WIKI_MAIL_NOTISE",TRUE);
} else {
	define("WIKI_MAIL_NOTISE",FALSE);
}

/////////////////////////////////////////////////
// �ᥤ�����

// Plug-in action
if(!empty($vars["plugin"]) && exist_plugin_action($vars["plugin"]))
{
	$retvars = do_plugin_action($vars["plugin"]);
	
	$title = strip_bracket($vars["refer"]);
	$page = make_search($vars["refer"]);
	
	if($retvars["msg"])
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
		$cmd = "read";
		$vars["page"] = $vars["refer"];
		$body = @join("",get_source($vars["refer"]));
		$body = convert_html($body);
	}
}
// ������ɽ��
else if(arg_check("list"))
{
	header_lastmod($whatsnew);
	
	$page = $title = $_title_list;
	$body = get_list(false);
}
// �ե�����̾������ɽ��
else if(arg_check("filelist"))
{
	header_lastmod($whatsnew);

	$page = $title = $_title_filelist;
	$body = get_list(true);
}
// �Խ��Բ�ǽ�ʥڡ������Խ����褦�Ȥ����Ȥ�
else if(((arg_check("add") || arg_check("edit") || arg_check("preview")) && (is_freeze($vars["page"]) || !is_editable($vars["page"]) || $vars["page"] == "" || !$anon_writable)))
{
	$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_cannotedit);
	$page = str_replace('$1',make_search($vars["page"]),$_title_cannotedit);

//	if(is_freeze($vars["page"]))
//		$body .= "(<a href=\"$script?cmd=unfreeze&amp;page=".rawurlencode($vars["page"])."\">$_msg_unfreeze</a>)";
}
// �ɲ�
else if(arg_check("add"))
{
	$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_add);
	$page = str_replace('$1',make_search($get["page"]),$_title_add);
	$body = "<ul>\n";
	$body .= "<li>$_msg_add</li>\n";
	$body .= "</ul>\n";
	$body .= edit_form("",$get["page"],true);
}
// �Խ�
else if(arg_check("edit"))
{
	$postdata = @join("",get_source($get["page"]));
	$postdata = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$postdata);
	unset ($create_uid);
	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$postdata,$arg)) {
		$create_uid = $arg[1];
		$freeze_check = "checked ";
		$postdata = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$postdata);
	}
	//�ڡ�������
	$author_uid = get_pg_auther($get["page"]);
	$postdata = preg_replace("/^\/\/ author:([0-9]+)\n/","",$postdata);

	if($postdata == '') {
		$postdata = auto_template($get["page"]);
		$author_uid = $X_uid;
	}  
	$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_edit);
	$page = str_replace('$1',make_search($get["page"]),$_title_edit);
	$body = edit_form($postdata,$get["page"]);
}
// �ץ�ӥ塼
else if(arg_check("preview") || $post["preview"] || $post["template"])
{
	if($post["template"] && page_exists($post["template_page"]))
	{
		$post["msg"] = @join("",get_source($post["template_page"]));
	}
	
	$freeze_check = ($post["freeze"])? "checked " : "";

	//���ԥ��������� by nao-pon
	$post["msg"] = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$post["msg"]);
	
	$post["msg"] = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$post["msg"]);
	
	//����ͭ�� by nao-pon
	if($post["enter_enable"]) {
		$post["msg"] = auto_br($post["msg"]);
	}
	
	//�ڡ���̾�˼�ư��� by nao-pon
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

	$body = "$_msg_preview<br />\n";
	if($postdata == "") $body .= "<strong>$_msg_preview_delete</strong><br />\n";
	else                $body .= "<br />\n";

	if($postdata != "")
	{
		$postdata = convert_html($postdata);
		
		$body .= "<table width=\"100%\" style=\"background-color:$preview_color\">\n"
			."<tr><td>\n"
			.$postdata
			."\n</td></tr>\n"
			."</table>\n";
	}

	if($post["add"])
	{
		if($post["add_top"]) $checked_top = " checked=\"checked\"";
		$addtag = '<input type="hidden" name="add" value="true" />';
		$add_top = '<input type="checkbox" name="add_top" value="true"'.$checked_top.' /><span class="small">$_btn_addtop</span>';
	}
	if($post["notimestamp"]) $checked_time = "checked=\"checked\"";
	if($post["enter_enable"]) $checked_enter = "checked=\"checked\"";
	if($function_freeze && (($X_uid && $X_uid == $post["f_author_uid"]) || $X_admin)){
		$freeze_tag = '<input type="hidden" name="f_create_uid" value="'.htmlspecialchars($post["f_create_uid"]).'" /><input type="checkbox" name="freeze" value="true" '.$freeze_check.'/><span class="small">'.$_btn_freeze_enable.'</span>';
		$allow_edit_tag = allow_edit_form($post["gids"],$post["aids"]);
	}
	if ($X_admin){
		$auther_tag = '  [ '.$_btn_auther_id.'<input type="text" name="f_author_uid" size="3" value="'.htmlspecialchars($post["f_author_uid"]).'" /> ]';
	} else {
		$auther_tag = '<input type="hidden" name="f_author_uid" value="'.htmlspecialchars($post["f_author_uid"]).'" />';
	}

	$body .= "<form action=\"$script\" method=\"post\">\n"
		."<div>\n"
		."<input type=\"checkbox\" name=\"enter_enable\" value=\"true\" $checked_enter /><span class=\"small\">$_btn_enter_enable</span>\n"
		."��<input type=\"checkbox\" name=\"auto_bra_enable\" value=\"true\" /><span class=\"small\">$_btn_autobracket_enable</span>��\n"
		."<input type=\"hidden\" name=\"help\" value=\"".htmlspecialchars($post["add"])."\" />\n"
		."<input type=\"hidden\" name=\"page\" value=\"".htmlspecialchars($post["page"])."\" />\n"
		."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($post["digest"])."\" />\n"
		."<input type=\"hidden\" name=\"f_create_uid\" value=\"".htmlspecialchars($post["f_create_uid"])."\" />\n"
		."$addtag\n"
		."<textarea name=\"msg\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" width=\"100%\">\n".htmlspecialchars($postdata_input)."</textarea><br />\n"
		."<input type=\"submit\" name=\"preview\" value=\"$_btn_repreview\" accesskey=\"p\" />\n"
		."<input type=\"submit\" name=\"write\" value=\"$_btn_update\" accesskey=\"s\" />\n"
		."$add_top\n"
		."<input type=\"checkbox\" name=\"notimestamp\" value=\"true\" $checked_time /><span class=\"small\">$_btn_notchangetimestamp</span>\n"
		.$auther_tag
		."</div>\n"
		.$allow_edit_tag
		."</form>\n";
}
// �񤭹��ߤ⤷�����ɲä⤷���ϥ����Ȥ�����
else if($post["write"])
{
	//�Խ������Υڡ����ǡ��������
	if(is_page($post["page"]))
		$oldpostdata = join("",get_source($post["page"]));
	else
		$oldpostdata = "\n";

	//���ԥ��������� by nao-pon
	$post["msg"] = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$post["msg"]);
	$oldpostdata = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$oldpostdata);

	$post["msg"] = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$post["msg"]);
	
	//�ե�����ǡ������Ѥ������̤����Ƥ��ޤ��Τ����꤬����Τ�
	//�����Υǡ�������뤵��Ƥ��ƺ��������������Υ����å�
	$freeze_org = $body = "";
	$checkpostdata=$oldpostdata;
	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg)){
		if (!X_uid) { //�������桼��������뤵�줿�ڡ������Խ��Ǥ���Ϥ����ʤ�
			$body = $title = str_replace('$1',htmlspecialchars(strip_bracket($vars["page"])),$_title_cannotedit);
			$page = str_replace('$1',make_search($vars["page"]),$_title_cannotedit);
		}
		// ����������򵭲�
		$freeze_org = $arg[0];
		
		$checkpostdata = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
	}

	//�ڡ�������
	$author_uid = ($X_admin)? $post['f_author_uid'] : get_pg_auther($post["page"]);
	
	if (!$body){
		//���顼��å����������åȤ���Ƥ��ʤ������¸�¹�
		
		//�Ǥξ��֤Υݥ��ȥǡ����򵭲�
		$postdata_org = $post["msg"];
		
		//����ͭ�� by nao-pon
		if($post["enter_enable"]) {
			$post["msg"] = auto_br($post["msg"]);
		}
		
		//�ڡ���̾�˼�ư��� by nao-pon
		if ($post["auto_bra_enable"]) {
			$post["msg"] = auto_braket($post["msg"],$post["page"]);
		}
		//nao-pon

		//�ڡ��������ղá����ΤȤ���ڡ�������ԤΤ�
		if ($X_uid && (!is_page($post["page"]))) {
			$post["msg"] = "// author:".$X_uid."\n".$post["msg"];
		} else {
			$post["msg"] = "// author:".$author_uid."\n".$post["msg"];
		}

		//������
		if ($post["freeze"]){
			$freeze_gid = implode(",",$post["gids"]);
			$freeze_aid = implode(",",$post["aids"]);
			
			if ($X_admin){
				$freeze_uid = ($author_uid == 0)? $post["f_create_uid"] : $author_uid ;
				$post["msg"] = "#freeze\tuid:".$freeze_uid."\taid:".$freeze_aid."\tgid:".$freeze_gid."\n".$post["msg"];
			} else {
				if ($X_uid){
					if (($X_uid == $author_uid) || ($author_uid==0)) {
						$post["msg"] = "#freeze\tuid:".$X_uid."\taid:".$freeze_aid."\tgid:".$freeze_gid."\n".$post["msg"];
					} else {
						$post["msg"] = $freeze_org.$post["msg"];
					}
				}
			}
		} else {
			if (!$X_admin && $X_uid != $author_uid) $post["msg"] = $freeze_org.$post["msg"];
		}

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

		$oldpagesrc = get_source($post["page"]);
		if(md5(join("",$oldpagesrc)) != $post["digest"])
		{
			$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_collided);
			$page = str_replace('$1',make_search($post["page"]),$_title_collided);
			$post["digest"] = md5(join("",($oldpagesrc)));
			list($postdata_input,$auto) = do_update_diff(join("",$oldpagesrc),$postdata_input);
			
			if($auto) {
			  $body = $_msg_collided_auto."\n";
			}
			else {
			  $body = $_msg_collided."\n";
			}
			$body .= "<form action=\"$script\" method=\"post\">\n"
				."<div>\n"
				."<input type=\"checkbox\" name=\"enter_enable\" value=\"true\" $checked_enter /><span class=\"small\">$_btn_enter_enable</span>\n"
				."��<input type=\"checkbox\" name=\"auto_bra_enable\" value=\"true\" /><span class=\"small\">$_btn_autobracket_enable</span>\n"
				."<input type=\"hidden\" name=\"page\" value=\"".htmlspecialchars($post["page"])."\" />\n"
				."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($post["digest"])."\" />\n"
				."<textarea name=\"msg\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" id=\"textarea\">".htmlspecialchars($postdata_input)."</textarea><br />\n"
				."<input type=\"submit\" name=\"preview\" value=\"$_btn_repreview\" accesskey=\"p\" />\n"
				."<input type=\"submit\" name=\"write\" value=\"$_btn_update\" accesskey=\"s\" />\n"
				."$add_top\n"
				."<input type=\"checkbox\" name=\"notimestamp\" value=\"true\" $checked_time /><span class=\"small\">$_btn_notchangetimestamp</span>\n"
				."</div>\n"
				."</form>\n";
		}
		else
		{
			$postdata = user_rules_str($postdata);
			
			// ��ʬ�ե�����κ���
			if($postdata)
				$diffdata = do_diff($oldpostdata,$postdata);
			file_write(DIFF_DIR,$post["page"],$diffdata);
			if($postdata)
				$diffdata = do_diff($oldpostdata,$postdata);
			file_write(DIFF_DIR,$post["page"],$diffdata);

			// �Хå����åפκ���
			if(is_page($post["page"]))
				$oldposttime = filemtime(get_filename(encode($post["page"])));
			else
				$oldposttime = time();

			// �Խ����Ƥ�����񤫤�Ƥ��ʤ��ȥХå����åפ�������?���ʤ��Ǥ���͡�
			//if(!$postdata && $del_backup)
			if(!$postdata_org && $del_backup)
				backup_delete(BACKUP_DIR.encode($post["page"]).".txt");
			else if($do_backup && is_page($post["page"]))
				make_backup(encode($post["page"]).".txt",$oldpostdata,$oldposttime);

			// �ե�����ν񤭹���
			if ($postdata_org){
				file_write(DATA_DIR,$post["page"],$postdata);
			} else {
				file_write(DATA_DIR,$post["page"],$postdata_org);
			}

			if (WIKI_MAIL_NOTISE) {
				// �᡼������ by nao-pon
				global $xoopsConfig;

				 //- �᡼���Ѻ�ʬ�ǡ����κ���
				$mail_add = $mail_del = "";
				$diffdata_ar = array();
				$diffdata_ar=split("\n",$diffdata);
				foreach($diffdata_ar as $diffdata_line){
					if (ereg("^\+(.*)",$diffdata_line,$regs)){
						$mail_add .= $regs[1]."\n";
					}
					if (ereg("^\-(.*)",$diffdata_line,$regs)){
						$mail_del .= $regs[1]."\n";
					}
				}

				$mail_body = _MD_PUKIWIKI_MAIL_FIRST."\n";
				$mail_body .= _MD_PUKIWIKI_MAIL_URL.XOOPS_URL."/modules/pukiwiki/?".rawurlencode(trim($post["page"]))."\n";
				$mail_body .= _MD_PUKIWIKI_MAIL_PAGENAME.strip_bracket(trim($post["page"]))."\n";
				$mail_body .= _MD_PUKIWIKI_MAIL_POSTER.$xoopsUser->uname()."\n";
				$mail_body .= _MD_PUKIWIKI_MAIL_DEL_LINES."\n";
				$mail_body .= $mail_del;
				$mail_body .= _MD_PUKIWIKI_MAIL_ADD_LINES."\n";
				$mail_body .= $mail_add;
				$mail_body .= _MD_PUKIWIKI_MAIL_ALL_LINES."\n";
				$mail_body .= $postdata;
				$xoopsMailer =& getMailer();
				$xoopsMailer->useMail();
				$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
				$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
				$xoopsMailer->setFromName($xoopsConfig['sitename']);
				$xoopsMailer->setSubject(_MD_PUKIWIKI_MAIL_SUBJECT.strip_bracket(trim($post["page"])));
				$xoopsMailer->setBody($mail_body);
				$xoopsMailer->send();
				//�᡼�����������ޤ� by nao-pon
			}

			// is_page�Υ���å���򥯥ꥢ���롣
			is_page($post["page"],true);

			//if($postdata)
			if($postdata_org)
			{
				$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_updated);
				$page = str_replace('$1',make_search($post["page"]),$_title_updated);
				$body = convert_html($postdata);
				header("Location: $script?".rawurlencode($post["page"]));
			}
			else
			{
				$title = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_deleted);
				$page = str_replace('$1',make_search($post["page"]),$_title_deleted);
				$body = str_replace('$1',htmlspecialchars(strip_bracket($post["page"])),$_title_deleted);
			}
		}
	}
}
// ���
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
//���β��
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
// ��ʬ��ɽ��
else if(arg_check("diff"))
{
	$pagename = htmlspecialchars(strip_bracket($get["page"]));
	if(!is_page($get["page"]))
	{
		$title = htmlspecialchars($pagename);
		$page = make_search($vars["page"]);
		$body = $_msg_notfound;
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
	}

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
		
		$body .= "<pre>\n"
                        .join("",$diffdata)
			."\n"
			."</pre>\n";
	}
}
// ����
else if(arg_check("search"))
{
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
// �Хå����å�
else if($do_backup && arg_check("backup"))
{
	if(!is_numeric($get["age"])) {
		unset($get["age"]);
	}

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
			$backupdata = htmlspecialchars(join("",get_backup($get["age"],encode($get["page"]).".txt")));
			
			$body.="</ul>\n<pre>\n$backupdata</pre>\n";
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
// �إ�פ�ɽ��
else if(arg_check("help"))
{
	$title = $page = "�إ��";
	$body = catrule();
}
// MD5�ѥ���ɤؤ��Ѵ�
else if($vars["md5"])
{
	$title = $page = "Make password of MD5";
	$body = "$vars[md5] : ".md5($vars["md5"]);
}
else if(arg_check("rss"))
{
	if(!arg_check("rss10"))
		catrss(1);
	else
		catrss(2);
	die();
}
// �ڡ�����ɽ����InterWikiName�β��
else if((arg_check("read") && $vars["page"] != "") || (!arg_check("read") && $arg != "" && $vars["page"] == ""))
{
	// ��������������Ū�˻��ꤷ�Ƥ��ʤ����ڡ���̾�Ȥ��Ʋ��
	if($arg != "" && $vars["page"] == "" && $vars["cmd"] == "")
	{
		$post["page"] = $arg;
		$get["page"] = $arg;
		$vars["page"] = $arg;
	}
	
	// �ڡ���̾��WikiName�Ǥʤ���BracketName�Ǥʤ����BracketName�Ȥ��Ʋ��
	if(!preg_match("/^(($WikiName)|($BracketName)|($InterWikiName))$/",$get["page"]))
	{
		$vars["page"] = "[[".$vars["page"]."]]";
		$get["page"] = $vars["page"];
	}

	// WikiName��BracketName�������ڡ�����ɽ��
	if(is_page($get["page"]))
	{
		$postdata = join("",get_source($get["page"]));
		$postdata = convert_html($postdata);

		$title = htmlspecialchars(strip_bracket($get["page"]));
		$page = make_search($get["page"]);
		$body = $postdata;

		header_lastmod($vars["page"]);
	}
	else if(preg_match("/($InterWikiName)/",$get["page"],$match))
	{
	// InterWikiName��Ƚ�̤ȥڡ�����ɽ��
		$interwikis = open_interwikiname_list();
		
		if(!$interwikis[$match[2]]["url"])
		{
			$title = $page = $_title_invalidiwn;
			$body = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),str_replace('$2',"<a href=\"$script?InterWikiName\">InterWikiName</a>",$_msg_invalidiwn));
		}
		else
		{
			// ʸ�����󥳡��ǥ���
			if($interwikis[$match[2]]["opt"] == "yw")
			{
				// YukiWiki��
				if(!preg_match("/$WikiName/",$match[3]))
					$match[3] = "[[".mb_convert_encoding($match[3],"SJIS","EUC-JP")."]]";
			}
			else if($interwikis[$match[2]]["opt"] == "moin")
			{
				// moin��
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
				// ����ʸ�����󥳡��ǥ��󥰤Τޤ�URL���󥳡���
				$match[3] = rawurlencode($match[3]);
			}
			else if($interwikis[$match[2]]["opt"] == "asis" || $interwikis[$match[2]]["opt"] == "raw")
			{
				// URL���󥳡��ɤ��ʤ�
				$match[3] = $match[3];
			}
			else if($interwikis[$match[2]]["opt"] != "")
			{
				// �����ꥢ�����Ѵ�
				if($interwikis[$match[2]]["opt"] == "sjis")
					$interwikis[$match[2]]["opt"] = "SJIS";
				else if($interwikis[$match[2]]["opt"] == "euc")
					$interwikis[$match[2]]["opt"] = "EUC-JP";
				else if($interwikis[$match[2]]["opt"] == "utf8")
					$interwikis[$match[2]]["opt"] = "UTF-8";

				// ����¾�����ꤵ�줿ʸ�������ɤإ��󥳡��ɤ���URL���󥳡���
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
				js_redirect($url);
				die();
			}
		}
	}
	// WikiName��BracketName�����Ĥ��餺��InterWikiName�Ǥ�ʤ����
	else
	{
		if (!$anon_writable){
			$title = strip_bracket($get["page"]);
			$page = make_search($get["page"]);
			$body = _MD_PUKIWIKI_NO_AUTH;
		} else {
			if(preg_match("/^(($BracketName)|($WikiName))$/",$get["page"])) {
				$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_edit);
				$page = str_replace('$1',make_search($get["page"]),$_title_edit);
				$template = auto_template($get["page"]);
				$author_uid = $X_uid;
				$body = edit_form($template,$get["page"]);
				$vars["cmd"] = "edit";
			}
			else {
				$title = str_replace('$1',htmlspecialchars(strip_bracket($get["page"])),$_title_invalidwn);
				$body = $page = str_replace('$1',make_search($get["page"]), str_replace('$2','WikiName',$_msg_invalidiwn));
				$template = '';
			}
		}
	  
	}
}
// ������ꤵ��ʤ���硢�ȥåץڡ�����ɽ��
else
{
	$postdata = join("",get_source($defaultpage));

	$vars["page"] = $defaultpage;
	$title = htmlspecialchars(strip_bracket($defaultpage));
	$page = make_search($vars["page"]);
	$body = convert_html($postdata);

	header_lastmod($vars["page"]);
}

// ** ���Ͻ��� **
catbody($title,$page,$body);
//XOOPS�եå�
//CloseTable();
//include(XOOPS_ROOT_PATH."/footer.php");
// ** ��λ **
?>
