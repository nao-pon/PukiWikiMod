<?php
//
// $Id: ping.inc.php,v 1.2 2003/12/16 04:48:52 nao-pon Exp $
//

function plugin_ping_convert()
{
	$links = func_get_args();
	return pligin_ping_maketag($links);
}

function plugin_ping_inline()
{
	$links = func_get_args();
	array_pop($links);
	return pligin_ping_maketag($links);
}

function pligin_ping_maketag($links)
{
	$ret = "";
	foreach($links as $link)
	{
		if (preg_match("/^https?:\/\/[^\"> ]+$/",$link))
			$ret .= "<!--__PINGSEND__\"".$link."\"-->";
	}
	return $ret;
}
?>
