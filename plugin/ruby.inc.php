<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ruby.inc.php,v 1.3 2004/07/31 06:48:05 nao-pon Exp $
//

function plugin_ruby_inline()
{
	if (func_num_args() < 1)
	{
		//$args = func_get_args();
		return FALSE; 
	}
	
	list($ruby,$body) = func_get_args();
	
	if ($body == '' && $ruby == '')
		return FALSE;
	elseif ( $body == '')
		return $ruby;

	$s_ruby = htmlspecialchars($ruby);
	return "<ruby><rb>$body</rb><rp>(</rp><rt>$s_ruby</rt><rp>)</rp></ruby>";
}
?>
