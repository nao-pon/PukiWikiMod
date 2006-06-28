<?php
//
// Created on 2006/06/25 by nao-pon http://hypweb.net/
// $Id: checkurl.php,v 1.3 2006/06/28 23:35:16 nao-pon Exp $
//

error_reporting(0);
$url = (empty($_GET['q']))? "" : str_replace("\0","",$_GET['q']);

if (!$url || !preg_match("/^https?:\/\/.+/i",$url)) out(0);

include ("../../include/hyp_common/hyp_common_func.php");

$d = new Hyp_HTTP_Request();

$d->url = $url;
$d->method = 'HEAD';
$d->ua = 'Mozilla/4.0';
if (!empty($_SERVER['HTTP_REFERER']))
{
	$d->headers = "Referer: ".$_SERVER['HTTP_REFERER']."\r\n";
}
$d->read_timeout = 3;
$d->get();

if (preg_match("/^\s*Content\-Type\:(.+)$/im",$d->header,$match))
{
	$type = trim($match[1]);
	if (!preg_match("/^image/i",$type))
	{
		$d->rc = 404;
	}
}

out($d->rc);
exit;

function out($str)
{
	(string)$str;
	header ("Content-Type: text/html; charset=iso-8859-1");
	header ("Content-Length: ".strlen($str));
	echo $str;
	exit;
}

?>