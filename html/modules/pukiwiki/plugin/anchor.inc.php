<?php
// $Id: anchor.inc.php,v 1.4 2004/11/24 13:15:35 nao-pon Exp $

function plugin_anchor_convert()
{
	global $WikiName,$BracketName,$script,$vars;

	if(func_num_args() == 1)
		$aryargs = func_get_args();
	else
		return FALSE;

	list($wbn,$aname) = explode("#",$aryargs[0]);

	if(!$aname) return FALSE;

	if(!preg_match("/^$WikiName|$BracketName$/",$wbn) && $wbn)
		$wbn = "[[$wbn]]";

	if(!preg_match("/^$WikiName|$BracketName$/",$aryargs[0]))
		$page = "[[$aryargs[0]]]";
	else
		$page = $aryargs[0];

	$page = strip_bracket($page);

	if($wbn) $wbn = "$script?".rawurlencode($wbn);

	$aname = rawurlencode($aname);
	$page = htmlspecialchars($page);

	return "<a href=\"$wbn#$aname\">$page</a>";
}
?>