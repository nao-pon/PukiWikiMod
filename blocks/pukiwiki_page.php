<?php
// $Id: pukiwiki_page.php,v 1.2 2004/08/23 13:50:07 nao-pon Exp $
function b_pukiwiki_page_show($options)
{
	$show_page = ($options[0])? $options[0] : "";
	$cache_time = (empty($options[1]))? 0 : $options[1];
	$cache_time = (int)$cache_time * 60;
	$cache_no = (empty($options[2]))? "0" : $options[2];
	$cache_file = XOOPS_ROOT_PATH."/modules/pukiwiki/cache/p/xoops_block_{$cache_no}.dat";

	if (file_exists($cache_file) && filemtime($cache_file) > time() - $cache_time)
	{
		$data = join('',@file($cache_file));
	}
	else
	{
		$data = "";
		if ($show_page)
		{
			$url = XOOPS_URL.'/modules/pukiwiki/index.php?xoops_block=1&cmd=read&page='.rawurlencode($show_page);
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