<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: tracker_list.inc.php,v 1.1 2003/07/14 09:02:01 nao-pon Exp $
//

require_once(PLUGIN_DIR.'tracker.inc.php');

function plugin_tracker_list_init()
{
	if (function_exists('plugin_tracker_init'))
	{
		plugin_tracker_init();
	}
}
?>
