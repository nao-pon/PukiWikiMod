<?php
// ------------------------------------------------------------------------- //
//                XOOPS - PHP Content Management System                      //
//                       <http://www.xoops.org/>                             //
// ------------------------------------------------------------------------- //
// Based on:                                                                 //
// myPHPNUKE Web Portal System - http://myphpnuke.com/                       //
// PHP-NUKE Web Portal System - http://phpnuke.org/                          //
// Thatware - http://thatware.org/                                           //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
// ------------------------------------------------------------------------- //
// $Id: xoops_search.inc.php,v 1.12 2004/10/29 12:54:09 nao-pon Exp $

function wiki_search($queryarray, $andor, $limit, $offset, $userid){
	global $xoopsDB,$xoopsUser;
	
	include (XOOPS_ROOT_PATH."/modules/pukiwiki/cache/config.php");
	
	$X_uid = $X_admin = 0;
	if ( $xoopsUser ) {
		$xoopsModule = XoopsModule::getByDirname("pukiwiki");
		if ( $xoopsUser->isAdmin($xoopsModule->mid()) ) { 
			$X_admin = 1;
		}
		$X_uid = $xoopsUser->uid();
	}

	$nocheck=false;
	$nolisting=true;
	
	$where_base = "p.editedtime != 0 AND p.name NOT LIKE ':config/%' AND p.name != 'RenameLog'";
	
	if ($nocheck || $X_admin)
		$where = "";
	else
	{
		$where = "(p.vaids LIKE '%all%') OR (p.vgids LIKE '%&3&%')";
		if ($X_uid) $where .= " OR (p.uid = $X_uid) OR (p.vaids LIKE '%&{$X_uid}&%')";
		foreach(pukiwikimod_X_get_groups() as $gid)
		{
			$where .= " OR (p.vgids LIKE '%&{$gid}&%')";
		}
	}
	
	if ($where)
		$where_base = "($where_base) AND ($where)";
	
	$sql = "SELECT p.id,p.name,p.editedtime,p.vaids,p.vgids,p.uid,p.title,t.plain FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." p LEFT JOIN ".$xoopsDB->prefix("pukiwikimod_plain")." t ON t.pgid=p.id WHERE ($where_base) ";
	if ( $userid != 0 ) {
		$sql .= "AND (p.uid=".$userid.") ";
	}
	// because count() returns 1 even if a supplied variable
	// is not an array, we must check if $querryarray is really an array
	if ( is_array($queryarray) && $count = count($queryarray) ) {
		$sql .= "AND ((p.name LIKE '%$queryarray[0]%' OR t.plain LIKE '%$queryarray[0]%')";
		for($i=1;$i<$count;$i++){
			$sql .= " $andor ";
			$sql .= "(p.name LIKE '%$queryarray[$i]%' OR t.plain LIKE '%$queryarray[$i]%')";
		}
		$sql .= ") ";
	}
	$sql .= "ORDER BY p.editedtime DESC";
	$result = $xoopsDB->query($sql,$limit,$offset);
	$ret = array();
	$i = 0;
	if (!$queryarray) $queryarray = array();
	$word_url = rawurlencode(join(' ',$queryarray));
	while($myrow = $xoopsDB->fetchArray($result)){
		$title = ($myrow['title'])? " [".$myrow['title']."]" : "";
		$page_url = rawurlencode($myrow['name']);
		if ($use_static_url)
			$ret[$i]['link'] = $myrow['id'].".html";
		else
			$ret[$i]['link'] = "index.php?cmd=read&amp;page=$page_url&amp;word=$word_url";
		$ret[$i]['title'] = htmlspecialchars($myrow['name'].$title, ENT_QUOTES);
		//$ret[$i]['title'] = $myrow['name'].$title;
		$ret[$i]['image'] = "image/search.gif";
		$ret[$i]['time'] = $myrow['editedtime'];
		$ret[$i]['uid'] = $myrow['uid'];
		$ret[$i]['page'] = $myrow['name'];
		if (!empty($myrow['plain']) && function_exists('xoops_make_context'))
		{
			$ret[$i]['context'] = xoops_make_context($myrow['plain'],$queryarray);
		}
		$i++;
	}
	return $ret;
}

// ユーザーが所属するグループIDを得る
function pukiwikimod_X_get_groups(){
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $X_uid,$xoopsDB;
		$X_M = new XoopsMemberHandler($xoopsDB);
		return $X_M->getGroupsByUser($X_uid);
	} else {
		// XOOPS 1
		global $xoopsUser;
		return XoopsGroup::getByUser($xoopsUser);
	}
}
?>