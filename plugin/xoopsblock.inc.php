<?php
// $Id: xoopsblock.inc.php,v 1.3 2004/01/12 13:13:47 nao-pon Exp $

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

	list($tgt,$option1,$option2) = func_get_args();

	$align = "left";
	$around = false;
	if (preg_match("/^(left|center|right)$/i",$option2,$arg))
		$align = $arg[1];
	if (preg_match("/^(left|center|right)$/i",$option1,$arg))
		$align = $arg[1];
	if (preg_match("/^(around|float)$/i",$option2))
		$around = true;
	if (preg_match("/^(around|float)$/i",$option1))
		$around = true;
		
	$style = " style='float:{$align};'";
	$clear = ($around)? "" : "<div style='clear:both;'></div>";

	global $xoopsUser;
	$xoopsblock = new XoopsBlock();
	$xoopsgroup = new XoopsGroup();
	$arr = array();
	$side = null;
	
	if ( $xoopsUser ) {
		//$arr = $xoopsblock->getAllBlocksByGroup($xoopsUser->groups(), true, $side, XOOPS_BLOCK_VISIBLE);
		$arr = $xoopsblock->getAllBlocksByGroup($xoopsUser->groups());
	} else {
		if (method_exists($xoopsgroup,"getByType")){
			//XOOPS 1.3
			//$arr = $xoopsblock->getAllBlocksByGroup($xoopsgroup->getByType("Anonymous"), true, $side, XOOPS_BLOCK_VISIBLE);
			$arr = $xoopsblock->getAllBlocksByGroup($xoopsgroup->getByType("Anonymous"));
		} else {
			//XOOPS 2
			//$arr = $xoopsblock->getAllBlocksByGroup(plugin_xoopsblock_getByType("Anonymous"), true, $side, XOOPS_BLOCK_VISIBLE);
			$arr = $xoopsblock->getAllBlocksByGroup(plugin_xoopsblock_getByType("Anonymous"));
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
	unset($xoopsblock,$xoopsgroup);
	return "<div{$style}>{$ret}</div>{$clear}";
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