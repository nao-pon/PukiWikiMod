<?php
//
// $Id: keyword.inc.php,v 1.2 2009/06/15 22:49:16 nao-pon Exp $
//

function plugin_keyword_convert()
{
	global $wiki_strong_words;
	
	$key = func_get_args();
	$key = array_map('htmlspecialchars', $key);
	$wiki_strong_words = array_merge($wiki_strong_words,$key);
	
	return "";
}
?>