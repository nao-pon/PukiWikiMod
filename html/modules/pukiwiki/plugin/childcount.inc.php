<?php
// $Id: childcount.inc.php,v 1.1 2004/12/23 14:04:04 nao-pon Exp $

function plugin_childcount_inline()
{
	// �p�����[�^��z��Ɋi�[
	$args = func_get_args();
	
	// {}���̒l
	$page = array_pop($args); // in {} = htmlspecialchars
	
	// {}�����Ȃ����()�����擾����
	if (!$page) list($page) = array_pad($args, 1, '');
	
	// �^����ꂽ�l���y�[�W������Ȃ���
	if ($page && !is_page($page)) return false;
	
	// �y�[�W���̎w�肪�Ȃ��Ƃ��͕\�����̃y�[�W��
	if (!$page) $page = $vars['page'];
	
	// ���w�y�[�W����Ԃ�
	return get_child_counts($page);
}
?>