<?php
// $Id: pukiwiki_page.php,v 1.16 2006/04/06 13:32:15 nao-pon Exp $

if (! defined('PWM_BLOCK_PAGE_INCLUDED')) {
define('PWM_BLOCK_PAGE_INCLUDED', true);

if (! defined('PWM_BLOCK_FUNC_INCLUDED')) include_once("block_function.php");

function b_pukiwiki_page_show($options)
{
	global $xoopsConfig;
	
	$dir_name = $options[3];
	
	$show_page = ($options[0])? $options[0] : "";
	$cache_time = (empty($options[1]))? 0 : $options[1];
	$cache_time = (int)$cache_time * 60;
	$cache_no = (empty($options[2]))? "0" : $options[2];
	$cache_file = XOOPS_ROOT_PATH."/modules/".$dir_name."/cache/p/xoops_block_{$cache_no}.dat";
	
	$wiki_url = XOOPS_URL."/modules/".$dir_name."/";
	
	if (file_exists($cache_file) && filemtime($cache_file) > time() - $cache_time)
	{
		$data = join('',@file($cache_file));
	}
	else
	{
		$error_str = $data = "";
		if ($show_page)
		{
			$url = $wiki_url.'index.php?xoops_block=1&cmd=read&page='.rawurlencode($show_page);

			include_once(XOOPS_ROOT_PATH."/class/snoopy.php");
			$snoopy = new Snoopy;
			
			$snoopy->read_timeout = 10;
			$snoopy->fetch($url);
			if (strpos($snoopy->response_code,"200") === FALSE)
			{
				if (file_exists($cache_file))
					$data = join('',@file($cache_file));
				else
					$data = $error_str = $snoopy->response_code;
			}
			else
			{
				if (isset($snoopy->error) && !empty($snoopy->error))
					$data = $error_str = $snoopy->error;
				else
					$data = $snoopy->results;
			}
			
		}
		
		// Fatal, Parseエラーが出た場合
		if (preg_match("#<b>(Fatal|Parse) error</b>:#",$data))
			$data = "";
		
		if ($data != $error_str)
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
		if ($xoopsUser)
		{
			$trip = (!empty($_COOKIE["pukiwiki_un"]))? preg_replace("/[^#]+(#.+)?/","$1",$_COOKIE["pukiwiki_un"]) : "";
			$uname = $xoopsUser->uname().$trip;
		}
		else
		{
			$uname = (!empty($_COOKIE["pukiwiki_un"]))? $_COOKIE["pukiwiki_un"] : $xoopsConfig['anonymous'];
		}
		//名前欄置換
		$data = str_replace("_gEsTnAmE_",$uname,$data);
	}
	// Ticket置換
	$data = preg_replace("/<!\-\-XOOPS_TOKEN_INSERT\-\->/e","xb_get_token_html()",$data);
	
	// 外部リンクマーク用 class設定
	//$data = preg_replace("/(<a[^>]+?)(href=(\"|')?(?!https?:\/\/".$_SERVER["HTTP_HOST"].")http)/","$1class=\"ext\" $2",$data);
	
	// CSS Link を発行
	$css_url = (file_exists(XOOPS_THEME_PATH.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'))?
		XOOPS_THEME_URL.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'
		:
		$wiki_url.'skin/default.ja.css';
	$css_tag = '<link rel="stylesheet" href="'.$css_url.'" type="text/css" media="screen" charset="shift_jis">';
	// 管理画面の CSS
	if(file_exists( XOOPS_ROOT_PATH."/modules/".$dir_name."/cache/css.css"))
	{
		$css_tag .= "\n".'<link rel="stylesheet" href="'.$wiki_url.'cache/css.css" type="text/css" media="screen" charset="shift_jis">'."\n";
	}
	// ページ用の .css
	$css_tag .= xb_get_page_css_tag($show_page,$wiki_url,$dir_name);
	
	// ヘッダ情報付加
	global $xoopsTpl;
	if ($xoopsTpl)
	{
		$xoopsTpl->assign('xoops_block_header',$xoopsTpl->get_template_vars('xoops_block_header').$css_tag);
	}
	else
	{
		$data = $css_tag."\n".$data;
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
	$form .= "<input type='hidden' name='options[]' value='".$options[3]."' />";
	return $form;
}


}
?>