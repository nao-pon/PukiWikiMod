<?php
//
// $Id: keyword.inc.php,v 1.1 2004/05/18 12:05:38 nao-pon Exp $
//

function plugin_keyword_convert()
{
	global $wiki_strong_words;
	
	$key = func_get_args();
	$wiki_strong_words = array_merge($wiki_strong_words,$key);
	
	return "";
}
?>