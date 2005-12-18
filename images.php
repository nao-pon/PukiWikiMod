<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: images.php,v 1.7 2005/12/18 14:10:47 nao-pon Exp $
/////////////////////////////////////////////////

error_reporting(0);

if (!isset($_GET['q'])) exit;

$file = get_image_filename($_GET['q']);

if (!$file) exit;

header("Location: ".$file);
exit;

function get_image_filename($q)
{
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
	
	include_once("include/hyp_common_func.php");
	include_once("proxy.php");
	
	$result =  http_request($q);
	if ($result['rc'] != 200)
	{
		return "";
	}
	$contents = $result['data'];
	
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