<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: size.inc.php,v 1.1 2003/10/13 12:23:28 nao-pon Exp $
//

function plugin_size_inline()
{
	if (func_num_args() != 2)
	{
		return FALSE;
	}
	
	list($size,$body) = func_get_args();
	
	if ($size == '' or $body == '')
	{
		return FALSE;
	}

	if (!preg_match('/^\d+$/',$size))
	{
		return $body;
	}
	
	$s_size = htmlspecialchars($size);
	return "<span style=\"font-size:{$s_size}px;display:inline-block;line-height:130%;text-indent:0px\">$body</span>";
}
?>