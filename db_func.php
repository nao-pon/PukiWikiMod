<?php
// pukiwiki.php - Yet another WikiWikiWeb clone.
//
// $Id: db_func.php,v 1.3 2003/12/16 04:48:52 nao-pon Exp $

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
		$page = addslashes(strip_bracket($page));
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
	$page = addslashes(strip_bracket($page));
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
	$page = addslashes(strip_bracket($page));
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
	
	//最初の見出し行取得
	$title = addslashes(get_heading_init($page));
	
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
		
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_pginfo")." (name,buildtime,editedtime,aids,gids,vaids,vgids,lastediter,uid,freeze,unvisible,title) VALUES('$s_name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible,'$title');";
		$result=$xoopsDB->queryF($query);
		plain_db_write($page,"insert");
	}
	
	// ページ更新
	elseif ($action == "update")
	{
		$value = "editedtime=$editedtime,lastediter=$lastediter,title='$title'";
		if ($aids) $value .= ",aids='$aids'";
		if ($gids) $value .= ",gids='$gids'";
		if ($vaids) $value .= ",vaids='$vaids'";
		if ($vgids) $value .= ",vgids='$vgids'";
		if ($uid) $value .= ",uid=$uid";
		if ($freeze !== "") $value .= ",freeze=$freeze";
		if ($unvisible !== "") $value .= ",unvisible=$unvisible";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET $value WHERE name = '$s_name';";
		//echo $query;
		//exit;
		$result=$xoopsDB->queryF($query);
		// 下層ページの閲覧権限更新
		if ($vaids && $vgids)
		{
			//コメントページも含む
			$comment_page = addslashes(strip_bracket(sprintf(PCMT_PAGE,$name)));
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET vaids='$vaids', vgids='$vgids' WHERE ((name LIKE '$s_name/%') OR (name = '$comment_page') OR (name LIKE '$comment_page/%')) AND (unvisible = 0);";
			$result=$xoopsDB->queryF($query);
		}
		plain_db_write($page,"update");
	}
	
	// ページ削除
	elseif ($action == "delete")
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name = '$s_name';";
		$result=$xoopsDB->queryF($query);
		plain_db_write($page,"delete");
	}
	
	// 完了
	return;
}

// plane_text DB を更新
function plain_db_write($page,$action)
{
	global $xoopsDB,$noplain_plugin,$post,$get,$vars;
	global $no_plugins;
	
	if (!$pgid = get_pgid_by_name($page)) return false;
	
	//ソースを取得
	$data = join('',get_source($page));
	delete_page_info($data);
	
	//処理しないプラグインを削除
	$no_plugins = split(',',$noplain_plugin);
	
	//echo $data."<hr>";
	$data = addslashes(preg_replace("/[\s]+/","",strip_htmltag(convert_html($data,false,$noplain_plugin))));
	// 新規作成
	if ($action == "insert")
	{
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_plain")." (pgid,plain) VALUES($pgid,'$data');";
		$result=$xoopsDB->queryF($query);
		if (!$result) echo $query."<hr>";
	}
	
	// ページ更新
	elseif ($action == "update")
	{
		$value = "plain='$data'";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_plain")." SET $value WHERE pgid = $pgid;";
		$result=$xoopsDB->queryF($query);
		if (!$result) echo $query."<hr>";
	}
	
	// ページ削除
	elseif ($action == "delete")
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_plain")." WHERE pgid = $pgid;";
		$result=$xoopsDB->queryF($query);
		if (!$result) echo $query."<hr>";
	}
	else
		return false;
	
	return true;
}
?>