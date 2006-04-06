<?php
//
// Created on 2006/04/06 by nao-pon http://hypweb.net/
// $Id: initialize.php,v 1.1 2006/04/06 13:32:15 nao-pon Exp $
//
 
// PukiWikiMod ディレクトリ名
define("PUKIWIKI_DIR_NAME", basename(dirname( __FILE__ )));
define("PUKIWIKI_DIR_NUM", preg_replace("/^(\D+)(\d*)$/","$2",PUKIWIKI_DIR_NAME));

if (file_exists("short_url.php"))
{
	include("short_url.php");
	
	$_SERVER['REQUEST_URI'] = str_replace("/".$GLOBALS['PWM_SHORTURL'.PUKIWIKI_DIR_NUM],"/modules/".PUKIWIKI_DIR_NAME,$_SERVER['REQUEST_URI']);
}

?>
