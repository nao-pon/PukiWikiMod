<?php
// $Id: index.php,v 1.1 2003/06/28 06:01:52 nao-pon Exp $
 
include("header.php");
$xoopsOption['show_rblock'] =0;
include(XOOPS_ROOT_PATH."/header.php");
OpenTable();
include_once(XOOPS_ROOT_PATH."/class/xoopsformloader.php");
include("pukiwiki.php");
CloseTable();
include(XOOPS_ROOT_PATH."/footer.php");
?>
