<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: popular.inc.php,v 1.13 2005/03/07 15:39:17 nao-pon Exp $
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
 * #popular(20,FrontPage|MenuBar,true,XOOPS)
 * #popular(20,FrontPage|MenuBar,true,,1)
 *
 * [引数]
 * 1 - 表示する件数                                    default 10
 * 2 - 表示させないページ(半角スペースまたは | 区切り) default なし
 * 3 - 今日(true)か通算(false)の一覧かのフラグ         default false
 * 4 - 集計対象の仮想階層ページ名                      default なし
 * 5 - 多階層ページの場合、最下層のみを表示 ( 0 or 1 ) default 0
 */


function plugin_popular_init()
{
	if (LANG == 'ja')
		$messages = array(
			'_popular_plugin_frame' => '<h5 class="side_label">人気の%d件%s</h5><div>%s</div>',
			'_popular_plugin_today_frame' => '<h5 class="side_label" >今日のTOP%d%s</h5><div>%s</div>',
		);
	else
		$messages = array(
			'_popular_plugin_frame' => '<h5 class="side_label" >popular(%d)%s</h5><div>%s</div>',
			'_popular_plugin_today_frame' => '<h5 class="side_label" >today\'s(%d)%s</h5><div>%s</div>',
		);
	set_plugin_messages($messages);
}

function plugin_popular_convert()
{
	//return false;
	global $_popular_plugin_frame, $_popular_plugin_today_frame;
	global $script,$whatsnew,$non_list;
	global $_list_left_margin, $_list_margin;
	
	$max = 10;
	$except = '';

	$array = func_get_args();
	$today = FALSE;
	$prefix = "";
	$compact = 0;

	switch (func_num_args()) {
	case 5:
		if ($array[4]) $compact = 1;
	case 4:
		$prefix = $array[3];
		$prefix = preg_replace("/\/$/","",$prefix);
	case 3:
		if ($array[2])
			$today = get_date('Y/m/d');
	case 2:
		$except = $array[1];
		$except = str_replace(array("&#124;","&#x7c;"," "),"|",$except);
	case 1:
		$max = $array[0];
	}

	$nopage = "";
	if ($except)
	{
		$excepts = explode("|",$except);
		foreach(explode("|",$except) as $_except)
		{
			$nopage .= " AND (p.name NOT LIKE '%$_except%')";
		}
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

	if ($prefix)
	{
		$prefix = strip_bracket($prefix);
		if ($where)
			$where = " (p.name LIKE '$prefix/%') AND ($where)";
		else
			$where = " p.name LIKE '$prefix/%'";
	}

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
				$counters["_$data[1]"] = $data[17];
			else
				$counters["_$data[1]"] = $data[15];
		}
	}


	$items = '';
	if ($prefix)
	{
		$bypege = " [ ".make_pagelink($prefix,$prefix)." ]";
		$prefix .= "/";
		$prefix = preg_quote($prefix,"/");
	}
	else
		$bypege = "";
	
	if (count($counters))
	{
		$_style = $_list_left_margin + $_list_margin;
		$_style = " style=\"margin-left:". $_style ."px;padding-left:". $_style ."px;\"";
		$items = '<ul class="popular_list"'.$_style.'">';
		
		foreach ($counters as $page=>$count) {
			$page = htmlspecialchars(substr($page,1));
			//Newマーク付加
			if (exist_plugin_inline("new"))
				$new_mark = do_plugin_inline("new","{$page}/,nolink","");
			if ($prefix)
				$page = make_pagelink($page,preg_replace("/$prefix/","",$page));
			else
			{
				if (!$compact)
					$page = make_pagelink($page);
				else
					$page = make_pagelink($page,"#compact#");
			}	
			$items .= " <li>".$page."<span class=\"counter\">($count)</span>$new_mark</li>\n";
			}
		$items .= '</ul>';
	}
	return sprintf($today ? $_popular_plugin_today_frame : $_popular_plugin_frame,count($counters),$bypege,$items);
}

?>