<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: file.php,v 1.9 2003/08/03 13:38:57 nao-pon Exp $
/////////////////////////////////////////////////

// �����������
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

// �ڡ�����¸�ߤ��뤫��
function page_exists($page)
{
	return file_exists(get_filename(encode($page)));
}

// �ڡ����ι������������
function get_filetime($page)
{
	return filemtime(get_filename(encode($page))) - LOCALZONE;
}

// �ڡ����ν���
function page_write($page,$postdata,$notimestamp=NULL)
{
	global $do_backup,$del_backup;

	$page = add_bracket($page);

	$postdata = user_rules_str($postdata);
	
	// ��ʬ�ե�����κ���
	$oldpostdata = is_page($page) ? join('',get_source($page)) : '';
	$diffdata = do_diff($oldpostdata,$postdata);
	file_write(DIFF_DIR,$page,$diffdata);

	// �Хå����åפκ���
	if(is_page($page))
		$oldposttime = filemtime(get_filename(encode($page)));
	else
		$oldposttime = time();

	// �Խ����Ƥ�����񤫤�Ƥ��ʤ��ȥХå����åפ�������?���ʤ��Ǥ���͡�
	if(!$postdata && $del_backup)
		backup_delete(BACKUP_DIR.encode($page).".txt");
	else if($do_backup && is_page($page))
		make_backup(encode($page).".txt",$oldpostdata,$oldposttime);

	// �ե�����ν񤭹���
	file_write(DATA_DIR,$page,$postdata,$notimestamp);
	
	// is_page�Υ���å���򥯥ꥢ���롣
	is_page($page,TRUE);
	
}

// �ե�����ؤν���
// ��4�����ɲ�:�ǽ��������ʤ�=true by nao-pon
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

// �ǽ������ڡ����ι���
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

// �ե�����̾������(���󥳡��ɤ���Ƥ���ɬ��ͭ��)
function get_filename($pagename)
{
	return DATA_DIR.$pagename.".txt";
}

// �ڡ�����¸�ߤ��뤫���ʤ���
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

// �ڡ������Խ���ǽ��
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

// �ڡ�������뤵��Ƥ��뤫
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

		// �����Ԥ������
		if ($X_admin) return false;

		// �������桼����
		if (!$X_uid) return (in_array("3",$gids))? false : true;
		
		//������桼�����ϸ��¥����å�
		
		// ��ʬ����뤷���ڡ���
		if ($arg[1] == $X_uid) return false;
		
		// �桼�������¤����뤫
		if (in_array($X_uid,$aids)) return false;
		
		// ���롼�׸��¤����뤫��
		$X_gids = X_get_groups();
		$gid_match = false;
		foreach ($X_gids as $gid){
			if (in_array($gid,$gids)) {
				$gid_match = true;
				break;
			}
		}
		if ($gid_match) return false;
		
		// �Խ����¤ʤ�
		$_freeze = true;
	} else {
		$_freeze = false;
	}
	return $_freeze;
}

// ���ꤵ�줿�ڡ����ηв����
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

// Last-Modified �إå�
function header_lastmod($page)
{
	global $lastmod;
	
	if($lastmod && is_page($page))
	{
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime(get_filename(encode($page))))." GMT");
	}
}

// ���ڡ���̾�������
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

//�ڡ���������ID������
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

//�ڡ���������̾������
function get_pg_auther_name($page)
{
	$uid = get_pg_auther($page);
	if (!$uid) return "";
	$user = new XoopsUser($uid);
	return $user->getVar("uname");
}

//�ڡ������Խ����¤�����
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

//EXIF�ǡ���������
function get_exif_data($file){
	if (!function_exists('read_exif_data')) return false;
	$exif_data = @read_exif_data($file);
	//$exif_tags .= (isset($exif_data['Make']))? "<hr>- EXIF ���ƥǡ��� -" : "";
	if (isset($exif_data['Make'])) {
		$ret['title'] = "- EXIF ���ƥǡ��� -";
		$ret['�᡼����'] = $exif_data['Make'];
	}
	if (isset($exif_data['Model'])) $ret['�����'] = $exif_data['Model'];
	if (isset($exif_data['DateTimeOriginal'])) $ret['��������'] = $exif_data['DateTimeOriginal'];
	if (isset($exif_data['ExposureTime'])){
		$exif_tmp = explode("/",$exif_data['ExposureTime']);
		if ($exif_tmp[0]) $exif_tmp2 = floor($exif_tmp[1]/$exif_tmp[0]);
		$ret['����å������ԡ���'] = "1/".$exif_tmp2;
	}
	if (isset($exif_data['FNumber'])){
		$exif_tmp = explode("/",$exif_data['FNumber']);
		if ($exif_tmp[1]) $exif_tmp2 = $exif_tmp[0]/$exif_tmp[1];
		$ret['�ʤ���'] = "F".$exif_tmp2;
	}
	if (isset($exif_data['Flash'])){
		if ($exif_data['Flash'] == 0) {$ret['�ե�å���'] = "OFF";}
		elseif ($exif_data['Flash'] == 1) {$ret['�ե�å���'] = "ON";}
		elseif ($exif_data['Flash'] == 5) {$ret['�ե�å���'] = "ȯ��(ȿ�ͤʤ�)";}
		elseif ($exif_data['Flash'] == 7) {$ret['�ե�å���'] = "ȯ��(ȿ�ͤ���)";}
		elseif ($exif_data['Flash'] == 9) {$ret['�ե�å���'] = "���ON";}
		elseif ($exif_data['Flash'] == 16) {$ret['�ե�å���'] = "���OFF";}
		elseif ($exif_data['Flash'] == 24) {$ret['�ե�å���'] = "������(��ȯ��)";}
		elseif ($exif_data['Flash'] == 25) {$ret['�ե�å���'] = "������(ȯ��)";}
		else {$ret['�ե�å���'] = $exif_data['Flash'];}
	}
	return $ret;
}
?>
