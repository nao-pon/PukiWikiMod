<?php
// $Id: navi.inc.php,v 1.7 2005/03/23 14:16:29 nao-pon Exp $
/*
Last-Update:2002-12-05 rev.3

*プラグイン navi

http://home.arino.jp/index.php?%5B%5Bnavi.inc.php%5D%5D

DobBook風のナビゲーションバーを表示する

*Usage
 #navi(page)

*パラメータ
-page~
 HOMEとなるページ。省略すると自ページをHOMEとする。

*動作

1ページで2回呼ばれることを考慮して、関連変数はスタティックに持つ。

-1回目の参照(HOME)~
 ページ一覧をls.inc.php風に表示する
-1回目の参照(HOME以外)
 header : ヘッダ風
  prev       next
 -----------------
-2回目の参照
 footer : フッタ風
 ------------------
  prev  home  next
  title  up  title

*/
function plugin_navi_convert() {
	global $script,$vars,$WikiName,$BracketName;
	static $_navi_pages;

	$home = $vars['page'];
	if (func_num_args()) {
		list($home) = func_get_args();
		$home = strip_bracket($home);
		//フルパスを得る
		$home = get_fullname($home,$vars['page']);

		if (!preg_match("/^($WikiName|$BracketName)$/",$home))
			$home = add_bracket($home);
		if (!is_page($home))
			return 'no page.';
	}
	$is_home = ($home == $vars['page']);
	$current = strip_bracket($vars['page']);

	$pattern = encode('[['.strip_bracket($home).'/');
	$length = strlen($pattern);

	$footer = isset($_navi_pages);
	if (!$footer) {
		// ページ一覧取得
		$pages = get_existpages_db(false,$home,0,"",FALSE,FALSE,TRUE,TRUE);
		
		// 未作成時のための番兵(プレビューとか)
		if (in_array($current,$pages) === FALSE)
			$pages[] = $current;
		
		natcasesort($pages);
		$prev = $home;
		foreach ($pages as $page) {
			if ($page == $current) { break; }
			$prev = $page;
		}
		$next = current($pages);

		$_navi_pages = array(
			'up' => '&nbsp;',
			'home' => '&nbsp;',
			'home1' => '&nbsp;',
			'prev' => '&nbsp;',
			'prev1' => '&nbsp;',
			'next' => '&nbsp;',
			'next1' => '&nbsp;',
		);

		$pos = strrpos($current, '/');
		if ($pos > 0) {
			$_navi_pages['up'] = make_link('[[Up>'.substr($current, 0, $pos).']]');
		}
		if (!$is_home) {
			if ($prev != $home) {
				$_navi_pages['prev'] = make_link("[[$prev]]");
			} else {
				$_navi_pages['prev'] = make_link("$prev");
			}
			$_navi_pages['prev1'] = make_link("[[Prev>$prev]]");
		}
		if ($next != "") {
			$_navi_pages['next'] = make_link("[[$next]]");
			$_navi_pages['next1'] = make_link("[[Next>$next]]");
		}
		if (!$is_home) {
			$_navi_pages['home'] = make_link($home);
			$_navi_pages['home1'] = make_link('[[Home>'.strip_bracket($home).']]');
		}
	}

	$ret = '';
	if ($footer) { //フッタ
		$ret = <<<EOD
<div class=".navi_footer">
<hr width="100%">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td width="33%" align="left" valign="top">{$_navi_pages['prev1']}</td>
<td width="34%" align="center" valign="top">{$_navi_pages['home1']}</td>
<td width="33%" align="right" valign="top">{$_navi_pages['next1']}</td></tr>
<tr><td width="33%" align="left" valign="top">{$_navi_pages['prev']}</td>
<td width="34%" align="center" valign="top">{$_navi_pages['up']}</td>
<td width="33%" align="right" valign="top">{$_navi_pages['next']}</td></tr>
</table>
</div>
EOD;
	} else if ($is_home) { //目次
		$ret .= "<ul>";
		foreach ($pages as $page) {
			if (strip_bracket($page) == strip_bracket($home)) { continue; }
			$ret .= "<li>".make_link("[[$page]]")."</li>";
		}
		$ret .= "</ul>";
	} else {
		$ret = <<<EOD
<div class=".navi_header">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td width="33%" align="left" valign="top">{$_navi_pages['prev1']}</td>
<td width="34%" align="center" valign="top">{$_navi_pages['home']}</td>
<td width="33%" align="right" valign="top">{$_navi_pages['next1']}</td></tr>
</table>
<hr width="100%">
</div>
EOD;
	}
	return $ret;
}
?>