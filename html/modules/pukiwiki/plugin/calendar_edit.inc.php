<?php
// $Id: calendar_edit.inc.php,v 1.3 2004/11/24 13:15:35 nao-pon Exp $

function plugin_calendar_edit_convert()
{
	global $command;
	
	$command = edit;

	if(func_num_args())
		$aryargs = func_get_args();
	else
		$aryargs = array();

	
	if(file_exists(PLUGIN_DIR."calendar.inc.php"))
	{
		require_once PLUGIN_DIR."calendar.inc.php";
		return call_user_func_array("plugin_calendar_convert",$aryargs);
	}
	else
	{
		return FALSE;
	}
}
?>