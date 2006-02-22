<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: where.inc.php,v 1.9 2006/02/22 12:52:09 nao-pon Exp $
//
// 指定ページへの階層をリンク付で表示するプラグイン
//
// 書式：#where 又は #where(ページ名)
// ※引数を省略すると現在のページが指定される。
//
// 例：ページ名が "メイン/サブ/コンテンツ" の場合
// ページへのリンク付で
// トップ > メイン > サブ > コンテンツ
//   と表示される。
//  表示中のページはリンクがつかない
/////////////////////////////////////////////////

function plugin_where_init() {
	if (LANG=='ja') {
		$_plugin_where_messages = array(
			'_where_msg_top' => 'トップ',
		);
	} else {
		$_plugin_where_messages = array(
			'_where_msg_top' => 'Top',
		);
	}
	set_plugin_messages($_plugin_where_messages);
}

function plugin_where_convert() {
	global $script,$vars;
	$prefix = "";
	$aryargs = func_get_args();
	if ($aryargs[0]) $prefix = strip_bracket(htmlspecialchars($aryargs[0]));
	//if ($defaultpage == $prefix) return false;
	return "<div>".plugin_where_make($prefix)."</div>";
}

function plugin_where_inline() {
	global $script,$vars;
	$prefix = "";
	$aryargs = func_get_args();
	if ($aryargs[0]) $prefix = strip_bracket(htmlspecialchars($aryargs[0]));
	return plugin_where_make($prefix);
}

function plugin_where_make($prefix) {
	global $_where_msg_top;
	global $script,$vars,$defaultpage;
	$this_page = false;

	if (!($prefix) || !(page_exists(add_bracket($prefix)))) {
		$prefix = strip_bracket($vars['page']);
	}
	$title = $defaultpage.' '.get_pg_passage($defaultpage,FALSE);
	$ret = "<a href=\"".XOOPS_WIKI_URL."/\" title=\"$title\">$_where_msg_top</a>";
	
	$ret .= " &gt; ".make_pagelink($prefix,"# > #","","",FALSE);
	
	if ($vars['page'] && $vars['is_rsstop'])
	{
		$spage = htmlspecialchars(strip_bracket($vars['page']));
		$epage = "&amp;page=".rawurlencode($vars['page']);
		$ret .= " <a href=\"$script?cmd=rss10{$epage}\"><img src=\"".XOOPS_WIKI_URL."/image/rss.png\" alt=\"RSS[{$spage}]\" /></a>";
	}
	
	return $ret;
}
?>