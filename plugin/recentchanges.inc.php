<?php
// $Id: recentchanges.inc.php,v 1.2 2003/10/31 12:22:59 nao-pon Exp $

function plugin_recentchanges_action()
{
	global $xoopsDB,$X_admin,$X_uid,$whatsnew,$maxshow,$date_format,$weeklabels,$time_format,$trackback;
	
	if ($X_admin)
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
	if ($where) $where = " AND ($where)";
	//echo $where;

	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE (name NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $maxshow;";
	$res = $xoopsDB->query($query);
	//echo $query."<br>";
	if ($res)
	{
		$date = $items = "";
		$cnt = 0;
		$items = '<ul class="list1" style="padding-left:16px;margin-left:16px">';
		while($data = mysql_fetch_row($res))
		{
			$lastmod = date($date_format,$data[3])
			 . " (" . $weeklabels[date("w",$data[3])] . ") "
			 . date($time_format,$data[3]);
			$tb_tag = ($trackback)? "<a href=\"$script?plugin=tb&amp;__mode=view&amp;tb_id=".tb_get_id($data[1])."\" title=\"TrackBack\">TB(".tb_count($data[1]).")</a> - " : "";
			$items .="<li>$lastmod - ".$tb_tag.make_pagelink($data[1])."</li>\n";
		}
		$items .= '</ul>';

	}
	
	//$ret['msg'] = make_search($whatsnew)." Last $maxshow";
	$ret['msg'] = $whatsnew." Last $maxshow";
	$ret['body'] = $items;
	return $ret;
}
?>