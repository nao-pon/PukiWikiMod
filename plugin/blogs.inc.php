<?php
//
// $Id: blogs.inc.php,v 1.2 2004/05/20 14:00:16 nao-pon Exp $
//

function plugin_blogs_convert()
{
	if (!exist_plugin_convert("showrss")) return false;

	$array = func_get_args();
	
	$show_description = 0;
	
	switch (func_num_args())
	{
		case 2:
			$show_description = trim($array[1]);
		case 1:
			$word = trim($array[0]);
	}
	return do_plugin_convert("showrss","http://bulkfeeds.net/app/search2.rdf?q=".rawurlencode(mb_convert_encoding($word,"UTF-8","EUC-JP")).",recent,1,1,{$show_description}");
	//return do_plugin_convert("showrss","http://naoya.dyndns.org/feedback/app/rss?keyword=".rawurlencode($word).",recent,1,1,{$show_description}");
}
?>