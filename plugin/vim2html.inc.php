<?php
/* $Id: vim2html.inc.php,v 1.2 2003/06/28 11:33:04 nao-pon Exp $
vimで
    :so $VIMRUNTIME/syntax/2html.vim
これを実行して出来るhtmlをwiki内に全て又は一部を表示する。
ファイルの保存場所は
	XOOPS_ROOT/modules/pukiwiki/vim2html
[使用例]
　該当ソースを全て表示
	#vim2html(hoge.cpp.html)
　開始位置のみ指定
	#vim2html(hoge.cpp.html, 10)
　開始位置、終了位置を指定
	#vim2html(hoge.cpp.html, 10, 50)
*/

function plugin_vim2html_convert() {
	list($file, $beg, $end) = func_get_args();
	$a = @file("./vim2html/".$file);

	if(!is_array($a)) return "file not found.[$file]<br>";
	if(!isset($end)) $end = count($a)-3;
	if(!isset($beg)) $beg = 1;

	$bgcolor = trim(preg_replace("/.*body bgcolor=\"(.*)\" text.*/", "\\1", $a[5]));
	$color = trim(preg_replace("/.*body .*text=\"(.*)\".*/", "\\1", $a[5]));
	$ret = "<pre style=\"background-color: $bgcolor; color: $color; font-size: 9pt;\">";

	for($i = $beg+6; $i <= $end+6; $i++){
		if($i >= count($a)-3) break;
		$ret .= preg_replace("/!/", "&#x21;", $a[$i]);
	}
	$ret .= "</pre>";

	return $ret;
}

?>
