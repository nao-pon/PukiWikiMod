<?php
// $Id: rsstop.inc.php,v 1.1 2004/05/15 02:55:10 nao-pon Exp $

function plugin_rsstop_convert()
{
	global $vars;
	$vars['is_rsstop'] = 1;
	return '';
}
?>