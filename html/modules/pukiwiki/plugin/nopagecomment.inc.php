<?php
//
// $Id: nopagecomment.inc.php,v 1.1 2005/11/06 05:35:00 nao-pon Exp $
//

function plugin_nopagecomment_convert()
{
	global $show_comments;
	$show_comments = false;
	return '';
}
?>