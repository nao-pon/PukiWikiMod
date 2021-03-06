<?php
/*
 * PukiWiki BugTrackプラグイン
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 * 
 * 変更履歴:
 *  2002.06.17: 作り始め
 *
 * $Id: bugtrack.inc.php,v 1.14 2006/03/06 06:20:30 nao-pon Exp $
 */

function plugin_bugtrack_init()
{
    global $script;
    if (LANG=='ja') {
        $_plugin_bugtrack_messages = array(
            '_bugtrack_plugin_priority_list' => array("緊急","重要","普通","低"),
            '_bugtrack_plugin_state_list' => array("提案","着手","CVS待ち","完了","保留","却下"),
            '_bugtrack_plugin_state_sort' => array("着手","CVS待ち","保留","完了","提案","却下"),
            '_bugtrack_plugin_state_bgcolor' => array("#ccccff","#ffcc99","#ccffcc","#ccffcc","#ffccff","#cccccc","#ff3333"),
            '_bugtrack_plugin_title' => "\$1 Bugtrack Plugin",
            '_bugtrack_plugin_base' => "ページ",
            '_bugtrack_plugin_summary' => "サマリ",
            '_bugtrack_plugin_priority' => "優先順位",
            '_bugtrack_plugin_state' => "状態",
            '_bugtrack_plugin_name' => "投稿者",
            '_bugtrack_plugin_date' => "投稿日",
            '_bugtrack_plugin_body' => "メッセージ",
            '_bugtrack_plugin_category' => "カテゴリー",
            '_bugtrack_plugin_pagename' => "ページ名",
            '_bugtrack_plugin_pagename_comment' => "<font size=\"1\">空欄のままだと自動的にページ名が振られます。</font>",
            '_bugtrack_plugin_version_comment' => "<font size=\"1\">空欄でも構いません</font>",
            '_bugtrack_plugin_version' => "バージョン",
            '_bugtrack_plugin_submit' => "追加"
        );
    } else {
        $_plugin_bugtrack_messages = array(
            '_bugtrack_plugin_priority_list' => array("Urgent","High","Middle","Low"),
            '_bugtrack_plugin_state_list' => array("Open","in Progress","Ready to CVS","Closed","Pending","Rejected"),
            '_bugtrack_plugin_state_sort' => array("in Progress","Ready to CVS","Pending","Closed","Open","Rejected"),
            '_bugtrack_plugin_state_bgcolor' => array("#ccccff","#ffcc99","#ccffcc","#ccffcc","#ffccff","#cccccc","#ff3333"),
            '_bugtrack_plugin_title' => "\$1 Bugtrack Plugin",
            '_bugtrack_plugin_base' => "Page",
            '_bugtrack_plugin_summary' => "Summary",
            '_bugtrack_plugin_priority' => "Priority",
            '_bugtrack_plugin_state' => "Status",
            '_bugtrack_plugin_name' => "Poster",
            '_bugtrack_plugin_date' => "Posted",
            '_bugtrack_plugin_body' => "Description",
            '_bugtrack_plugin_category' => "Category",
            '_bugtrack_plugin_pagename' => "Page Name",
            '_bugtrack_plugin_pagename_comment' => "<font size=\"1\">Automatically assigned if left empty</font>",
            '_bugtrack_plugin_version_comment' => "<font size=\"1\">Can be empty</font>",
            '_bugtrack_plugin_version' => "Version",
            '_bugtrack_plugin_submit' => "Add"
        );
    }
    set_plugin_messages($_plugin_bugtrack_messages);
}


function plugin_bugtrack_action()
{
	global $command,$vars,$_bugtrack_plugin_default_category,$script,$post,$no_name;
	global $_bugtrack_plugin_title_submitted,$_bugtrack_plugin_title;
	//改行コード統一 by nao-pon
	$post['body'] = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$post['body']);

	//改行有効 by nao-pon
	if($post['enter_enable']) {
		$post['body'] = auto_br($post['body']);
	}

	//ページ名に自動リンク by nao-pon
	if ($post['auto_bra_enable']) {
		$post['body'] = auto_braket($post['body'],$vars['page']);
	}

  if($post['mode']=='submit') {
    $ret['msg'] = $_bugtrack_plugin_title_submitted;
    $post['name'] = ($post['name'])? $post['name'] : $no_name;
    $page = plugin_bugtrack_write($post['base'], $post['pagename'], $post['summary'], $post['name'], $post['priority'], $post['state'], $post['category'], $post['version'], $post['body']);
	js_redirect("$script?".rawurlencode($page));
    die;
  }
  else {
    $ret['msg'] = $_bugtrack_plugin_title;
    $ret["body"] = plugin_bugtrack_print_form($vars['category']);
  }
  
  return $ret;
}

function plugin_bugtrack_print_form($base,$category)
{
  global $_bugtrack_plugin_priority_list,$_bugtrack_plugin_state_list;
  global $_bugtrack_plugin_priority, $_bugtrack_plugin_state, $_bugtrack_plugin_name;
  global $_bugtrack_plugin_date, $_bugtrack_plugin_category, $_bugtrack_plugin_body;
  global $_bugtrack_plugin_summary, $_bugtrack_plugin_submit, $_bugtrack_plugin_version;
  global $_bugtrack_plugin_pagename, $_bugtrack_plugin_pagename_comment;
  global $_bugtrack_plugin_version_comment;
  global $script,$_btn_enter_enable,$_btn_autobracket_enable,$autolink;

	// xoops //
	global $xoopsUser;
	if ($xoopsUser){
		$name = $xoopsUser->uname();
	}
	// ---- //

  $select_priority = '';
  for($i=0; $i<count($_bugtrack_plugin_priority_list); ++$i) {
    if($i<count($_bugtrack_plugin_priority_list)-1) {
      $selected = '';
    }
    else {
      $selected = ' selected';
    }
    $select_priority .= '<option name="'.$_bugtrack_plugin_priority_list[$i].'"'.$selected.'>'.$_bugtrack_plugin_priority_list[$i]."</option>";
  }
  
  $select_state = '';
  for($i=0; $i<count($_bugtrack_plugin_state_list); ++$i) {
    $select_state .= '<option name="'.$_bugtrack_plugin_state_list[$i].'">'.$_bugtrack_plugin_state_list[$i]."</option>";
  }
  
  if(count($category)==0) {
    $encoded_category = "<input name=\"category\" type=\"text\">";
  }
  else {
    $encoded_category = "<select name=\"category\">";
    for($i=0; $i<count($category); ++$i) {
      $s_category = htmlspecialchars($category[$i]);
      $encoded_category .= '<option name="'.$s_category.'">'.$s_category."</option>";
    }
    $encoded_category .= "</select>";
  }
  
  $auto_bra_enable = ($autolink)? "" : " checked";
  
  $body = "<table border=\"0\"><form action=\"$script\" method=\"post\">
<tr><th nowrap>$_bugtrack_plugin_name</th><td><input name=\"name\" size=\"20\" type=\"text\" value=\"$name\"></td></tr>
<tr><th nowrap>$_bugtrack_plugin_category</th><td>$encoded_category</td></tr>
<tr><th nowrap>$_bugtrack_plugin_priority</th><td><select name=\"priority\">$select_priority</select></td></tr>
<tr><th nowrap>$_bugtrack_plugin_state</th><td><select name=\"state\">$select_state</select></td></tr>
<tr><th nowrap>$_bugtrack_plugin_pagename</th><td><input name=\"pagename\" size=\"20\" type=\"text\">$_bugtrack_plugin_pagename_comment</td></tr>
<tr><th nowrap>$_bugtrack_plugin_version</th><td><input name=\"version\" size=\"10\" type=\"text\">$_bugtrack_plugin_version_comment</td></tr>
<tr><th nowrap>$_bugtrack_plugin_summary</th><td><input name=\"summary\" size=\"60\" type=\"text\"></td></tr>
<tr><th nowrap></th><td><input type=\"checkbox\" name=\"enter_enable\" value=\"true\" checked /><span class=\"small\">$_btn_enter_enable</span> <input type=\"checkbox\" name=\"auto_bra_enable\" value=\"true\"".$auto_bra_enable." /><span class=\"small\">$_btn_autobracket_enable</span></td></tr>
<tr><th nowrap>$_bugtrack_plugin_body</th><td><textarea name=\"body\" cols=\"60\" rows=\"6\"></textarea></td></tr>
<tr><td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"$_bugtrack_plugin_submit\">
<input type=\"hidden\" name=\"plugin\" value=\"bugtrack\">
<input type=\"hidden\" name=\"mode\" value=\"submit\">
<input type=\"hidden\" name=\"base\" value=\"".htmlspecialchars($base)."\">
</td></tr>
</form></table>";
  
  return $body;
}

function plugin_bugtrack_template($base, $summary, $name, $priority, $state, $category, $version, $body)
{
  global $_bugtrack_plugin_priority, $_bugtrack_plugin_state, $_bugtrack_plugin_name;
  global $_bugtrack_plugin_date, $_bugtrack_plugin_category, $_bugtrack_plugin_base;
  global $_bugtrack_plugin_body, $_bugtrack_plugin_version;
  global $script, $WikiName;

	if (WIKI_USER_DIR)
		make_user_link($name);
	else
	{
	  if(!preg_match("/^$WikiName$$/",$name)) {
	    $name = "[[$name]]";
	  }
	}
  if(!preg_match("/^$WikiName$$/",$base)) {
    $base = "[[$base]]";
  }

   return 
"*$summary

-$_bugtrack_plugin_base: $base
-$_bugtrack_plugin_name: $name
-$_bugtrack_plugin_priority: $priority
-$_bugtrack_plugin_state: $state
-$_bugtrack_plugin_category: $category
-$_bugtrack_plugin_date: now?
-$_bugtrack_plugin_version: $version

**$_bugtrack_plugin_body
$body
----

#pcomment";
}

function plugin_bugtrack_write($base, $pagename, $summary, $name, $priority, $state, $category, $version, $body)
{
  global $WikiName,$BracketName;
  global $X_uid,$X_uname;
  
  $pagename = strip_bracket($pagename);
  
  $postdata = plugin_bugtrack_template($base, $summary, $name, $priority, $state, $category, $version, $body);
  // author:uid の追加
  $postdata = "// author:".$X_uid."\n"."// author_ucd:".PUKIWIKI_UCD."\t".preg_replace("/#[^#]*$/","",$X_uname)."\n".$postdata;
	
  $i = 0;
  do {
    $i++;
    $page = "[[".strip_bracket($base)."/$i]]";
  } while(is_page($page));
  
  if($pagename == '') {
    file_write(DATA_DIR,$page,$postdata);
  }
  else {
    if(!preg_match("/^$WikiName$$/",$pagename)) {
      $pagename = "[[$pagename]]";
    }
    $pagename = get_fullname($pagename,$base);
    if (is_page($pagename)
    	or !preg_match("/^(($BracketName)|($WikiName))$/",$pagename))
      $pagename = $page;
    else
      page_write($page,"move to $pagename",NULL,"","","","","","",array('plugin'=>'bugtrack','mode'=>'all'));
    page_write($pagename,$postdata,NULL,"","","","","","",array('plugin'=>'bugtrack','mode'=>'all'));
  }
  
  return $page;
}

function plugin_bugtrack_convert()
{
	global $script,$weeklabels,$vars,$command,$WikiName,$BracketName;
	global $_bugtrack_plugin_default_category;

	$args = func_get_args();
	$base = $vars['page'];
	$category = array();
	if(func_num_args() > 0)
	{
	    $_base = get_fullname($args[0],$base);
		if (preg_match("/^(($BracketName)|($WikiName))$/",$_base))
		{
			$base = $_base;
		}	    
		$category = $args;
		array_shift($category);
	}

	return plugin_bugtrack_print_form($base,$category);
}


function plugin_bugtrack_pageinfo($page) {
	global $WikiName, $InterWikiName, $BracketName;

	$source = get_source($page);
	$source = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$source);
	
	$match = array();
	if(preg_match("/move\s*to\s*($WikiName|$InterWikiName|$BracketName)/",$source[0],$match)) {
		return(plugin_bugtrack_pageinfo($match[1]));
	}

	$body = join("\n",$source);
	$summary = $name = $priority = $state = $category = "\x08";
	$itemlist = array();
	foreach(array('summary','name','priority','state','category') as $item) {
		$itemname = '_bugtrack_plugin_'.$item;
		global $$itemname;
		$itemname = $$itemname;
		$matches = array();
		if(preg_match("/-[ \t\r\f]*".$itemname."[ \t]*:[ \t]*(.*)[ \t]*/",$body,$matches)) {
			if($item == "summary")
			{
				$$item = htmlspecialchars(strip_bracket($matches[1]));
			}
			else
			{
				$$item = htmlspecialchars($matches[1]);
			}
		}
	}

	global $_bugtrack_plugin_summary;
	if(preg_match("/\*([^\n]+)/",$body,$matches)) {
		$summary = htmlspecialchars($matches[1]);
	}
	
	return(array($page, $summary, $name, $priority, $state, $category));
}

function plugin_bugtrack_list_convert()
{
  global $vars, $script;
  global $_bugtrack_plugin_priority, $_bugtrack_plugin_state, $_bugtrack_plugin_name;
  global $_bugtrack_plugin_date, $_bugtrack_plugin_category, $_bugtrack_plugin_summary;
  global $_bugtrack_plugin_state_sort,$_bugtrack_plugin_state_list,$_bugtrack_plugin_state_bgcolor;
  global $WikiName,$BracketName;
  
  $page = $vars['page'];
  if(func_num_args()) {
	  $aryargs = func_get_args();
	  $_page = get_fullname($aryargs[0],$page);
	  if (preg_match("/^(($BracketName)|($WikiName))$/",$_page)){
		  $page = $_page;
	  } else {
			$compact = ($aryargs[0]=="compact")? true : false;
		}
	  if (!$compact) $compact = ($aryargs[1]=="compact")? true : false;
  }
  
  $data = array();
  $states = array();
  $filepattern = encode('[['.strip_bracket($page).'/');
  $filepattern_len = strlen($filepattern);
  if ($dir = @opendir(DATA_DIR))
    {
      while($file = readdir($dir))
	{
	  if($file == ".." || $file == ".") continue;
	  if(substr($file,0,$filepattern_len)!=$filepattern) continue; 
	  $page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
	  // 閲覧権限
	  if (!check_readable($page,false,false)) continue;
	  $line = plugin_bugtrack_pageinfo($page);
	  list($page, $summary, $name, $priority, $state, $category) = $line;
	  if ($category != "\x08") {
			array_push($data,$line);
	  	array_push($states,$state);
	  }
	}
      closedir($dir);
    }
  array_unique($states);
  $table = array();

  for($i=0; $i<=count($_bugtrack_plugin_state_list)+1; ++$i) {
    $table[$i] = array();
  }
  foreach($data as $line) {
    list($page, $summary, $name, $priority, $state, $category) = $line;
    //$page_link = make_link($page);
    $short_name = ($compact)? "- ".preg_replace("/.*\/([^\/]*)$/","$1",strip_bracket($page))." -" : "";
    $page_link = make_pagelink($page,$short_name);
    $state_no = array_search($state,$_bugtrack_plugin_state_sort);
    if($state_no === NULL or $state_no === FALSE) {
      $state_no = count($_bugtrack_plugin_state_list);
    }
    $bgcolor = $_bugtrack_plugin_state_bgcolor[$state_no];
    array_push($table[$state_no],"<tr bgcolor=\"$bgcolor\"><td nowrap>$page_link</td><td nowrap>$state</td><td nowrap>$priority</td><td nowrap>$category</td><td nowrap>".make_link("$name")."</td><td>".make_pagelink($page,$summary)."</td></tr>");
  }
  
  $table_html = "<tr><th></th><th>$_bugtrack_plugin_state</th><th>$_bugtrack_plugin_priority</th><th>$_bugtrack_plugin_category</th><th>$_bugtrack_plugin_name</th><th>$_bugtrack_plugin_summary</th></tr>\n";
  for($i=0; $i<=count($_bugtrack_plugin_state_list); ++$i) {
    $table_html .= join("\n",$table[$i]);
  }
  return "<table border=1 style=\"width:100%\">$table_html</table>";
}

?>