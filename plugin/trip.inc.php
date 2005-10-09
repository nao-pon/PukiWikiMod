<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: trip.inc.php,v 1.1 2005/10/09 05:09:50 nao-pon Exp $
//

function plugin_trip_inline()
{
	$prmcnt = func_num_args();

	$prms = func_get_args();
	$body = $prms[0];

	return "<span class=\"pukiwiki_trip\">&#9830;$body</span>";
}

?>