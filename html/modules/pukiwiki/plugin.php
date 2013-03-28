<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: plugin.php,v 1.15 2005/11/16 23:49:16 nao-pon Exp $
//

// プラグイン用に未定義の変数を設定
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

//プラグインが存在するか
function exist_plugin($name)
{
	global $no_plugins;
	static $exists = array();
	
	if (!isset($no_plugins)) $no_plugins = array();
	
	$name = strtolower($name);	//Ryuji_edit(2003-03-18) add 大文字と小文字を区別しないファイルシステム対
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

//プラグイン(action)が存在するか
function exist_plugin_action($name)
{
	if (!exist_plugin($name))
	{
		return FALSE;
	}
	return function_exists('plugin_'.$name.'_action');
}

//プラグイン(convert)が存在するか
function exist_plugin_convert($name)
{
	if (!exist_plugin($name))
	{
		return FALSE;
	}
	return function_exists('plugin_'.$name.'_convert');
}

//プラグイン(inline)が存在するか
function exist_plugin_inline($name)
{
	if (!exist_plugin($name))
	{
		return FALSE;
	}
	return function_exists('plugin_'.$name.'_inline');
}

//プラグインの初期化を実行
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

//プラグイン(action)を実行
function do_plugin_action($name)
{
	if(!exist_plugin_action($name))
	{
		return FALSE;
	}
	
	do_plugin_init($name);
	$retvar = call_user_func('plugin_'.$name.'_action');
	
	// 文字エンコーディング検出用 hidden フィールドを挿入する
	return preg_replace('/(<form[^>]*>)(?!<!\-\-CHECK\-\->)/',"$1<!--CHECK-->\n<div><!--XOOPS_TOKEN_INSERT--><input type=\"hidden\" name=\"encode_hint\" value=\"ぷ\" /></div>",$retvar);
}

//プラグイン(convert)を実行
function do_plugin_convert($name,$args="")
{
	$args = str_replace("\\\"","&quot;",$args);
	// "と"で囲んだパラメータは、,を含む事ができるように
	// 制御文字へ置換
	$args = str_replace("&quot;,&quot;","\x1d\x1c",$args);
	$args = str_replace(",&quot;","\x1c",$args);
	$args = str_replace("&quot;,","\x1d",$args);
	$args = preg_replace("/^&quot;/","\x00\x1c",$args);
	$args = preg_replace("/&quot;$/","\x1d\x00",$args);
	// , を \x08 に変換
	$args = preg_replace("/(\x1c.*?\x1d)/e","str_replace(',','\x08','$1')",$args);
	// 制御文字を戻す
	$args = str_replace(array("\x00\x1c","\x1d\x00"),"",$args);
	$args = str_replace(array("\x1d\x1c","\x1c","\x1d"),",",$args);

	// 配列に格納
	$aryargs = ($args !== '') ? explode(',',$args) : array();

	// \x08 を , に戻す
	$aryargs = str_replace("\x08",",",$aryargs);

	do_plugin_init($name);
	$retvar = call_user_func_array('plugin_'.$name.'_convert',$aryargs);
	
	if($retvar === FALSE)
	{
		return htmlspecialchars('#'.$name.($args ? "($args)" : ''));
	}
	//プラグイン側でコンバートすると何故かApacheがこける場合があるので
	//配列で値が帰ってきたときは、未コンバートなので、コンバートする。
	if (is_array($retvar)) $retvar = convert_html($retvar);
	
	// 文字エンコーディング検出用 hidden フィールドを挿入する
	return preg_replace('/(<form[^>]*>)(?!<!\-\-CHECK\-\->)/',"$1<!--CHECK-->\n<div><!--XOOPS_TOKEN_INSERT--><input type=\"hidden\" name=\"encode_hint\" value=\"ぷ\" /></div>",$retvar);
}

//プラグイン(inline)を実行
function do_plugin_inline($name,$args="",$body="")
{
	// "と"で囲んだパラメータは、,を含む事ができるように
	// 制御文字へ置換
	$args = str_replace("\",\"","\x1d\x1c",$args);
	$args = str_replace(",\"","\x1c",$args);
	$args = str_replace("\",","\x1d",$args);
	$args = preg_replace("/^\"/","\x00\x1c",$args);
	$args = preg_replace("/\"$/","\x1d\x00",$args);
	// , を \x08 に変換
	$args = preg_replace("/(\x1c.*?\x1d)/e","str_replace(',','\x08','$1')",$args);
	// 制御文字を戻す
	$args = str_replace(array("\x00\x1c","\x1d\x00"),"",$args);
	$args = str_replace(array("\x1d\x1c","\x1c","\x1d"),",",$args);
	
	// 配列に格納
	$aryargs = ($args !== '') ? explode(',',$args) : array();

	// \x08 を , に戻す
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