<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: images.php,v 1.6 2005/06/23 08:15:32 nao-pon Exp $
/////////////////////////////////////////////////
//exit();
error_reporting(0);

if (!isset($_GET['q'])) exit;

$file = get_image_filename($_GET['q']);

if (!$file) exit;

header("Location: ".$file);
exit;

/*
$img = getimagesize($file);

header('Content-Type: '.$img['mime']);

readfile($file);

exit;
*/

function get_image_filename($q)
{
/*
	$dir = "./cache/p/";
	$q = str_replace(array("%","+"),array("%25","%2B"),$q);
	$file = $dir.md5($q).".tig";
*/
	if (preg_match("/^.+(\.[^.\/]+)$/",$q,$arg))
	{
		$exp = $arg[1];
	}
	$dir = "./cache/p/";
	$q = str_replace(array("%","+"),array("%25","%2B"),$q);
	$file = $dir."tig_".md5($q).$exp;
	
	if (file_exists($file))
	{
		return $file;
	}
	$q = "http://images-partners.google.com/images?q=".$q;
	
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