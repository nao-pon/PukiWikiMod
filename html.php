<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: html.php,v 1.21 2003/10/13 12:23:28 nao-pon Exp $
/////////////////////////////////////////////////

// 本文をページ名から出力
function catbodyall($page,$title="",$pg="")
{
	if($title === "") $title = strip_bracket($page);
	if($pg === "") $pg = make_search($page);

	$body = join("",get_source($page));
	$body = convert_html($body);

	header_lastmod($vars["page"]);
	catbody($title,$pg,$body);
	die();
}

// 本文を出力
function catbody($title,$page,$body)
{
	global $script,$vars,$arg,$do_backup,$modifier,$modifierlink,$defaultpage,$whatsnew,$hr;
	global $date_format,$weeklabels,$time_format,$related_link;
	global $HTTP_SERVER_VARS,$cantedit;
	global $longtaketime;
	global $foot_explain, $note_hr, $_msg_word, $search_word_color;

	if($vars["page"] && !arg_check("backup") && $vars["page"] != $whatsnew)
	{
		$is_page = 1;
	}

 	$link_add = "$script?cmd=add&amp;page=".rawurlencode($vars["page"]);
 	$link_edit = "$script?cmd=edit&amp;page=".rawurlencode($vars["page"]);
 	$link_diff = "$script?cmd=diff&amp;page=".rawurlencode($vars["page"]);
	$link_top = "$script?$defaultpage";
	$link_list = "$script?cmd=list";
	$link_filelist = "$script?cmd=filelist";
	$link_search = "$script?cmd=search";
	$link_whatsnew = "$script?$whatsnew";
 	$link_backup = "$script?cmd=backup&amp;page=".rawurlencode($vars["page"]);
	$link_help = "$script?cmd=help";

	if(is_page($vars["page"]) && $is_page)
	{
		$fmt = @filemtime(get_filename(encode($vars["page"])));
	}

	if(is_page($vars["page"]) && !in_array($vars["page"],$cantedit) && !arg_check("backup") && !arg_check("edit") && !$vars["preview"])
	{
		$is_read = TRUE;
	}

	if(is_page($vars["page"]) && $related_link && $is_page && !arg_check("edit") && !arg_check("freeze") && !arg_check("unfreeze"))
	{
		$related = make_related($vars["page"]);
	}

	//単語検索
	if ($search_word_color and array_key_exists('word',$vars))
	{
		$search_word = '';
		$words = array_flip(array_splice(preg_split('/\s+/',$vars['word'],-1,PREG_SPLIT_NO_EMPTY),0,10));
		$keys = array();
		foreach ($words as $word=>$id)
		{
			$keys[$word] = strlen($word);
		}
		arsort($keys,SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys));
		$id = 0;
		foreach ($keys as $key=>$pattern)
		{
			$s_key = htmlspecialchars($key);
			$search_word .= " <strong class=\"word$id\">$s_key</strong>";
			$pattern = ($s_key{0} == '&') ?
				"/(<[^>]*>)|($pattern)/" :
				"/(<[^>]*>|&(?:#[0-9]+|#x[0-9a-f]+|[0-9a-zA-Z]+);)|($pattern)/";
			$body = preg_replace_callback($pattern,
				create_function('$arr',
					'return $arr[1] ? $arr[1] : "<strong class=\"word'.$id.'\">{$arr[2]}</strong>";'),$body);
			$id++;
		}
		$body = "<div class=\"small\">$_msg_word$search_word</div>$hr\n$body";
	}

	$longtaketime = getmicrotime() - MUTIME;
	$taketime = sprintf("%01.03f",$longtaketime);

	if ($foot_explain)
		//$body .= "\n$note_hr\n".join("\n",convert::inline2($foot_explain));
		$body .= "\n$note_hr\n".join("\n",$foot_explain);

	if(!file_exists(SKIN_FILE)||!is_readable(SKIN_FILE))
	  die_message(SKIN_FILE."(skin file) is not found.");
	require(SKIN_FILE);
}

// ページネームの取得
function get_page_name(){
	global $non_list,$whatsnew;
	
	$tmpnames = array();
	$retval = array();
	$files = get_existpages();	// 閲覧権限を無視して取り込む場合は get_existpages(true) を使用
	foreach($files as $page) {
		if(preg_match("/$non_list/",$page)) continue;
		if($page == $whatsnew) continue;
		$tmpnames[strip_bracket($page)] = (function_exists('mb_strlen'))? mb_strlen(strip_bracket($page)) : strlen(strip_bracket($page));
	}
	arsort ($tmpnames);
	reset ($tmpnames);
	return array_keys($tmpnames);
}

// 編集フォームの表示
function edit_form($postdata,$page,$add=0)
{
	global $script,$rows,$cols,$hr,$vars,$function_freeze;
	global $_btn_addtop,$_btn_preview,$_btn_update,$_btn_freeze,$_msg_help,$_btn_notchangetimestamp,$_btn_enter_enable,$_btn_autobracket_enable,$_btn_freeze_enable,$_btn_auther_id;
	global $whatsnew,$_btn_template,$_btn_load,$non_list,$load_template_func;
	global $freeze_check,$create_uid,$author_uid,$X_admin,$X_uid,$freeze_tag,$wiki_writable;
	global $unvisible_tag,$_btn_unvisible_enable,$_btn_v_allow_memo,$read_auth;
	
	$digest = md5(@join("",get_source($page)));
	$create_uid = (isset($create_uid))? $create_uid : $X_uid ;

	if($add)
	{
		$addtag = '<input type="hidden" name="add" value="true" />';
		$add_top = '<input type="checkbox" name="add_top" value="true" /><span class="small">'.$_btn_addtop.'</span>';
	}

	if($vars["help"] == "true")
		$help = $hr.catrule();
	else
 		$help = "<br />\n<ul><li><a href=\"$script?cmd=edit&amp;help=true&amp;page=".rawurlencode($page)."\">$_msg_help</a></ul></li>\n";

	$allow_edit_tag = $freeze_tag = $unvisible_tag = '';
	if($function_freeze){
		if (($X_uid && $X_uid == $author_uid) || $X_admin) {
			if ($wiki_writable === 2){
				$enable_user = _MD_PUKIWIKI_ADMIN;
			} elseif($wiki_writable === 1){
				$enable_user = _MD_PUKIWIKI_REGIST;
			} else {
				$enable_user = _MD_PUKIWIKI_ALL;
			}
			$freeze_tag = '<input type="hidden" name="f_create_uid" value="'.htmlspecialchars($create_uid).'" /><input type="checkbox" name="freeze" value="true" '.$freeze_check.'/><span class="small">'.sprintf($_btn_freeze_enable,$enable_user).'</span>';
			$allow_edit_tag = allow_edit_form();
		}
	}
	
	// 閲覧制限フォーム
	if ($read_auth)
	{
		if (($X_uid && $X_uid == $author_uid) || $X_admin) {
			$auth_viewer = get_pg_allow_viewer($page,false);
			$unvisible_check = ($auth_viewer['owner'])? "checked" : "";
			$unvisible_tag = ($function_freeze)? '' : '<input type="hidden" name="f_create_uid" value="'.htmlspecialchars($create_uid).'" />';
			$unvisible_tag .= '<input type="checkbox" name="unvisible" value="true" '.$unvisible_check.'/><span class="small">'.sprintf($_btn_unvisible_enable).'</span>';
			$allow_view_tag = allow_view_form();
		}
	}



	if ($X_admin){
		$auther_tag = '  [ '.$_btn_auther_id.'<input type="text" name="f_author_uid" size="3" value="'.htmlspecialchars($author_uid).'" /> ]';
	} else {
		$auther_tag = '<input type="hidden" name="f_author_uid" value="'.htmlspecialchars($author_uid).'" />';
	}
	
	if($load_template_func)
	{
		$vals = array();

		$files = get_existpages();
		foreach($files as $pg_org) {
			if($pg_org == $whatsnew) continue;
			if(preg_match("/$non_list/",$pg_org)) continue;
			$name = strip_bracket($pg_org);
			$s_name = htmlspecialchars($name);
			$s_org = htmlspecialchars($pg_org);
			$vals[$name] = "    <option value=\"$s_org\">$s_name</option>";
		}
		@ksort($vals);
		
		$template = "   <select name=\"template_page\">\n"
			   ."    <option value=\"\">-- $_btn_template --</option>\n"
			   .join("\n",$vals)
			   ."   </select>\n"
			   ."   <input type=\"submit\" name=\"template\" value=\"$_btn_load\" accesskey=\"r\" /><br />\n";

		if($vars["refer"]) $refer = $vars["refer"]."\n\n";
	}

return '
<form enctype="multipart/form-data" action="'.$script.'" method="post">
'.$addtag.'
<table cellspacing="3" cellpadding="0" border="0" width="100%">
 <tr>
  <td>
'.file_attache_form().'
  </td>
  <td align="right">
'.$template.'
  </td>
 </tr>
 <tr><td colspan=2>
   <input type="checkbox" name="enter_enable" value="true" checked /><span class="small">'.$_btn_enter_enable.'</span> 
   <input type="checkbox" name="auto_bra_enable" value="true" checked /><span class="small">'.$_btn_autobracket_enable.'</span>
 </td></tr>
 <tr>
  <td align="right" colspan=2>
   <input type="hidden" name="page" value="'.htmlspecialchars($page).'" />
   <input type="hidden" name="digest" value="'.htmlspecialchars($digest).'" />
   <textarea name="msg" rows="'.$rows.'" cols="'.$cols.'" wrap="virtual">
'.htmlspecialchars($refer.$postdata).'</textarea>
  </td>
 </tr>
 <tr>
  <td colspan=2>
   <input type="submit" name="preview" value="'.$_btn_preview.'" accesskey="p" />
   <input type="submit" name="write" value="'.$_btn_update.'" accesskey="s" />
   '.$add_top.'
   <input type="checkbox" name="notimestamp" value="true" /><span style="small">'.$_btn_notchangetimestamp.'</span>
   '.$auther_tag.'
  </td>
 </tr>
</table>
'.$allow_edit_tag.$allow_view_tag.'
</form>
<!--
<form action="'.$script.'?cmd=freeze" method="post">
<div>
<input type="hidden" name="page" value="'.htmlspecialchars($vars["page"]).'" />
'.$str_freeze.'
</div>
</form>
-->
' . $help;
}

// 関連するページ
function make_related($page,$tag='')
{
	global $script,$vars,$related,$rule_related_str,$related_str,$non_list;
	global $_ul_left_margin, $_ul_margin, $_list_pad_str;
	$page = strip_bracket($page);
	$links = links_get_related($page);
	
	if ($tag) {
		ksort($links);
	}
	else {
		arsort($links);
	}
	$_links = array();
	foreach ($links as $page=>$lastmod)
	{
		if (preg_match("/$non_list/",$page))
		//if (preg_match("/$non_list/",$page))
		{
			continue;
		}
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		$passage = get_passage(($lastmod));
		$_links[] = $tag ?
			"<a href=\"$script?$r_page\" title=\"$s_page $passage\">$s_page</a>" :
			"<a href=\"$script?$r_page\">$s_page</a>$passage";
	}
	
	if (count($_links) == 0)
	{
		return '';
	}
	
	if ($tag == 'p') // 行頭から
	{
		$margin = $_ul_left_margin + $_ul_margin;
		$style = sprintf($_list_pad_str,1,$margin,$margin);
		$retval =  "\n<ul$style>\n<li>".join($rule_related_str,$_links)."</li>\n</ul>\n";
	}
	else if ($tag)
	{
		$retval = join($rule_related_str,$_links);
	}
	else
	{
		$retval = join($related_str,$_links);
	}
	return $retval;
}

/*
// リンクを付加する
function make_link($name,$page = '')
{
	return p_make_link($name,$page);
}
*/
// ユーザ定義ルール(ソースを置換する)
function user_rules_str($str)
{
	global $str_rules;

	$arystr = split("\n",$str);

	// 日付・時刻置換処理
	foreach($arystr as $str)
	{
		if(substr($str,0,1) != " ")
		{
			foreach($str_rules as $rule => $replace)
			{
				$str = preg_replace("/$rule/",$replace,$str);
			}
		}
		$retvars[] = $str;
	}

	return join("\n",$retvars);
}

// ユーザ定義ルール(ソースは置換せずコンバート)
function make_user_rules($str)
{
	global $user_rules;
	static $pattern,$replace;
	
	if (!isset($pattern))
	{
		$pattern = array_map(create_function('$a','return "/$a/";'),array_keys($user_rules));
		$replace = array_values($user_rules);
		unset($user_rules);
	}
	return preg_replace($pattern,$replace,$str);
}

// ユーザ定義ルール(ソースは置換せずコンバート)
function make_line_rules($str)
{
	global $line_rules;
	static $pattern,$replace;
	
	if (!isset($pattern))
	{
		$pattern = array_map(create_function('$a','return "/$a/";'),array_keys($line_rules));
		$replace = array_values($line_rules);
		unset($line_rules);
	}
	return preg_replace($pattern,$replace,$str);
}

// HTMLタグを取り除く
function strip_htmltag($str)
{
	//$str = preg_replace("/<a[^>]+>\?<\/a>/","",$str);
	return preg_replace("/<[^>]+>/","",$str);
}

// ページ名からページ名を検索するリンクを作成
function make_search($page)
{
	global $script,$WikiName;

	$name = strip_bracket($page);
	$url = rawurlencode($name);

	//WikiWikiWeb like...
	//if(preg_match("/^$WikiName$/",$page))
	//	$name = preg_replace("/([A-Z][a-z]+)/","$1 ",$name);

 	return "<a href=\"$script?cmd=search&amp;word=$url\">".htmlspecialchars($name)."</a> ";
}

// 見出しを生成 (注釈やHTMLタグを除去)
function make_heading(&$str,$strip=TRUE)
{
	global $NotePattern;
	
	// 見出しの固有ID部を削除
	$id = '';
	if (preg_match('/^(\*{0,3})(.*?)\[#([A-Za-z][\w-]+)\](.*?)$/m',$str,$matches))
	{
		$str = $matches[2].$matches[4];
		$id = $matches[3];
	}
	else
	{
		$str = preg_replace('/^\*{0,3}/','',$str);
	}
	if ($strip)
	{
		$str = strip_htmltag(make_link(preg_replace($NotePattern,'',$str)));
	} 
	
	return $id; 
}

// テーブル入れ子用の連結
function table_inc_add ($arytable)
{
	//+-で囲んだ場合は、同じセル内＝テーブルを入れ子にできる。
	$td_level = 0 ;
	$lines_tmp = array();
	$td_tmp = "";
	foreach($arytable as $td){
		if (preg_match("/^\}([^|]*)$/",$td,$reg)) {
			$td_level += 1;
			if ($td_level == 1) $td = $reg[1];
		}
		if (preg_match("/^([^|]*)\{$/",$td,$reg)) {
			$td_level -= 1;
			if ($td_level == 0) $td = $reg[1];
		}
		if ($td_level) {
			if ($td_level == 1){
				//表内であるかの判定
				if (preg_match("/^.*&br;$/",$td) || preg_match("/^&br;.*$/",$td)) {
					$rep_str = "\n";
				} else {
					$rep_str = "->\n";
				}
				$td = preg_replace("/&br;([ #\-+*]|(&br;)+)/e","str_replace('&br;','$rep_str','$0')",$td);
				$td_tmp .= str_replace("~&br;","~$rep_str",$td)."|";//ok
				
			} else {
				$td_tmp .= str_replace("&br;","->\n",$td)."|";
			}
		} else {
			$td_tmp .= $td;//ok
			$lines_tmp[] = $td_tmp;
			$td_tmp = "";
		}
	}
	return $lines_tmp;
}
//編集権限フォーム
function allow_edit_form($allow_groups=NULL,$allow_users=NULL) {
	//global $xoopsUser;
	global $wiki_writable,$X_uid,$vars;
	global $_btn_allow_memo,$_btn_allow_header,$_btn_allow_group,$_btn_allow_user,$_btn_allow_memo_t,$_btn_allow_deny,$freeze_tag;
	global $defvalue_gids,$defvalue_aids;

	//ページの編集権限を得る
	if (is_null($allow_groups) || is_null($allow_users)) $allows = get_pg_allow_editer($vars['page']);
	if (is_null($allow_groups)) $allow_groups = ($allows['group'])? explode(",",$allows['group']) : explode(",",$defvalue_gids,",");
	if (is_null($allow_users)) $allow_users = ($allows['user'])? explode(",",$allows['user']) : explode(",",$defvalue_aids.",");
	//ゲストが投稿不可の設定の場合「ゲスト」グループのメッセージを表示しない
	//$_btn_allow_guest = ($wiki_writable === 0)? $_btn_allow_guest : "";
	
	$ret = "<hr>";
	$ret .= "<table class='style_table'><tr><th colspan='3'>$freeze_tag</th></tr>";
	$ret .= "<tr><th class='style_th'>$_btn_allow_group</th><th class='style_th'>$_btn_allow_user</th><th class='style_th'>$_btn_allow_memo_t</th></tr>";
	$ret .= "<tr><td class='style_td'>";

	if ($wiki_writable !== 2){
		$groups = X_get_group_list();
		$mygroups = X_get_groups();
	}

	// グループの名前をサイトの設定に書き換え
	//$_btn_allow_memo = str_replace("_GUEST_ALLOW_",$_btn_allow_guest,$_btn_allow_memo);
	$_btn_allow_memo = str_replace("_LOGDINUSER_",$groups[2],$_btn_allow_memo);
	//$_btn_allow_memo = str_replace("_GUREST_",$groups[3],$_btn_allow_memo);

	// グループ一覧表示
	$ret .= "<select  size='10' name='gids[]' id='gids[]' multiple='multiple'>";
	if (!is_array($allow_groups)){
		$sel = " selected";
	} else {
		$sel = (in_array("0",$allow_groups))? " selected" : "";
	}
	$ret .= "<option value='0'$sel>$_btn_allow_deny</option>";
	foreach ($groups as $gid => $gname){
		if ($gid !== 1 && $gid !== 3 && in_array($gid,$mygroups)){
			$sel = (in_array($gid,$allow_groups))? " selected" : "";
			$ret .= "<option value='".$gid."'".$sel.">$gname</option>";
		}
	}
	$ret .= "</select></td>";
	$ret .= "<td class='style_td'>";
	
	if ($wiki_writable !== 2){
		$allusers = X_get_users();
		asort($allusers);
	}

	// ユーザ一覧表示
	$ret .= "<select  size='10' name='aids[]' id='aids[]' multiple='multiple'>";
	if (!is_array($allow_users)){
		$sel = " selected";
	} else {
		$sel = (in_array("0",$allow_users))? " selected" : "";
	}
	$ret .= "<option value='0' $sel>$_btn_allow_deny</option>";
	foreach ($allusers as $uid => $uname){
			$sel = (in_array($uid,$allow_users))? " selected" : "";
			if ($uid != $X_uid) $ret .= "<option value='".$uid."'$sel>$uname</option>";
	}
	$ret .= "</select></td><td class='style_td'>".$_btn_allow_memo."</td></tr></table>";
	
	return $ret;

}

//閲覧制限フォーム
function allow_view_form($allow_groups=NULL,$allow_users=NULL) {
	//global $xoopsUser;
	global $wiki_writable,$X_uid,$vars;
	global $_btn_allow_header,$_btn_allow_group,$_btn_allow_user,$_btn_allow_deny;
	global $unvisible_tag,$_btn_unvisible_enable,$_btn_allow_memo_t,$_btn_v_allow_memo;
	//global $defvalue_gids,$defvalue_aids;

	//ページの閲覧権限を得る
	if (is_null($allow_groups) || is_null($allow_users)) $allows = get_pg_allow_viewer(strip_bracket($vars['page']),true);
	if (is_null($allow_groups)) $allow_groups = explode(",",$allows['group']);
	if (is_null($allow_users)) $allow_users = explode(",",$allows['user']);
	//ゲストが投稿不可の設定の場合「ゲスト」グループのメッセージを表示しない
	//$_btn_allow_guest = ($wiki_writable === 0)? $_btn_allow_guest : "";
	
	$ret = "<hr>";
	$ret .= "<table class='style_table'><tr><th colspan='3'>$unvisible_tag</th></tr>";
	$ret .= "<tr><th class='style_th'>$_btn_allow_group</th><th class='style_th'>$_btn_allow_user</th><th class='style_th'>$_btn_allow_memo_t</th></tr>";
	$ret .= "<tr><td class='style_td'>";

	//if ($wiki_writable !== 2){
		$groups = X_get_group_list();
		$mygroups = X_get_groups();
	//}

	// グループの名前をサイトの設定に書き換え
	//$_btn_allow_memo = str_replace("_GUEST_ALLOW_",$_btn_allow_guest,$_btn_allow_memo);
	$_btn_v_allow_memo = str_replace("_LOGDINUSER_",$groups[2],$_btn_v_allow_memo);
	$_btn_v_allow_memo = str_replace("_GUREST_",$groups[3],$_btn_v_allow_memo);

	// グループ一覧表示
	$ret .= "<select  size='10' name='v_gids[]' id='v_gids[]' multiple='multiple'>";
	if (!is_array($allow_groups)){
		$sel = " selected";
	} else {
		$sel = (in_array("0",$allow_groups))? " selected" : "";
	}
	$ret .= "<option value='0'$sel>$_btn_allow_deny</option>";
	foreach ($groups as $gid => $gname){
		//if ($gid !== 1 && $gid !== 3 && in_array($gid,$mygroups)){
		if ($gid !== 1 && in_array($gid,$mygroups) || $gid == 3){
			$sel = (in_array($gid,$allow_groups))? " selected" : "";
			$ret .= "<option value='".$gid."'".$sel.">$gname</option>";
		}
	}
	$ret .= "</select></td>";
	$ret .= "<td class='style_td'>";
	
	//if ($wiki_writable !== 2){
		$allusers = X_get_users();
		asort($allusers);
	//}

	// ユーザ一覧表示
	$ret .= "<select  size='10' name='v_aids[]' id='v_aids[]' multiple='multiple'>";
	if (!is_array($allow_users)){
		$sel = " selected";
	} else {
		$sel = (in_array("0",$allow_users))? " selected" : "";
	}
	$ret .= "<option value='0' $sel>$_btn_allow_deny</option>";
	foreach ($allusers as $uid => $uname){
			$sel = (in_array($uid,$allow_users) || in_array("all",$allow_users))? " selected" : "";
			if ($uid != $X_uid) $ret .= "<option value='".$uid."'$sel>$uname</option>";
	}
	$ret .= "</select></td><td class='style_td'>".$_btn_v_allow_memo."</td></tr></table>";
	
	return $ret;

}

// 添付ファイルアップロードフォーム
function file_attache_form() {
	global $_msg_attach_filelist,$max_size,$_msg_maxsize,$_msg_attachfile,$vars;

	$max_size = number_format(MAX_FILESIZE/1000);
	$max_size.= "KB";

	$ret.= "<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\">\n";
	$ret.= "<input type=\"hidden\" name=\"max_file_size\" value=\"".MAX_FILESIZE."\" />\n";
	//$ret.= "<span class=\"small\">[<a href=\"$script?plugin=attach&amp;pcmd=list\" target=\"_blank\">$_msg_attach_filelist</a>]</span><br />\n";
	$ret.= "<b>".$_msg_attachfile."</b>:<input type=\"file\" name=\"attach_file\" />Max[$max_size]\n";
	//$ret.= "<br /><span class=\"small\">".str_replace('$1',$max_size,$_msg_maxsize)."</span><br />\n";

	return $ret;
}
?>
