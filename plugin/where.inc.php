<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: where.inc.php,v 1.4 2003/09/14 13:09:04 nao-pon Exp $
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
//  ページ名を省略した場合は、最後のページ名はリンクがつかない。
//  ページ名を指定すると、最後のページもリンクがつく。
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

	if (!($prefix) || !(page_exists("[[".$prefix."]]"))) {
		$prefix = strip_bracket($vars['page']);
		$this_page = true;
	}
	$page_names = array();
	$page_names = explode("/",$prefix);
	$title = $defaultpage.' '.get_pg_passage($defaultpage,FALSE);
	//$ret = "<a href=\"$script?cmd=read&amp;page=$defaultpage\" title=\"$title\">$_where_msg_top</a>";
	$ret = "<a href=\"$script?$defaultpage\" title=\"$title\">$_where_msg_top</a>";
	$access_name = "";
	foreach ($page_names as $page_name){
		$access_name .= $page_name."/";
		$name = substr($access_name,0,strlen($access_name)-1);
		$title = $name.' '.get_pg_passage("[[".$name."]]",FALSE);
		//$href = $script.'?cmd=read&amp;page='.rawurlencode($name);
		$href = $script.'?'.rawurlencode(strip_bracket($name));
		if ($prefix == $name){
			if ($this_page) {
				$ret .= " &gt; <b>$page_name</b>";
			} else {
				$ret .= " &gt; <a href=\"$href\" title=\"$title\"><b>$page_name</b></a>";
			}
		} else {
			$ret .= " &gt; <a href=\"$href\" title=\"$title\">$page_name</a>";
		}
	}
	return $ret;
}
?>