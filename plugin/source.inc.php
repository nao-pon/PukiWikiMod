<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: source.inc.php,v 1.3 2003/06/28 15:54:53 nao-pon Exp $
//
// ページソースを表示

function plugin_source_init()
{
	$messages = array(
		'_source_messages'=>array(
			'msg_title' => '$1のソース',
			'msg_notfound' => '$1が見つかりません',
			'err_notfound' => 'ページのソースを表示できません。'
		)
	);
	set_plugin_messages($messages);
}

function plugin_source_action()
{
	global $vars;
	global $_source_messages;
	
	$vars['refer'] = $vars['page'];
	
	if (!is_page($vars['page']))
	{
		return array(
			'msg'=>$_source_messages['msg_notfound'],
			'body'=>$_source_messages['err_notfound']
		);
	}
	$source = join('',get_source($vars['page']));
	$source = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$source);
	$source = preg_replace("/^#freeze(\tuid:[0-9]+)?\n/","",$source);
	$source = preg_replace("/^\/\/ author:([0-9]+)\n/","",$source);
	$source = nl2br(htmlspecialchars($source));
	
	return array(
		'msg'=>$_source_messages['msg_title'],
		'body' =>'<div class="wiki_source">'.$source.'</div>'
	);
}
?>
