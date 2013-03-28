<?php
// pukiwiki.php - Yet another WikiWikiWeb clone.
//
// $Id: db_func.php,v 1.44 2008/10/02 08:38:47 nao-pon Exp $

// 全ページ名を配列にDB版
function get_existpages_db($nocheck=false,$page="",$limit=0,$order="",$nolisting=false,$nochiled=false,$nodelete=true,$strip=FALSE)
{
	static $_aryret = array();
	if ($_aryret && !$nocheck && !$page && !$limit && !$order && !$nolisting && !$nochiled && $nodelete && !$strip) return $_aryret;

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
		if (substr($page,-1) == '/')
		{
			$page = addslashes(substr($page,0,-1));
			if ($nochiled)
				$page_where = "name = '$page' OR ( name LIKE '$page/%' AND name NOT LIKE '$page/%/%' )";
			else
				$page_where = "name = '$page' OR name LIKE '$page/%'";
		}
		else
		{
			$page = addslashes(strip_bracket($page));
			if ($nochiled)
				$page_where = "name LIKE '$page%' AND name NOT LIKE '$page%/%'";
			else
				$page_where = "name LIKE '$page%'";
		}
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
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")."$where$order$limit;";
	$res = $xoopsDB->query($query);
	if ($res)
	{
		while($data = mysql_fetch_row($res))
		{
			array_push($aryret,($strip)? $data[1] : add_bracket($data[1]));
		}
	}
	if ($_aryret && !$nocheck && !$page && !$limit && !$order && !$nolisting && !$nochiled && $nodelete && !$strip) $_aryret = $aryret;
	return $aryret;
}

//DBからページ情報を得る
function get_pg_info_db($page)
{
	global $xoopsDB;
	static $val = array();
	$page = addslashes(strip_bracket($page));
	if (isset($val[$page])) return $val[$page];
	
	$val[$page] = array();
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return $val[$page];
	$val[$page] = mysql_fetch_array($res,MYSQL_ASSOC);
	
	return $val[$page];
}

//ページIDからページ名を求める
function get_pgname_by_id($id)
{
	global $xoopsDB;
	static $page_name = array();
	if (isset($page_name[$id])) return $page_name[$id];
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE id=$id LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return "";
	$ret = mysql_fetch_row($res);
	$page_name[$id] = $ret[1];
	return $ret[1];
}

//ページ名からページIDを求める
function get_pgid_by_name($page)
{
	global $xoopsDB;
	static $page_id = array();
	$page = addslashes(strip_bracket($page));
	if (isset($page_id[$page])) return $page_id[$page];
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE name='$page' LIMIT 1;";
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
	$match = array();
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
	$where .= "AND editedtime !=0 ";
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE id<$this_id ".$where."ORDER BY id DESC LIMIT 1;";
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
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE id>$this_id ".$where."ORDER BY id LIMIT 1;";
	$ret = mysql_fetch_row($xoopsDB->query($query));
	if ($ret) 
	{
		$next_pg = $ret[1];
		$next_pg_id = $ret[0];
	}
	else
	{
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
	
	return make_pagelink($data['prev'][1],"#compact#");
}

//ページ名から後ろページへのリンクを得る
function get_nextpage_link_by_name($page)
{
	$data = get_relaypage_by_name($page);
	
	if (!$data['next'][0]) return "";
	
	return make_pagelink($data['next'][1],"#compact#");
}

//ページ名から前後のページへの<link>タグを得る
function get_header_link_tag_by_name($page)
{
	$data = get_relaypage_by_name($page);
	
	$up_page = $data['up'][0];
	$prev_pg = $data['prev'][0];
	$next_pg = $data['next'][0];
	
	$ret = "";
	if ($up_page)
	{
		$ret .= '<link rel="start" href="'.XOOPS_WIKI_HOST.get_url_by_id($up_page).'">'."\n";
	}
	else
		$ret .= '<link rel="start" href="'.XOOPS_WIKI_HOST.XOOPS_WIKI_URL.'/">'."\n";
	if ($prev_pg)
	{
		$ret .= '<link rel="prev" href="'.XOOPS_WIKI_HOST.get_url_by_id($prev_pg).'">'."\n";
	}
	if ($next_pg)
	{
		$ret .= '<link rel="next" href="'.XOOPS_WIKI_HOST.get_url_by_id($next_pg).'">'."\n";
	}
	return $ret;

}

// 指定ページ以下のページ数をカウントする
function get_child_counts($page)
{
	$page = strip_bracket($page);
	$page = preg_replace("/\/$/","",$page);
	return count(array_diff(get_existpages_db(false,$page."/",0,"",false,false,true,true),array($page)));
}

// pginfo DB を更新
function pginfo_db_write($page,$action,$aids="",$gids="",$vaids="",$vgids="",$freeze="",$unvisible="",$notimestamp=false)
{
	global $xoopsDB,$X_uid,$X_admin,$countup_xoops;
	
	//最初の見出し行取得
	$title = addslashes(str_replace(array('&lt;','&gt;','&amp;','&quot;','&#039;'),array('<','>','&','"',"'"),get_heading_init($page)));
	
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
	$editedtime = filemtime(DATA_DIR.encode($page).".txt");
	
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
		$page_auths = get_pg_allow_viewer(strip_bracket($page));
		$vaids = "&".str_replace(",","&",$page_auths['user']);
		$vgids = "&".str_replace(",","&",$page_auths['group']);
		$unvisible = 0;
	}

	// 新規作成
	if ($action == "insert")
	{
		$buildtime = $editedtime;
		
		$query = "SELECT count(*) FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE name = '$s_name' LIMIT 1;";
		//if ($X_admin) echo $query."<br />";
		$result=$xoopsDB->query($query);
		$count = mysql_fetch_row($result);
		
		if ($count[0])
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
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET $value WHERE name = '$s_name';";
		}
		else
		{
			$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." (name,buildtime,editedtime,aids,gids,vaids,vgids,lastediter,uid,freeze,unvisible,title) VALUES('$s_name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible,'$title');";
		}
		//if ($X_admin) echo $query;exit;
		$result=$xoopsDB->queryF($query);
		//plain_db_write($page,"insert");
		need_update_plaindb($page);
		
		//投稿数カウントアップ
		if ($uid && $countup_xoops)
		{
			$user =new XoopsUser($uid);
			$user->incrementPost();
		}
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
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET $value WHERE name = '$s_name';";
		//echo $query;
		//exit;
		$result=$xoopsDB->queryF($query);
		// 下層ページの閲覧権限更新
		if ($vaids && $vgids)
		{
			//コメントページも含む
			$comment_page = addslashes(strip_bracket(sprintf(PCMT_PAGE,$name)));
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET vaids='$vaids', vgids='$vgids' WHERE ((name LIKE '$s_name/%') OR (name = '$comment_page') OR (name LIKE '$comment_page/%')) AND (unvisible = 0);";
			$result=$xoopsDB->queryF($query);
		}
		//plain_db_write($page,"update");
		need_update_plaindb($page);
	}
	
	// ページ削除
	elseif ($action == "delete")
	{

		$value = "editedtime=0";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET $value WHERE name = '$s_name';";
		
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
	global $pagereading_config_page,$whatsdeleted;
	global $script,$_symbol_noexists;
	global $pwm_plugin_flg,$fusen_enable_allpage;
	global $related;
	
	if (!$pgid = get_pgid_by_name($page)) return false;
	
	//ソースを取得
	$data = join('',get_source($page));
	delete_page_info($data);
	
	//処理しないプラグインを削除 $no_plugins = GLOBAL
	$no_plugins = explode(',',$noplain_plugin);
	
	$s_page = strip_bracket($page);
	
	// ページ読みのデータページはコンバート処理しない(過負荷対策)
	if ($s_page != $pagereading_config_page)
	{
		$spc = array
		(
			array
			(
				'&lt;',
				'&gt;',
				'&amp;',
				'&quot;',
				'&#039;',
				'&nbsp;',
			)
			,
			array
			(
				'<',
				'>',
				'&',
				'"',
				"'",
				" ",
			)
		);
		
		$_X_admin = $GLOBALS['X_admin'];
		$GLOBALS['X_admin'] = 1;
		
//		$data = convert_html($data);
		$pcon = new pukiwiki_converter();
		$pcon->safe = TRUE;
		$pcon->page = $page;
		$pcon->string = $data;
		$data = $pcon->convert();

		// remove javascript
		$data = preg_replace("#<script.+?/script>#i","",$data);

		
		$GLOBALS['X_admin'] = $_X_admin;
		
		// リンク先ページ名
		$rel_pages = array_keys($related);
		$rel_pages = array_unique($rel_pages);
		
		// 未作成ページ
		if ($s_page != $whatsdeleted)
		{
			$yetlists = $notyet = array();
			foreach($rel_pages as $_tmp)
			{
				$_tmp = add_bracket($_tmp);
				if (!is_page($_tmp))
				{
					$notyet[] = $_tmp;
				}
			}
			if (file_exists(CACHE_DIR."yetlist.dat"))
			{
				$yetlists = unserialize(join("",file(CACHE_DIR."yetlist.dat")));
			}
			if (isset($yetlists[$page])) {unset($yetlists[$page]);}
			if ($action != "delete" && $notyet) {$yetlists[$page] = $notyet;}
			if ($fp = fopen(CACHE_DIR."yetlist.dat","wb"))
			{
				fputs($fp, serialize($yetlists));
				fclose($fp);
			}
		}

		// 付箋
		if ($fusen_enable_allpage && empty($pwm_plugin_flg['fusen']['convert']))
		{
			require_once(PLUGIN_DIR."fusen.inc.php");
			$fusen_tag = do_plugin_convert("fusen");
			$fusen_tag = str_replace(array(WIKI_NAME_DEF,WIKI_UCD_DEF,'_XOOPS_WIKI_HOST_'),array("","",XOOPS_WIKI_HOST),$fusen_tag);
			$data .= $fusen_tag;
		}

		$data = preg_replace("/".preg_quote("<a href=\"$script?cmd=edit&amp;page=","/")."[^\"]+".preg_quote("\">$_symbol_noexists</a>","/")."/","",$data);
		$data = str_replace($spc[0],$spc[1],strip_tags($data)).join(',',$rel_pages);
		
		// 英数字は半角,カタカナは全角,ひらがなはカタカナに
		if (function_exists("mb_convert_kana"))
		{
			$data = mb_convert_kana($data,'aKCV');
		}
	}
	$data = addslashes(preg_replace("/[\s]+/","",$data));
	//echo $data."<hr>";
	// 新規作成
	if ($action == "insert")
	{
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_plain")." (pgid,plain) VALUES($pgid,'$data');";
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
		
		//リンク先ページ
		foreach ($rel_pages as $rel_page)
		{
			$relid = get_pgid_by_name($rel_page);
			if ($pgid == $relid || !$relid) {continue;}
			$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_rel")." (pgid,relid) VALUES(".$pgid.",".$relid.");";
			$result=$xoopsDB->queryF($query);
			//if (!$result) echo $query."<hr>";
		}
		
		//リンク元ページ
		global $WikiName,$autolink,$nowikiname,$search_non_list,$wiki_common_dirs;
		// $s_pageがAutoLinkの対象となり得る場合
		if ($autolink
			and (preg_match("/^$WikiName$/",$s_page) ? $nowikiname : strlen($s_page) >= $autolink))
		{
			// $s_pageを参照していそうなページに一気に追加
			$search_non_list = 1;
			
			$lookup_page = $s_page;
			// 検索ページ名の共通リンクディレクトリを省略
			if (count($wiki_common_dirs))
			{
				foreach($wiki_common_dirs as $wiki_common_dir)
				{
					if (strpos($lookup_page,$wiki_common_dir) === 0)
					{
						$lookup_page = str_replace($wiki_common_dir,"",$lookup_page);
						if ($autolink > strlen($lookup_page)){$lookup_page = $s_page;}
						break;
					}
				}
			}
			// 検索実行
			$pages = do_search($lookup_page,'AND',TRUE);
			
			foreach ($pages as $_page)
			{
				$refid = get_pgid_by_name($_page);
				if ($pgid == $refid || !$refid) {continue;}
				$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_rel")." (pgid,relid) VALUES(".$refid.",".$pgid.");";
				$result=$xoopsDB->queryF($query);
				//PlainテキストDB 更新予約を設定
				need_update_plaindb(add_bracket($_page));
				// ページHTMLキャッシュを削除
				delete_page_html(add_bracket($_page),"html");
			}
		}
	}
	
	// ページ更新
	elseif ($action == "update")
	{
		$value = "plain='$data'";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_plain")." SET $value WHERE pgid = $pgid;";
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
		
		//リンク先ページ
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_rel")." WHERE pgid = ".$pgid.";";
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
		foreach ($rel_pages as $rel_page)
		{
			$relid = get_pgid_by_name($rel_page);
			if ($pgid == $relid || !$relid) {continue;}
			$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_rel")." (pgid,relid) VALUES(".$pgid.",".$relid.");";
			$result=$xoopsDB->queryF($query);
			//if (!$result) echo $query."<hr>";
		}
	}
	
	// ページ削除
	elseif ($action == "delete")
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_plain")." WHERE pgid = $pgid;";
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
		
		//リンクページ
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_rel")." WHERE pgid = ".$pgid." OR relid = ".$pgid.";";
		$result=$xoopsDB->queryF($query);
	}
	else
		return false;
	
	return true;
}

// attach DB を更新
function attach_db_write($data,$action)
{
	global $xoopsDB,$post,$get,$vars;
	
	$ret = TRUE;
	
	//if (!$pgid = $data['pgid']) return false;
	
	$pgid = (int)$data['pgid'];
	$name = addslashes($data['name']);
	$type = addslashes($data['type']);
	$mtime = (int)$data['mtime'];
	$size = (int)$data['size'];
	// $mode normal=0, isbn=1, thumb=2
	$mode = (preg_match("/^ISBN.*\.(dat|jpg)/",$name))? 1 : ((preg_match("/^\d\d?%/",$name))? 2 : 0);
	$age = (int)$data['status']['age'];
	$count = (int)$data['status']['count'][$age];
	$pass = addslashes($data['status']['pass']);
	$freeze = (int)$data['status']['freeze'];
	$copyright = (int)$data['status']['copyright'];
	$owner = (int)$data['status']['owner'];
	
	// 新規作成
	if ($action == "insert")
	{
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_attach")." (pgid,name,type,mtime,size,mode,count,age,pass,freeze,copyright,owner) VALUES($pgid,'$name','$type',$mtime,$size,'$mode',$count,$age,'$pass',$freeze,$copyright,$owner);";
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
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
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_attach")." SET $value WHERE pgid=$pgid AND name='$name' LIMIT 1;";
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
	}
	
	// ファイル削除
	elseif ($action == "delete")
	{
		$q_name = ($name)? " AND name='{$name}' LIMIT 1" : "";
		
		$ret = array();
		$query = "SELECT name FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_attach")." WHERE pgid = {$pgid}{$q_name};";
		if ($result=$xoopsDB->query($query))
		{
			while($data = mysql_fetch_row($result))
			{
				$ret[] = $data[0];
			}
		}
		if (!$ret) $ret = TRUE;
		
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_attach")." WHERE pgid = {$pgid}{$q_name};";
		
		$result=$xoopsDB->queryF($query);
		//if (!$result) echo $query."<hr>";
	}
	else
		return false;
	
	return $ret;
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

function check_pginfo($page)
{
	global $X_uid,$xoopsDB;
	
	if (!$page) return;
	
	// id取得
	$id = get_pgid_by_name($page);
	
	if ($id) return;
	
	$name = strip_bracket($page);
	
	$name = addslashes($name);
	$buildtime = 0;
	$editedtime = 0;
	
	// 編集権限
	$aids = "&all";
	$gids = "&3&";
	$freeze = 0;
	
	// 閲覧権限
	$unvisible = 0;
	$vaids = "";
	$vgids = "";

	// ページ作成者
	$uid = $X_uid;
	$lastediter = $uid;
	
	// 新規作成
	$query = "insert into ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." (`name`,`buildtime`,`editedtime`,`aids`,`gids`,`vaids`,`vgids`,`lastediter`,`uid`,`freeze`,`unvisible`) values('$name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible);";
	
	$result=$xoopsDB->queryF($query);
	
	return;
	
}

// ページコメント件数取得
function get_pagecomment_count($pgid,$link="",$str="")
{
	global $xoopsDB,$pukiwiki_mid;
	
	$count = xoops_comment_count($pukiwiki_mid,$pgid);
	
	// 20件以上はスレッド表示に
	if (empty($_GET['com_mode']) && $count >= 20) $_GET['com_mode'] = 'thread';
	
	if ($str)
		$str = str_replace('$1',$count,$str);
	if ($link)
	{
		$link = str_replace('$1',$count,$link);
		$count = "<a href=\"$link\">$str</a>";
	}
	return $count;
}

// ページの最終更新時間を更新
function touch_db($page)
{
	global $xoopsDB,$X_uid;
	if ($id = get_pgid_by_name($page))
	{
		clearstatcache();
		$editedtime = filemtime(DATA_DIR.encode($page).".txt");
		$value = "editedtime=$editedtime";
		$value .= ",lastediter=$X_uid";
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET $value WHERE id=$id;";
		$result=$xoopsDB->queryF($query);
		//exit($query);
	}
}
?>