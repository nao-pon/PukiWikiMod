<?php
error_reporting(0);
$url = (empty($_GET['q']))? "" : $_GET['q'];

if (!$url) exit("0");

include ("../../include/hyp_common/hyp_common_func.php");

$d = new Hyp_HTTP_Request();

$d->url = $url;
$d->method = 'HEAD';
$d->ua = 'Mozilla/4.0';
$d->read_timeout = 3;
$d->get();

exit("$d->rc");

?>