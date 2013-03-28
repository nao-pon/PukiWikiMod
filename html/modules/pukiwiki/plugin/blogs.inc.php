<?php
//
// $Id: blogs.inc.php,v 1.10 2006/04/07 12:15:58 nao-pon Exp $
//

function plugin_blogs_convert()
{
	global $vars;
	
	if (!exist_plugin_convert("showrss")) return false;
	
	$array = func_get_args();
	
	$show_description = 0;
	$max = 10;
	$host = "google";
	$template = "recent";
	
	switch (func_num_args())
	{
		case 4:
			$host = trim($array[3]);
		case 3:
			$max = trim($array[2]);
		case 2:
			$show_description = trim($array[1]);
		case 1:
			$word = trim($array[0]);
	}
	
	if (function_exists('mb_convert_kana')) $word = mb_convert_kana($word, "KVa");
	
	if ($word == "like")
	{
		$url = "http://blog.goo.ne.jp/search/search.php?status=select&tg=rel&st=score&dc=25&dp=all&bu=&ts=all&da=all&rss=1&MT=".rawurlencode(XOOPS_WIKI_HOST.get_url_by_name($vars['page']));
		$template = "menubar";
	}
	else
	{
		switch ($host)
		{
			case "bulkfeeds":
				$url = "http://bulkfeeds.net/app/search2.rdf?q=".rawurlencode(mb_convert_encoding($word,"UTF-8","EUC-JP"));
				break;
			case "feedback":
				$url = "http://naoya.dyndns.org/feedback/app/rss?keyword=".rawurlencode($word);
				break;
			case "livedoor":
				$url = "http://rss.sf.livedoor.com/search?start=0&q=".rawurlencode($word);
			case "goo":
				$url = "http://blog.goo.ne.jp/search/search.php?status=select&tg=all&st=time&dc=25&dp=all&bu=&ts=all&da=all&rss=1&MT=".rawurlencode($word);
				break;
			case "google":
			default:
				//$url = 'http://www.google.com/blogsearch_feeds?hl=en&c2coff=1&as_drrb=q&as_mind=1&lr=lang_ja&safe=off&num='.$max.'&output=rss&ie=UTF-8&oe=UTF-8&scoring=d&as_q='.rawurlencode(mb_convert_encoding($word,"UTF-8","EUC-JP"));
				$url = 'http://www.google.com/blogsearch_feeds?hl=en&output=rss&num='.$max.'&ie=UTF-8&oe=UTF-8&scoring=d&lr=lang_ja&q='.rawurlencode(mb_convert_encoding($word,"UTF-8","EUC-JP"));
		}
	}
	
	return do_plugin_convert("showrss","{$url},{$template},3,1,{$show_description},{$max}");
}
?>