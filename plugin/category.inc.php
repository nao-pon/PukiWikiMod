<?php
//
// $Id: category.inc.php,v 1.4 2005/03/30 00:01:36 nao-pon Exp $
//

function plugin_category_convert()
{
	$args = func_get_args();
	return pligin_category_maketag($args);
}

function pligin_category_maketag($args)
{
	$base_name = $ret = $base = "";
	$base = ":";
	$cats = array();
	foreach($args as $arg)
	{
		$arg = htmlspecialchars($arg);
		$arg = str_replace(" ","",$arg);
		if ($arg{0} == ":")
		{
			$base = $arg."/";
			$base_name = add_bracket($arg);
		}
		elseif ($arg{0} == "#")
		{
			$option = substr($arg,1);
			if (preg_match("/(left|center|right)/i",substr($arg,1),$option))
				$align = $option[1].":";
		}
		else
			$cats[] = $arg;
	}
	foreach ($cats as $cat)
	{
		if ($cat) 
		{
			if ($base_name && !is_page($base_name))
			{
				page_write($base_name,"#norelated\n***Category lists of ''".substr(strip_bracket($base_name),1)."''\n#ls2(,pagename,notemplate,relatedcount)\n");
				//if (!is_page(add_bracket($base."template")))
				//{
					page_write(add_bracket($base."template"),"**$3\n***Category: [[#real#>$1]]\n|T:100% TC:0 SC:0 :TOP|SC:0 :TOP|c\n|#related|****Sub Categorys->\n#ls2(,pagename,notemplate,relatedcount)|\n");
				//}
			}
			$page_names = explode("/",$cat);
			if (count($page_names) > 1)
			{
				$_cat = "";
				$cats = array();
				foreach ($page_names as $page_name)
				{
					$_cat .= $page_name;
					$cats[] = "[[$page_name>$base$_cat]]";
					$_cat .= "/";
				}
				$ret .= "[ ".join('/',$cats)." ]";
			}
			else
				$ret .= "[ [[$cat>$base$cat]] ]";
		}
	}
	return strtoupper($align)."[[Category ".substr($base,1,strlen($base)-2).">".strip_bracket($base_name)."]]:".$ret;
}
?>