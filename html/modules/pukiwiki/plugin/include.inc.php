<?php
// $Id: include.inc.php,v 1.8 2005/03/05 02:13:12 nao-pon Exp $
// internationalization
function plugin_include_init() {
	if (LANG=='ja') {
		$_plugin_include_messages = array(
			'_include_msg_see' => '%s<font size=-2> を参照</font>'
		);
	} else {
		$_plugin_include_messages = array(
			'_include_msg_see' => '<font size=-2>See </font>%s'
		);
	}
	set_plugin_messages($_plugin_include_messages);
}

function plugin_include_convert()
{
	global $_include_msg_see;
	global $script,$get,$post,$vars,$WikiName,$BracketName,$hr,$digest,$comment_no,$h_excerpt;
	
	if(func_num_args() == 0) return;
	
	list($page,$com) = func_get_args();
	
	$page = add_bracket($page);

	// 閲覧権限
	if (!check_readable($page,false,false))
		return str_replace('$1',strip_bracket($page),_MD_PUKIWIKI_NO_VISIBLE);

	//インクルード
	$body = include_page($page);
	
	$link = "<a href=\"$script?cmd=edit&amp;page=".rawurlencode($page)."\">".strip_bracket($page)."</a>";
	if($page == 'MenuBar'){
		$head = "<span align=\"center\"><h5 class=\"side_label\">$link</h5></span>";
		$body = "$head\n<small>$body</small>\n";
	} else {
		if ($com != "notitle"){
			$head = "<h4>".sprintf($_include_msg_see, $link)."</h4>\n";
		}
		$body = "$head\n$body\n";
	}
	
	return $body;
}
?>