<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: file.php,v 1.25 2004/05/20 14:00:16 nao-pon Exp $
/////////////////////////////////////////////////

// �����������
function get_source($page,$row=0)
{	
	global $WikiName;
	$page = add_bracket($page);
	if(page_exists($page))
	{
		if ($row)
		{
			$ret = array();
			$f_name = get_filename(encode($page));
			$fp = fopen($f_name,"r");
			if (!$fp) return file(get_filename(encode($page)));
			while (!feof($fp))
			{
				$ret[] = fgets($fp, 4096);
				$row--;
				if ($row < 1) break;
			}
			fclose($fp);
		}
		else
		{
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
	//return filemtime(get_filename(encode(add_bracket($page)))) - LOCALZONE;
	return filemtime(get_filename(encode(add_bracket($page))));
}

// �ڡ����ν���
function page_write($page,$postdata,$notimestamp=NULL,$aids="",$gids="",$vaids="",$agids="",$freeze="",$unvisible="",$mail_op=array())
{
	global $do_backup,$del_backup;
	global $X_uid,$X_admin,$X_uname,$wiki_mail_sw,$xoopsConfig;
	global $pagereading_config_page,$use_static_url;
	
	// �᡼�륪�ץ�������
	if ($mail_op == "nomail")
		$wiki_mail_sw = 0;
	else
	{
		$mail_mode = (isset($mail_op['mode']))? explode("&",$mail_op['mode']):$mail_mode = array("del","add","all");
		$plugin_title = (isset($mail_op['plugin']))? sprintf(_MD_PUKIWIKI_MAIL_HEAD,$mail_op['plugin'])."\n":"";
		$add_text = (isset($mail_op['text']))? $mail_op['text'] : "";
	}
	
	if ($postdata) $postdata = rtrim($postdata)."\n";

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
	file_write(DATA_DIR,$page,$postdata,$notimestamp,$aids,$gids,$vaids,$agids,$freeze,$unvisible);
	
	// �᡼��������Υ��å�
	$pukiwiki_send_mails = "";
	$pukiwiki_pg_auther_mail = get_pg_auther_mail($page);
	
	$s_page = strip_bracket($page);
	
	// �᡼����������ʤ��ڡ���
	if ($s_page == $pagereading_config_page)
		$wiki_mail_sw = 0;
	
	if ($wiki_mail_sw === 2)
	{
		//̵���
		$pukiwiki_send_mails = $xoopsConfig['adminmail'];
		if ($xoopsConfig['adminmail'] != $pukiwiki_pg_auther_mail)
			$pukiwiki_send_mails .= " ".$pukiwiki_pg_auther_mail;
	}
	elseif ($wiki_mail_sw === 1)
	{
		$pukiwiki_pg_auther = get_pg_auther($page);
		//�����԰ʳ�
		if ($X_admin)
		{
			//�����ͥ�������
			if ($X_uid != $pukiwiki_pg_auther)
			{
				//¾�桼��������ä��ڡ���
				$pukiwiki_send_mails = get_pg_auther_mail($page);
			}
		}
		else
		{
			//����
			$pukiwiki_send_mails = $xoopsConfig['adminmail'];
			if ($X_uid != $pukiwiki_pg_auther && $xoopsConfig['adminmail'] != $pukiwiki_pg_auther_mail)
			{
				//¾�桼��������ä��ڡ���
				$pukiwiki_send_mails .= " ".get_pg_auther_mail($page);
			}
		}
	}

	if ($pukiwiki_send_mails) {
		// �᡼������ by nao-pon
		global $xoopsConfig;

		 //- �᡼���Ѻ�ʬ�ǡ����κ���
		$mail_add = $mail_del = "";
		$diffdata_ar = array();
		$diffdata_ar=split("\n",$diffdata);
		foreach($diffdata_ar as $diffdata_line){
			if (ereg("^\+(.*)",$diffdata_line,$regs)){
				$mail_add .= $regs[1]."\n";
			}
			if (ereg("^\-(.*)",$diffdata_line,$regs)){
				$mail_del .= $regs[1]."\n";
			}
		}
		$mail_body = _MD_PUKIWIKI_MAIL_FIRST."\n";
		if ($use_static_url)
			$mail_body .= _MD_PUKIWIKI_MAIL_URL.XOOPS_WIKI_URL."/".get_pgid_by_name($page).".html";
		else
			$mail_body .= _MD_PUKIWIKI_MAIL_URL.XOOPS_WIKI_URL."/?".rawurlencode($s_page)."\n";
		$mail_body .= _MD_PUKIWIKI_MAIL_PAGENAME.$s_page."\n";
		$mail_body .= _MD_PUKIWIKI_MAIL_POSTER.$X_uname."\n";
		$mail_body .= "IP:".$_SERVER["REMOTE_ADDR"]."\n";
		$mail_body .= $plugin_title;
		if (in_array("del",$mail_mode))
		{
			$mail_body .= _MD_PUKIWIKI_MAIL_DEL_LINES."\n";
			$mail_body .= $mail_del;
		}
		if (in_array("add",$mail_mode))
		{
			$mail_body .= _MD_PUKIWIKI_MAIL_ADD_LINES."\n";
			$mail_body .= $mail_add;
		}
		if (in_array("all",$mail_mode))
		{
			$mail_body .= _MD_PUKIWIKI_MAIL_ALL_LINES."\n";
			$mail_body .= $postdata;
		}
		$mail_body .= $add_text;
		$mail_body .= _MD_PUKIWIKI_MAIL_FOOT."\n";
		$xoopsMailer =& getMailer();
		foreach(explode(" ",$pukiwiki_send_mails) as $pukiwiki_sendto_mail)
		{
			$xoopsMailer->useMail();
			$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
			$xoopsMailer->setFromName($xoopsConfig['sitename']);
			$xoopsMailer->setSubject(_MD_PUKIWIKI_MAIL_SUBJECT.$s_page);
			$xoopsMailer->setBody($mail_body);
			$xoopsMailer->setToEmails($pukiwiki_sendto_mail);
			$xoopsMailer->send();
			$xoopsMailer->reset();
		}
		//�᡼�����������ޤ� by nao-pon
	}
}

// �ե�����ؤν���
// ��4�����ɲ�:�ǽ��������ʤ�=true by nao-pon
function file_write($dir,$page,$str,$notimestamp=NULL,$aids="",$gids="",$vaids="",$agids="",$freeze="",$unvisible="")
{
	global $post,$update_exec,$autolink;
	
	if (is_null($notimestamp)) $notimestamp=$post['notimestamp'];
	
	$timestamp = FALSE;

	if($str == "")
	{
		@unlink($dir.encode($page).".txt");
		$action = "delete";
		put_recentdeleted(strip_bracket($page));
	}
	else
	{
		$str = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$str);
		
		$action = (is_page($page))? "update" : "insert";
		
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

	// is_page�Υ���å���򥯥ꥢ���롣
	is_page($page,true);
	
	if(!$timestamp)
		put_lastmodified();

	if($update_exec and $dir == DATA_DIR)
	{
		system($update_exec." > /dev/null &");
	}
	
	if ($dir === DATA_DIR)
	{
		// link�ǡ����١����򹹿�
		links_update($page);
		
		// pginfo DB�򹹿�
		pginfo_db_write($page,$action,$aids,$gids,$vaids,$agids,$freeze,$unvisible);
		
		// �ڡ���HTML����å����RSS����å������
		delete_page_html($page);
		
		// for autolink
		if ($autolink)
		{
			list($pattern,$forceignorelist) = get_autolink_pattern(get_existpages());
			
			$fp = fopen(CACHE_DIR.'autolink.dat','w')
				or die_message('cannot write autolink file '.CACHE_DIR.'/autolink.dat<br />maybe permission is not writable');
			flock($fp,LOCK_EX);
			fputs($fp,$pattern."\n");
			fputs($fp,join("\t",$forceignorelist));
			flock($fp,LOCK_UN);
			fclose($fp);
		}
		
		if (!$notimestamp && !$unvisible)
		{
			// TrackBack Ping�ѥե��������
			// pcomment�ѡ��ƥڡ���̾�򥻥å�
			$page = ($post['refer'])? $post['refer'] : $page;
			$s_page = strip_bracket($page);
			// : �ǻϤޤ�ڡ�����Ping���Ǥ��ʤ�
			if ($s_page[0] !== ":")
				tb_send($page);
		}
	}	
}

// �������ڡ����ι���
function put_recentdeleted($page)
{
	global $whatsdeleted,$maxshow_deleted,$unvisible_deleted;
	
	if ($maxshow_deleted == 0 || $page == $whatsdeleted)
	{
		return;
	}
	// update RecentDeleted
	$lines = array();
	foreach (get_source($whatsdeleted) as $line)
	{
		if (preg_match('/^-(.+) - (\[\[.+\]\])$/',$line,$matches))
		{
			$lines[$matches[2]] = $line;
		}
	}
	$_page = "[[$page]]";
	if (array_key_exists($_page,$lines))
	{
		unset($lines[$_page]);
	}
	array_unshift($lines,'-'.format_date(UTIME)." - $_page\n");
	$lines = array_splice($lines,0,$maxshow_deleted);
	
	$postdata = "#freeze\tuid:1\taid:0\tgid:0\n";
	if ($unvisible_deleted) $postdata .= "#unvisible\tuid:1\taid:0\tgid:0\n";
	$postdata .= "// author:1\n";
	$postdata .= join('',$lines);
	$postdata .= "#norelated\n";
	if ($unvisible_deleted)
		file_write(DATA_DIR,$whatsdeleted,$postdata,0,"0","0","0","0","1","1");
	else
		file_write(DATA_DIR,$whatsdeleted,$postdata,0,"0","0","","","1","0");
}

// �ǽ������ڡ����ι���
function put_lastmodified()
{
	// �ǽ������ڡ�����DB��������褦�ˤ����Τ�ɬ�פʤ��ʤä�
	return;
}

// �ե�����̾������(���󥳡��ɤ���Ƥ���ɬ��ͭ��)
function get_filename($pagename)
{
	return DATA_DIR.$pagename.".txt";
}

// �ڡ�����¸�ߤ��뤫���ʤ���
function is_page($page,$reload=FALSE)
{
	return file_exists(get_filename(encode(add_bracket($page))));
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
	elseif(in_array($page,$cantedit))
		$_editable = false;
	else
		$_editable = true;
	
	return $_editable;
}

// �ڡ�������뤵��Ƥ��뤫
function is_freeze($page,$cache=true)
{
	if ($cache) global $_freeze;
	global $X_uid,$X_admin,$anon_writable;

	if(!is_page($page)) return false;
	if($_freeze === true || $_freeze === false) return $_freeze;
	
	// �������¤�����å�
	if (!check_readable($page,false,false)) return true;
	
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
		$_freeze = ($anon_writable)? false : true;
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
		$_pg_passage[$page]["label"] = get_passage($pgdt);
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

// �ڡ���̾���ɤߤ������
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
		// ChaSen/KAKASI �ƤӽФ���ͭ�������ꤵ��Ƥ�����

		// �ɤߤ������Υڡ��������뤫�����å�
		foreach ($readings as $page => $reading) {
			if($reading=='') {
				$unknownPage = TRUE;
				break;
			}
		}
		if($unknownPage) {
			// �ɤߤ������Υڡ�����������
			//			$tmpfname = tempnam(CACHE_DIR, 'PageReading');
			$tmpfname = tempnam(CACHE_DIR, 'PageReading');
			$fp = fopen($tmpfname, "w")
				or die_message("cannot write temporary file '$tmpfname'.\n");
			foreach ($readings as $page => $reading) {
				if($reading=='')
				{
					$s_page = strip_bracket($page);
					//�ڡ���̾���ֿ�����-�פ����ξ��ϡ�*(**)�Ԥ�������Ƥߤ�
					if (preg_match("/^(.*\/)?[0-9\-]+$/",$s_page,$f_name))
						$s_page = $f_name[1].get_heading($s_page);
					fputs($fp, mb_convert_encoding($s_page."\n", $pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
			}
			fclose($fp);

			// ChaSen/KAKASI ��¹�
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
			// �ɤߤǥ�����
			asort($readings);

			// �ڡ�����񤭹���
			$body = '';
			foreach ($readings as $page => $reading) {
				$body .= "-[[$page]] $reading\n";
			}
			page_write($pagereading_config_page, $body);
		}
	}

	// �ɤ������Υڡ����ϡ����Τޤޥڡ���̾���֤� (ChaSen/KAKASI ��
	// �ӽФ���̵�������ꤵ��Ƥ�����䡢ChaSen/KAKASI �ƤӽФ���
	// ���Ԥ������ΰ�)
	foreach ($pages as $page) {
		if($readings[$page]=='') {
			$readings[$page] = $page;
		}
	}

	return $readings;
}

// ���ڡ���̾�������
function get_existpages($nocheck=false,$page="",$limit=0,$order="",$nolisting=false)
{
	// �̾��DB�Ǥش��ꤲ
	if (!is_string($nocheck) || $nocheck == DATA_DIR)
		return get_existpages_db($nocheck,$page,$limit,$order,$nolisting);
	
	// PukiWiki 1.4 �ߴ���
	$dir = $nocheck;
	$ext = ($page)? $page : ".txt";
	$aryret = array();
	
	$pattern = '^((?:[0-9A-F]{2})+)';
	if ($ext != '')
	{
		$pattern .= preg_quote($ext,'/').'$';
	}
	$dp = @opendir($dir)
		or die_message($dir. ' is not found or not readable.');
	while ($file = readdir($dp))
	{
		if (preg_match("/$pattern/",$file,$matches))
		{
			$aryret[$file] = add_bracket(decode($matches[1]));
		}
	}
	closedir($dp);
	return $aryret;
}

//�ե�����̾�ΰ����������(���󥳡��ɺѤߡ���ĥ�Ҥ����)
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

//����ڡ����δ�Ϣ�ڡ�����������
function links_get_related_count($page)
{
	global $non_list;
	$links = links_get_related($page);
	$_links = array();
	foreach ($links as $_page=>$lastmod)
	{
		if (preg_match("/$non_list/",$_page))
		{
			continue;
		}
		$_links[$_page] = $lastmod;
	}
	return count($_links);
}

//����ڡ����δ�Ϣ�ڡ���������
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
	// ��ǽ�ʤ�make_link()������������Ϣ�ڡ����������
	$links[$page] = ($page == strip_bracket($vars['page'])) ? $related : array();
	
	// �ǡ����١��������Ϣ�ڡ���������
	//$links[$page] += links_get_related_db(strip_bracket($vars['page']));
	$links[$page] += links_get_related_db($page);
	
	return $links[$page];
}

//�ڡ���������ID������
function get_pg_auther($page)
{
	static $get_pg_auther = array();
	if (isset($get_pg_auther[$page]))
		return $get_pg_auther[$page];
	
	$author_uid = 0;
	if (!is_page($page)) return $author_uid;
	
	$string = join('',get_source($page,3));
	$string = preg_replace("/(^|\n)#(freeze|unvisible)([^\n]*)?/","",$string);
	$string = trim($string);
	
	if (preg_match("/^\/\/ author:([0-9]+)($|\n)/",$string,$arg))
		$author_uid = $arg[1];
		
	$get_pg_auther[$page] = $author_uid;
	return $get_pg_auther[$page];
}

//�ڡ���������̾������
function get_pg_auther_name($page)
{
	global $no_name;
	
	if (!is_page($page)) return "";
	
	$uid = get_pg_auther($page);
	if (!$uid) return "$no_name";
	
	$user = new XoopsUser($uid);
	return $user->getVar("uname");
}

// �ڡ��������Ԥ�E-mail���ɥ쥹������
function get_pg_auther_mail($page)
{
	$uname = get_pg_auther_name($page);
	//config�ڡ����Υ����å�
	$obj = new Config('user/'.$uname);
	if ($obj->read())
	{
		$mail = $obj->get("Mail");
		if (preg_match("/������|no/i",$mail[0]))
			return "";
	}
	
	$uid = get_pg_auther($page);
	if (!$uid) return "";
	
	$user = new XoopsUser($uid);
	//strip_tags:XOOPS 1.3 ���к�
	return strip_tags($user->getVar("email"));
}

//�Խ����·Ѿ������åȤ���Ƥ����̥ڡ��������������
function get_freezed_uppage($page)
//�Ѿ����� #newfreeze
//��������� 0:�ڡ���̾, 1:�����ʡ�, 2:���ĥ桼����(����), 3:���ĥ��롼��(����), 4:�Խ����¤��ʤ�(0 or 1)
{
	$ret = array("",0,null,null,0);
	$page = strip_bracket($page);
	// ��̥ڡ���̾������
	if (preg_match("/^(.*)\/[^\/]*$/",$page,$arg))
		$uppage_name = $arg[1];
	else
		return $ret;
	
	$lines = get_source($uppage_name);
	
	if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$lines[0],$arg) && preg_match("/(^|\n)#newfreeze(\n|$)/",join('',$lines)))
	{
		$owner = 1;
		$gids = $aids = array();
		if (isset($arg[1])) $owner = $arg[1];
		if (isset($arg[2]))
		{
			$arg[2] .= ",$owner";
			$aids = explode(",",$arg[2].",");
		}
		if (isset($arg[3])) $gids = explode(",",$arg[3].",");
		//$aids[] = $owner;
		$uppage_name = add_bracket($uppage_name);
		return array($uppage_name,$owner,$aids,$gids,is_freeze($uppage_name,false));
	}
	else
		return get_freezed_uppage($uppage_name);
	
}

//�ڡ������Խ����¤�����
function get_pg_allow_editer($page){
	$lines = get_source($page,2);

	$allows['group'] = $allows['user']= $allows['uid'] ="";
	foreach($lines as $line)
	{
		if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$line,$arg)){
			if (!$arg[2]) $arg[2]=0;
			if (!$arg[3]) $arg[3]=0;
			$allows['user'] = $arg[2].",";
			$allows['group'] = $arg[3].",";
			if (!empty($arg[1])) $allows['uid'] = $arg[1];
			break;
		}
	}
	
	return $allows;
	
}

//�ڡ����α������¤�����
function get_pg_allow_viewer($page, $uppage=true, $clr=false){
	static $cache_page_info = array();
	if (!$uppage && !$clr) //����å���򥯥ꥢ��
		get_pg_allow_viewer("",true,true);
		
	// ����å��奯�ꥢ��
	if ($clr)
	{
		$cache_page_info = array();
		return;
	}
	
	// pcoment �Υ����ȥڡ���Ĵ��
	if (preg_match("/^\[\[(.*\/)%s\]\]/",PCMT_PAGE,$arg))
	{
		$page = str_replace($arg[1],"",$page);
	}
	
	// ����å��夬����Х���å�����֤�
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
	//���Υڡ��������꤬�ʤ��ΤǾ�̥ڡ�����ߤ�
	{
		// ��̥ڡ���̾������
		if (preg_match("/^(.*)\/[^\/]*$/",$page,$arg))
			$uppage_name = $arg[1];
		else
			$uppage_name = "";

		// ��̥ڡ���������Ф��θ��¤�����(�Ƶ�����)����å�������å�����
		if ($uppage_name)
		{
			if (isset($cache_page_info[$uppage_name]))
				$allows = $cache_page_info[$uppage_name];
			else
				$allows = get_pg_allow_viewer($uppage_name,true);
		}
	}
	// ����å������¸
	if ($uppage) $cache_page_info[$page] = $allows;
	return $allows;
	
}

//�������¤����뤫�����å����롣
function read_auth($page, $auth_flag=true, $exit_flag=true){
	return check_readable($page, $auth_flag, $exit_flag);
}

// PukiWiki 1.4 �ߴ���
// �Խ����¥����å�
function edit_auth($page, $auth_flag=true, $exit_flag=true)
{
	return (!is_freeze($page));
}

//�������¤�����
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
	
	// �������¤���Ƥ��ʤ�
	if ($auth['owner'] === "" || $auth['user'] == "all") $ret = true;
	
	// �������桼����
	elseif (!$_X_uid) $ret = (in_array("3",$gids))? true : false;
	
	//������桼�����ϸ��¥����å�
	
	// ��ʬ�����¤����ڡ���
	elseif ($auth['owner'] == $_X_uid) $ret = true;
	
	// �桼�������¤����뤫
	elseif (in_array($_X_uid,$aids)) $ret = true;
	
	else
	{
		// ���롼�׸��¤����뤫��
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

// �Խ��Բ�ǽ�ʥڡ������Խ����褦�Ȥ����Ȥ�
function check_editable($page,$auth_flag=TRUE,$exit_flag=TRUE)
{
	global $script,$_title_cannotedit,$_msg_unfreeze;
	
	if (edit_auth($page,$auth_flag,$exit_flag) and is_editable($page))
	{
		return TRUE;
	}
	//if (!$exit_flag)
	//{
		return FALSE;
	//}
}
//�������뤳�Ȥ��Ǥ��뤫�����å����롣
function check_readable($page, $auth_flag=true, $exit_flag=true){
	global $X_admin,$read_auth;
	static $_X_admin,$_read_auth;
	static $readable = array();
	
	$page = strip_bracket($page);
	
	// ����å�����֤�
	if (!$auth_flag && !$exit_flag && isset($readable[$page])) return $readable[$page];
	
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
	if (!$_read_auth)
	{
		// ����å���
		$readable[$page] = true;
		return true;
	}
	$ret = false;
	
	// �����ԤϤ��٤�OK
	if ($_X_admin) $ret = true;
	
	else
	{
		$auth = get_pg_allow_viewer($page,true);
		$ret = get_readable($auth);
	}
	
	// ����å���
	$readable[$page] = $ret;
	
	return $ret;

}

// �ڡ��������������
function delete_page_info(&$str)
{
	$str = preg_replace("/(^|\n)(#freeze|#unvisible|\/\/ author:)([^\n]*)?/","",$str);
	$str = preg_replace("/^\n+/","",$str);
	//$str = trim($str);
	return;
}

//�ڡ���̾����ǽ�θ��Ф�������
function get_heading($page)
{
	global $xoopsDB;
	$page = addslashes(strip_bracket($page));
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE name='$page' LIMIT 1;";
	$res = $xoopsDB->query($query);
	if (!$res) return "";
	$ret = mysql_fetch_row($res);
	return $ret[12];
}

//�ڡ���̾����ǽ�θ��Ф�������(�ե����뤫��)
function get_heading_init($page)
{
	global $nowikiname;
	
	$_body = get_source($page);
	if (!$_body) return;
	
	$_nowikiname = $nowikiname;
	$nowikiname = 1;

	$s_page = strip_bracket($page);
	$first_line = "";
	foreach($_body as $line){
		if (!$first_line && preg_match("/^(?!(\/\/|#|\n))/",$line))
		{
			$first_line = $line;
		}
		if (preg_match("/(?:^|\|}?)\*{1,6}([^\|]*)/",$line,$reg))
		{
			$reg[1] = preg_replace("/->$/","",rtrim($reg[1]));
			$ret = trim(htmlspecialchars(strip_htmltag(make_link($reg[1],add_bracket($page)))));
			//$ret = trim(strip_htmltag(make_link(htmlspecialchars($reg[1]),add_bracket($page))));
			$nowikiname = $_nowikiname;
			return $ret;
			break;
		}
	}
	if (!$first_line) $first_line = str_replace("/","",substr($s_page,strrpos($s_page,"/")));
	$ret = trim(htmlspecialchars(strip_htmltag(make_link($first_line,add_bracket($page)))));
	//$ret = trim(strip_htmltag(make_link(htmlspecialchars($first_line),add_bracket($page))));
	$nowikiname = $_nowikiname;
	return $ret;
}

// ����ڡ����Υ���С��ȸ��HTML����å���ե������RSS����å������
function delete_page_html($page)
{
	global $defaultpage, $post;
	$pages = array();
	$rsss = array();
	// �������ڡ���
	$pages[] = $page;
	$pages[] = $defaultpage; //�ȥåץڡ���
	$rss[] = '';//�ǥե����
	$_page = strip_bracket($page);
	while(preg_match("/(.+)\/[^\/]+/",$_page,$match))
	{
		//�峬�ؤΥڡ���
		$_page = $match[1];
		$pages[] = add_bracket($_page);
		$rss[] = $_page;
	}
	//�ƤӽФ���ȤΥڡ���
	$pages[] = add_bracket($post['refer']);
	
	foreach($pages as $del_page)
	{
		//echo $del_page."<br>";
		$filename = PAGE_CACHE_DIR.encode($del_page).".txt";
		//echo $filename."<br>";
		if (file_exists($filename)) unlink($filename);
	}
	
	//RSS����å���
	foreach($rss as $del_page)
	{
		$filename = CACHE_DIR.encode($del_page).".rss10";
		if (file_exists($filename)) unlink($filename);
		$filename = CACHE_DIR.encode($del_page).".rss20";
		if (file_exists($filename)) unlink($filename);
		$filename = CACHE_DIR.encode($del_page).".rss21";
		if (file_exists($filename)) unlink($filename);
	}
	
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
