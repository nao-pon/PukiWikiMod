<?php
// $Id: newpage.inc.php,v 1.4 2004/05/20 13:59:11 nao-pon Exp $

function plugin_newpage_init()
{
	$messages = array(
		'_msg_newpage' => 'ページ新規作成'
	);
	set_plugin_messages($messages);
}

function plugin_newpage_convert()
{
	global $script,$vars,$_btn_edit,$_msg_newpage,$BracketName;
	
	$newpage = '';
	if (func_num_args()) {
		list($newpage) = func_get_args();
	}
	$array = func_get_args();
	
	$template_page = $newpage = "";
	
	switch (func_num_args())
	{
		case 2:
			$template_page = htmlspecialchars(trim($array[1]));
		case 1:
			$newpage = trim($array[0]);
	}
	
	if ($newpage == 'this')
		$newpage = $vars['page'];
	else
		$newpage = add_bracket($newpage);
	
	if (!preg_match("/^$BracketName$/",$newpage)) {
		$newpage = '';
	}
	$s_page = htmlspecialchars(array_key_exists('refer',$vars) ? $vars['refer'] : $vars['page']);
	$s_newpage = ($newpage)? htmlspecialchars(strip_bracket($newpage))."/" : "" ;
	$ret = <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="newpage" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="prefix" value="$s_newpage" />
  <input type="hidden" name="template_page" value="$template_page" />
  $_msg_newpage:
  <b>$s_newpage</b><input type="text" name="page" size="50" value="" />
  <input type="submit" value="$_btn_edit" />
 </div>
</form>
EOD;
	
	return $ret;
}

function plugin_newpage_action()
{
	global $vars,$script,$_btn_edit,$_msg_newpage;
	
	if ($vars['page'] == '') {
		$retvars['msg'] = $_msg_newpage;
		$retvars['body'] = plugin_newpage_convert();
		return $retvars;
	}
	$page = $vars['prefix'].strip_bracket($vars['page']);
	$r_page = rawurlencode(array_key_exists('refer',$vars) ?
		get_fullname($page,$vars['refer']) : $page);
	$r_refer = rawurlencode($vars['refer']);
	$template_page = (!empty($vars['template_page']))? "&template_page=".rawurlencode(add_bracket($vars['template_page'])) : "";
	
	header("Location: $script?cmd=read&page=$r_page&refer=$r_refer$template_page");
	die();
}
?>
