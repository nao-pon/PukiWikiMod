<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ruby.inc.php,v 1.2 2003/06/28 11:33:03 nao-pon Exp $
//

function plugin_ruby_inline()
{
	if (func_num_args() != 2)
	{
		//$args = func_get_args();
		return FALSE; 
	}
	
	list($ruby,$body) = func_get_args();
	
	if ($ruby == '' or $body == '')
	{
		return FALSE;
	}
	
	$s_ruby = htmlspecialchars($ruby);
	return "<ruby><rb>$body</rb><rp>(</rp><rt>$s_ruby</rt><rp>)</rp></ruby>";
}
?>
