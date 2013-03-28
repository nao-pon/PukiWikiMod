<?php
/*
 * PukiWiki lsプラグイン
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 *
 * $Id: ls.inc.php,v 1.5 2006/01/17 00:42:33 nao-pon Exp $
 */

function plugin_ls_convert()
{
	global $vars, $script;
	
	if(func_num_args())
		$aryargs = func_get_args();
	else
		$aryargs = array();

	$option = $vars["page"];
	if(array_search('title',$aryargs)!==FALSE)
	{
		$option .= ",title";
	}
	
	if (exist_plugin('ls2'))
	{
		return do_plugin_convert('ls2',$option);
	}
	else
	{
		return 'ls2 plugin is not installed.';
	}
}
?>