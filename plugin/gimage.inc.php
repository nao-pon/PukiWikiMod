<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: gimage.inc.php,v 1.16 2006/06/09 01:49:57 nao-pon Exp $
//
//	 GNU/GPL にしたがって配布する。
//

function plugin_gimage_init()
{
	$GLOBALS['vars']['plugin']['gimage']['col'] = 5;
	$GLOBALS['vars']['plugin']['gimage']['row'] = 4;
	$config = PLUGIN_DATA_DIR."gimage/config.php";
	if (file_exists($config))
	{
		include_once($config);
	}
}

function plugin_gimage_convert()
{
	global $vars;
	if (!exist_plugin_convert("yahoo")) return false;

	$array = func_get_args();
	
	$query = "";
	$qmode = "0";
	$col = $vars['plugin']['gimage']['col'];
	$row = $vars['plugin']['gimage']['row'];
	
	$i = 0;
	$_array = array();
	foreach($array as $prm)
	{
		$match = array();
		if (preg_match("/^c(?:ol)?:([\d]+)$/",$prm,$match))
		{
			$col = min(10,$match[1]);
		}
		else if (preg_match("/^r(?:ow)?:([\d]+)$/",$prm,$match))
		{
			$row = min(10,$match[1]);
		}
		else
		{
			$_array[] = $prm;
		}
	}
	$array = $_array;
	
	switch (count($array))
	{
		case 2:
			$qmode = trim($array[1]);
		case 1:
			$query = trim($array[0]);
	}
	
	if ($qmode)
	{
		$qmode = "any";
	}
	else
	{
		$qmode = "all";
	}
	
	$max = $col * $row;
	
	return do_plugin_convert("yahoo","img,{$query},type:{$qmode},max:{$max},col:{$col}");
}
?>