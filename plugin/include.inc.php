<?php
// $Id: include.inc.php,v 1.5 2004/05/20 14:52:48 nao-pon Exp $
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
	static $include_list; //処理済ページ名の配列
	if (!isset($include_list))
		$include_list = array($vars['page']=>TRUE);
	
	if(func_num_args() == 0)
		return;
	
	list($page,$com) = func_get_args();
	
	if (!preg_match("/^($WikiName|$BracketName)$/",$page))
		$page = "[[$page]]";
	
	if (!is_page($page))
		return '';

	// 閲覧権限
	if (!check_readable($page,false,false))
		return str_replace('$1',strip_bracket($page),_MD_PUKIWIKI_NO_VISIBLE);
	
	if (isset($include_list[$page]))
		return '';
	
	$include_list[$page] = TRUE;

	//変数値退避
	$digest_esc = $digest;
	$_comment_no = $comment_no;
	$_h_excerpt = $h_excerpt;
	$tmppage = $vars['page'];
	
	//初期化
	$get['page'] = $post['page'] = $vars['page'] = $page;
	$comment_no = 0;
	
	$body = convert_html($page,false,true);
	
	// $link = "<a href=\"$script?".rawurlencode($page)."\">".strip_bracket($page)."</a>";
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
	
	//退避変数戻し
	$get['page'] = $post['page'] = $vars['page'] = $tmppage;
	$comment_no = $_comment_no;
	$h_excerpt = $_h_excerpt;
	$digest = $digest_esc; //元に戻す

	return $body;
}
?>
