<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: images.php,v 1.3 2004/09/01 12:12:09 nao-pon Exp $
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
	
	//include_once("proxy.php");
	
	//$result =  http_request("http://images-partners.google.com/images?q=".$q);
	//if ($result['rc'] != 200) return "";
	//$contents = $result['data'];
	//echo $q;
	$q = str_replace("%","%25",$q);
	$url = fopen("http://images-partners.google.com/images?q=".$q, "rb");
	$contents = "";
	do {
		$data = fread($url, 8192);
		if (strlen($data) == 0) {
			break;
		}
		$contents .= $data;
	} while(true);
	
	fclose ($rul);
	
	// �L���b�V���ۑ�
	if ($contents)
	{
		$fp = fopen($file, "wb");
		fwrite($fp, $contents);
		fclose($fp);
	}
	
	return $file;
	
}


?>