<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: rsslink.inc.php,v 1.5 2005/02/23 00:16:41 nao-pon Exp $
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
		$with_content="";
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
		$s_page = "&amp;p=".get_pgid_by_name($page);
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
		$with_content = strtolower($with_content);
		if ($with_content)
		{
			if ($with_content == "s")
				$s_content = "&amp;content=s";
			else
				$s_content = "&amp;content=l";
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