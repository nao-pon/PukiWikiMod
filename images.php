<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: images.php,v 1.4 2004/11/24 14:48:29 nao-pon Exp $
/////////////////////////////////////////////////

error_reporting(0);

if (!isset($_GET['q'])) exit;

$file = get_image_filename($_GET['q']);

if (!$file) exit;

$img = getimagesize($file);

header('Content-Type: '.$img['mime']);

readfile($file);

exit;

function get_image_filename($q)
{
	$dir = "./cache/p/";
	$file = $dir.md5($q).".tig";
	if (file_exists($file))
		return $file;
	
	$q = "http://images-partners.google.com/images?q=".str_replace("%","%25",$q);
	
	if ($url = @fopen($q, "rb"))
	{
		$contents = "";
		do {
			$data = fread($url, 8192);
			if (strlen($data) == 0) {
				break;
			}
			$contents .= $data;
		} while(true);
		
		fclose ($url);
	}
	else
	{
		include_once("proxy.php");
		
		$result =  http_request($q);
		if ($result['rc'] != 200) return "";
		$contents = $result['data'];
	}
	
	// キャッシュ保存
	if ($contents)
	{
		$fp = fopen($file, "wb");
		fwrite($fp, $contents);
		fclose($fp);
	}
	
	return $file;
	
}
?>