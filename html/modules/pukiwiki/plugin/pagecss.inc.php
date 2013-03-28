<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: pagecss.inc.php,v 1.1 2005/03/07 14:54:16 nao-pon Exp $
/////////////////////////////////////////////////

function plugin_pagecss_convert()
{
	$str = "This page's CSS";
	$arg = func_get_args();
	if ($arg) $str = htmlspecialchars($arg[0]);
	
	return "<p>".plugin_pagecss_get_tag($str)."</p>";
}

function plugin_pagecss_inline()
{
	$str = "This page's CSS";
	$arg = func_get_args();
	if (count($arg) == 1)
		$str = htmlspecialchars($arg[0]);
	else
		$str = $arg[0];
	
	return plugin_pagecss_get_tag($str);
}

function plugin_pagecss_get_tag($str)
{
	global $vars;
	if (is_page($vars['page']))
	{
		$_pagecss_file = CACHE_DIR.encode(strip_bracket($vars['page'])).".css";
		if (file_exists($_pagecss_file))
		{
			$_pagecss_file = str_replace(XOOPS_ROOT_PATH,XOOPS_URL,$_pagecss_file);
			return "<a href=\"$_pagecss_file\">$str</a>";
		}
	}
	return "";
}
?>