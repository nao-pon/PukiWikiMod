<?php
// $Id: pukiwiki_new.php,v 1.10 2004/01/15 13:11:44 nao-pon Exp $
function b_pukiwiki_new_show($option) {

	//表示する件数
	$show_num = 10;

	global $xoopsUser,$xoopsDB;
	if ( $xoopsUser )
	{
		$xoopsModule = XoopsModule::getByDirname("pukiwiki");
		if ( $xoopsUser->isAdmin($xoopsModule->mid()) )
			$X_admin = 1;
		else
			$X_admin = 0;
	
		$X_uid = $xoopsUser->uid();
	}
	else
	{
		$X_admin = $X_uid = 0;
	}

	if ($X_admin)
		$where = "";
	else
	{
		$where = "";
		if ($X_uid) $where .= "  (uid = $X_uid) OR";
		$where .= " (vaids LIKE '%all%') OR (vgids LIKE '%&3&%')";
		if ($X_uid) $where .= " OR (vaids LIKE '%&{$X_uid}&%')";
		foreach(xb_X_get_groups($X_uid) as $gid)
		{
			$where .= " OR (vgids LIKE '%&{$gid}&%')";
		}
	}

	if ($where) $where = " AND ($where)";

	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE (name NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $show_num;";
	$res = $xoopsDB->query($query);

	if ($res)
	{
		$date = $items = "";
		while($data = mysql_fetch_row($res))
		{
			if(date("Y-n-j",$data[3]) != $date) {
				$date = date("Y-n-j",$data[3]);
				$items .= "&nbsp;<strong>".$date."</strong><br />\n";
			}
			$items .="&nbsp;&nbsp;<strong><big>&middot;</big></strong>&nbsp;".xb_make_link($data[1])."<br />\n";
		}
	}

	$block['title'] = _MI_PUKIWIKI_BTITLE;
	$block['content'] = "<small>$items</small>";

	return $block;
}

//ページ名からリンクを作成
function xb_make_link($page,$alias="#/#")
{
	static $linktag = array();

	$pukiwiki_path = XOOPS_URL."/modules/pukiwiki/index.php";

	$_name = $name = $page;
	$page = xb_add_bracket($page);
	
	if (isset($linktag[$page.$alias])) return $linktag[$page.$alias];
	
	$url = rawurlencode($name);
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
				$page_name = preg_replace("/^(#.*#)$/","$1 ",$page_name);
			}
			$link = xb_make_link($name,$page_name);
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
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$name,$f_name)){
			$heading = xb_get_heading($page);
			if ($heading) $_name = $heading;
		}
		$pgp = xb_get_pg_passage($page,FALSE);
		if ($pgp)
			$retval = "<a href=\"".$pukiwiki_path."?$url\" title=\"".$name.$pgp."\">".$_name."</a>";
		else
			$retval = "$_name";
	}
	
	$linktag[$page.$alias] = $retval;
	return $retval;
}

//ページ名から最初の見出しを得る
function xb_get_heading($page)
{
/*
	$_body = xb_get_source($page);
	foreach($_body as $line){
		if (preg_match("/^\*{1,6}(.*)/",$line,$reg)){
			return trim(htmlspecialchars(str_replace(array("[[","]]"),"",$reg[1])));
			break;
		}
	}
*/
	global $xoopsDB;
	$page = addslashes(xb_strip_bracket($page));
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return "";
	$ret = mysql_fetch_row($res);
	return $ret[12];
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
function xb_get_filename($pagename)
{
	return XOOPS_ROOT_PATH."/modules/pukiwiki/wiki/".$pagename.".txt";
}

// ページが存在するか？
function xb_page_exists($page)
{
	return file_exists(xb_get_filename(xb_encode($page)));
}

// ソースを取得
function xb_get_source($page)
{	
	if(xb_page_exists($page)) {
		$ret = file(xb_get_filename(xb_encode($page)));
		$ret = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$ret);
		return $ret;
  }
  return array();
}

// 指定されたページの経過時刻
function xb_get_pg_passage($page,$sw=true)
{
	if($pgdt = @filemtime(xb_get_filename(xb_encode($page))))
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
function xb_get_readable(&$auth)
{
	static $_X_uid,$_X_admin,$_X_gids;
	
	if (!isset($_X_admin))
	{
		global $xoopsUser;
		if ( $xoopsUser )
		{
			$xoopsModule = XoopsModule::getByDirname("pukiwiki");
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
?>
