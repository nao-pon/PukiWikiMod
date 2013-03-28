<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: list.inc.php,v 1.7 2006/01/12 00:07:52 nao-pon Exp $
//
// 一覧の表示
function plugin_list_action()
{
	global $vars,$_title_list,$_title_filelist,$whatsnew,$related_link;
	
	$related_link = 0;
	
	$filelist = (array_key_exists('cmd',$vars) and $vars['cmd']=='filelist'); //姑息だ…
	$prefix = (array_key_exists('prefix',$vars))? $vars['prefix'] : "";
	$lword = (array_key_exists('lw',$vars))? $vars['lw'] : "";
	if ($lword == " ") $lword = "";
	$t_prefix = ($prefix)? "$prefix: " : "";
	return array(
		'msg'=>$filelist ? $_title_filelist : $t_prefix.$_title_list,
		'body'=>get_list($filelist,$prefix,$lword)
	);
}

// 一覧の取得
function get_list($withfilename,$prefix="",$lword="")
{
	global $non_list,$whatsnew,$X_uid;
	
	if (!$X_uid)
	{
		// キャッシュファイル名
		$cache_file = CACHE_DIR.md5($withfilename.$prefix.$lword).".list";
		// キャッシュチェック
		$auto_time = filemtime(CACHE_DIR . '/autolink.dat');
		if (file_exists($cache_file))
		{
			$data = file($cache_file);
			$_auto_time = trim(array_shift($data));
			if ($auto_time)
			{
				if ($auto_time == $_auto_time || time()-filemtime($cache_file) < 3600) return join('',$data);
			}
			elseif (time()-filemtime($cache_file) < 600)
			{
				return join('',$data);
			}
		}
	}
	
	if ($prefix)
	{
		$pages = array_diff(get_existpages_db(false,$prefix."/",0,"",false,true,true,true),array($whatsnew,$prefix));
		if (!count($pages))
			$pages = array_diff(get_existpages_db(false,$prefix."/",0,"",false,false,true,true),array($whatsnew,$prefix));
	}
	else
		$pages = array_diff(get_existpages_db(false,"",0,"",false,true,true,true),array($whatsnew));
	if (!$withfilename)
	{
		$pages = array_diff($pages,preg_grep("/$non_list/",$pages));
	}
	if (count($pages) == 0)
	{
	        return '';
	}
	
	$ret = page_list($pages,'read',$withfilename,$prefix,$lword);
	
	if (!$X_uid)
	{
		$fp = fopen($cache_file, 'wb');
		fwrite($fp, $auto_time."\n".$ret);
		fclose($fp);
	}

	return $ret;
}
?>