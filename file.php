<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: file.php,v 1.9 2003/08/03 13:38:57 nao-pon Exp $
/////////////////////////////////////////////////

// ソースを取得
function get_source($page,$row=0)
{	
	global $WikiName;
	$page = add_bracket($page);
	if(page_exists($page)) {
		if ($row){
			$ret = array();
			$f_name = get_filename(encode($page));
			$fp = fopen($f_name,"r");
			if (!$fp) return file(get_filename(encode($page)));
			while (!feof($fp)) {
				$ret[] = fgets($fp, 4096);
				$row--;
				if ($row < 1) break;
			}
			fclose($fp);
		} else {
			$ret = file(get_filename(encode($page)));
		}
		$ret = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$ret);
		return $ret;
  }
  return array();
}

// ページが存在するか？
function page_exists($page)
{
	return file_exists(get_filename(encode($page)));
}

// ページの更新時刻を得る
function get_filetime($page)
{
	return filemtime(get_filename(encode($page))) - LOCALZONE;
}

// ページの出力
function page_write($page,$postdata,$notimestamp=NULL)
{
	global $do_backup,$del_backup;

	$page = add_bracket($page);

	$postdata = user_rules_str($postdata);
	
	// 差分ファイルの作成
	$oldpostdata = is_page($page) ? join('',get_source($page)) : '';
	$diffdata = do_diff($oldpostdata,$postdata);
	file_write(DIFF_DIR,$page,$diffdata);

	// バックアップの作成
	if(is_page($page))
		$oldposttime = filemtime(get_filename(encode($page)));
	else
		$oldposttime = time();

	// 編集内容が何も書かれていないとバックアップも削除する?しないですよね。
	if(!$postdata && $del_backup)
		backup_delete(BACKUP_DIR.encode($page).".txt");
	else if($do_backup && is_page($page))
		make_backup(encode($page).".txt",$oldpostdata,$oldposttime);

	// ファイルの書き込み
	file_write(DATA_DIR,$page,$postdata,$notimestamp);
	
	// is_pageのキャッシュをクリアする。
	is_page($page,TRUE);
	
}

// ファイルへの出力
// 第4引数追加:最終更新しない=true by nao-pon
function file_write($dir,$page,$str,$notimestamp=NULL)
{
	global $post,$update_exec;
	
	if (is_null($notimestamp)) $notimestamp=$post['notimestamp'];
	
	$timestamp = FALSE;

	if($str == "")
	{
		@unlink($dir.encode($page).".txt");
	}
	else
	{
		$str = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$str);
		
		if($notimestamp && is_page($page))
		{
			$timestamp = @filemtime($dir.encode($page).".txt");
		}
		$fp = fopen($dir.encode($page).".txt","w");
		if($fp===FALSE) die_message("cannot write page file or diff file or other".htmlspecialchars($page)."<br>maybe permission is not writable or filename is too long");
		while(!flock($fp,LOCK_EX));
		fputs($fp,$str);
		flock($fp,LOCK_UN);
		fclose($fp);
		if($timestamp)
			touch($dir.encode($page).".txt",$timestamp);
	}
	
	if(!$timestamp)
		put_lastmodified();

	if($update_exec and $dir == DATA_DIR)
	{
		system($update_exec." > /dev/null &");
	}
}

// 最終更新ページの更新
function put_lastmodified()
{
	global $script,$maxshow,$whatsnew,$date_format,$time_format,$weeklabels,$post,$non_list;

	$files = get_existpages();
	foreach($files as $page) {
		if($page == $whatsnew) continue;
		if(preg_match("/$non_list/",$page)) continue;

		if(file_exists(get_filename(encode($page))))
			{
			$page_url = rawurlencode($page);
			$lastmodtime = filemtime(get_filename(encode($page)));
			$lastmod = date($date_format,$lastmodtime)
				 . " (" . $weeklabels[date("w",$lastmodtime)] . ") "
				 . date($time_format,$lastmodtime);
			$putval[$lastmodtime][] = "-$lastmod - $page";
		}
	}
	
	$cnt = 1;
	krsort($putval);
	$fp = fopen(get_filename(encode($whatsnew)),"w");
	if($fp===FALSE) die_message("cannot write page file ".htmlspecialchars($whatsnew)."<br>maybe permission is not writable or filename is too long");
	flock($fp,LOCK_EX);
	foreach($putval as $pages)
	{
		foreach($pages as $page)
		{
			fputs($fp,$page."\n");
			$cnt++;
			if($cnt > $maxshow) break;
		}
		if($cnt > $maxshow) break;
	}
	flock($fp,LOCK_UN);
	fclose($fp);
}

// ファイル名を得る(エンコードされている必要有り)
function get_filename($pagename)
{
	return DATA_DIR.$pagename.".txt";
}

// ページが存在するかしないか
function is_page($page,$reload=false)
{
	global $InterWikiName,$_ispage;
	
	$page = add_bracket($page);
	if(($_ispage[$page] === true || $_ispage[$page] === false) && !$reload) return $_ispage[$page];

	if(preg_match("/($InterWikiName)/",$page))
		$_ispage[$page] = false;
	else if(!page_exists($page))
		$_ispage[$page] = false;
	else
		$_ispage[$page] = true;
	
	return $_ispage[$page];
}

// ページが編集可能か
function is_editable($page)
{
	global $BracketName,$WikiName,$InterWikiName,$cantedit,$_editable;

	if($_editable === true || $_editable === false) return $_editable;

	if(preg_match("/^$InterWikiName$/",$page))
		$_editable = false;
	elseif(!preg_match("/^$BracketName$/",$page) && !preg_match("/^$WikiName$/",$page))
		$_editable = false;
	else if(in_array($page,$cantedit))
		$_editable = false;
	else
		$_editable = true;
	
	return $_editable;
}

// ページが凍結されているか
function is_freeze($page)
{
	global $_freeze,$X_uid,$X_admin;

	if(!is_page($page)) return false;
	if($_freeze === true || $_freeze === false) return $_freeze;

	$lines = get_source($page,1);

	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$lines[0],$arg)){
		$gids = $aids = array();
		if ($arg[2]) $aids = explode(",",$arg[2].",");
		if ($arg[3]) $gids = explode(",",$arg[3].",");

		// 管理者は凍結解除
		if ($X_admin) return false;

		// 非ログインユーザー
		if (!$X_uid) return (in_array("3",$gids))? false : true;
		
		//ログインユーザーは権限チェック
		
		// 自分で凍結したページ
		if ($arg[1] == $X_uid) return false;
		
		// ユーザー権限があるか
		if (in_array($X_uid,$aids)) return false;
		
		// グループ権限があるか？
		$X_gids = X_get_groups();
		$gid_match = false;
		foreach ($X_gids as $gid){
			if (in_array($gid,$gids)) {
				$gid_match = true;
				break;
			}
		}
		if ($gid_match) return false;
		
		// 編集権限なし
		$_freeze = true;
	} else {
		$_freeze = false;
	}
	return $_freeze;
}

// 指定されたページの経過時刻
function get_pg_passage($page,$sw=true)
{
	global $_pg_passage,$show_passage;

	if(!$show_passage) return "";

	if(isset($_pg_passage[$page]))
	{
		if($sw)
			return $_pg_passage[$page]["str"];
		else
			return $_pg_passage[$page]["label"];
	}
	if($pgdt = @filemtime(get_filename(encode($page))))
	{
		$pgdt = UTIME - $pgdt;
		if(ceil($pgdt / 60) < 60)
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60)."m)";
		else if(ceil($pgdt / 60 / 60) < 24)
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60 / 60)."h)";
		else
			$_pg_passage[$page]["label"] = "(".ceil($pgdt / 60 / 60 / 24)."d)";
		
		$_pg_passage[$page]["str"] = "<small>".$_pg_passage[$page]["label"]."</small>";
	}
	else
	{
		$_pg_passage[$page]["label"] = "";
		$_pg_passage[$page]["str"] = "";
	}

	if($sw)
		return $_pg_passage[$page]["str"];
	else
		return $_pg_passage[$page]["label"];
}

// Last-Modified ヘッダ
function header_lastmod($page)
{
	global $lastmod;
	
	if($lastmod && is_page($page))
	{
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime(get_filename(encode($page))))." GMT");
	}
}

// 全ページ名を配列に
function get_existpages()
{
	$aryret = array();

	if ($dir = @opendir(DATA_DIR))
	{
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			array_push($aryret,$page);
		}
		closedir($dir);
	}
	
	return $aryret;
}

//ページ作成者IDを得る
function get_pg_auther($page)
{
	$lines = get_source($page,2);
	
	$author_uid = 0;
	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$lines[0],$arg)){
		if (preg_match("/^\/\/ author:([0-9]+)\n/",$lines[1],$arg))
			$author_uid = $arg[1];
	} else {
		if (preg_match("/^\/\/ author:([0-9]+)\n/",$lines[0],$arg))
			$author_uid = $arg[1];
	}

	return $author_uid;
}

//ページ作成者名を得る
function get_pg_auther_name($page)
{
	$uid = get_pg_auther($page);
	if (!$uid) return "";
	$user = new XoopsUser($uid);
	return $user->getVar("uname");
}

//ページの編集権限を得る
function get_pg_allow_editer($page){
	$lines = get_source($page,1);

	$allows['group'] = $allows['user']= "";
	
	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$lines[0],$arg)){
		if (!$arg[2]) $arg[2]=0;
		if (!$arg[3]) $arg[3]=0;
		$allows['user'] = $arg[2].",";
		$allows['group'] = $arg[3].",";
		//echo "this!".$arg[3];
	}
	
	return $allows;
	
}

//EXIFデータを得る
function get_exif_data($file){
	if (!function_exists('read_exif_data')) return false;
	$exif_data = @read_exif_data($file);
	//$exif_tags .= (isset($exif_data['Make']))? "<hr>- EXIF 撮影データ -" : "";
	if (isset($exif_data['Make'])) {
		$ret['title'] = "- EXIF 撮影データ -";
		$ret['メーカー'] = $exif_data['Make'];
	}
	if (isset($exif_data['Model'])) $ret['カメラ'] = $exif_data['Model'];
	if (isset($exif_data['DateTimeOriginal'])) $ret['撮影日時'] = $exif_data['DateTimeOriginal'];
	if (isset($exif_data['ExposureTime'])){
		$exif_tmp = explode("/",$exif_data['ExposureTime']);
		if ($exif_tmp[0]) $exif_tmp2 = floor($exif_tmp[1]/$exif_tmp[0]);
		$ret['シャッタースピード'] = "1/".$exif_tmp2;
	}
	if (isset($exif_data['FNumber'])){
		$exif_tmp = explode("/",$exif_data['FNumber']);
		if ($exif_tmp[1]) $exif_tmp2 = $exif_tmp[0]/$exif_tmp[1];
		$ret['絞り値'] = "F".$exif_tmp2;
	}
	if (isset($exif_data['Flash'])){
		if ($exif_data['Flash'] == 0) {$ret['フラッシュ'] = "OFF";}
		elseif ($exif_data['Flash'] == 1) {$ret['フラッシュ'] = "ON";}
		elseif ($exif_data['Flash'] == 5) {$ret['フラッシュ'] = "発光(反射なし)";}
		elseif ($exif_data['Flash'] == 7) {$ret['フラッシュ'] = "発光(反射あり)";}
		elseif ($exif_data['Flash'] == 9) {$ret['フラッシュ'] = "常時ON";}
		elseif ($exif_data['Flash'] == 16) {$ret['フラッシュ'] = "常時OFF";}
		elseif ($exif_data['Flash'] == 24) {$ret['フラッシュ'] = "オート(非発光)";}
		elseif ($exif_data['Flash'] == 25) {$ret['フラッシュ'] = "オート(発光)";}
		else {$ret['フラッシュ'] = $exif_data['Flash'];}
	}
	return $ret;
}
?>
