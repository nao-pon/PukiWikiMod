<?php
//
// Created on 2006/04/06 by nao-pon http://hypweb.net/
// $Id: initialize.php,v 1.2 2006/08/04 13:06:04 nao-pon Exp $
//
 
// PukiWikiMod ディレクトリ名
define("PUKIWIKI_DIR_NAME", basename(dirname( __FILE__ )));
define("PUKIWIKI_DIR_NUM", preg_replace("/^(\D+)(\d*)$/","$2",PUKIWIKI_DIR_NAME));

if (file_exists("short_url.php"))
{
	include("short_url.php");
	$_SERVER['_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	$_SERVER['REQUEST_URI'] = str_replace("/".$GLOBALS['PWM_SHORTURL'.PUKIWIKI_DIR_NUM],"/modules/".PUKIWIKI_DIR_NAME,$_SERVER['REQUEST_URI']);
}

?>
