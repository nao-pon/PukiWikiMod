<?php
// $Id: rsstop.inc.php,v 1.2 2004/05/15 11:37:07 nao-pon Exp $

function plugin_rsstop_convert()
{
	global $vars, $content_id;
	// $content_id �� 2�ʾ�ˤʤäƤ������
	// ���󥯥롼�ɤ���Ƥ���Τǽ������ʤ���
	// �ơ��֥륻����ˤ� #rsstop �Ͻ񤤤Ƥⵡǽ���ʤ���
	if ($content_id === 1) $vars['is_rsstop'] = 1;
	return '';
}
?>