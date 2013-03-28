<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: tenki.inc.php,v 1.12 2006/03/06 06:20:30 nao-pon Exp $
//
//	 GNU/GPL にしたがって配布する。
//	&tenki([pic],[w:width])[{now?}];

function plugin_tenki_inline()
{
	$args = array();
	$args = func_get_args();
	
	$pic = FALSE;
	$cid = "";
	$v_width = 264;
	
	foreach($args as $arg)
	{
		$match = array();
		if (preg_match("/w:(\d+)(px)?/i",$arg,$match))
			$v_width = $match[1];
		else if (strtolower($arg) == "pic")
			$pic = TRUE;
		else if ($arg == "now?")
			continue;
		else
			if ($arg) $cid = $arg;
	}
	if ($pic) {
		//$url = "http://weather.is.kochi-u.ac.jp/FE/00Latest.jpg";
		$url = "http://www.jwa.or.jp/sat/images/sat-japan.jpg";
		$alt = "日本付近赤外画像";
		$picid = "pic";
	} else {
		$url = "http://www.jma.go.jp/JMA_HP/jp/g3/latest/SPAS-GG.gif";
		$alt = "気象庁発表天気図";
		$picid = "";
	}

	if ($cid){
		$id = $cid.$picid;
		$id = str_replace(" ","",$id);
		$id = encode($id);
		$img_arg = plugin_tenki_cache_image_fetch($url, CACHE_DIR, $id);
		$url = str_replace(XOOPS_WIKI_PATH,".",$img_arg[0]);
		$size = $img_arg[1];
	} else {
		if (ini_get('allow_url_fopen'))
		{
			$size = @getimagesize($url);
			if ($size[0] < 1) return false;
		}
	}
	
	$v_width = min(640,$v_width);
	$v_height = (!empty($size[0]))? ' height="'.floor(($v_width * $size[1])/ $size[0]).'"' : "";
	$body = "<a href=\"$url\"><img src=\"$url\" width=\"$v_width\"{$v_height} alt=\"$alt\" title=\"$alt\" /></a>\n";
	return $body;
}
// 画像キャッシュがあるか調べる
function plugin_tenki_cache_image_fetch($target, $dir, $id) {
	$filename = $dir.$id."_tenki.gif";
	if (!is_readable($filename))
	{
		$data = pkwk_http_request($target);
		if ($data['rc'] == 200 && $data['data'])
		{
			$data = $data['data'];
			plugin_tenki_cache_image_save($data, $filename, CACHE_DIR);
		}
		else
		{
			$data = "";
		}
	}
	$size = @getimagesize($filename);
	
	return array($filename,$size);
}
// 画像キャッシュを保存
function plugin_tenki_cache_image_save($data, $filename, $dir) {
	$fp = fopen($filename, "wb");
	fwrite($fp, $data);
	fclose($fp);

	return $filename;
}
?>