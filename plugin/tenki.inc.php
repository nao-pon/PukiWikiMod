<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: tenki.inc.php,v 1.2 2003/06/28 11:33:04 nao-pon Exp $
//
//	 GNU/GPL �ˤ������ä����ۤ��롣
//

function plugin_tenki_inline()
{
	$url = "http://www.jma.go.jp/JMA_HP/jp/g3/latest/SPAS-GG.gif";
	$size = @getimagesize($url);
	if ($size[0] < 1) return false;
	$args = func_get_args();
	if ($args[0] == "now?") $args[0] = "";
	if ($args[0]){
		$img_arg = plugin_tenki_cache_image_fetch($url, CACHE_DIR, $args[0]);
		$url = $img_arg[0];
		$size = $img_arg[1];
	}

	$v_width = 264;
	$v_height = floor(($v_width * $size[1])/ $size[0]);
	$body = "<a href=\"$url\"><img src=\"$url\" width=\"$v_width\" height=\"$v_height\" alt=\"����ģȯɽŷ����\" title=\"����ģȯɽŷ����\" /></a>\n";
	return $body;
}
// ��������å��夬���뤫Ĵ�٤�
function plugin_tenki_cache_image_fetch($target, $dir, $id) {
	$id = str_replace(" ","",$id);
	$id = encode($id);
	$filename = $dir.$id."_tenki.gif";
	if (!is_readable($filename)) {
		$file = fopen($target, "rb"); // ���֤� size ������ꤳ���餬����Ū��������®��
		if (! $file) {
			fclose($file);
			$url = NOIMAGE;
		} else {
			$data = fread($file, 1000000); 
			fclose ($file);
			$size = @getimagesize($target); // ���ä��顢size ��������̾��1���֤뤬ǰ�Τ���0�ξ���(reimy)
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
// ��������å������¸
function plugin_tenki_cache_image_save($data, $filename, $dir) {
	$fp = fopen($filename, "wb");
	fwrite($fp, $data);
	fclose($fp);

	return $filename;
}
?>
