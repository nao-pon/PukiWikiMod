<?php
// $Id: random.inc.php,v 1.3 2003/10/13 12:23:28 nao-pon Exp $
/*
Last-Update:2002-10-29 rev.2

*プラグイン random
配下のページをランダムに表示する

*Usage
 #random(メッセージ)

*パラメータ
-メッセージ~
 リンクに表示する文字列

*/
function plugin_random_convert()
{
	global $script,$vars;
	
	$title = 'press here.';
	
	if(func_num_args()) {
		$args = func_get_args();
		$title = htmlspecialchars($args[0]);
	}
	$s_page = rawurlencode(htmlspecialchars($vars['page']));
	
	return "<p><a href=\"$script?plugin=random&amp;refer={$s_page}\">$title</a></p>";
}

function plugin_random_action()
{
	global $script,$vars,$post;
	
	$pattern = '[['.strip_bracket($vars['refer']).'/';
	
	$pages = array();
	foreach (get_existpages() as $_page)
	{
		if (strpos($_page,$pattern) === 0)
			$pages[$_page] = strip_bracket($_page);
//	natcasesort($pages);
	}
	
	srand((double)microtime()*1000000);
	$page = array_rand($pages);
	
	if ($page != '') { $vars['refer'] = $page; }
	return array('body'=>'','msg'=>'');
}
?>
