<?php
// pukiwiki.php - Yet another WikiWikiWeb clone.
//
// $Id: db_func.php,v 1.2 2003/11/06 12:45:12 nao-pon Exp $

// 全ページ名を配列にDB版
function get_existpages_db($nocheck=false,$page="",$limit=0,$order="",$nolisting=false)
{
	static $_aryret = array();
	if ($_aryret && $nocheck == false && $page == "") return $_aryret;

	$aryret = array();

	global $xoopsDB,$X_admin,$X_uid;
	
	if ($nocheck || $X_admin)
		$where = "";
	else
	{
		$where = "";
		if ($X_uid) $where .= "  (uid = $X_uid) OR";
		$where .= " (vaids LIKE '%all%') OR (vgids LIKE '%&3&%')";
		if ($X_uid) $where .= " OR (vaids LIKE '%&{$X_uid}&%')";
		foreach(X_get_groups() as $gid)
		{
			$where .= " OR (vgids LIKE '%&{$gid}&%')";
		}
	}
	if ($page)
	{
		$page = strip_bracket($page);
		if ($where)
			$where = " (name LIKE '$page%') AND ($where)";
		else
			$where = " name LIKE '$page%'";
	}
	if ($nolisting)
	{
		if ($where)
			$where = " (name NOT LIKE ':%') AND ($where)";
		else
			$where = " (name NOT LIKE ':%')";
	}
	if ($where) $where = " WHERE".$where;
	$limit = ($limit)? " LIMIT $limit" : "";
	//echo $where;
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")."$where$order$limit;";
	$res = $xoopsDB->query($query);
	if ($res)
	{
		while($data = mysql_fetch_row($res))
		{
			array_push($aryret,add_bracket($data[1]));
		}
	}
	if ($nocheck == false && $page == "") $_aryret = $aryret;
	return $aryret;
}

//DBからページ情報を得る
function get_pg_info_db($page)
{
	global $xoopsDB;
	$page = strip_bracket($page);
	$ret = array();
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return $ret;
	$ret = mysql_fetch_array($res,MYSQL_ASSOC);
	return $ret;
}

//ページIDからページ名を求める
function get_pgname_by_id($id)
{
	global $xoopsDB;
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE id=$id LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return "";
	$ret = mysql_fetch_row($res);
	return $ret[1];
}

//ページ名からページIDを求める
function get_pgid_by_name($page)
{
	global $xoopsDB;
	$page = strip_bracket($page);
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return 0;
	$ret = mysql_fetch_row($res);
	return $ret[0];
}

// pginfo DB を更新
function pginfo_db_write($page,$action,$aids="",$gids="",$vaids="",$vgids="",$freeze="",$unvisible="")
{
	global $xoopsDB,$X_uid;
	
	$uid = $X_uid;
	// ページ削除時
	if ($action == "delete")
		$unvisible = false;
	
	$name = strip_bracket($page);
	$s_name = addslashes($name);
	$editedtime = @filemtime(DATA_DIR.encode($page).".txt");
	$lastediter = $X_uid;
	
	// 編集権限情報
	if ($freeze === "")
	{
		if ($action == "insert")
		{
			$aids = "&all";
			$gids = "&3&";
			$freeze = 0;
		}
		else
			$freeze=$aids=$gids="";
	}
	elseif($freeze)
	{
		$aids = "&".str_replace(",","&",$aids)."&";
		$gids = "&".str_replace(",","&",$gids)."&";
		$freeze = 1;
	}
	else
	{
		$aids = "&all";
		$gids = "&3&";
		$freeze = 0;
	}
	
	// 閲覧権限情報
	if ($unvisible === "")
	{
		if ($action == "insert")
		{
			//$up_page = preg_replace("/\/[^/]+$/","",$page);
			//if ($up_page == $page) $up_page = "";
			//$page_auths = get_pg_allow_viewer($up_page);
			$page_auths = get_pg_allow_viewer(strip_bracket($page));
			$vaids = "&".str_replace(",","&",$page_auths['user']);
			$vgids = "&".str_replace(",","&",$page_auths['group']);
			$unvisible = 0;
		}
		else
			$unvisible=$vaids=$vgids="";
	}
	elseif ($unvisible)
	{
		$vaids = "&".str_replace(",","&",$vaids)."&";
		$vgids = "&".str_replace(",","&",$vgids)."&";
		$unvisible = 1;
	}
	else
	{
		//$up_page = preg_replace("/\/[^/]+$/","",$page);
		//if ($up_page == $page) $up_page = "";
		//$page_auths = get_pg_allow_viewer($up_page);
		$page_auths = get_pg_allow_viewer(strip_bracket($page));
		$vaids = "&".str_replace(",","&",$page_auths['user']);
		$vgids = "&".str_replace(",","&",$page_auths['group']);
		$unvisible = 0;
	}

	// 新規作成
	if ($action == "insert")
	{
		$buildtime = $editedtime;
		
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_pginfo")." (name,buildtime,editedtime,aids,gids,vaids,vgids,lastediter,uid,freeze,unvisible) VALUES('$s_name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible);";
		$result=$xoopsDB->queryF($query);
	}
	
	// ページ更新
	elseif ($action == "update")
	{
		$value = "editedtime=$editedtime,lastediter=$lastediter";
		if ($aids) $value .= ",aids='$aids'";
		if ($gids) $value .= ",gids='$gids'";
		if ($vaids) $value .= ",vaids='$vaids'";
		if ($vgids) $value .= ",vgids='$vgids'";
		if ($uid) $value .= ",uid=$uid";
		if ($freeze !== "") $value .= ",freeze=$freeze";
		if ($unvisible !== "") $value .= ",unvisible=$unvisible";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET $value WHERE name = '$name';";
		//echo $query;
		//exit;
		$result=$xoopsDB->queryF($query);
	}
	
	// ページ削除
	elseif ($action == "delete")
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name = '$name';";
		$result=$xoopsDB->queryF($query);
	}
	
	// $action が無効な値
	else
		return;
	
	// 下層ページの閲覧権限更新
	if ($vaids && $vgids)
	{
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET vaids='$vaids', vgids='$vgids' WHERE (name LIKE '$name/%') AND (unvisible = 0);";
		$result=$xoopsDB->queryF($query);
	}
	
	// 完了
	return;
}
?>