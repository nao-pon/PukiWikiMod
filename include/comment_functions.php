<?php
// $Id: comment_functions.php,v 1.6 2006/08/14 06:09:49 nao-pon Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

// comment callback functions

function pukiwiki_com_update($id, $total_num)
{
	global $xoopsDB,$xoopsUser;
	// PukiWikiMod ディレクトリ名
	$dir_name = basename(dirname(dirname( __FILE__ )));
	$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);
	
	$X_uid = (is_object($xoopsUser))? $xoopsUser->uid() : 0;
	$time = time();
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".$dir_num."_pginfo")." WHERE id=$id LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return false;
	$arg = mysql_fetch_row($res);
	$name = $arg[1];
	
	$value = "editedtime=$time";
	$value .= ",lastediter=$X_uid";
	$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".$dir_num."_pginfo")." SET $value WHERE id = '$id';";
	if ($result=$xoopsDB->query($query))
	{
		pukiwiki_com_touch($id,$time,$dir_num);
		return true;
	}
	return false;
	
}

function pukiwiki_com_approve(&$comment)
{
	// notification mail here
}

function pukiwiki_com_touch($id,$time,$dir_num)
{
	global $xoopsDB,$WikiName;

	$dir = dirname(dirname(__FILE__));
	
	if (empty($WikiName)) $WikiName = '(?:[A-Z][a-z]+){2,}(?![A-Za-z0-9_])';
	
	include($dir."/cache/config.php");
	include_once($dir."/func.php");
	include_once($dir."/file.php");
	// キャッシュディレクトリ
	if (!defined("CACHE_DIR")) define("CACHE_DIR",$dir."/cache/");
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".$dir_num."_pginfo")." WHERE id=$id LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return false;
	$arg = mysql_fetch_row($res);
	$name = $arg[1];
	
	$fname = $pukiwiki_dirs['wiki'].encode(add_bracket($name)).".txt";
	
	pkwk_touch_file($fname,$time);
	
	return;
}
?>