<?php
// $Id: pukiwiki_page.php,v 1.13 2005/11/16 23:49:16 nao-pon Exp $
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
		$trip = (!empty($_COOKIE["pukiwiki_un"]))? preg_replace("/[^#]+(#.+)?/","$1",$_COOKIE["pukiwiki_un"]) : "";
		$uname = ($xoopsUser)? $xoopsUser->uname().$trip : $xoopsConfig['anonymous'];
		//名前欄置換
		$data = str_replace("_gEsTnAmE_",$uname,$data);
	}
	// Ticket置換
	$data = preg_replace("/<!\-\-XOOPS_TOKEN_INSERT\-\->/e","b_pukiwiki_get_token_html()",$data);
	
	// 外部リンクマーク用 class設定
	//$data = preg_replace("/(<a[^>]+?)(href=(\"|')?(?!https?:\/\/".$_SERVER["HTTP_HOST"].")http)/","$1class=\"ext\" $2",$data);
	
	// CSS Link を発行
	$css_url = (file_exists(XOOPS_THEME_PATH.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'))?
		XOOPS_THEME_URL.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'
		:
		$wiki_url.'skin/default.ja.css';
	$css_tag = '<link rel="stylesheet" href="'.$css_url.'" type="text/css" media="screen" charset="shift_jis">';
	// 管理画面の CSS
	if(file_exists( XOOPS_ROOT_PATH."/modules/pukiwiki/cache/css.css"))
	{
		$css_tag .= "\n".'<link rel="stylesheet" href="'.$wiki_url.'cache/css.css" type="text/css" media="screen" charset="shift_jis">'."\n";
	}
	// ページ用の .css
	$css_tag .= b_pukiwiki_page_get_page_css_tag($show_page,$wiki_url);
	
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
	return $form;
}

// ページ名のエンコード
function b_pukiwiki_page_encode($key)
{
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);
	
	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}

// [[ ]] を取り除く
function b_pukiwiki_page_strip_bracket($str)
{
	if(preg_match("/^\[\[(.*)\]\]$/",$str,$match))
	{
		$str = $match[1];
	}
	return $str;
}

// ページ専用CSSタグを得る
function b_pukiwiki_page_get_page_css_tag($page,$wiki_url)
{
	$page = b_pukiwiki_page_strip_bracket($page);
	$ret = '';
	$_page = '';
	$dir = XOOPS_ROOT_PATH."/modules/pukiwiki/cache/";
	foreach(explode('/',$page) as $val)
	{
		$_page = ($_page)? $_page."/".$val : $val;
		$_pagecss_file = b_pukiwiki_page_encode($_page).".css";
		if(file_exists($dir.$_pagecss_file))
		{
			$ret .= '<link rel="stylesheet" href="'.$wiki_url.'cache/'.$_pagecss_file.'" type="text/css" media="screen" charset="shift_jis">'."\n";
		}
	}
	return $ret;
}

// チケット取得
function b_pukiwiki_get_token_html()
{
	if (!class_exists('XoopsTokenHandler')) {return "";}
	static $handler;
	if (!is_object($handler))
	{
		$handler = new XoopsMultiTokenHandler();
	}
	$ticket = &$handler->create('pukiwikimod',0);
	return $ticket->getHtml();
}