<?php
// $Id: xoopsblock.inc.php,v 1.1 2003/07/30 14:57:28 nao-pon Exp $

/*
 * countdown.inc.php
 * License: GPL
 * Author: nao-pon http://hypweb.net
 * XOOPS Module Block Plugin
 *
 * XOOPSのブロックを表示するプラグイン
 */

function plugin_xoopsblock_init() {

	include_once(XOOPS_ROOT_PATH."/class/xoopsmodule.php");
	include_once(XOOPS_ROOT_PATH."/class/xoopsblock.php");

}

function plugin_xoopsblock_convert() {

	list($tgt) = func_get_args();

	global $xoopsUser;
	$xoopsblock = new XoopsBlock();
	$arr = array();
	$side = null;
	
	if ( $xoopsUser ) {
		$arr = $xoopsblock->getAllBlocksByGroup($xoopsUser->groups(), true, $side, XOOPS_BLOCK_VISIBLE);
	} else {
		if (function_exists('XoopsGroup::getByType')){
			//XOOPS 1.3
			$arr = $xoopsblock->getAllBlocksByGroup(XoopsGroup::getByType("Anonymous"), true, $side, XOOPS_BLOCK_VISIBLE);
		} else {
			//XOOPS 2
			$arr = $xoopsblock->getAllBlocksByGroup(plugin_xoopsblock_getByType("Anonymous"), true, $side, XOOPS_BLOCK_VISIBLE);
		}
	}
	
	$ret = "";
	
	foreach ( $arr as $myblock ) {
		$block = array();
		$name = ($myblock->getVar("type") != "C") ? $myblock->getVar("name") : $myblock->getVar("title");

		if ($tgt == "?"){
			$ret .= "<li>".$name."</li>";
		} else {
			if ($tgt == $name){
				$block = $myblock->buildBlock();
				$ret = $block['content'];
				unset($myblock);
				unset($block);
				break;
			}
		}
		unset($myblock);
		unset($block);
	}
	if ($tgt == "?") $ret = "<ul>$ret</ul>";
	return "<div>".$ret."</div>";
}

function plugin_xoopsblock_getByType($type=""){
	// For XOOPS 2
	global $xoopsDB;
	$ret = array();
	$where_query = "";
	if ( !empty($type) ) {
		$where_query = " WHERE group_type='".$type."'";
	}
	$sql = "SELECT groupid FROM ".$xoopsDB->prefix("groups")."".$where_query;
	$result = $xoopsDB->query($sql);
	while ( $myrow = $xoopsDB->fetchArray($result) ) {
		$ret[] = $myrow['groupid'];
	}
	return $ret;
}

?>