<?php
/*
 * PukiWiki 最新の?件を表示するプラグイン
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 * 
 * 変更履歴:
 *  2002.04.08: patさん、みのるさんの指摘により、リンク先が日本語の場合に
 *              化けるのを修正
 * 
 *  2002.06.17: plugin_recent_init()を設定
 *  2002.07.02: <ul>による出力に変更し構造化
 *
 * $id$
 */

function plugin_recent_init()
{
	if (LANG == "ja") {
		$_plugin_recent_messages = array(
    '_recent_plugin_frame '=>'<h5 class="side_label" style="margin:auto;margin-top:0px;margin-bottom:.5em">最新の%d件</h5><div class="small" style="margin-left:.8em;margin-right:.8em">%s</div>');
  } else {
		$_plugin_recent_messages = array(
    '_recent_plugin_frame '=>'<h5 class="side_label" style="margin:auto;margin-top:0px;margin-bottom:.5em">Recent(%d)</h5><div class="small" style="margin-left:.8em;margin-right:.8em">%s</div>');
	}
  set_plugin_messages($_plugin_recent_messages);
}

function plugin_recent_convert()
{
	global $_recent_plugin_frame;
	global $WikiName,$BracketName,$script,$whatsnew,$X_admin;
	
	$recent_lines = 10;
	if(func_num_args()>0) {
		$array = func_get_args();
		$recent_lines = $array[0];
	}


	global $xoopsDB,$X_admin,$X_uid;
	
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
	/*
	if ($page)
	{
		$page = strip_bracket($page);
		if ($where)
			$where = " (name LIKE '$page/%') AND ($where)";
		else
			$where = " name LIKE '$page/%'";
	}
	*/
	if ($where) $where = " AND ($where)";
	//echo $where;

	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE (name NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $recent_lines;";
	$res = $xoopsDB->query($query);
	//echo $query."<br>";
	if ($res)
	{
		$date = $items = "";
		$cnt = 0;
		while($data = mysql_fetch_row($res))
		{
			if(date("Y-n-j",$data[3]) != $date) {
					if($date != "") {
						$items .= "</ul>";
					}
					$date = date("Y-n-j",$data[3]);
					$items .= "<div class=\"recent_date\">".$date."</div><ul class=\"recent_list\">";
			}
			$items .="<li>".make_pagelink($data[1])."</a></li>\n";
			$cnt++;
		}
	}

	$items .="</ul>";
	return sprintf($_recent_plugin_frame,$cnt,$items);
}
?>
