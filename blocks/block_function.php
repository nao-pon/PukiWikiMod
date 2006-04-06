<?php
//
// Created on 2006/04/06 by nao-pon http://hypweb.net/
// $Id: block_function.php,v 1.1 2006/04/06 13:32:15 nao-pon Exp $
//

if (! defined('PWM_BLOCK_FUNC_INCLUDED'))
{

define('PWM_BLOCK_FUNC_INCLUDED', true);

//ページ名からリンクを作成
function xb_make_link($page,$alias="#/#",$dir_name)
{
	static $linktag = array();
	static $_use_static_url = array();

	if (!isset($_use_static_url[$dir_name]))
	{
		include (XOOPS_ROOT_PATH."/modules/".$dir_name."/cache/config.php");
		$_use_static_url[$dir_name] = (string)$use_static_url;
	}

	$pukiwiki_path = XOOPS_URL."/modules/".$dir_name."/index.php";

	$_name = $name = $page;
	$page = xb_add_bracket($page);
	
	if (isset($linktag[$dir_name][$page.$alias])) return $linktag[$dir_name][$page.$alias];
	
	$url = rawurlencode($name);
	$sep = array();
	if (preg_match("/^#(.*)#$/",$alias,$sep))
	{
		// パン屑リスト出力
		$sep = htmlspecialchars($sep[1]);
		$prefix = xb_strip_bracket($page);
		$page_names = array();
		$page_names = explode("/",$prefix);
		$access_name = "";
		$i = 0;
		foreach ($page_names as $page_name){
			$access_name .= $page_name."/";
			$name = substr($access_name,0,strlen($access_name)-1);
			if (preg_match("/^[0-9\-]+$/",$page_name))
			{
				$heading = xb_get_heading($page);
				if ($heading) $page_name = $heading;
				// 無限ループ防止　姑息だけど
				$page_name = preg_replace("/^(#.*#)$/"," $1",$page_name);
			}
			$link = xb_make_link($name,$page_name,$dir_name);
			if ($i)
				$retval .= $sep.$link;
			else
				$retval = $link;
			$i++;
		}
	}
	else
	{
		if ($alias) $_name = $alias;
		//ページ名が「数字と-」だけの場合は、*(**)行を取得してみる
		$f_name = array();
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$name,$f_name)){
			$heading = xb_get_heading($page,$dir_name);
			if ($heading) $_name = $heading;
		}
		$pgp = xb_get_pg_passage($page,FALSE,$dir_name);
		if ($pgp)
			$retval = "<a href=\"".xb_get_url_by_name($page,$_use_static_url[$dir_name],$dir_name)."\" title=\"".$name.$pgp."\">$_name</a>";
		else
			$retval = "$_name";
	}
	
	$linktag[$dir_name][$page.$alias] = $retval;
	return $retval;
}

//ページ名から最初の見出しを得る
function xb_get_heading($page,$dir_name)
{
	global $xoopsDB;
	$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);
	
	$page = addslashes(xb_strip_bracket($page));
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod{$dir_num}_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return "";
	$ret = mysql_fetch_row($res);
	return htmlspecialchars($ret[12]);
}

// ページ名のエンコード
function xb_encode($key)
{
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);

	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}

// [[ ]] を取り除く
function xb_strip_bracket($str)
{
	$match = array();
	if(preg_match("/^\[\[(.*)\]\]$/",$str,$match)) {
		$str = $match[1];
	}
	return $str;
}

// [[ ]] を付加する
function xb_add_bracket($str){
	$WikiName = '(?<!(!|\w))[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	if (!preg_match("/^".$WikiName."$/",$str)){
		if (!preg_match("/\[\[.*\]\]/",$str)) $str = "[[".$str."]]";
	}
	return $str;
}

// ファイル名を得る(エンコードされている必要有り)
function xb_get_filename($pagename,$dir_name)
{
	return XOOPS_ROOT_PATH."/modules/".$dir_name."/wiki/".$pagename.".txt";
}

// ページが存在するか？
function xb_page_exists($page,$dir_name)
{
	return file_exists(xb_get_filename(xb_encode($page),$dir_name));
}

// ソースを取得
function xb_get_source($page,$dir_name)
{	
	if(xb_page_exists($page,$dir_name)) {
		$ret = file(xb_get_filename(xb_encode($page),$dir_name));
		$ret = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$ret);
		return $ret;
  }
  return array();
}

// 指定されたページの経過時刻
function xb_get_pg_passage($page,$sw=true,$dir_name)
{
	if($pgdt = @filemtime(xb_get_filename(xb_encode($page),$dir_name)))
	{
		$pgdt = time() - $pgdt;
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

function xb_X_get_groups($X_uid){
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $xoopsDB;
		$X_M = new XoopsMemberHandler($xoopsDB);
		return $X_M->getGroupsByUser($X_uid);
	} else {
		// XOOPS 1
		global $xoopsUser;
		return XoopsGroup::getByUser($xoopsUser);
	}
}


//閲覧権限を得る
function xb_get_readable(&$auth, $dir_name)
{
	static $_X_uid,$_X_admin,$_X_gids;
	
	if (!isset($_X_admin))
	{
		global $xoopsUser;
		if ( $xoopsUser )
		{
			$xoopsModule = XoopsModule::getByDirname($dir_name);
			if ( $xoopsUser->isAdmin($xoopsModule->mid()) )
				$_X_admin = 1;
			else
				$_X_admin = 0;
		
			$_X_uid = $xoopsUser->uid();
		}
		else
		{
			$_X_admin = $_X_uid = 0;
		}
		// ユーザーが所属するグループIDを得る
		if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
			// XOOPS 2
			global $xoopsDB;
			$X_M = new XoopsMemberHandler($xoopsDB);
			$_X_gids = $X_M->getGroupsByUser($_X_uid);
		} else {
			// XOOPS 1
			$_X_gids = XoopsGroup::getByUser($xoopsUser);
		}
	}
	
	if ($_X_admin) return true;


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
//ページ名からページIDを求める
function xb_get_pgid_by_name($page,$dir_name)
{
	global $xoopsDB;
	static $page_id = array();
	$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);
	$page = addslashes(xb_strip_bracket($page));
	if (!empty($page_id[$dir_name][$page])) return $page_id[$dir_name][$page];
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod{$dir_num}_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return 0;
	$ret = mysql_fetch_row($res);
	$page_id[$dir_name][$page] = $ret[0];
	return $ret[0];
}

//ページ名からURLを求める
function xb_get_url_by_name($name="",$use_static_url,$dir_name)
{
	static $ret = array();
	static $dir = array();
	
	if(isset($ret[$dir_name][$name])) return $ret[$dir_name][$name];
	
	if (!isset($dir[$dir_name]))
	{
		if (file_exists(XOOPS_ROOT_PATH."/modules/".$dir_name."/short_url.php"))
			include(XOOPS_ROOT_PATH."/modules/".$dir_name."/short_url.php");
		
		$dir_num = preg_replace("/^(\D+)(\d*)$/","$2",$dir_name);
		if (!empty($GLOBALS['PWM_SHORTURL'.$dir_num]))
		{
			$dir[$dir_name] = $GLOBALS['PWM_SHORTURL'.$dir_num];
		}
		else
		{
			$dir[$dir_name] = "modules/".$dir_name;
		}
	}
	
	
	if (!$name || !xb_page_exists($name,$dir_name)) return $ret[$dir_name][$name] = XOOPS_URL."/modules/".$dir_name."/";
	
	if ($use_static_url == 3)
		return $ret[$dir_name][$name] = XOOPS_URL."/pukiwiki+._".xb_get_pgid_by_name($name).".htm";
	else if ($use_static_url == 2)
		return $ret[$dir_name][$name] = XOOPS_URL."/pukiwiki+index.pgid+_".xb_get_pgid_by_name($name,$dir_name).".htm";
	else if ($use_static_url)
		return $ret[$dir_name][$name] = XOOPS_URL."/".$dir[$dir_name]."/".xb_get_pgid_by_name($name,$dir_name).".html";
	else
		return $ret[$dir_name][$name] = XOOPS_URL."/".$dir[$dir_name]."/?".rawurlencode(xb_strip_bracket($name));
}

// ページ専用CSSタグを得る
function xb_get_page_css_tag($page,$wiki_url,$dir_name)
{
	$page = xb_strip_bracket($page);
	$ret = '';
	$_page = '';
	$dir = XOOPS_ROOT_PATH."/modules/".$dir_name."/cache/";
	foreach(explode('/',$page) as $val)
	{
		$_page = ($_page)? $_page."/".$val : $val;
		$_pagecss_file = xb_encode($_page).".css";
		if(file_exists($dir.$_pagecss_file))
		{
			$ret .= '<link rel="stylesheet" href="'.$wiki_url.'cache/'.$_pagecss_file.'" type="text/css" media="screen" charset="shift_jis">'."\n";
		}
	}
	return $ret;
}

// チケット取得
function xb_get_token_html()
{
	if (!class_exists('XoopsTokenHandler')) {return "";}
	static $handler;
	if (!is_object($handler))
	{
		$handler = new XoopsMultiTokenHandler();
	}
	$ticket = &$handler->create('pukiwikimod',0);
	return $ticket->getHtml();
}

}
?>