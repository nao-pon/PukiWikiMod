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
// $Id: xoops_search.inc.php,v 1.3 2003/07/02 00:56:45 nao-pon Exp $
function wiki_search($queryarray, $andor, $limit, $offset, $userid){
	$files = get_existpages();
	$non_format = 1;
	$ret_count = 0;
	$ret = array();
	$arywords = $queryarray;
	$result_word= "";
	foreach($queryarray as $tmp){
		$result_word .= "$tmp ";
	}
	$type = $andor;
	$whatsnew = "RecentChanges";

	foreach($files as $name=>$ftime) {
		$cnt++;
		if($name == $whatsnew) continue;
		if($name == $vars["page"] && $non_format) continue;
		$lines = get_source($name);
		$lines = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$lines);

		$author_uid = 0;
		if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$lines[0],$arg)){
			$lines[0] = "";
			if (preg_match("/^\/\/ author:([0-9]+)\n/",$lines[1],$arg))
				$author_uid = $arg[1];
				$lines[1] = "";
		} else {
			if (preg_match("/^\/\/ author:([0-9]+)\n/",$lines[0],$arg))
				$author_uid = $arg[1];
				$lines[0] = "";
		}

		//nao-pon
		//$line = join("\n",$lines);
		$line = strtolower(join("\n",$lines));
		
		$hit = 0;
		//echo "$author_uid:$userid<br />";
		if($userid <= 0)
		{
			foreach($arywords as $word)
			{
				//nao-pon
				$word = strtolower($word);
				if($type=="AND")
				{
					if(strpos($line,$word) === FALSE)
					{
						$hit = 0;
						break;
					}
					else
					{
						$hit = 1;
					}
				}
				else if($type=="OR")
				{
					if(strpos($line,$word) !== FALSE)
						$hit = 1;
				}
			}
			if($hit==1 || strpos($name,$word)!==FALSE)
			{
				$name2 = strip_bracket($name);
				$page_url = rawurlencode($name2);
				$word_url = rawurlencode($word);
				$str = get_pg_passage($name);

				//$ret[$ret_count]['link'] = "index.php?$page_url";
				$ret[$ret_count]['link'] = "index.php?cmd=read&amp;page=$page_url&amp;word=$word_url";
				$ret[$ret_count]['title'] = htmlspecialchars($name2, ENT_QUOTES);
				$ret[$ret_count]['image'] = "image/search.gif";
				$ret[$ret_count]['time'] = "$str";
				$ret[$ret_count]['uid'] = $author_uid;
				$ret_count++;
			}
		}
		else
		{
			if($author_uid == $userid)
			{
				$name2 = htmlspecialchars(strip_bracket($name));
				$page_url = rawurlencode($name);
				$word_url = htmlspecialchars(rawurlencode($word));
				$str = get_pg_passage($name);

				$ret[$ret_count]['link'] = "index.php?$page_url";
				$ret[$ret_count]['title'] = htmlspecialchars($name2, ENT_QUOTES);
				$ret[$ret_count]['image'] = "image/search.gif";
				$ret[$ret_count]['time'] = "$str";
				$ret[$ret_count]['uid'] = $author_uid;
				$ret_count++;
			}
		}
	}
	if ($limit==0) {
		return array_slice($ret,$offset);
	} else {
		return array_slice($ret,$offset,$limit);
	}
}

function get_existpages()
{
	$aryret = array();
	if ($dir = @opendir(XOOPS_ROOT_PATH."/modules/pukiwiki/wiki/"))
	{
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			//array_push($aryret[$page],get_pg_passage($page,false));
			$aryret[$page]=get_pg_passage($page);
		}
		closedir($dir);
	}
	arsort($aryret);
	return $aryret;
}

function decode($key)
{
	$dekey = '';
	
	for($i=0;$i<strlen($key);$i+=2)
	{
		$ch = substr($key,$i,2);
		$dekey .= chr(intval("0x".$ch,16));
	}
	return $dekey;
}

function get_source($page)
{
  if(page_exists($page)) {
     return file(get_filename(encode($page)));
  }

  return array();
}

function page_exists($page)
{
	return file_exists(get_filename(encode($page)));
}

function get_filename($pagename)
{
	global $xoopsModule;
	return (XOOPS_ROOT_PATH."/modules/pukiwiki/wiki/".$pagename.".txt");
}

function encode($key)
{
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);
	
	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}

function strip_bracket($str)
{
	global $strip_link_wall;
	
	//if($strip_link_wall)
	//{
	  if(preg_match("/^\[\[(.*)\]\]$/",$str,$match)) {
	    $str = $match[1];
	  }
	//}
	return $str;
}

function get_pg_passage($page,$sw=true)
{
	global $_pg_passage,$show_passage;
//	global $xoopsUser;

//	if(!$show_passage) return "";

	if(isset($_pg_passage[$page]))
	{
		if($sw)
			return $_pg_passage[$page]["str"];
		else
			return $_pg_passage[$page]["label"];
	}
	if($pgdt = @filemtime(get_filename(encode($page))))
	{
//		$pgdt = UTIME - $pgdt;
//		$pgdt = time() - $pgdt;
//echo "page => ".$page."<br>";
//echo "pgdt => ".$pgdt."<br>";
//return ceil($pgdt / 60 / 60 / 24);
//echo "==>".ceil($xoopsUser->vars['timezone_offset']['value'])*3600;
//return ($pgdt + (ceil($xoopsUser->vars['timezone_offset']['value'])*3600));
return $pgdt;
		if(ceil($pgdt / 60) < 60)
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60)."m)";
		else if(ceil($pgdt / 60 / 60) < 24)
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60 / 60)."h)";
		else
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60 / 60 / 24)."d)";
		
		$_pg_passage[$page]["str"] = "<small>".$_pg_passage[$page]["label"]."</small>";
	}
	else
	{
		$_pg_passage[$page]["label"] = "";
		$_pg_passage[$page]["str"] = "";
	}

	if($sw)
		return $_pg_passage[$page]["str"];
	else
		return $_pg_passage[$page]["label"];
}

?>
