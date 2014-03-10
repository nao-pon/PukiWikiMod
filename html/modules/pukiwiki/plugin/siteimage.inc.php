<?php
//
// Created on 2006/05/11 by nao-pon http://hypweb.net/
// $Id: siteimage.inc.php,v 1.8 2006/05/12 01:06:00 nao-pon Exp $
//

function plugin_siteimage_init()
{

}

// インラインプラグインとしての挙動
function plugin_siteimage_inline()
{
	global $link_target;
	$args = func_get_args();
	$url = array_shift($args);
	$prms = array("nolink"=>false,"target"=>$link_target);
	pwm_check_arg($args, $prms);
	return plugin_siteimage_make($url, $prms['nolink'], $prms['target']);
}

function plugin_siteimage_convert()
{
	global $link_target;
	$args = func_get_args();
	$url = array_shift($args);
	$prms = array("nolink"=>false,"target"=>$link_target,"around"=>false,"left"=>false,"right"=>false,"center"=>false);
	pwm_check_arg($args, $prms);
	$style = "width:128px;height:128px;margin:10px;";
	if ($prms['around'])
	{
		if ($prms['right'])
		{
			$style .= "float:right;margin-right:5px;";
		}
		else
		{
			$style .= "float:left;margin-left:5px;";
		}
	}
	else
	{
		if ($prms['right'])
		{
			$style .= "margin-right:10px;margin-left:auto;";
		}
		else if ($prms['center'])
		{
			$style .= "margin-right:auto;margin-left:auto;";
		}
		else
		{
			$style .= "margin-right:auto;margin-left:10px;";
		}
	}
	$img = plugin_siteimage_make($url, $prms['nolink'], $prms['target']);
	return "<div style=\"$style\">$img</div>\n";
}

function plugin_siteimage_make($url, $nolink, $target)
{
	$url = htmlspecialchars($url);
	$target = htmlspecialchars($target);
	$ret = "<img src=\"http://img.simpleapi.net/small/".$url."\" width=\"128\" height=\"128\" alt=\"{$url}\">";
	if (!$nolink)
		$ret = "<a href=\"{$url}\" target=\"{$target}\" title=\"{$url}\">".$ret."</a>";
	return $ret;
}

?>