<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: where.inc.php,v 1.4 2003/09/14 13:09:04 nao-pon Exp $
//
// ����ڡ����ؤγ��ؤ����դ�ɽ������ץ饰����
//
// �񼰡�#where ���� #where(�ڡ���̾)
// ���������ά����ȸ��ߤΥڡ��������ꤵ��롣
//
// �㡧�ڡ���̾�� "�ᥤ��/����/����ƥ��" �ξ��
// �ڡ����ؤΥ���դ�
// �ȥå� > �ᥤ�� > ���� > ����ƥ��
//   ��ɽ������롣
//  �ڡ���̾���ά�������ϡ��Ǹ�Υڡ���̾�ϥ�󥯤��Ĥ��ʤ���
//  �ڡ���̾����ꤹ��ȡ��Ǹ�Υڡ������󥯤��Ĥ���
/////////////////////////////////////////////////

function plugin_where_init() {
	if (LANG=='ja') {
		$_plugin_where_messages = array(
			'_where_msg_top' => '�ȥå�',
		);
	} else {
		$_plugin_where_messages = array(
			'_where_msg_top' => 'Top',
		);
	}
	set_plugin_messages($_plugin_where_messages);
}

function plugin_where_convert() {
	global $script,$vars;
	$prefix = "";
	$aryargs = func_get_args();
	if ($aryargs[0]) $prefix = strip_bracket(htmlspecialchars($aryargs[0]));
	//if ($defaultpage == $prefix) return false;
	return "<div>".plugin_where_make($prefix)."</div>";
}

function plugin_where_inline() {
	global $script,$vars;
	$prefix = "";
	$aryargs = func_get_args();
	if ($aryargs[0]) $prefix = strip_bracket(htmlspecialchars($aryargs[0]));
	return plugin_where_make($prefix);
}

function plugin_where_make($prefix) {
	global $_where_msg_top;
	global $script,$vars,$defaultpage;
	$this_page = false;

	if (!($prefix) || !(page_exists("[[".$prefix."]]"))) {
		$prefix = strip_bracket($vars['page']);
		$this_page = true;
	}
	$page_names = array();
	$page_names = explode("/",$prefix);
	$title = $defaultpage.' '.get_pg_passage($defaultpage,FALSE);
	//$ret = "<a href=\"$script?cmd=read&amp;page=$defaultpage\" title=\"$title\">$_where_msg_top</a>";
	$ret = "<a href=\"$script?$defaultpage\" title=\"$title\">$_where_msg_top</a>";
	$access_name = "";
	foreach ($page_names as $page_name){
		$access_name .= $page_name."/";
		$name = substr($access_name,0,strlen($access_name)-1);
		$title = $name.' '.get_pg_passage("[[".$name."]]",FALSE);
		//$href = $script.'?cmd=read&amp;page='.rawurlencode($name);
		$href = $script.'?'.rawurlencode(strip_bracket($name));
		if ($prefix == $name){
			if ($this_page) {
				$ret .= " &gt; <b>$page_name</b>";
			} else {
				$ret .= " &gt; <a href=\"$href\" title=\"$title\"><b>$page_name</b></a>";
			}
		} else {
			$ret .= " &gt; <a href=\"$href\" title=\"$title\">$page_name</a>";
		}
	}
	return $ret;
}
?>