<?php
// $Id: pukiwiki_new.php,v 1.2 2003/06/28 11:33:05 nao-pon Exp $
function b_pukiwiki_new_show($option) {

	//表示する件数
	$show_num = 10;
	
	//最新記事のページ名
	$puki_new_name = "RecentChanges";

	$block = array();
	// プログラムファイル読み込み
	$pukiwiki_path = XOOPS_ROOT_PATH."/modules/pukiwiki/";

	$postdata = file($pukiwiki_path."/wiki/".xb_encode($puki_new_name).".txt");

	$block['title'] = _MI_PUKIWIKI_BTITLE;
	$block['content'] = "<small>";
	$d = '';
	for ($i = 0; $i < $show_num; $i++){
		$show_line = split(" ",$postdata[$i]);
		if($d != substr($show_line[0],1)){
			$block['content'] .= "&nbsp;<strong>".substr($show_line[0],1)."</strong><br />";
			$d = substr($show_line[0],1);
		}
		$block['content'] .= "&nbsp;&nbsp;<strong><big>&middot;</big></strong>&nbsp;".xb_make_link($show_line[4],substr($show_line[0],6)." $show_line[1] $show_line[2]")."<br />";
	}
	$block['content'] .= "</small>";
	return $block;
}

//ページ名からリンクを作成
function xb_make_link($page,$date)
{
	$pukiwiki_path = XOOPS_URL."/modules/pukiwiki/index.php";

	$url = rawurlencode(trim($page));
	if(preg_match("/^\[\[(.*)\]\]\s$/",$page,$match)) {
		$page = $match[1];
	}
	$name = $page;

	return "<a href=\"".$pukiwiki_path."?$url\" title=\"".$date."\">".htmlspecialchars($name)."</a> ";
}

// ページ名のエンコード
function xb_encode($key)
{
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);

	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}
?>
