<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: plugin.php,v 1.15 2005/11/16 23:49:16 nao-pon Exp $
//

// �ץ饰�����Ѥ�̤������ѿ�������
function set_plugin_messages($messages)
{
	foreach ($messages as $name=>$val)
	{
		global $$name;
		
		if(!isset($$name))
		{
			$$name = $val;
		}
	}
}

//�ץ饰����¸�ߤ��뤫
function exist_plugin($name)
{
	global $no_plugins;
	static $exists = array();
	
	if (!isset($no_plugins)) $no_plugins = array();
	
	$name = strtolower($name);	//Ryuji_edit(2003-03-18) add ��ʸ���Ⱦ�ʸ������̤��ʤ��ե����륷���ƥ���
	if(isset($exists[$name])) return $exists[$name];
	
	if (in_array($name,$no_plugins)) return false;
	
	if (preg_match('/^\w{1,64}$/',$name)
		and file_exists(PLUGIN_DIR.$name.'.inc.php'))
	{
		$exists[$name] = TRUE;
		require_once(PLUGIN_DIR.$name.'.inc.php');
		return TRUE;
	}
	else
	{
		$exists[$name] = FALSE;
		return FALSE;
	}
}

//�ץ饰����(action)��¸�ߤ��뤫
function exist_plugin_action($name)
{
	if (!exist_plugin($name))
	{
		return FALSE;
	}
	return function_exists('plugin_'.$name.'_action');
}

//�ץ饰����(convert)��¸�ߤ��뤫
function exist_plugin_convert($name)
{
	if (!exist_plugin($name))
	{
		return FALSE;
	}
	return function_exists('plugin_'.$name.'_convert');
}

//�ץ饰����(inline)��¸�ߤ��뤫
function exist_plugin_inline($name)
{
	if (!exist_plugin($name))
	{
		return FALSE;
	}
	return function_exists('plugin_'.$name.'_inline');
}

//�ץ饰����ν������¹�
function do_plugin_init($name)
{
	static $ret = array();
	
	if (isset($ret[$name])) return $ret[$name];
	
	$funcname = 'plugin_'.$name.'_init';
	if (!function_exists($funcname)) {
		$ret[$name] = FALSE;
		return $ret[$name];
	}
	
	$func_check = '_funccheck_'.$funcname;
	global $$func_check;
	
	if ($$func_check)
	{
		$ret[$name] = TRUE;
		return $ret[$name];
	}
	$$func_check = TRUE;
	$ret[$name] = @call_user_func($funcname);
	return $ret[$name];
}

//�ץ饰����(action)��¹�
function do_plugin_action($name)
{
	if(!exist_plugin_action($name))
	{
		return FALSE;
	}
	
	do_plugin_init($name);
	$retvar = call_user_func('plugin_'.$name.'_action');
	
	// ʸ�����󥳡��ǥ��󥰸����� hidden �ե�����ɤ���������
	return preg_replace('/(<form[^>]*>)(?!<!\-\-CHECK\-\->)/',"$1<!--CHECK-->\n<div><!--XOOPS_TOKEN_INSERT--><input type=\"hidden\" name=\"encode_hint\" value=\"��\" /></div>",$retvar);
}

//�ץ饰����(convert)��¹�
function do_plugin_convert($name,$args="")
{
	$args = str_replace("\\\"","&quot;",$args);
	// "��"�ǰϤ���ѥ�᡼���ϡ�,��ޤ�����Ǥ���褦��
	// ����ʸ�����ִ�
	$args = str_replace("&quot;,&quot;","\x1d\x1c",$args);
	$args = str_replace(",&quot;","\x1c",$args);
	$args = str_replace("&quot;,","\x1d",$args);
	$args = preg_replace("/^&quot;/","\x00\x1c",$args);
	$args = preg_replace("/&quot;$/","\x1d\x00",$args);
	// , �� \x08 ���Ѵ�
	$args = preg_replace("/(\x1c.*?\x1d)/e","str_replace(',','\x08','$1')",$args);
	// ����ʸ�����᤹
	$args = str_replace(array("\x00\x1c","\x1d\x00"),"",$args);
	$args = str_replace(array("\x1d\x1c","\x1c","\x1d"),",",$args);

	// ����˳�Ǽ
	$aryargs = ($args !== '') ? explode(',',$args) : array();

	// \x08 �� , ���᤹
	$aryargs = str_replace("\x08",",",$aryargs);

	do_plugin_init($name);
	$retvar = call_user_func_array('plugin_'.$name.'_convert',$aryargs);
	
	if($retvar === FALSE)
	{
		return htmlspecialchars('#'.$name.($args ? "($args)" : ''));
	}
	//�ץ饰����¦�ǥ���С��Ȥ���Ȳ��Τ�Apache���������礬����Τ�
	//������ͤ����äƤ����Ȥ��ϡ�̤����С��ȤʤΤǡ�����С��Ȥ��롣
	if (is_array($retvar)) $retvar = convert_html($retvar);
	
	// ʸ�����󥳡��ǥ��󥰸����� hidden �ե�����ɤ���������
	return preg_replace('/(<form[^>]*>)(?!<!\-\-CHECK\-\->)/',"$1<!--CHECK-->\n<div><!--XOOPS_TOKEN_INSERT--><input type=\"hidden\" name=\"encode_hint\" value=\"��\" /></div>",$retvar);
}

//�ץ饰����(inline)��¹�
function do_plugin_inline($name,$args="",$body="")
{
	// "��"�ǰϤ���ѥ�᡼���ϡ�,��ޤ�����Ǥ���褦��
	// ����ʸ�����ִ�
	$args = str_replace("\",\"","\x1d\x1c",$args);
	$args = str_replace(",\"","\x1c",$args);
	$args = str_replace("\",","\x1d",$args);
	$args = preg_replace("/^\"/","\x00\x1c",$args);
	$args = preg_replace("/\"$/","\x1d\x00",$args);
	// , �� \x08 ���Ѵ�
	$args = preg_replace("/(\x1c.*?\x1d)/e","str_replace(',','\x08','$1')",$args);
	// ����ʸ�����᤹
	$args = str_replace(array("\x00\x1c","\x1d\x00"),"",$args);
	$args = str_replace(array("\x1d\x1c","\x1c","\x1d"),",",$args);
	
	// ����˳�Ǽ
	$aryargs = ($args !== '') ? explode(',',$args) : array();

	// \x08 �� , ���᤹
	$aryargs = str_replace("\x08",",",$aryargs);
	
	$aryargs[] =& $body;

	do_plugin_init($name);
	$retvar = call_user_func_array('plugin_'.$name.'_inline',$aryargs);
	
	if($retvar === FALSE)
	{
		return htmlspecialchars("&${name}" . ($args ? "($args)" : '') . ';');
	}
	
	return $retvar;
}
?>