<?php
// $Id: pukiwiki_new.php,v 1.5 2003/10/13 12:23:28 nao-pon Exp $
function b_pukiwiki_new_show($option) {

	//ɽ��������
	$show_num = 10;
	
	//�ǿ������Υڡ���̾
	$puki_new_name = "RecentChanges";

	$block = array();
	// �ץ����ե������ɤ߹���
	$pukiwiki_path = XOOPS_ROOT_PATH."/modules/pukiwiki/";

	$postdata = xb_get_source($puki_new_name);

	$block['title'] = _MI_PUKIWIKI_BTITLE;
	$block['content'] = "<small>";
	$d = '';
	$count = 0;
	$i = 1;
	while ($count < $show_num){
		if (!isset($postdata[$i]))
			break;

		list($auth['owner'],$auth['user'],$auth['group']) = split("\t",substr($postdata[$i],3));
		$auth = preg_replace("/^.*:/","",$auth);

		if (xb_get_readable($auth)) //�����å��ؿ�
		{
			$show_line = split(" ",$postdata[$i+1]);
			if($d != substr($show_line[0],1)){
				$block['content'] .= "&nbsp;<strong>".substr($show_line[0],1)."</strong><br />";
				$d = substr($show_line[0],1);
			}
			$block['content'] .= "&nbsp;&nbsp;<strong><big>&middot;</big></strong>&nbsp;".xb_make_link(trim($show_line[4]))."<br />";
			$count++;
		}
		$i = $i + 2;
	}
	$block['content'] .= "</small>";
	return $block;
}

//�ڡ���̾�����󥯤����
function xb_make_link($page)
{
	$pukiwiki_path = XOOPS_URL."/modules/pukiwiki/index.php";

	//$url = rawurlencode($page);
	$_name = $name = xb_strip_bracket($page);
	$url = rawurlencode($name);

	//�ڡ���̾���ֿ�����-�פ����ξ��ϡ�*(**)�Ԥ�������Ƥߤ�
	if (preg_match("/^(.*\/)?[0-9\-]+$/",$name)){
		$body = xb_get_source($page);
		foreach($body as $line){
			if (preg_match("/^\*{1,3}(.*)/",$line,$reg)){
				$_name = str_replace(array("[[","]]"),"",$reg[1]);
				break;
			}
		}
	}
	$pgp = xb_get_pg_passage($page,FALSE);
	//return "<a href=\"".$pukiwiki_path."?$url\" title=\"".$date."\">".htmlspecialchars($name)."</a> ";
	return "<a href=\"".$pukiwiki_path."?$url\" title=\"".$name.$pgp."\">".htmlspecialchars($_name)."</a> ";
}

// �ڡ���̾�Υ��󥳡���
function xb_encode($key)
{
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);

	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}

// [[ ]] �������
function xb_strip_bracket($str)
{
	if(preg_match("/^\[\[(.*)\]\]$/",$str,$match)) {
		$str = $match[1];
	}
	return $str;
}

// �ե�����̾������(���󥳡��ɤ���Ƥ���ɬ��ͭ��)
function xb_get_filename($pagename)
{
	return XOOPS_ROOT_PATH."/modules/pukiwiki/wiki/".$pagename.".txt";
}

// �ڡ�����¸�ߤ��뤫��
function xb_page_exists($page)
{
	return file_exists(xb_get_filename(xb_encode($page)));
}

// �����������
function xb_get_source($page)
{	
	if(xb_page_exists($page)) {
		$ret = file(xb_get_filename(xb_encode($page)));
		$ret = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$ret);
		return $ret;
  }
  return array();
}

// ���ꤵ�줿�ڡ����ηв����
function xb_get_pg_passage($page,$sw=true)
{
	if($pgdt = @filemtime(xb_get_filename(xb_encode($page))))
	{
		$pgdt = time() - $pgdt;
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

//�������¤�����
function xb_get_readable(&$auth)
{
	static $_X_uid,$_X_admin,$_X_gids;
	
	if (!isset($_X_admin))
	{
		global $xoopsUser;
		if ( $xoopsUser )
		{
			$xoopsModule = XoopsModule::getByDirname("pukiwiki");
			if ( $xoopsUser->isAdmin($xoopsModule->mid()) )
				$_X_admin = 1;
			else
				$_X_admin = 0;
		
			$_X_uid = $xoopsUser->uid();
		}
		else
		{
			$_X_admin = $_X_uid = 0;
		}
		// �桼��������°���륰�롼��ID������
		if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
			// XOOPS 2
			global $xoopsDB;
			$X_M = new XoopsMemberHandler($xoopsDB);
			$_X_gids = $X_M->getGroupsByUser($_X_uid);
		} else {
			// XOOPS 1
			$_X_gids = XoopsGroup::getByUser($xoopsUser);
		}
	}
	
	if ($_X_admin) return true;


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
?>
