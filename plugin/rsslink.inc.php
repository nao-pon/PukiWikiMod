<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: rsslink.inc.php,v 1.4 2004/09/20 12:37:43 nao-pon Exp $
//

function plugin_rsslink_inline()
{
	global $script;
	$list_count = 0;
	if (func_num_args() == 5)
	{
		list($page,$type,$with_content,$list_count,$body) = func_get_args();
	}
	elseif (func_num_args() == 4)
	{
		list($page,$type,$with_content,$body) = func_get_args();
	}
	elseif (func_num_args() == 3)
	{
		list($page,$type,$body) = func_get_args();
		$with_content="false";
	}
	elseif (func_num_args() == 2)
	{
		list($page,$body) = func_get_args();
		$type = $with_content = "";
	}
	elseif (func_num_args() == 1)
		$page = $type = $with_content = "";
	else
		return FALSE;
	if ($type == "rss10" || $type == "10")
		$type = "rss10";
	else
		$type = "rss";

	if (is_page($page))
	{
		$s_page = "&amp;page=".rawurlencode($page);
		$page = " of ".htmlspecialchars($page);
	}
	else
	{
		global $xoopsModule;
		$s_page = "";
		$page = " of ".htmlspecialchars($xoopsModule->name());
	}
	if ($type == "rss10")
	{
		if ($with_content=="true") 
		{
			$s_content = "&amp;content=true";
		}
		else
		{
			$s_content = "";
		}
	}
	$s_list_count = ($list_count)? "&amp;count=$list_count" : "";
	return "<a href=\"$script?cmd=$type$s_page$s_content$s_list_count\"><img src=\"".XOOPS_WIKI_URL."/image/rss.png\" alt=\"RSS$page\" /></a>";
}
?>