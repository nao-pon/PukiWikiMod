<?php
// $Id: block.inc.php,v 1.7 2006/03/06 06:20:30 nao-pon Exp $

/*
 * countdown.inc.php
 * License: GPL
 * Author: nao-pon http://hypweb.net
 * XOOPS Module Block Plugin
 *
 */

function plugin_block_convert()
{
	static $b_count = 1;
	static $b_tag = array();
	$ie5_div = "";
	$_style = "";
	$tate_div = "";
	$tate_js = "";
	$tate_style = "";
	$block_class = "wiki_body_block";
	if (!isset($b_tag[$b_count])) $b_tag[$b_count] = 0;
	
	$params = array('end'=>false,'clear'=>false,'left'=>false,'center'=>false,'right'=>false,'around'=>false,'tate'=>false,'h'=>'','width'=>"",'w'=>"",'class'=>false,'font-size'=>'','_args'=>array(),'_done'=>FALSE);
	//array_walk(func_get_args(), 'block_check_arg', &$params);
	//なぜか $args のメンバー数が多い時 array_walk ではPHPが落ちることがある
	foreach(func_get_args() as $key=>$val)
	{
		block_check_arg($val, $key, $params);
	}

	// end
	if ($params['end'])
	{
		$ret = str_repeat("</div>",$b_tag[$b_count])."\n";
		$b_tag[$b_count]--;
		return $ret;
	}
	// clear
	if ($params['clear']) return '<div style="clear:both"></div>'."\n";
	
	$b_tag[$b_count] = 1;
	
	if ($params['left']) $align = 'left';
	if ($params['center']) $align = 'center';
	if ($params['right']) $align = 'right';
	
	$around = $params['around'];
	$width = $params['w'];
	if (!$width) $width = $params['width'];
	$fontsize = $params['font-size'];
	
	$tate = $params['tate'];
	$height = $params['h'];
	
	$b_count++;
	$b_tag[$b_count]++;
	
	if ($tate)
	{
		$block_class = "wiki_body_block_tate";
		//$tate_div = "<div class=\"tate\">";
		//$b_tag[$b_count]++;
		$tate_style = " style=\"writing-mode:tb-rl;\"";
		$tate_js = "\n<script type=\"text/javascript\">\n<!--\nif (!pukiwiki_WinIE) document.write(\"<div style='text-align:right;'><small>※ このブロックは IE(5.5以上)で閲覧すると縦書きで表示されます。</small></div>\");\n-->\n</script>\n";
		
		if (strpos($width,"%")) $width = "";
		if (strpos($height,"%")) $height = "";
	}
	
	if (preg_match("/^[\d]+%?$/",$fontsize))
	{
		$fontsize = (!strstr($fontsize,"%"))? $fontsize."px" : $fontsize;
		$_style .= "font-size:".$fontsize.";";
	}

	$match = array();
	if (preg_match("/^([\d]+%?)(px)?$/i",$width,$match))
	{
		$width = (!strstr($match[1],"%"))? $match[1]."px" : $match[1];
		$_style .= "width:".$width.";";
	}
	
	if (preg_match("/^([\d]+%?)(px)?$/i",$height,$match))
	{
		$height = (!strstr($match[1],"%"))? $match[1]."px" : $match[1];
		$_style .= "height:".$height.";";
	}
	
	if ($params['around'])
		$style = " style='float:{$align};{$_style}'";
	else
	{
		if ($params['left'])
		{
			$style = " style='margin-left:0px;margin-right:auto;{$_style}'";
		}
		elseif ($params['right'])
		{
			$style = " style='margin-left:auto;margin-right:0px;{$_style}'";
		}
		else
		{
			$style = " style='margin-left:auto;margin-right:auto;{$_style}'";
		}
		//$ie5_div = "<div class=\"ie5\"{$tate_style}>";
		$ie5_div = "<div class=\"ie5\">";
		$b_tag[$b_count]++;
	}
	
	return "{$ie5_div}<div{$style} class=\"{$block_class}\">{$tate_div}{$tate_js}";
}

//オプションを解析する
function block_check_arg($val, $key, &$params)
{
	if ($val == '') { $params['_done'] = TRUE; return; }

	if (!$params['_done']) {
		foreach (array_keys($params) as $key)
		{
			if (strpos($val,':')) // PHP4.3.4＋Apache2 環境で何故かApacheが落ちるとの報告があったので
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