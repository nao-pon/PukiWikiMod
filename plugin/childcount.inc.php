<?php
// $Id: childcount.inc.php,v 1.1 2004/12/23 14:04:04 nao-pon Exp $

function plugin_childcount_inline()
{
	// パラメータを配列に格納
	$args = func_get_args();
	
	// {}内の値
	$page = array_pop($args); // in {} = htmlspecialchars
	
	// {}内がなければ()内を取得する
	if (!$page) list($page) = array_pad($args, 1, '');
	
	// 与えられた値がページ名じゃない時
	if ($page && !is_page($page)) return false;
	
	// ページ名の指定がないときは表示中のページ名
	if (!$page) $page = $vars['page'];
	
	// 下層ページ数を返す
	return get_child_counts($page);
}
?>