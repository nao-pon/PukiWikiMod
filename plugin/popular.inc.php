<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: popular.inc.php,v 1.6 2003/12/16 04:48:52 nao-pon Exp $
//

/*
 * PukiWiki popular プラグイン
 * (C) 2002, Kazunori Mizushima <kazunori@uc.netyou.jp>
 *
 * 人気のある(アクセス数の多い)ページの一覧を recent プラグインのように表示します。
 * 通算および今日に別けて一覧を作ることができます。
 * counter プラグインのアクセスカウント情報を使っています。
 *
 * [使用例]
 * #popular
 * #popular(20)
 * #popular(20,FrontPage|MenuBar)
 * #popular(20,FrontPage|MenuBar,true)
 *
 * [引数]
 * 1 - 表示する件数                             default 10
 * 2 - 表示させないページの正規表現             default なし
 * 3 - 今日(true)か通算(false)の一覧かのフラグ  default false
 */


function plugin_popular_init()
{
	if (LANG == 'ja')
		$messages = array(
			'_popular_plugin_frame' => '<h5 class="side_label">人気の%d件</h5><div>%s</div>',
			'_popular_plugin_today_frame' => '<h5 class="side_label" >今日の%d件</h5><div>%s</div>',
		);
	else
		$messages = array(
			'_popular_plugin_frame' => '<h5 class="side_label" >popular(%d)</h5><div>%s</div>',
			'_popular_plugin_today_frame' => '<h5 class="side_label" >today\'s(%d)</h5><div>%s</div>',
		);
	set_plugin_messages($messages);
}

function plugin_popular_convert()
{
	//return false;
	global $_popular_plugin_frame, $_popular_plugin_today_frame;
	global $script,$whatsnew,$non_list;
	
	$max = 10;
	$except = '';

	$array = func_get_args();
	$today = FALSE;

	switch (func_num_args()) {
	case 3:
		if ($array[2])
			$today = get_date('Y/m/d');
	case 2:
		$except = $array[1];
		$except = str_replace("&#124;","|",$except);
	case 1:
		$max = $array[0];
	}

	$nopage = "";
	$excepts = explode("|",$except);
	foreach(explode("|",$except) as $_except)
	{
		$nopage .= " AND (p.name NOT LIKE '%$_except%')";
	}
	$counters = array();

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
	if ($today)
	{
		$where = " WHERE (c.name = p.name) AND (p.name NOT LIKE ':%') AND (today = '$today')$nopage$where";
		$sort = "today_count";
	}
	else
	{
		$where = " WHERE (c.name = p.name) AND (p.name NOT LIKE ':%')$nopage$where";
		$sort = "count";
	}
	//echo $where;
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." as p , ".$xoopsDB->prefix("pukiwikimod_count")." as c $where ORDER BY $sort DESC LIMIT $max;";
	$res = $xoopsDB->query($query);
	//echo $query."<br>";
	if ($res)
	{
		while($data = mysql_fetch_row($res))
		{
			//echo $data[1]."<br>";
			if ($today)
				$counters["_$data[1]"] = $data[16];
			else
				$counters["_$data[1]"] = $data[14];
		}
	}


	$items = '';
	if (count($counters)) {
		$items = '<ul class="recent_list">';
		
		foreach ($counters as $page=>$count) {
			$page = htmlspecialchars(substr($page,1));
			$items .= " <li>".make_pagelink($page)."<span class=\"counter\">($count)</span></li>\n";
			}
		$items .= '</ul>';
	}
	return sprintf($today ? $_popular_plugin_today_frame : $_popular_plugin_frame,count($counters),$items);
}

?>