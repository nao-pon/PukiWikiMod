<?php
// $Id: pukiwiki_page.php,v 1.3 2004/10/16 03:36:59 nao-pon Exp $
function b_pukiwiki_page_show($options)
{
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
		
		//マルチドメイン対応
		$data = preg_replace("/(<[^>]+(href|action|src)=(\"|'))https?:\/\/".$_SERVER["HTTP_HOST"]."(:[\d]+)?/i","$1",$data);
		
		if ($fp = fopen($cache_file,"w"))
		{
			fputs($fp,$data);
			fclose($fp);
		}
	}
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