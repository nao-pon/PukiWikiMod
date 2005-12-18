<?php
// $Id: admin_header.php,v 1.4 2005/12/18 14:10:47 nao-pon Exp $

include("../../../mainfile.php");
include_once(XOOPS_ROOT_PATH."/class/xoopsmodule.php");
include(XOOPS_ROOT_PATH."/include/cp_functions.php");
if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname("pukiwiki");
	if ( !$xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		redirect_header(XOOPS_URL."/",3,_NOPERM);
		exit();
	}
} else {
	redirect_header(XOOPS_URL."/",3,_NOPERM);
	exit();
}

if ( file_exists("../language/".$xoopsConfig['language']."/admin.php") ) {
	include("../language/".$xoopsConfig['language']."/admin.php");
} else {
	include("../language/english/admin.php");
}
if ( file_exists("../../system/language/".$xoopsConfig['language']."/admin.php") ) {
	include("../../system/language/".$xoopsConfig['language']."/admin.php");
} else {
	include("../../system/language/english/admin.php");
}

?>