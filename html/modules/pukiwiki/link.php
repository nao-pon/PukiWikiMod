<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: link.php,v 1.12 2006/04/06 13:32:16 nao-pon Exp $
//

// PukiWikiMod 1.4.1 以降でMySQLデータベース利用に変更
// データベースから関連ページを得る
function links_get_related_db($page)
{
	global $xoopsDB,$X_admin,$X_uid;
	
	static $where = NULL;
	static $links = array();
	
	if (isset($links[$page])) {return $links[$page];}
	$links[$page] = array();
	
	if (is_null($where))
	{
		// 閲覧権限チェック用 WHERE句
		if ($X_admin)
			$where = "";
		else
		{
			$where = "";
			if ($X_uid) $where .= " (`uid` = $X_uid) OR";
			$where .= " (`vaids` LIKE '%all%') OR (`vgids` LIKE '%&3&%')";
			if ($X_uid) $where .= " OR (`vaids` LIKE '%&{$X_uid}&%')";
			foreach(X_get_groups() as $gid)
			{
				$where .= " OR (vgids LIKE '%&{$gid}&%')";
			}
			if ($where) $where = " AND (".$where.")";
		}
	}
	
	$query = "SELECT p.name, p.editedtime FROM `".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_rel")."` AS r, `".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")."` AS p WHERE `relid` = ".get_pgid_by_name($page)." AND p.id = r.pgid".$where.";";
	$result = $xoopsDB->query($query);
	
	if ($result)
	{
		while(list($name,$time) = mysql_fetch_row($result))
		{
			$links[$page][$name] = $time;
		}
	}
	
	return $links[$page];
}
//ページの関連を更新する
function links_update($page)
{
	return;
}
//ページの関連を初期化する
function links_init()
{
	return;

}
function links_add($page,$add,$rel_auto)
{
	return;
}
function links_delete($page,$del)
{
	return;

}
function &links_get_objects($page,$refresh=FALSE)
{
	return;
}
?>