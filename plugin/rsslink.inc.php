<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: rsslink.inc.php,v 1.2 2004/01/12 13:12:06 nao-pon Exp $
//

function plugin_rsslink_inline()
{
	global $script;
	if (func_num_args() == 3)
		list($page,$type,$body) = func_get_args();
	elseif (func_num_args() == 2)
	{
		list($page,$body) = func_get_args();
		$type = "";
	}
	elseif (func_num_args() == 1)
		$page = $type = "";
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
		$s_page = $page = "";
	
	return "<a href=\"$script?cmd=$type$s_page\"><img src=\"".XOOPS_WIKI_URL."/image/rss.png\" alt=\"RSS$page\" /></a>";
}
?>
