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
// $Id: xoops_search.inc.php,v 1.5 2003/10/13 12:23:28 nao-pon Exp $

$_cache_file = XOOPS_ROOT_PATH."/modules/pukiwiki/cache/config.php";
if(file_exists($_cache_file) && is_readable($_cache_file)){
	require($_cache_file);
}
// pcomment のコメントページ名
define ("PCMT_PAGE",$pcmt_page_name);
// 閲覧制限を使用するか
define("PUKIWIKI_READ_AUTH",$read_auth);

function wiki_search($queryarray, $andor, $limit, $offset, $userid){
	$files = get_existpages();
	if (!isset($vars["page"])) $vars["page"]="";
	$non_format = 1;
	$ret_count = 0;
	$ret = array();
	$arywords = $queryarray;
	$result_word= "";
	if (is_array($queryarray)){
		foreach($queryarray as $tmp){
			$result_word .= "$tmp ";
		}
	} else {
		$result_word = $queryarray;
	}
	$type = $andor;
	$whatsnew = "RecentChanges";
	$cnt = 0;
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
				//$word_url = htmlspecialchars(rawurlencode($word));
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
			if (check_readable($page,false,false)) $aryret[$page]=get_pg_passage($page);
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

function get_source($page,$row=0)
{	
	$page = add_bracket($page);
	if(page_exists($page)) {
		if ($row){
			$ret = array();
			$f_name = get_filename(encode($page));
			$fp = fopen($f_name,"r");
			if (!$fp) return file(get_filename(encode($page)));
			while (!feof($fp)) {
				$ret[] = fgets($fp, 4096);
				$row--;
				if ($row < 1) break;
			}
			fclose($fp);
		} else {
			$ret = file(get_filename(encode($page)));
		}
		$ret = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$ret);
		return $ret;
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

function add_bracket($str){
	$WikiName = '(?<!(!|\w))[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	if (!preg_match("/^".$WikiName."$/",$str)){
		if (!preg_match("/\[\[.*\]\]/",$str)) $str = "[[".$str."]]";
	}
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

//ページの閲覧権限を得る
function get_pg_allow_viewer($page, $uppage=true, $clr=false){
	static $cache_page_info = array();
	
	// キャッシュクリアー
	if ($clr)
	{
		$cache_page_info = array();
		return;
	}
	
	// pcoment のコメントページ調整
	if (preg_match("/^\[\[(.*\/)%s\]\]/",PCMT_PAGE,$arg))
	{
		$page = str_replace($arg[1],"",$page);
	}
	
	// キャッシュがあればキャッシュを返す
	if (isset($cache_page_info[$page])) 
		return $cache_page_info[$page];

	$lines = get_source($page,2);

	$allows['owner'] = "";
	$allows['group'] = "3,";
	$allows['user'] = "all";
	foreach($lines as $line)
	{
		if (preg_match("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/",$line,$arg)){
			if (!$arg[2]) $arg[2]=0;
			if (!$arg[3]) $arg[3]=0;
			$allows['owner'] = $arg[1];
			if ($arg[2] !== "all") $allows['user'] = $arg[2].",";
			$allows['group'] = $arg[3].",";
			break;
		}
	}
	if (!$allows['owner'] && $uppage)
	//このページに設定がないので上位ページをみる
	{
		// 上位ページ名を得る
		if (preg_match("/^(.*)\/[^\/]*$/",$page,$arg))
			$uppage_name = $arg[1];
		else
			$uppage_name = "";

		// 上位ページがあればその権限を得る(再帰処理)キャッシュチェックする
		if ($uppage_name)
		{
			if (isset($cache_page_info[$uppage_name]))
				$allows = $cache_page_info[$uppage_name];
			else
				$allows = get_pg_allow_viewer($uppage_name,true);
		}
	}
	// キャッシュを保存
	$cache_page_info[$page] = $allows;
	return $allows;
	
}

//閲覧権限を得る
function get_readable(&$auth)
{
	static $_X_uid, $_X_gids;
	if (!isset($_X_uid))
	{
		global $xoopsUser;
		if ( $xoopsUser ) {
			$X_uid = $xoopsUser->uid();
		} else {
			$X_uid = 0;
		}
		$_X_uid = $X_uid;
	}
	if (!isset($_X_gids)) $_X_gids = X_get_groups();

	$ret = false;
	
	$aids = explode(",",$auth['user']);
	$gids = explode(",",$auth['group']);
	
	// 閲覧制限されていない
	if ($auth['owner'] === "" || $auth['user'] == "all") $ret = true;
	
	// 非ログインユーザー
	elseif (!$_X_uid) $ret = (in_array("3",$gids))? true : false;
	
	//ログインユーザーは権限チェック
	
	// 自分で制限したページ
	elseif ($auth['owner'] == $_X_uid) $ret = true;
	
	// ユーザー権限があるか
	elseif (in_array($_X_uid,$aids)) $ret = true;
	
	else
	{
		// グループ権限があるか？
		$gid_match = false;
		foreach ($_X_gids as $gid)
		{
			if (in_array($gid,$gids))
			{
				$gid_match = true;
				break;
			}
		}
		if ($gid_match) $ret = true;
	}
	return $ret;
}

//閲覧することができるかチェックする。
function check_readable($page, $auth_flag=true, $exit_flag=true){

	static $_X_admin,$_read_auth;
	
	if (!isset($_X_admin))
	{
		global $xoopsUser;
		$X_admin = 0;
		if ( $xoopsUser ) {
			$xoopsModule = XoopsModule::getByDirname("pukiwiki");
			if ( $xoopsUser->isAdmin($xoopsModule->mid()) ) { 
				$X_admin = 1;
			}
		}
		$_X_admin = $X_admin;
	}
	if (!isset($_read_auth)) $_read_auth = PUKIWIKI_READ_AUTH;
	
	if (!$_read_auth) return true;
	
	$ret = false;
	
	// 管理者はすべてOK
	if ($_X_admin) $ret = true;
	
	else
	{
		$auth = get_pg_allow_viewer(strip_bracket($page),true);
		$ret = get_readable($auth);
	}
	
	return $ret;

}
// ユーザーが所属するグループIDを得る
function X_get_groups(){
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
