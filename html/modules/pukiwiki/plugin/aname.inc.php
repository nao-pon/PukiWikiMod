<?php
// $Id: aname.inc.php,v 1.5 2004/11/24 13:15:35 nao-pon Exp $

// internationalization
function plugin_aname_init() {
	if (LANG=='ja') {
		$_plugin_aname_messages = array(
			'_aname_msg_noargs' => '引数を指定してください',
			'_aname_msg_badname' => '不正なAnameです',
		);
	} else {
		$_plugin_aname_messages = array(
			'_aname_msg_noargs' => 'no arguments!!',
			'_aname_msg_badname' => 'Bad Aname!!',
		);
	}
	set_plugin_messages($_plugin_aname_messages);
}

function plugin_aname_convert()
{
  global $_aname_msg_noargs, $_aname_msg_badname;

  if (!func_num_args()) return "aname: ".$_aname_msg_noargs."\n";
  $aryargs = func_get_args();
  if (eregi("^[A-Z][A-Z0-9\-_]*$", $aryargs[0]))
    return "<a name=\"$aryargs[0]\" id=\"$aryargs[0]\"></a>";
  else
    return $_aname_msg_badname." -- ".htmlspecialchars($aryargs[0])."\n";
}
?>