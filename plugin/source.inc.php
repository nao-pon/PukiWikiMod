<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: source.inc.php,v 1.8 2004/11/24 13:15:35 nao-pon Exp $
//
// �ڡ�����������ɽ��

function plugin_source_init()
{
	$messages = array(
		'_source_messages'=>array(
			'msg_title' => '$1�Υ�����',
			'msg_notfound' => '$1�����Ĥ���ޤ���',
			'err_notfound' => '�ڡ����Υ�������ɽ���Ǥ��ޤ���'
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
	
	if (!check_readable($vars['page'],false,false))
	{
		// �������¤ʤ�
		return array(
			'msg'=>htmlspecialchars(strip_bracket($vars["page"])),
			'body'=>str_replace('$1',make_search($vars["page"]),_MD_PUKIWIKI_NO_VISIBLE)
		);
	}
	
	$source = join('',get_source($vars['page']));
	delete_page_info($source);
	$source = nl2br(str_replace(" ","&nbsp;",htmlspecialchars($source)));
	
	return array(
		'msg'=>$_source_messages['msg_title'],
		'body' =>'<div class="wiki_source">'.$source.'</div>'
	);
}
?>