<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: template.php,v 1.10 2006/03/06 06:20:30 nao-pon Exp $
/////////////////////////////////////////////////

function auto_template($page,$this=false,$matches=array())
{
	global $auto_template_rules,$auto_template_func,$vars;
	if(!$auto_template_func) return '';

	$body = '';
	if (!$this)
	{
		foreach($auto_template_rules as $rule => $template)
		{
			if (is_array($template))
			{
				foreach($template as $rule => $template){}
			}
			if(preg_match("/$rule/",$page,$matches))
			{
				$template_page = preg_replace("/$rule/",$template,$page);
				if (!is_page($template_page)) $template_page = get_uptemplate_page($template_page);
				if ($template_page && is_page($template_page) && check_readable($template_page,false,false))
				{
					$body = join('',get_source($template_page));
					delete_page_info($body,TRUE);
					for($i=0; $i<count($matches); ++$i)
					{
						$body = str_replace("\$$i",$matches[$i],$body);
					}
					$vars["refer"] = "";
					break;
				}
			}
		}
	}
	else
	{
		if (is_page($page) && check_readable($page,false,false))
		{
			$body = join('',get_source($page));
			delete_page_info($body,TRUE);
			for($i=0; $i<count($matches); ++$i)
			{
				$body = str_replace("\$$i",$matches[$i],$body);
			}
			$vars["refer"] = "";
		}
	}
	return $body;
	
}

function get_uptemplate_page($page)
{
	global $auto_template_name;
	$page = strip_bracket($page);
	$arg = array();
	if (preg_match("/^(.+)\/[^\/]+(\/$auto_template_name)$/",$page,$arg))
	{
		if (is_page($arg[1].$arg[2]))
			return $arg[1].$arg[2];
		else
			return get_uptemplate_page($arg[1].$arg[2]);
	}
	return "";
}
?>