<?php
/*
 * Created on 2007/04/26 by nao-pon http://hypweb.net/
 * $Id: api.inc.php,v 1.1 2007/12/06 04:18:32 nao-pon Exp $
 */

function plugin_api_action () {
	
	global $vars;
	
	$cmd = (isset($vars['pcmd']))? (string)$vars['pcmd'] : '';
	
	switch ($cmd) {
		case 'autolink':
			plugin_api_autolink();
	}
	
	exit();
	
}

function plugin_api_autolink ($need_ret = false, $base = null) {
	
	global $vars;
	
	if (is_null($base)) {
		$base = '';
		$base = (isset($vars['base']))? (string)$vars['base'] : '';
	}
	$base = trim($base, '/');
	
	$cache = CACHE_DIR.md5($base).'.autolink.api';
	
	if (file_exists($cache)) {
		$out = join('',file($cache));
	} else {
		$pages = array();
		if (!$base || is_page($base)) {
			// 常にゲストユーザーとして処理
			global $X_admin,$X_uid;
			$X_admin =0;
			$X_uid =0;
			
			$pages = get_existpages_db(false,$base,0,"",true,false,true,true);
			
			if ($base) {
				$pages = array_diff($pages, array($base));
				$pages = array_map(create_function('$page','return substr($page,'.(strlen($base)+1).');'), $pages);
			}
		}
		
		if ($pages) {
			sort($pages, SORT_STRING);
			$out = get_matcher_regex_safe($pages);
		} else {
			$out = '(?!)';
		}
		
		$fp = fopen($cache, 'w');
		fwrite($fp, $out);
		fclose($fp);
	}
	if ($need_ret) {
		return $out;
	} else {
		plugin_api_output($out);
	}
}

function plugin_api_output ($str) {
	header ("Content-Type: text/plain; charset=".CONTENT_CHARSET);
	header ("Content-Length: ".strlen($str));
	echo $str;
	exit();
}
?>