<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: font.inc.php,v 1.1 2003/09/25 13:13:59 nao-pon Exp $
//

function plugin_font_inline()
{
	$prmcnt = func_num_args();
	if ($prmcnt < 2)
	{
		return FALSE;
	}
	// カラーネームの正規表現
	$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow";

	$prms = func_get_args();
	$body = array_pop($prms);

	$style = "";
	$color_type = $decoration = true;
	foreach ($prms as $prm)
	{
		if ($prm == "") $color_type = false;
		elseif (preg_match("/^i(talic)?$/i",$prm)) $style .= "font-style:italic;";
		elseif (preg_match("/^b(old)?$/i",$prm)) $style .= "font-weight:bold;";
		elseif ($decoration && preg_match("/^u(nderline)?$/i",$prm))
		{
			$style .= "text-decoration:underline;";
			$decoration = false;
		}
		elseif ($decoration && preg_match("/^o(verline)?$/i",$prm))
		{
			$style .= "text-decoration:overline;";
			$decoration = false;
		}
		elseif ($decoration && preg_match("/^l(ine-through)?$/i",$prm))
		{
			$style .= "text-decoration:line-through;";
			$decoration = false;
		}
		elseif (preg_match('/^(#[0-9a-f]+|'.$colors_reg.')$/i',$prm,$color))
		{
			if ($color_type)
			{
				$style .= "color:".htmlspecialchars($color[1]).";";
				$color_type = false;
			} else {
				$style .= "background-color:".htmlspecialchars($color[1]).";";
			}
		}
		elseif (preg_match('/^(\d+)$/',$prm,$size)) $style .= "font-size:".htmlspecialchars($size[1])."px;display:inline-block;line-height:130%;text-indent:0px;";
		elseif (preg_match('/^(\d+(%|px|pt|em))$/',$prm,$size)) $style .= "font-size:".htmlspecialchars($size[1]).";display:inline-block;line-height:130%;text-indent:0px;";
	}
	
	if ($style == "") return $body;
	return "<span style=\"$style\">$body</span>";
}

?>