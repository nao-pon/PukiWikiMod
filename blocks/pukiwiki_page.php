<?php
// $Id: pukiwiki_page.php,v 1.8 2004/11/24 12:08:48 nao-pon Exp $
function b_pukiwiki_page_show($options)
{
	global $xoopsConfig;
	
	$show_page = ($options[0])? $options[0] : "";
	$cache_time = (empty($options[1]))? 0 : $options[1];
	$cache_time = (int)$cache_time * 60;
	$cache_no = (empty($options[2]))? "0" : $options[2];
	$cache_file = XOOPS_ROOT_PATH."/modules/pukiwiki/cache/p/xoops_block_{$cache_no}.dat";
	
	$wiki_url = XOOPS_URL.'/modules/pukiwiki/';

	if (file_exists($cache_file) && filemtime($cache_file) > time() - $cache_time)
	{
		$data = join('',@file($cache_file));
	}
	else
	{
		$data = "";
		if ($show_page)
		{
			$url = $wiki_url.'index.php?xoops_block=1&cmd=read&page='.rawurlencode($show_page);
			$fp = fopen($url,"r");
			if ($fp)
			{
				$contents = "";
				do {
					$data = fread($fp, 8192);
					if (strlen($data) == 0) {
						break;
					}
					$contents .= $data;
				} while(true);
				
				fclose ($fp);
				
				$data = $contents;
				unset ($contents);
			}
		}
		
		// Fatal, Parseエラーが出た場合
		if (preg_match("#<b>(Fatal|Parse) error</b>:#",$data))
			$data = "";
		
		if ($data)
		{
			//マルチドメイン対応
			$data = preg_replace("/(<[^>]+(href|action|src)=(\"|'))https?:\/\/".$_SERVER["HTTP_HOST"]."(:[\d]+)?/i","$1",$data);
			
			// SID 自動付加環境 は SID を削除
			if (ini_get("session.use_trans_sid"))
			{
				$data = preg_replace("/(&|\?)?".preg_quote(ini_get("session.name"),"/")."=[0-9a-f]{32}/","",$data);
			}
			
			if ($fp = fopen($cache_file,"wb"))
			{
				fputs($fp,$data);
				fclose($fp);
			}
		}
	}
	
	if (strpos($data,"_gEsTnAmE_") !== FALSE)
	{
		global $xoopsUser,$xoopsModule,$xoopsConfig;
		$uname = ($xoopsUser)? $xoopsUser->uname() : $xoopsConfig['anonymous'];
		//名前欄置換
		$data = str_replace("_gEsTnAmE_",$uname,$data);
	}
	
	// 外部リンクマーク用 class設定
	$data = preg_replace("/(<a[^>]+?)(href=(\"|')?(?!https?:\/\/".$_SERVER["HTTP_HOST"].")http)/i","$1class=\"ext\" $2",$data);
	
	// テーマ専用CSS Link を置換
	$css_url = (file_exists(XOOPS_THEME_PATH.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'))?
		XOOPS_THEME_URL.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'
		:
		$wiki_url.'skin/default.ja.css';
	$css_tag = '<link rel="stylesheet" href="'.$css_url.'" type="text/css" media="screen" charset="shift_jis">';
	$data = str_replace('<!-- This tag will be replace CSS Link -->',$css_tag,$data);
	
	$block['title'] = "PukiWiki - {$show_page}";
	$block['content'] = $data;

	return $block;
}
function b_pukiwiki_page_edit($options)
{
	$form = "";
	$form .= "PukiWiki Page Name: ";
	$form .= "<input type='text' name='options[]' value='".$options[0]."' /><br />";
	$form .= "Block cache: ";
	$form .= "<input type='text' name='options[]' value='".$options[1]."' />(min)<br />";
	$form .= "Cache file number: ";
	$form .= "<input type='text' name='options[]' value='".$options[2]."' />";
	return $form;
}