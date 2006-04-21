<?php
// $Id: makepage.inc.php,v 1.17 2006/04/21 14:25:04 nao-pon Exp $

function plugin_makepage_init()
{
	global $wiki_user_dir;
	
	if ($wiki_user_dir)
		$name = sprintf($wiki_user_dir,'$1');
	else
		$name = '[[$1]]';
	
	$messages = array('_makepage_messages' => array(
		'btn' => '作成',
		'msg_makepage' => 'ページ自動作成',
		'msg_body' => 'テンプレートを適用後、本文に挿入する内容',
		'msg_name' => 'お名前:',
		'body_add_name' => $name,
		'err_notemplate' => 'エラー: $1 に対応するテンプレートページがありません。',
		'err_badname' => 'エラー: $1 は無効なページ名です。',
	)
	);
	set_plugin_messages($messages);
}

function plugin_makepage_convert()
{
	global $script,$vars,$post,$_btn_edit,$_makepage_messages,$BracketName,$WikiName,$X_uname;
	global $cols,$rows;
	
	$makepage = '';
	if (func_num_args()) {
		list($makepage) = func_get_args();
	}
	$array = func_get_args();
	
	$body_message = $makepage = "";
	
	switch (func_num_args())
	{
		case 2:
			$body_message = htmlspecialchars(trim($array[1]));
		case 1:
			$makepage = trim($array[0]);
	}
	
	if ($makepage == 'this')
		$makepage = $vars['page'];
	else
		$makepage = add_bracket($makepage);
	
	if (!preg_match("/^(($BracketName)|($WikiName))$/",$makepage)) {
		$makepage = '';
	}
	$s_makepage = ($makepage)? htmlspecialchars(strip_bracket($makepage))."/" : "" ;
	$stage_tag = (empty($post['stage']))? '' : '<input type="hidden" name="stage" value="1" />';

	if (!$stage_tag)
	{
		$page_tag = $_makepage_messages['msg_makepage'].':<b>'.$s_makepage.'</b><input type="text" name="new_page" size="50" value="" />';
		$body_tag = '';
	}
	else
	{
		$body_message = (!empty($post['body_message']))? htmlspecialchars($post['body_message']) : $_makepage_messages['msg_makepage'];
		$body_message = str_replace('$1',htmlspecialchars($post['new_page']),$body_message);
		
		$matches = array();
		preg_match("/((.+)\/([^\/]+))/",$s_makepage.$post['new_page'],$matches);
		$temp = auto_template("[[:template_mp/".strip_bracket($makepage)."]]",TRUE,$matches);
		
		$post['usebody'] = ($post['usebody'])? "1" : "0";
		$post['usename'] = ($post['usename'] || preg_match("/___NAME___/",$temp))? "1" : "0";
		
		$page_tag  = '<input type="hidden" name="new_page" value="'.htmlspecialchars($post['new_page']).'" />';
		$page_tag .= '<input type="hidden" name="usebody" value="'.$post['usebody'].'" />';
		$page_tag .= '<input type="hidden" name="usename" value="'.$post['usename'].'" />';
		$page_tag .= '<input type="hidden" name="body_message" value="'.$body_message.'" />';
		$body_tag  = convert_html("***".$_makepage_messages['msg_makepage'].": [[".$s_makepage.htmlspecialchars($post['new_page'])."]]");
		$body_tag .= "<br />".$body_message.'<br />'.fontset_js_tag().'<br /><textarea name="body" rows="'.$rows.'" cols="'.$cols.'">'.$temp.'</textarea><br />';
		if ($post['usename'])
			$body_tag .= $_makepage_messages['msg_name'].' <input type="text" name="name" size="50" value="'.WIKI_NAME_DEF.'" />';
		$body_message = "";
	}

	$ret = <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="makepage" />
  <input type="hidden" name="prefix" value="$s_makepage" />
  <input type="hidden" name="body_message" value="$body_message" />
  $stage_tag
  $page_tag
  $body_tag
  <input type="submit" value="{$_makepage_messages['btn']}" />
 </div>
</form>
EOD;
	
	return $ret;
}

function plugin_makepage_action()
{
	global $X_uid,$vars,$post,$script,$_btn_edit,$_makepage_messages,$no_name,$X_uname;
	
	if ($vars['new_page'] == '')
	{
		$retvars['msg'] = $_makepage_messages['msg_makepage'];
		$retvars['body'] = do_plugin_convert('makepage',preg_replace("/\/$/","",$vars['prefix']));
		return $retvars;
	}
	
	if (!empty($vars['multi']))
	{
		$words = explode(' ',$vars['new_page']);
	}
	else
	{
		$words = array($vars['new_page']);
	}
	$_cnt = 0;
	foreach($words as $word)
	{
		@set_time_limit(120);
		if (function_exists("mb_convert_kana")) $word = mb_convert_kana($word, "KVs");
		$word = reform2pagename($word);
		$page = add_bracket($vars['prefix'].strip_bracket($word));
		
		if (is_page($page, true))
		{
			if (!empty($vars['multi'])) continue;
			if (!empty($vars['auto_make'])) exit();
			header("Location: ".XOOPS_WIKI_HOST.get_url_by_name($page));
		}
		else
		{
			if (!is_pagename($page))
			{
				//無効なページ名
				if (!empty($vars['multi'])) continue;
				if (!empty($vars['auto_make'])) exit();
				$retvars['msg'] =  str_replace('$1',htmlspecialchars(strip_bracket($page)),$_makepage_messages['err_badname']);
				$retvars['body'] = str_replace('$1',make_search($page),$_makepage_messages['err_badname']);
				$vars["page"] = "";
				return $retvars;
			}
			
			//待つ
			if ($_cnt++ > 0) sleep(30);
			
			//ページ新規作成
			$up_freeze_info = get_freezed_uppage($page);
			if (!check_readable($page,false,false) || $up_freeze_info[4])
			{
				//ページを作成する権限がない
				if (!empty($vars['multi'])) continue;
				if (!empty($vars['auto_make'])) exit();
				$retvars['msg'] =  str_replace('$1',htmlspecialchars(strip_bracket($page)),_MD_PUKIWIKI_NO_AUTH);
				$retvars['body'] = str_replace('$1',make_search($page),_MD_PUKIWIKI_NO_AUTH);
				$vars["page"] = "";
				return $retvars;
			}
			$for_template = (strpos($page,"/") !== FALSE)? $page : add_bracket("default/".strip_bracket($page));
			$postdata = auto_template($for_template);
			
			if (!$postdata)
			{
				//テンプレートがない
				if (!empty($vars['multi'])) continue;
				if (!empty($vars['auto_make'])) exit();
				$retvars['msg'] =  str_replace('$1',htmlspecialchars(strip_bracket($page)),$_makepage_messages['err_notemplate']);
				$retvars['body'] = str_replace('$1',make_search($page),$_makepage_messages['err_notemplate']);
				$vars["page"] = "";
				return $retvars;
			}
			if ((empty($post['stage']) && preg_match("/___(BODY|NAME)___/",$postdata)))
			{
				$post['usename'] = preg_match("/___NAME___/",$postdata);
				$post['usebody'] = preg_match("/___BODY___/",$postdata);
				$post['stage'] = 1;
				$retvars['msg'] = $_makepage_messages['msg_makepage'];
				$retvars['body'] = do_plugin_convert('makepage',preg_replace("/\/$/","",$post['prefix']));
				$post["page"] = "";
				return $retvars;
			}
			if (!empty($post['stage']))
			{
				if ($post['usebody'] && !trim($post['body']))
				{
					$post['usename'] = preg_match("/___NAME___/",$postdata);
					$post['usebody'] = preg_match("/___BODY___/",$postdata);
					$post['stage'] = 1;
					$retvars['msg'] = $_makepage_messages['msg_makepage'];
					$retvars['body'] = do_plugin_convert('makepage',preg_replace("/\/$/","",$post['prefix']).",\"".$post['$body_message']."\"");
					$post["page"] = "";
					return $retvars;
				}
				$post['body'] = rtrim(preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$post['body']));
				
				$postdata = str_replace("___BODY___",$post['body'],$postdata);
				
				if (!empty($post['name']))
				{
					$_name = $post['name'];
					$X_uname = $_name;
				}
				else
				{
					$_name = $no_name;
				}
				make_user_link($_name);
				
				$postdata = str_replace("___NAME___",$_name,$postdata);
				
				$postdata = auto_br($postdata);
			}
			
			// ページ作成者情報付加
			$postdata = "// author:".$X_uid."\n"."// author_ucd:".PUKIWIKI_UCD."\t".preg_replace("/#[^#]*$/","",$X_uname)."\n".$postdata;
			
			//ページ保存
			page_write($page,$postdata,NULL,"","","","","","",array('plugin'=>'makepage','mode'=>'all'));
		}
	}
	
	if (!empty($vars['auto_make']) || !empty($vars['multi'])) exit();
	
	global $_title_updated;
	$title = str_replace('$1',htmlspecialchars(strip_bracket($page)),$_title_updated);
	redirect_header(XOOPS_WIKI_HOST.get_url_by_name($page),1,$title);
	exit();
}
?>