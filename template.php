<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: template.php,v 1.3 2003/07/15 14:03:15 nao-pon Exp $
/////////////////////////////////////////////////

function auto_template($page)
{
  global $auto_template_rules,$auto_template_func;
  if(!$auto_template_func) return '';

  $body = '';
  foreach($auto_template_rules as $rule => $template)
    {
      if(preg_match("/$rule/",$page,$matches)) {
	$template_page = preg_replace("/$rule/",$template,$page);
	$body = join('',get_source($template_page));
	$body = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$body);
	$body = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$body);
	$body = preg_replace("/^\/\/ author:([0-9]+)\n/","",$body);
	for($i=0; $i<count($matches); ++$i) {
	  $body = str_replace("\$$i",$matches[$i],$body);
	}
	break;
      }
    }
  return $body;
}
?>