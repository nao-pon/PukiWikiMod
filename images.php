<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: images.php,v 1.2 2004/08/04 13:59:54 nao-pon Exp $
/////////////////////////////////////////////////

if (!isset($_GET['q'])) exit;

$file = get_image_filename($_GET['q']);

if (!$file) exit;

$img = getimagesize($file);

header('Content-Type: '.$img['mime']);

readfile($file);

exit;

function get_image_filename($q)
{
	error_reporting(0);
	$dir = "./cache/p/";
	$file = $dir.md5($q).".tig";
	if (file_exists($file))
		return $file;
	
	include_once("proxy.php");
	
	$result =  http_request("http://images-partners.google.com/images?q=".$q);
	if ($result['rc'] != 200) return "";
	$contents = $result['data'];
	
	// ƒLƒƒƒbƒVƒ…•Û‘¶
	if ($contents)
	{
		$fp = fopen($file, "wb");
		fwrite($fp, $contents);
		fclose($fp);
	}
	
	return $file;
	
}


?>