<?php
// $Id: recentchanges.inc.php,v 1.1 2003/10/13 12:23:28 nao-pon Exp $

function plugin_recentchanges_action()
{
	global $X_admin,$whatsnew;
	$lines = get_source($whatsnew);
	$linedata = "";
	$i = 1;
	while (isset($lines[$i]))
	{
		list($auth['owner'],$auth['user'],$auth['group']) = split("\t",substr($lines[$i],3));
		$auth = preg_replace("/^.*:/","",$auth);
		if ($X_admin || get_readable($auth))
			$linedata .= $lines[$i+1];
		$i = $i + 2;
	}
	$ret['msg'] = make_search($whatsnew);
	$ret['body'] = convert_html($linedata);
	return $ret;
}
?>