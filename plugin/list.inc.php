<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: list.inc.php,v 1.4 2004/11/24 13:15:35 nao-pon Exp $
//
// ������ɽ��
function plugin_list_action()
{
	global $vars,$_title_list,$_title_filelist,$whatsnew;
	
	$filelist = (array_key_exists('cmd',$vars) and $vars['cmd']=='filelist'); //��©����
	$prefix = (array_key_exists('prefix',$vars))? $vars['prefix'] : "";
	$t_prefix = ($prefix)? "$prefix: " : "";
	return array(
		'msg'=>$filelist ? $_title_filelist : $t_prefix.$_title_list,
		'body'=>get_list($filelist,$prefix)
	);
}

// �����μ���
function get_list($withfilename,$prefix="")
{
	global $non_list,$whatsnew;
	if ($prefix)
	{
		$pages = array_diff(get_existpages_db(false,$prefix."/",0,"",false,true),array($whatsnew));
		if (!count($pages))
			$pages = array_diff(get_existpages_db(false,$prefix."/",0,"",false,false),array($whatsnew));
	}
	else
		$pages = array_diff(get_existpages_db(false,"",0,"",false,true),array($whatsnew));
	if (!$withfilename)
	{
		$pages = array_diff($pages,preg_grep("/$non_list/",$pages));
	}
	if (count($pages) == 0)
	{
	        return '';
	}
	
	return page_list($pages,'read',$withfilename,$prefix);
}
?>