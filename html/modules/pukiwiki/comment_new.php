<?php
// $Id: comment_new.php,v 1.2 2006/04/06 13:32:15 nao-pon Exp $
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

include 'initialize.php';

include '../../mainfile.php';
$com_itemid = isset($_GET['com_itemid']) ? intval($_GET['com_itemid']) : 0;
if ($com_itemid > 0)
{
	$sql = "SELECT * FROM " . $xoopsDB->prefix('pukiwikimod'.PUKIWIKI_DIR_NUM.'_pginfo') . " WHERE id=" . $com_itemid . "";
	$result = $xoopsDB->query($sql);
	$row = $xoopsDB->fetchArray($result);
	$com_replytitle = preg_replace("#/[0-9-]+$#","/".$row['title'],$row['name']);
	include XOOPS_ROOT_PATH.'/include/comment_new.php';
}
?>