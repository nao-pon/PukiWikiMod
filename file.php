<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: file.php,v 1.11 2003/10/13 12:23:28 nao-pon Exp $
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
	return filemtime(get_filename(encode(add_bracket($page)))) - LOCALZONE;
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

	// is_pageのキャッシュをクリアする。
	is_page($page,true);
	
	if(!$timestamp)
		put_lastmodified();

	if($update_exec and $dir == DATA_DIR)
	{
		system($update_exec." > /dev/null &");
	}
	// linkデータベースを更新
	if ($dir === DATA_DIR)
		links_update($page);
}

// 最終更新ページの更新
function put_lastmodified()
{
	global $script,$maxshow,$whatsnew,$date_format,$time_format,$weeklabels,$post,$non_list;
	
	// ページ閲覧権限のキャッシュをクリアー
	get_pg_allow_viewer("",false,true);

	$files = get_existpages(true);
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

			$page_auths = get_pg_allow_viewer(strip_bracket($page));
			$page_auth = "//uid:".$page_auths['owner']."\taid:".$page_auths['user']."\tgid:".$page_auths['group'];

			$putval[$lastmodtime][] = "$page_auth\n-$lastmod - $page";
		}
	}
	
	$cnt = 1;
	krsort($putval);
	$fp = fopen(get_filename(encode($whatsnew)),"w");
	if($fp===FALSE) die_message("cannot write page file ".htmlspecialchars($whatsnew)."<br>maybe permission is not writable or filename is too long");
	flock($fp,LOCK_EX);
	// 閲覧制限する
	fputs($fp,"#unvisible	uid:1	aid:0	gid:0\n");
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
function is_freeze($page,$cache=true)
{
	if ($cache) global $_freeze;
	global $X_uid,$X_admin,$anon_writable;

	if(!is_page($page)) return false;
	if($_freeze === true || $_freeze === false) return $_freeze;
	
	// 閲覧権限をチェック
	if (!check_readable($page,false,false)) return true;
	
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
		$_freeze = ($anon_writable)? false : true;
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

// ページ名の読みを配列に
function get_readings()
{
	global $pagereading_enable, $pagereading_kanji2kana_converter;
	global $pagereading_kanji2kana_encoding, $pagereading_chasen_path;
	global $pagereading_kakasi_path, $pagereading_config_page;

	$pages = get_existpages(true);

	$readings = array();
	foreach ($pages as $page) {
		$page = strip_bracket($page);
		$readings[$page] = '';
	}
	foreach (get_source($pagereading_config_page) as $line) {
		$line = preg_replace('/[\s\r\n]+$/', '', $line);
		if(preg_match('/^-\[\[([^]]+)\]\]\s(.+)$/', $line, $matches)
		   and isset($readings[$matches[1]])) {
			$readings[$matches[1]] = $matches[2];
		}
	}
	if($pagereading_enable) {
		// ChaSen/KAKASI 呼び出しが有効に設定されている場合

		// 読みが不明のページがあるかチェック
		foreach ($readings as $page => $reading) {
			if($reading=='') {
				$unknownPage = TRUE;
				break;
			}
		}
		if($unknownPage) {
			// 読みが不明のページがある場合
			//			$tmpfname = tempnam(CACHE_DIR, 'PageReading');
			$tmpfname = tempnam(CACHE_DIR, 'PageReading');
			$fp = fopen($tmpfname, "w")
				or die_message("cannot write temporary file '$tmpfname'.\n");
			foreach ($readings as $page => $reading) {
				if($reading=='') {
					fputs($fp, mb_convert_encoding("$page\n", $pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
			}
			fclose($fp);

			// ChaSen/KAKASI を実行
			switch($pagereading_kanji2kana_converter) {
			case 'chasen':
			case 'CHASEN':
			case 'Chasen':
			case 'ChaSen':
				if(!file_exists($pagereading_chasen_path)) {
					unlink($tmpfname);
					die_message("CHASEN not found: $pagereading_chasen_path");
				}					
				$fp = popen("$pagereading_chasen_path -F %y $tmpfname", "r");
				if(!$fp) {
					unlink($tmpfname);
					die_message("ChaSen execution failed: $pagereading_chasen_path -F %y $tmpfname");
				}
				break;
			case 'kakasi':
			case 'KAKASI':
			case 'Kakasi':
			case 'KaKaSi':
			case 'kakashi':
			case 'KAKASHI':
			case 'Kakashi':
			case 'KaKaShi':
				if(!file_exists($pagereading_kakasi_path)) {
					unlink($tmpfname);
					die_message("KAKASI not found: $pagereading_kakasi_path");
				}					
				$fp = popen("$pagereading_kakasi_path -kK -HK -JK <$tmpfname", "r");
				if(!$fp) {
					unlink($tmpfname);
					die_message("KAKASI execution failed: $pagereading_kakasi_path -kK -HK -JK <$tmpfname");
				}
				break;
			default:
				die_message("unknown kanji-kana converter: $pagereading_kanji2kana_converter.");
				break;
			}
			foreach ($readings as $page => $reading) {
				if($reading=='') {
					$line = mb_convert_encoding(fgets($fp), SOURCE_ENCODING, $pagereading_kanji2kana_encoding);
					$line = preg_replace('/[\s\r\n]+$/', '', $line);
					$readings[$page] = $line;
				}
			}
			pclose($fp);
			unlink($tmpfname) or die_message("temporary file can not be removed: $tmpfname");
			// 読みでソート
			asort($readings);

			// ページを書き込み
			$body = '';
			foreach ($readings as $page => $reading) {
				$body .= "-[[$page]] $reading\n";
			}
			page_write($pagereading_config_page, $body);
		}
	}

	// 読み不明のページは、そのままページ名を返す (ChaSen/KAKASI 呼
	// び出しが無効に設定されている場合や、ChaSen/KAKASI 呼び出しに
	// 失敗した時の為)
	foreach ($pages as $page) {
		if($readings[$page]=='') {
			$readings[$page] = $page;
		}
	}

	return $readings;
}


// 全ページ名を配列に
function get_existpages($nocheck=false)
{
	$aryret = array();

	if ($dir = @opendir(DATA_DIR))
	{
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			// 閲覧権限
			if ($nocheck || check_readable($page,false,false))
				array_push($aryret,$page);
		}
		closedir($dir);
	}
	
	return $aryret;
}

//ファイル名の一覧を配列に(エンコード済み、拡張子を指定)
function get_existfiles($dir,$ext)
{
	$aryret = array();
	
	$pattern = '^(?:[0-9A-F]{2})+'.preg_quote($ext,'/').'$';
	$dp = @opendir($dir)
		or die_message($dir. ' is not found or not readable.');
	while ($file = readdir($dp)) {
		if (preg_match("/$pattern/",$file)) {
			$aryret[] = $dir.$file;
		}
	}
	closedir($dp);
	return $aryret;
}

//あるページの関連ページを得る
function links_get_related($page)
{
	global $vars,$related;
	static $links = array();
	$page = strip_bracket($page);
	
	if (array_key_exists($page,$links))
	{
		return $links[$page];
	}
	
	if (!$related) $related = array();
	// 可能ならmake_link()で生成した関連ページを取り込む
	$links[$page] = ($page == strip_bracket($vars['page'])) ? $related : array();
	
	// データベースから関連ページを得る
	$links[$page] += links_get_related_db(strip_bracket($vars['page']));
	
	return $links[$page];
}

//ページ作成者IDを得る
function get_pg_auther($page)
{
	$author_uid = 0;
	if (!is_page($page)) return $author_uid;
	
	$string = join('',get_source($page,3));
	$string = preg_replace("/(^|\n)#(freeze|unvisible)([^\n]*)?/","",$string);
	$string = trim($string);
	
	
	if (preg_match("/^\/\/ author:([0-9]+)($|\n)/",$string,$arg))
		$author_uid = $arg[1];

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
	$lines = get_source($page,2);

	$allows['group'] = $allows['user']= "";
	foreach($lines as $line)
	{
		if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$line,$arg)){
			if (!$arg[2]) $arg[2]=0;
			if (!$arg[3]) $arg[3]=0;
			$allows['user'] = $arg[2].",";
			$allows['group'] = $arg[3].",";
			break;
		}
	}
	
	return $allows;
	
}

//ページの閲覧権限を得る
function get_pg_allow_viewer($page, $uppage=true, $clr=false){
	static $cache_page_info = array();
	
	// キャッシュクリアー
	if ($clr)
	{
		$cache_page_info = array();
		return;
	}
	
	// pcoment のコメントページ調整
	if (preg_match("/^\[\[(.*\/)%s\]\]/",PCMT_PAGE,$arg))
	{
		$page = str_replace($arg[1],"",$page);
	}
	
	// キャッシュがあればキャッシュを返す
	if (isset($cache_page_info[$page])) 
		return $cache_page_info[$page];

	$lines = get_source($page,2);

	$allows['owner'] = "";
	$allows['group'] = "3,";
	$allows['user'] = "all";
	foreach($lines as $line)
	{
		if (preg_match("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/",$line,$arg)){
			if (!$arg[2]) $arg[2]=0;
			if (!$arg[3]) $arg[3]=0;
			$allows['owner'] = $arg[1];
			if ($arg[2] !== "all") $allows['user'] = $arg[2].",";
			$allows['group'] = $arg[3].",";
			break;
		}
	}
	if (!$allows['owner'] && $uppage)
	//このページに設定がないので上位ページをみる
	{
		// 上位ページ名を得る
		if (preg_match("/^(.*)\/[^\/]*$/",$page,$arg))
			$uppage_name = $arg[1];
		else
			$uppage_name = "";

		// 上位ページがあればその権限を得る(再帰処理)キャッシュチェックする
		if ($uppage_name)
		{
			if (isset($cache_page_info[$uppage_name]))
				$allows = $cache_page_info[$uppage_name];
			else
				$allows = get_pg_allow_viewer($uppage_name,true);
		}
	}
	// キャッシュを保存
	$cache_page_info[$page] = $allows;
	return $allows;
	
}

//閲覧権限があるかチェックする。
function read_auth($page, $auth_flag=true, $exit_flag=true){
	return check_readable($page, $auth_flag, $exit_flag);
}

//閲覧権限を得る
function get_readable(&$auth)
{
	static $_X_uid, $_X_gids;
	if (!isset($_X_uid))
	{
		global $X_uid;
		$_X_uid = $X_uid;
	}
	if (!isset($_X_gids)) $_X_gids = X_get_groups();

	$ret = false;
	
	$aids = explode(",",$auth['user']);
	$gids = explode(",",$auth['group']);
	
	// 閲覧制限されていない
	if ($auth['owner'] === "" || $auth['user'] == "all") $ret = true;
	
	// 非ログインユーザー
	elseif (!$_X_uid) $ret = (in_array("3",$gids))? true : false;
	
	//ログインユーザーは権限チェック
	
	// 自分で制限したページ
	elseif ($auth['owner'] == $_X_uid) $ret = true;
	
	// ユーザー権限があるか
	elseif (in_array($_X_uid,$aids)) $ret = true;
	
	else
	{
		// グループ権限があるか？
		$gid_match = false;
		foreach ($_X_gids as $gid)
		{
			if (in_array($gid,$gids))
			{
				$gid_match = true;
				break;
			}
		}
		if ($gid_match) $ret = true;
	}
	return $ret;
}

//閲覧することができるかチェックする。
function check_readable($page, $auth_flag=true, $exit_flag=true){
	global $X_admin,$read_auth;
	static $_X_admin,$_read_auth;
	
	if (!isset($_X_admin))
	{
		global $X_admin;
		$_X_admin = $X_admin;
	}
	if (!isset($_read_auth))
	{
		global $read_auth;
		$_read_auth = $read_auth;
	}
	if (!$_read_auth) return true;
	
	$ret = false;
	
	// 管理者はすべてOK
	if ($_X_admin) $ret = true;
	
	else
	{
		$auth = get_pg_allow_viewer(strip_bracket($page),true);
		$ret = get_readable($auth);
	}
	
	return $ret;

}

// ページ情報を削除する
function delete_page_info(&$str)
{
	$str = preg_replace("/(^|\n)(#freeze|#unvisible|\/\/ author:)([^\n]*)?/","",$str);
	$str = preg_replace("/^\n+/","",$str);
	//$str = trim($str);
	return;
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
