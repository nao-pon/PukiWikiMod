<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: template.php,v 1.4 2003/12/16 04:48:52 nao-pon Exp $
/////////////////////////////////////////////////

function auto_template($page)
{
	global $auto_template_rules,$auto_template_func;
	if(!$auto_template_func) return '';

	$body = '';
	foreach($auto_template_rules as $rule => $template)
	{
		if(preg_match("/$rule/",$page,$matches))
		{
			$template_page = preg_replace("/$rule/",$template,$page);
			
			if (!is_page($template_page)) $template_page = get_uptemplate_page($template_page);
			
			if (is_page($template_page))
			{
				$body = join('',get_source($template_page));
				$body = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$body);
				delete_page_info($body);
				for($i=0; $i<count($matches); ++$i)
				{
					$body = str_replace("\$$i",$matches[$i],$body);
				}
				break;
			}
		}
	}
	return $body;
}

function get_uptemplate_page($page)
{
	global $auto_template_name;
	$page = strip_bracket($page);
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