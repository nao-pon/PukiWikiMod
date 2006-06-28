<?php
error_reporting(0);
$url = (empty($_GET['q']))? "" : str_replace("\0","",$_GET['q']);

if (!preg_match("/^http/i",$url)) exit("0");
if (!$url) exit("0");

include ("../../include/hyp_common/hyp_common_func.php");

$d = new Hyp_HTTP_Request();

$d->url = $url;
$d->method = 'HEAD';
$d->ua = 'Mozilla/4.0';
$d->read_timeout = 3;
$d->get();

if (preg_match("/^Content-Type:(.+)$/im",$d->header,$match))
{
	$type = trim($match[1]);
	if (!preg_match("/^image/i",$type))
	{
		$d->rc = 404;
	}
}

exit("$d->rc");

?>