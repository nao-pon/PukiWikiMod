<?php
// $Id: ls2.inc.php,v 1.11 2004/01/30 14:47:12 nao-pon Exp $
/*
Last-Update:2002-10-29 rev.8

*プラグイン ls2
配下のページの見出し(*,**,***)の一覧を表示する

*Usage
 #ls2(パターン[,パラメータ])

*パラメータ
-パターン(最初に指定)~
省略するときもカンマが必要
-title~
 見出しの一覧を表示する
-include~
 インクルードしているページの見出しを再帰的に列挙する
-link~
 actionプラグインを呼び出すリンクを表示
-reverse~
 ページの並び順を反転し、降順にする
-pagename~
 パスは省いてページ名のみ表示
-notemplate~
 テンプレートページはリストアップしない
*/

//見出しアンカーの書式
//define('LS2_CONTENT_HEAD','#content:'); // html.php 1.35以前
define('LS2_CONTENT_HEAD','#content_1_'); // html.php 1.36以降

//見出しアンカーの開始番号
//define('LS2_ANCHOR_ORIGIN',''); // html.php 1.35以前
define('LS2_ANCHOR_ORIGIN',0); // html.php 1.36以降

function plugin_ls2_init() {
	global $_ls2_anchor;
	if (!isset($_ls2_anchor)) { $_ls2_anchor = 0; }
	$messages = array('_ls2_messages'=>array(
		'err_nopages' => '<p>\'$1\' には、下位層のページがありません。</p>',
		'msg_title' => '\'$1\'で始まるページの一覧',
		'msg_go' => '<span class="small">...</span>',
	));
  set_plugin_messages($messages);
}
function plugin_ls2_action() {
	global $vars;
	global $_ls2_messages;

	$params = array();
	foreach (array('title','include','reverse','depth') as $key)
		$params[$key] = array_key_exists($key,$vars);
	$prefix = array_key_exists('prefix',$vars) ? $vars['prefix'] : '';
	$body = ls2_show_lists($prefix,$params);

	return array(
		'body'=>$body,
		'msg'=>str_replace('$1',htmlspecialchars($prefix),$_ls2_messages['msg_title'])
	);
}

function plugin_ls2_convert() {
	global $script,$vars;
	global $_ls2_messages;

	$prefix = '';
	if (func_num_args()) {
		$args = func_get_args();
		$prefix = array_shift($args);
		$prefix = preg_replace("/\/$/","",$prefix);
	} else {
		$args = array();
	}
	if ($prefix == '')
		//$prefix = strip_bracket($vars['page']).'/';
		$prefix = strip_bracket($vars['page']);
		
	$params = array('link'=>FALSE,'title'=>FALSE,'include'=>FALSE,'reverse'=>FALSE,'_args'=>array(),'pagename'=>FALSE,'notemplate'=>FALSE,'relatedcount'=>FALSE,'depth'=>FALSE,'_done'=>FALSE);
	array_walk($args, 'ls2_check_arg', &$params);
	$title = (count($params['_args']) > 0) ?
		join(',', $params['_args']) :
		str_replace('$1',htmlspecialchars($prefix),$_ls2_messages['msg_title']);

	if ($params['link']) {
		$tmp = array();
		$tmp[] = 'plugin=ls2&amp;prefix='.$prefix;
		if (isset($params['title'])) { $tmp[] = 'title=1'; }
		if (isset($params['include'])) { $tmp[] = 'include=1'; }
		return '<p><a href="'.$script.'?'.join('&amp;',$tmp).'">'.$title.'</a></p>'."\n";
	}
	return ls2_show_lists($prefix,$params);
}
function ls2_show_lists($prefix,&$params) {
	global $_ls2_messages;
	$pages = ls2_get_child_pages($prefix,$params['depth']);
	//list($pages,$child_count) = explode(" ",ls2_get_child_pages($prefix,$params['depth']));
	if ($params['reverse']) $pages = array_reverse($pages);

	foreach ($pages as $page) { $params[$page] = 0; }

	if (count($pages) == 0) { return str_replace('$1',htmlspecialchars($prefix),$_ls2_messages['err_nopages']); }

	$ret = '<ul>';
	foreach ($pages as $page)
	{
		list($page,$child_count) = explode(" ",$page);
		$ret .= ls2_show_headings($page,$params,FALSE,$prefix,$child_count);
	}
	$ret .= '</ul>'."\n";
	return $ret;
}

function ls2_show_headings($page,&$params,$include = FALSE,$prefix="",$child_count="") {
	global $script,$auto_template_name;
	global $_ls2_anchor, $_ls2_messages;
	static $_auto_template_name = "";
	
	if (!$_auto_template_name) $_auto_template_name = preg_quote($auto_template_name,'/');
	
	// テンプレートページは表示しない場合
	//echo $auto_template_name;
	if ($params['notemplate'] && preg_match("/\/".$_auto_template_name."(_m)?$/",$page)) return;
	
	$ret = '';
	$rules = '/\(\(((?:(?!\)\)).)*)\)\)/';
	$is_done = (isset($params[$page]) and $params[$page] > 0); //ページが表示済みのときTrue
	if (!$is_done) { $params[$page] = ++$_ls2_anchor; }
	
	$name = strip_bracket($page);
	$title = $name.' '.get_pg_passage($page,FALSE);
	//$href = $script.'?cmd=read&amp;page='.rawurlencode($page);
	$href = $script.'?'.rawurlencode($name);

	//ページ名が「数字と-」だけの場合は、*(**)行を取得してみる
	$_name = "";
	if (preg_match("/^(.*\/)?[0-9\-]+$/",$name)){
		$_body = get_source($page);
		foreach($_body as $line){
			if (preg_match("/^\*{1,6}(.*)/",$line,$reg)){
				$_name = str_replace(array("[[","]]"),"",$reg[1]);
				break;
			}
		}
	}

	if ($params['pagename']){
		//基準ページ名は省く nao-pon
		if ($name != $prefix) {
			$name = str_replace($prefix,"",$name);
			$_is_base = false;
		} else {
			$_is_base = true;
		}
		
		//階層でマージン設定
		$name = str_replace("/","\t",$name);//マルチバイトを考慮してTABに変換
		$c_count =count_chars($name);
		if ($_is_base) {
			$c_margin = 0; //基準ページ
		} else {
			$c_margin = $c_count[9]*15;//TABのコード＝９
		}
		//[/(\tに変換済)]以前をカット
		$name = preg_replace("/.*\t/","",$name);

		$ret .= '<li style="margin-left:'.$c_margin.'px;">';
	} else {
		$ret .= '<li style="margin-left;">';
	}
	
	if ($_name) $name = $_name;
	
	if ($params['relatedcount'])
		$name .= " (".links_get_related_count($page).")";
	
	$child_count = ($child_count)? " [+".$child_count."]":"";
	
	if ($include) { $ret .= 'include '; }
	$ret .= '<a id="list_'.$params[$page].'" href="'.$href.'" title="'.$title.'">'.htmlspecialchars($name).'</a>'.$child_count;
	if ($params['title'] and $is_done) {
		$ret .= '<a href="#list_'.$params[$page].'">+</a></li>'."\n";
		return $ret;
	}
	$anchor = LS2_ANCHOR_ORIGIN;
	$_ret = '';
	foreach (get_source($page) as $line) {
		if ($params['title'] and preg_match('/^(\*+)(.*)$/',$line,$matches)) {
			$special = strip_htmltag(make_line_rules(inline($matches[2],TRUE)));
			$left = (strlen($matches[1]) - 1) * 16;
			$_ret .= '<li style="margin-left:'.$left.'px">'.$special.
				'<a href="'.$href.LS2_CONTENT_HEAD.$anchor.'">'.$_ls2_messages['msg_go'].'</a></li>'."\n";
			$anchor++;
		}
		else if ($params['include'] and preg_match('/^#include\((.+)\)/',$line,$matches) and is_page($matches[1]))
			$_ret .= ls2_show_headings($matches[1],$params,TRUE);
	}
	if ($_ret != '') { $ret .= "<ul>$_ret</ul>\n"; }
	$ret .= '</li>'."\n";
	return $ret;
}
function ls2_get_child_pages($prefix,$depth=FALSE) {
	global $vars;
	
	$pages = array();
	foreach (get_existpages_db(false,$prefix."/") as $_page)
	{
		$_page = strip_bracket($_page);
		if ((int)$depth)
		{
			$c_count =count_chars(preg_replace("/^".preg_quote($prefix,'/')."\//","",$_page));
			$child_count = (($c_count[47] + 1) == $depth)? " ".get_child_counts($_page) : "";
			if ($c_count[47] < $depth)
				$pages[$_page.$child_count] = str_replace("/","\x00",$_page);
		}
		else
			$pages[$_page] = str_replace("/","\x00",$_page);
	}
	natcasesort($pages);

	return array_keys($pages);
}
//オプションを解析する
function ls2_check_arg($val, $key, &$params) {
	if ($val == '') { $params['_done'] = TRUE; return; }

	if (!$params['_done']) {
		foreach (array_keys($params) as $key)
		{
			list($val,$thisval) = split(":",$val);
			if (strtolower($val) == $key)
			{
				if (!empty($thisval))
					$params[$key] = $thisval;
				else
					$params[$key] = TRUE;
				return;
			}
		}
		$params['_done'] = TRUE;
	}
	$params['_args'][] = $val;
}
?>
