<?php
// pukiwiki.php - Yet another WikiWikiWeb clone.
//
// $Id: db_func.php,v 1.10 2004/10/05 12:46:48 nao-pon Exp $

// 全ページ名を配列にDB版
function get_existpages_db($nocheck=false,$page="",$limit=0,$order="",$nolisting=false,$nochiled=false,$nodelete=true)
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
		if ($nochiled)
			$page_where = "name LIKE '$page%' AND name NOT LIKE '$page%/%'";
		else
			$page_where = "name LIKE '$page%'";
			
		if ($where)
			$where = " ($page_where) AND ($where)";
		else
			$where = " $page_where";
			
	}
	else
	{
		if ($nochiled)
		{
			$page_where = "name NOT LIKE '%/%'";

			if ($where)
				$where = " ($page_where) AND ($where)";
			else
				$where = " $page_where";
		}
	}
	if ($nolisting)
	{
		if ($where)
			$where = " (name NOT LIKE ':%') AND ($where)";
		else
			$where = " (name NOT LIKE ':%')";
	}
	if ($nodelete)
	{
		if ($where)
			$where = " (editedtime !=0) AND ($where)";
		else
			$where = " (editedtime !=0)";
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
	static $page_id = array();
	$page = addslashes(strip_bracket($page));
	if (!empty($page_id[$page])) return $page_id[$page];
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return 0;
	$ret = mysql_fetch_row($res);
	$page_id[$page] = $ret[0];
	return $ret[0];
}

//ページ名から上層ページと前後ページのページ名とページIDを得る
function get_relaypage_by_name($page)
{
	global $xoopsDB,$X_admin,$X_uid;
	
	static $ret=array();
	$page = strip_bracket($page);
	
	if (isset($ret[$page])) return $ret[$page];
	
	$this_id = get_pgid_by_name($page);
	
	$where2 = "";
	// ページ閲覧権限
	if ($X_admin)
		$where = "";
	else
	{
		$where = "";
		if ($X_uid) $where .= "(uid = $X_uid) OR";
		$where .= " (vaids LIKE '%all%') OR (vgids LIKE '%&3&%')";
		if ($X_uid) $where .= " OR (vaids LIKE '%&{$X_uid}&%')";
		foreach(X_get_groups() as $gid)
		{
			$where .= " OR (vgids LIKE '%&{$gid}&%')";
		}
		$where = "AND($where) ";
	}
	
	// 階層を抽出
	if (preg_match("/(.+)\/[^\/]+/",$page,$match))
	{
		$up_page = $match[1];
		$up_pg_id = get_pgid_by_name($up_page);
		$where .= "AND name LIKE '".addslashes($up_page)."/%' ";
		$where2 = "AND name NOT LIKE '".addslashes($up_page)."/%/%' ";
		$where .= $where2;
		
	}
	else
	{
		$up_page = "";
		$where .= "AND name NOT LIKE '%/%' ";
	}
	
	$where .= "AND name NOT LIKE ':%' ";
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE id<$this_id ".$where."ORDER BY id DESC LIMIT 1;";
	$ret = mysql_fetch_row($xoopsDB->query($query));
	if ($ret) 
	{
		$prev_pg = $ret[1];
		$prev_pg_id = $ret[0];
	}
	else
	{
		$prev_pg = $up_page;
		$prev_pg_id = ($up_page)? $up_pg_id : 0;
	}
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE id>$this_id ".$where."ORDER BY id LIMIT 1;";
	$ret = mysql_fetch_row($xoopsDB->query($query));
	if ($ret) 
	{
		$next_pg = $ret[1];
		$next_pg_id = $ret[0];
	}
	else
	{
		/*
		$query = str_replace($where2,"",$query);
		$ret = mysql_fetch_row($xoopsDB->query($query));
		if ($ret) 
		{
			$next_pg = $ret[1];
			$next_pg_id = $ret[0];
		}
		else
		{
			$next_pg = "";
			$next_pg_id = 0;
		}
		*/
		$next_pg = $up_page;
		$next_pg_id = ($up_page)? $up_pg_id : 0;
	}
	$ret[$page]['up'] = array($up_pg_id,$up_page);
	$ret[$page]['prev'] = array($prev_pg_id,$prev_pg);
	$ret[$page]['next'] = array($next_pg_id,$next_pg);
	return $ret[$page];
}

//ページ名から前ページへのリンクを得る
function get_prevpage_link_by_name($page)
{
	$data = get_relaypage_by_name($page);
	
	if (!$data['prev'][0]) return "";
	
	$link = make_pagelink($data['prev'][1],"#".chr(0)."#");
	$links = explode(chr(0),$link);
	$link = $links[count($links)-1];
	return $link;
	//return ($data['prev'][1])? make_link("[[".preg_replace("/.+\//","",$data['prev'][1]).">".$data['prev'][1]."]]"):"";
}

//ページ名から後ろページへのリンクを得る
function get_nextpage_link_by_name($page)
{
	$data = get_relaypage_by_name($page);
	
	if (!$data['next'][0]) return "";
	
	$link = make_pagelink($data['next'][1],"#".chr(0)."#");
	$links = explode(chr(0),$link);
	$link = $links[count($links)-1];
	return $link;

}
//ページ名から前後のページへの<link>タグを得る
function get_header_link_tag_by_name($page)
{
	global $use_static_url;
	
	$data = get_relaypage_by_name($page);
	
	$up_pg_id = $data['up'][0];
	$up_page = rawurlencode($data['up'][1]);
	$prev_pg_id = $data['prev'][0];
	$prev_pg = rawurlencode($data['prev'][1]);
	$next_pg_id = $data['next'][0];
	$next_pg = rawurlencode($data['next'][1]);
	
	$ret = "";
	if ($up_page)
	{
		if ($use_static_url)
			$ret .= '<link rel="start" href="'.XOOPS_WIKI_URL.'/'.$up_pg_id.'.html">'."\n";
		else
			$ret .= '<link rel="start" href="'.XOOPS_WIKI_URL.'/index.php?'.$up_page.'">'."\n";
	}
	else
		$ret .= '<link rel="start" href="'.XOOPS_URL.'/modules/pukiwiki/">'."\n";
	if ($prev_pg)
	{
		if ($use_static_url)
			$ret .= '<link rel="prev" href="'.XOOPS_WIKI_URL.'/'.$prev_pg_id.'.html">'."\n";
		else
			$ret .= '<link rel="prev" href="'.XOOPS_WIKI_URL.'/index.php?'.$prev_pg.'">'."\n";
	}
	if ($next_pg)
	{
		if ($use_static_url)
			$ret .= '<link rel="next" href="'.XOOPS_WIKI_URL.'/'.$next_pg_id.'.html">'."\n";
		else
			$ret .= '<link rel="next" href="'.XOOPS_WIKI_URL.'/index.php?'.$next_pg.'">'."\n";
	}
	return $ret;

}

// 指定ページ以下のページ数をカウントする
function get_child_counts($page)
{
	$page = strip_bracket($page);
	$page = preg_replace("/\/$/","",$page);
	return count(get_existpages_db(false,$page."/"));
}


// pginfo DB を更新
function pginfo_db_write($page,$action,$aids="",$gids="",$vaids="",$vgids="",$freeze="",$unvisible="",$notimestamp=false)
{
	global $xoopsDB,$X_uid;
	
	//最初の見出し行取得
	$title = addslashes(get_heading_init($page));
	
	//ページ作成者
	//$uid = $X_uid;
	$uid = get_pg_auther($page);
	//ページ更新者
	$lastediter = $X_uid;
	
	// ページ削除時
	if ($action == "delete")
		$unvisible = false;
	
	$name = strip_bracket($page);
	$s_name = addslashes($name);
	$editedtime = @filemtime(DATA_DIR.encode($page).".txt");
	
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
		
		$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name = '$s_name' LIMIT 1;";
		if (!$result=$xoopsDB->query($query))
		{
			// 以前に削除したページ
			$value = "editedtime=$editedtime";
			$value .= ",buildtime=$buildtime";
			$value .= ",title='$title'";
			$value .= ",lastediter=$lastediter";
			$value .= ",aids='$aids'";
			$value .= ",gids='$gids'";
			$value .= ",vaids='$vaids'";
			$value .= ",vgids='$vgids'";
			$value .= ",uid=$uid";
			$value .= ",freeze=$freeze";
			$value .= ",unvisible=$unvisible";
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET $value WHERE name = '$s_name';";
		}
		else
		{
			$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_pginfo")." (name,buildtime,editedtime,aids,gids,vaids,vgids,lastediter,uid,freeze,unvisible,title) VALUES('$s_name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible,'$title');";
		}
		$result=$xoopsDB->queryF($query);
		plain_db_write($page,"insert");
	}
	
	// ページ更新
	elseif ($action == "update")
	{
		$value = "editedtime=$editedtime,title='$title'";
		if (!$notimestamp) $value .= ",lastediter=$lastediter";
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

		//$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name = '$s_name';";
		$value = "editedtime=0";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET $value WHERE name = '$s_name';";
		
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
	
	$data = addslashes(preg_replace("/[\s]+/","",strip_htmltag(convert_html($data,false))));
	//echo $data."<hr>";
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

// attach DB を更新
function attach_db_write($data,$action)
{
	global $xoopsDB,$post,$get,$vars;
	
	//if (!$pgid = $data['pgid']) return false;
	
	$pgid = (int)$data['pgid'];
	$name = $data['name'];
	$type = $data['type'];
	$mtime = (int)$data['mtime'];
	$size = (int)$data['size'];
	// $mode normal=0, isbn=1, thumb=2
	$mode = (preg_match("/^ISBN.*\.(dat|jpg)/",$name))? 1 : ((preg_match("/^\d\d?%/",$name))? 2 : 0);
	$age = (int)$data['status']['age'];
	$count = (int)$data['status']['count'][$age];
	$pass = $data['status']['pass'];
	$freeze = (int)$data['status']['freeze'];
	$copyright = (int)$data['status']['copyright'];
	$owner = (int)$data['status']['owner'];
	
	// 新規作成
	if ($action == "insert")
	{
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_attach")." (pgid,name,type,mtime,size,mode,count,age,pass,freeze,copyright,owner) VALUES($pgid,'$name','$type',$mtime,$size,'$mode',$count,$age,'$pass',$freeze,$copyright,$owner);";
		$result=$xoopsDB->queryF($query);
		if (!$result) echo $query."<hr>";
	}
	
	// 更新
	elseif ($action == "update")
	{
		$value = "pgid=$pgid"
		.",name='$name'"
		.",type='$type'"
		.",mtime=$mtime"
		.",size=$size"
		.",mode=$mode"
		.",count=$count"
		.",age=$age"
		.",pass='$pass'"
		.",freeze=$freeze"
		.",copyright=$copyright"
		.",owner=$owner";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_attach")." SET $value WHERE pgid=$pgid AND name='$name' LIMIT 1;";
		$result=$xoopsDB->queryF($query);
		if (!$result) echo $query."<hr>";
	}
	
	// ファイル削除
	elseif ($action == "delete")
	{
		$q_name = ($name)? " AND name='{$name}' LIMIT 1" : "";
		
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_attach")." WHERE pgid = {$pgid}{$q_name};";
		
		$result=$xoopsDB->queryF($query);
		if (!$result) echo $query."<hr>";
	}
	else
		return false;
	
	return true;
}

// プラグインからplane_text DB を更新を指示(コンバート時)
function need_update_plaindb($page = null)
{
	global $vars;
	if (is_null($page)) $page = $vars['page'];
	
	if (is_page($page))
	{
		$page =strip_bracket($page);
		// ランチャーファイル作成
		$filename = CACHE_DIR.encode($page).".udp";
		if (!($fp = fopen($filename,'w')))
		{
			return;
		}
		fclose($fp);
	}
	return;
}
?>