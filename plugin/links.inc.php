<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: links.inc.php,v 1.2 2003/10/31 12:22:59 nao-pon Exp $
// ORG: links.inc.php,v 1.17 2003/05/19 09:22:08 arino Exp $
//

// ��å���������
function plugin_links_init()
{
	$messages = array(
		'_links_messages'=>array(
			'title_update'  => '����å��幹��',
			'msg_adminpass' => '�����ԥѥ����',
			'btn_submit'    => '�¹�',
			'msg_done'      => '����å���ι�������λ���ޤ�����',
			'msg_usage'     => "
* ��������

:����å���򹹿�:���ƤΥڡ����򥹥���󤷡�����ڡ������ɤΥڡ��������󥯤���Ƥ��뤫��Ĵ�����ơ�����å���˵�Ͽ���ޤ���

* ���
�¹ԤˤϿ�ʬ��������⤢��ޤ����¹ԥܥ���򲡤������ȡ����Ф餯���Ԥ�����������

* �¹�
[�¹�]�ܥ���� ''1��Τ�'' ����å����Ƥ���������~
���β��˼¹ԥܥ���ɽ������Ƥ��ʤ����ϡ������Ը��¤ǥ����󤷤ƺ�ɽ�����Ƥ���������
"
		)
	);
	set_plugin_messages($messages);
}

function plugin_links_action()
{
	global $script,$post,$vars,$adminpass,$foot_explain;
	global $_links_messages,$X_admin;
	
	if (empty($vars['action']) or !$X_admin)
	{
		$body = convert_html($_links_messages['msg_usage']);
	if ($X_admin)
	{
		$body .= <<<EOD
<form method="POST" action="$script">
 <div>
  <input type="hidden" name="plugin" value="links" />
  <input type="hidden" name="action" value="update" />
  <input type="submit" value="{$_links_messages['btn_submit']}" />
 </div>
</form>
EOD;
	}
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$body
		);
	}
	else if ($vars['action'] == 'update')
	{
		error_reporting(E_ALL);
		links_init();
		
		// ������ˤ���
		$foot_explain = array();
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$_links_messages['msg_done']
		);
	}
	
	return array(
		'msg'=>$_links_messages['title_update'],
		'body'=>$_links_messages['err_invalid']
	);
}
?>
