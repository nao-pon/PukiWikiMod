<?php
// $Id: include.inc.php,v 1.2 2003/06/28 11:33:03 nao-pon Exp $
function plugin_include_convert()
{
	global $script,$get,$post,$vars,$WikiName,$BracketName,$hr,$digest;
	static $include_list; //処理済ページ名の配列
	$digest_esc = $digest; //一時退避
	if (!isset($include_list))
		$include_list = array($vars['page']=>TRUE);
	
	if(func_num_args() == 0)
		return;
	
	list($page,$com) = func_get_args();
	
	if (!preg_match("/^($WikiName|$BracketName)$/",$page))
		$page = "[[$page]]";
	
	if (!is_page($page))
		return '';
	
	if (isset($include_list[$page]))
		return '';
	
	$include_list[$page] = TRUE;
	
	$tmppage = $vars['page'];
	
	$get['page'] = $post['page'] = $vars['page'] = $page;

	$body = @join('',@file(get_filename(encode($page))));
	$body = convert_html($body);

	// $link = "<a href=\"$script?".rawurlencode($page)."\">".strip_bracket($page)."</a>";
	$link = "<a href=\"$script?cmd=edit&amp;page=".rawurlencode($page)."\">".strip_bracket($page)."</a>";
	if($page == 'MenuBar'){
		$head = "<span align=\"center\"><h5 class=\"side_label\">$link</h5></span>";
		$body = "$head\n<small>$body</small>\n";
	} else {
		if ($com != "notitle"){
			$head = "<h4>$link<font size=-2> を参照</font></h4>\n";
		}
		$body = "$head\n$body\n";
	}

	$get['page'] = $post['page'] = $vars['page'] = $tmppage;
	
	$digest = $digest_esc; //元に戻す
	return $body;
}
?>
