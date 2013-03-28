<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: images.php,v 1.9 2006/04/25 11:38:38 nao-pon Exp $
/////////////////////////////////////////////////

error_reporting(0);

$exp = "";
if (isset($_GET['q']))
{
	$type = "google";
	$q = $_GET['q'];
	if (preg_match("/^.+(\.[^.\/]+)$/",$q,$arg))
	{
		$exp = $arg[1];
	}
}
else
{
	if (!isset($_GET['SV'])) exit;
	if (!isset($_GET['THN_URL'])) exit;
	$type = "goo";
	$q = "SV=".$_GET['SV']."&THN_URL=".$_GET['THN_URL'];
	$exp = (preg_match("/(gif|jpe?g|png|bmp)/i",$_GET['exp']))? ".".$_GET['exp'] : "";
}

$file = get_image_filename($q,$type,$exp);

if (!$file) exit;

header("Location: ".$file);
exit;

function get_image_filename($q,$type,$exp)
{
	if ($type == "google")
	{
		$q = str_replace(array("%","+"),array("%25","%2B"),$q);
		$url = "http://images-partners.google.com/images?q=";
	}
	else
	{
		$url = "http://thumb1.goo.ne.jp/img/relay.php?";
	}
	$dir = "./cache/p/";
	$file = $dir."tig_".md5($q).$exp;
	
	if (file_exists($file))
	{
		return $file;
	}
	$q = $url.$q;
	
	include_once("include/hyp_common/hyp_common_func.php");
	include_once("proxy.php");
	
	$result =  pkwk_http_request($q);
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
	
	/*
	if (!$exp)
	{
		$exps = array("",".gif",".jpg",".png",".swf",".psd",".bmp");
		$info = getimagesize($file);
		$exp = $exps[$info[2]];
		rename($file,$file.$exp);
		$file = $file.$exp;
	}
	*/
	return $file;
}
?>