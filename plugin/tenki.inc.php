<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: tenki.inc.php,v 1.9 2005/02/23 00:16:41 nao-pon Exp $
//
//	 GNU/GPL �ˤ������ä����ۤ��롣
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
		if (preg_match("/w:(\d+)(px)?/i",$arg,$match))
			$v_width = $match[1];
		else if (strtolower($arg) == "pic")
			$pic = TRUE;
		else if ($arg == "now?")
			continue;
		else
			$cid = $arg;
	}

	if ($pic) {
		//$url = "http://weather.is.kochi-u.ac.jp/FE/00Latest.jpg";
		$url = "http://www.jwa.or.jp/sat/images/sat-japan.jpg";
		$alt = "�����ն��ֳ�����";
		$picid = "pic";
	} else {
		$url = "http://www.jma.go.jp/JMA_HP/jp/g3/latest/SPAS-GG.gif";
		$alt = "����ģȯɽŷ����";
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
		$size = @getimagesize($url);
		if ($size[0] < 1) return false;
	}
	
	$v_width = min(640,$v_width);
	$v_height = floor(($v_width * $size[1])/ $size[0]);
	$body = "<a href=\"$url\"><img src=\"$url\" width=\"$v_width\" height=\"$v_height\" alt=\"$alt\" title=\"$alt\" /></a>\n";
	return $body;
}
// ��������å��夬���뤫Ĵ�٤�
function plugin_tenki_cache_image_fetch($target, $dir, $id) {
	$filename = $dir.$id."_tenki.gif";
	if (!is_readable($filename)) {
		$file = fopen($target, "rb"); // ���֤� size ������ꤳ���餬����Ū��������®��
		if (! $file) {
			fclose($file);
			$url = NOIMAGE;
		} else {
			// ��⡼�ȥե�����Υѥ��å�ͭ�����к�
			// http://search.net-newbie.com/php/function.fread.html
			$contents = "";
			do {
				$data = fread($file, 8192);
				if (strlen($data) == 0) {
					break;
				}
				$contents .= $data;
			} while(true);
			
			fclose ($file);
			
			$data = $contents;
			unset ($contents);
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