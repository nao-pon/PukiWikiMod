<?php
// $Id: block.inc.php,v 1.2 2004/09/04 01:16:46 nao-pon Exp $

/*
 * countdown.inc.php
 * License: GPL
 * Author: nao-pon http://hypweb.net
 * XOOPS Module Block Plugin
 *
 */

function plugin_block_convert()
{
	$params = array('end'=>false,'clear'=>false,'left'=>false,'center'=>false,'right'=>false,'around'=>false,'width'=>"",'w'=>"",'class'=>false,'font-size'=>'','_args'=>array(),'_done'=>FALSE);
	array_walk(func_get_args(), 'block_check_arg', &$params);	

	// end
	if ($params['end']) return '</div>'."\n";
	// clear
	if ($params['clear']) return '<div style="clear:both"></div>'."\n";
	
	if ($params['left']) $align = 'left';
	if ($params['center']) $align = 'center';
	if ($params['right']) $align = 'right';
	
	$around = $params['around'];
	$width = $params['w'];
	if (!$width) $width = $params['width'];
	$fontsize = $params['font-size'];
	$_style = "";
	
	if (preg_match("/^[\d]+%?$/",$fontsize))
	{
		$fontsize = (!strstr($fontsize,"%"))? $fontsize."px" : $fontsize;
		$_style .= "font-size:".$fontsize.";";
	}

	
	if (preg_match("/^[\d]+%?$/",$width))
	{
		$width = (!strstr($width,"%"))? $width."px" : $width;
		$_style .= "width:".$width.";";
	}

	if ($params['around'])
		$style = " style='float:{$align};{$_style}'";
	else
	{
		if ($params['left'])
		{
			$style = " align='left' style='{$_style}'";
		}
		elseif ($params['right'])
		{
			$style = " align='right' style='{$_style}'";
		}
		else
		{
			$style = " align='center' style='{$_style}'";
		}
	}
	//$clear = ($around)? "" : "<div style='clear:both;'></div>\n";

	return "<div{$style} class=\"wiki_body_block\">";
}

//ƒIƒvƒVƒ‡ƒ“‚ð‰ðÍ‚·‚é
function block_check_arg($val, $key, &$params)
{
	if ($val == '') { $params['_done'] = TRUE; return; }

	if (!$params['_done']) {
		foreach (array_keys($params) as $key)
		{
			if (strpos($val,':')) // PHP4.3.4{Apache2 ŠÂ‹«‚Å‰½ŒÌ‚©Apache‚ª—Ž‚¿‚é‚Æ‚Ì•ñ‚ª‚ ‚Á‚½‚Ì‚Å
				list($_val,$thisval) = explode(":",$val);
			else
			{
				$_val = $val;
				$thisval = null;
			}
			if (strtolower($_val) == $key)
			{
				if (!empty($thisval))
					$params[$key] = $thisval;
				else
					$params[$key] = TRUE;
				return;
			}
		}
		$params['_done'] = TRUE;
	}
	$params['_args'][] = $val;
}
?>