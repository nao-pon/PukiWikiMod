<?php
// $Id: pukiwiki_new.php,v 1.3 2003/09/02 14:06:55 nao-pon Exp $
function b_pukiwiki_new_show($option) {

	//表示する件数
	$show_num = 10;
	
	//最新記事のページ名
	$puki_new_name = "RecentChanges";

	$block = array();
	// プログラムファイル読み込み
	$pukiwiki_path = XOOPS_ROOT_PATH."/modules/pukiwiki/";

	$postdata = xb_get_source($puki_new_name);

	$block['title'] = _MI_PUKIWIKI_BTITLE;
	$block['content'] = "<small>";
	$d = '';
	for ($i = 0; $i < $show_num; $i++){
		$show_line = split(" ",$postdata[$i]);
		if($d != substr($show_line[0],1)){
			$block['content'] .= "&nbsp;<strong>".substr($show_line[0],1)."</strong><br />";
			$d = substr($show_line[0],1);
		}
		$block['content'] .= "&nbsp;&nbsp;<strong><big>&middot;</big></strong>&nbsp;".xb_make_link(trim($show_line[4]))."<br />";
	}
	$block['content'] .= "</small>";
	return $block;
}

//ページ名からリンクを作成
function xb_make_link($page)
{
	$pukiwiki_path = XOOPS_URL."/modules/pukiwiki/index.php";

	$url = rawurlencode($page);
	$_name = $name = xb_strip_bracket($page);

	//ページ名が「数字と-」だけの場合は、*(**)行を取得してみる
	if (preg_match("/^(.*\/)?[0-9\-]+$/",$name)){
		$body = xb_get_source($page);
		foreach($body as $line){
			if (preg_match("/^\*{1,3}(.*)/",$line,$reg)){
				$_name = str_replace(array("[[","]]"),"",$reg[1]);
				break;
			}
		}
	}
	$pgp = xb_get_pg_passage($page,FALSE);
	//return "<a href=\"".$pukiwiki_path."?$url\" title=\"".$date."\">".htmlspecialchars($name)."</a> ";
	return "<a href=\"".$pukiwiki_path."?$url\" title=\"".$name.$pgp."\">".htmlspecialchars($_name)."</a> ";
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

// [[ ]] を取り除く
function xb_strip_bracket($str)
{
	if(preg_match("/^\[\[(.*)\]\]$/",$str,$match)) {
		$str = $match[1];
	}
	return $str;
}

// ファイル名を得る(エンコードされている必要有り)
function xb_get_filename($pagename)
{
	return XOOPS_ROOT_PATH."/modules/pukiwiki/wiki/".$pagename.".txt";
}

// ページが存在するか？
function xb_page_exists($page)
{
	return file_exists(xb_get_filename(xb_encode($page)));
}

// ソースを取得
function xb_get_source($page)
{	
	if(xb_page_exists($page)) {
		$ret = file(xb_get_filename(xb_encode($page)));
		$ret = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$ret);
		return $ret;
  }
  return array();
}

// 指定されたページの経過時刻
function xb_get_pg_passage($page,$sw=true)
{
	if($pgdt = @filemtime(xb_get_filename(xb_encode($page))))
	{
		$pgdt = time() - $pgdt;
		if(ceil($pgdt / 60) < 60)
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60)."m)";
		else if(ceil($pgdt / 60 / 60) < 24)
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60 / 60)."h)";
		else
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60 / 60 / 24)."d)";
		
		$_pg_passage[$page]["str"] = "<small>".$_pg_passage[$page]["label"]."</small>";
	}
	else
	{
		$_pg_passage[$page]["label"] = "";
		$_pg_passage[$page]["str"] = "";
	}

	if($sw)
		return $_pg_passage[$page]["str"];
	else
		return $_pg_passage[$page]["label"];
}

?>
