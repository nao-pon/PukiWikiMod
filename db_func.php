<?php
// pukiwiki.php - Yet another WikiWikiWeb clone.
//
// $Id: db_func.php,v 1.1 2003/10/31 12:22:59 nao-pon Exp $

// �S�y�[�W����z���DB��
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

// pginfo DB ���X�V
function pginfo_db_write($page,$action,$aids="",$gids="",$vaids="",$vgids="",$freeze="",$unvisible="")
{
	global $xoopsDB,$X_uid;
	
	$uid = $X_uid;
	// �y�[�W�폜��
	if ($action == "delete")
		$unvisible = false;
	
	$name = strip_bracket($page);
	$s_name = addslashes($name);
	$editedtime = @filemtime(DATA_DIR.encode($page).".txt");
	$lastediter = $X_uid;
	
	// �ҏW�������
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
	
	// �{���������
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

	// �V�K�쐬
	if ($action == "insert")
	{
		$buildtime = filemtime($file);
		
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_pginfo")." (name,buildtime,editedtime,aids,gids,vaids,vgids,lastediter,uid,freeze,unvisible) VALUES('$s_name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible);";
		$result=$xoopsDB->queryF($query);
	}
	
	// �y�[�W�X�V
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
	
	// �y�[�W�폜
	elseif ($action == "delete")
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name = '$name';";
		$result=$xoopsDB->queryF($query);
	}
	
	// $action �������Ȓl
	else
		return;
	
	// ���w�y�[�W�̉{�������X�V
	if ($vaids && $vgids)
	{
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET vaids='$vaids', vgids='$vgids' WHERE (name LIKE '$name/%') AND (unvisible = 0);";
		$result=$xoopsDB->queryF($query);
	}
	
	// ����
	return;
}
?>