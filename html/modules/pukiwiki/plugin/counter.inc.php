<?php
/*
 * PukiWiki カウンタープラグイン
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 *
 * $Id: counter.inc.php,v 1.11 2006/06/27 23:47:13 nao-pon Exp $
 */

// counter file
if (!defined('COUNTER_EXT'))
{
	define('COUNTER_EXT','.count');
}

function plugin_counter_inline()
{
	global $vars;

	$arg = '';
	if (func_num_args() > 0)
	{
		$args = func_get_args();
		$arg = strtolower($args[0]);
	}

	$counter = plugin_counter_get_count($vars['page']);

	switch ($arg)
	{
		case 'today':
		case 'yesterday':
			$count = $counter[$arg];
			break;
		default:
			$count = $counter['total'];
	}
	return $count;
}

function plugin_counter_convert()
{
	global $vars;

	$counter = plugin_counter_get_count($vars['page']);

	return <<<EOD
<div class="counter">
Counter: {$counter['total']},
today: {$counter['today']},
yesterday: {$counter['yesterday']}
</div>
EOD;
}

function plugin_counter_get_count($page)
{
	global $vars;
	static $counters = array();
	static $default;

	// カウンタのデフォルト値
	if (!isset($default))
	{
		$default = array(
			'total'     => 0,
			'date'      => get_date('Y/m/d'),
			'today'     => 0,
			'yesterday' => 0,
			'ip'        => ''
		);
	}
	if (!is_page($page))
	{
		return $default;
	}
	if (array_key_exists($page,$counters))
	{
		return $counters[$page];
	}

	// カウンタのデフォルト値をセット
	$counters[$page] = $default;

	// カウンタファイルが存在する場合は読み込む
	$file = COUNTER_DIR.encode($page).COUNTER_EXT;
	$fp = fopen($file, file_exists($file) ? 'r+' : 'w+')
		or die_message('counter.inc.php:cannot open '.$file);
	set_file_buffer($fp, 0);
	flock($fp,LOCK_EX);
	rewind($fp);

	foreach ($default as $key=>$val)
	{
		$counters[$page][$key] = rtrim(fgets($fp,256));
		if (feof($fp)) { break; }
	}
	// ファイル更新が必要か?
	$modify = FALSE;

	// 日付が変わった
	if ($counters[$page]['date'] != $default['date'])
	{
		$modify = TRUE;
		$is_yesterday = ($counters[$page]['date'] == get_date('Y/m/d',strtotime('yesterday',UTIME)));
		$counters[$page]['ip']        = $_SERVER['REMOTE_ADDR'];
		$counters[$page]['date']      = $default['date'];
		$counters[$page]['yesterday'] = $is_yesterday ? $counters[$page]['today'] : 0;
		$counters[$page]['today']     = 1;
		$counters[$page]['total']++;
	}
	// IPアドレスが異なる
	else if ($counters[$page]['ip'] != $_SERVER['REMOTE_ADDR'])
	{
		$modify = TRUE;
		$counters[$page]['ip']        = $_SERVER['REMOTE_ADDR'];
		$counters[$page]['today']++;
		$counters[$page]['total']++;
	}
	
	//なぜかカウンタがリセットされることがある問題に対処
	if (!$counters[$page]['total'] || preg_match("/[^0-9]/",$counters[$page]['total'])) $modify = FALSE;

	//ページ読み出し時のみファイルを更新
	if ($modify and $vars['cmd'] == 'read')
	{
		// ファイルを丸める
		rewind($fp);
		ftruncate($fp,0);
		// 書き出す
		foreach (array_keys($default) as $key)
		{
			fputs($fp,$counters[$page][$key]."\n");
			$$key = $counters[$page][$key];
		}

		// DBを更新
		global $xoopsDB;
		$name = strip_bracket($page);
		$s_name = addslashes($name);
		$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." WHERE name='$s_name';";
		$result=$xoopsDB->query($query);

		if (mysql_num_rows($result))
		{
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." SET count=$total,today='$date',today_count=$today,yesterday_count=$yesterday,ip='$ip' WHERE name='$s_name';";
			$result=$xoopsDB->queryF($query);
		}
		else
		{
			$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." (name,count,today,today_count,yesterday_count,ip) VALUES('$s_name',$total,'$date',$today,$yesterday,'$ip');";
			$result=$xoopsDB->queryF($query);
		}

	}
	// ファイルを閉じる
	flock($fp,LOCK_UN);
	fclose($fp);

	return $counters[$page];
}
?>
