<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: tenki.inc.php,v 1.1 2003/06/28 06:01:53 nao-pon Exp $
//
//	 GNU/GPL にしたがって配布する。
//

function plugin_tenki_inline()
{
	$args = array();
	$args = func_get_args();
	//echo $args[0].":".$args[1];
	if ($args[0] == "pic") {
		$url = "http://weather.is.kochi-u.ac.jp/FE/00Latest.jpg";
		$alt = "日本付近赤外画像";
		$picid = "pic";
		$args[0] = $args[1];
	} else {
		$url = "http://www.jma.go.jp/JMA_HP/jp/g3/latest/SPAS-GG.gif";
		$alt = "気象庁発表天気図";
		$picid = "";
	}

	if ($args[0] == "now?") $args[0] = "";
	if ($args[0]){
		$img_arg = plugin_tenki_cache_image_fetch($url, CACHE_DIR, $args[0].$picid);
		$url = $img_arg[0];
		$size = $img_arg[1];
	} else {
		$size = @getimagesize($url);
		if ($size[0] < 1) return false;
	}

	$v_width = 264;
	$v_height = floor(($v_width * $size[1])/ $size[0]);
	$body = "<a href=\"$url\"><img src=\"$url\" width=\"$v_width\" height=\"$v_height\" alt=\"$alt\" title=\"$alt\" /></a>\n";
	return $body;
}
// 画像キャッシュがあるか調べる
function plugin_tenki_cache_image_fetch($target, $dir, $id) {
	$id = str_replace(" ","",$id);
	$id = encode($id);
	$filename = $dir.$id."_tenki.gif";
	if (!is_readable($filename)) {
		$file = fopen($target, "rb"); // たぶん size 取得よりこちらが原始的だからやや速い
		if (! $file) {
			fclose($file);
			$url = NOIMAGE;
		} else {
			$data = fread($file, 1000000); 
			fclose ($file);
			$size = @getimagesize($target); // あったら、size を取得、通常は1が返るが念のため0の場合も(reimy)
			if ($size[0] <= 1)
				$url = NOIMAGE;
			else
				$url = $filename;
		}
		plugin_tenki_cache_image_save($data, $filename, CACHE_DIR);
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
