<?php
// $Id: ls2.inc.php,v 1.28 2006/03/06 06:20:30 nao-pon Exp $
/*
Last-Update:2002-10-29 rev.8

*�ץ饰���� ls2
�۲��Υڡ����θ��Ф�(*,**,***)�ΰ�����ɽ������

*Usage
 #ls2(�ѥ�����[,�ѥ�᡼��])

*�ѥ�᡼��
-�ѥ�����(�ǽ�˻���)~
��ά����Ȥ��⥫��ޤ�ɬ��
-title~
 ���Ф��ΰ�����ɽ������
-include~
 ���󥯥롼�ɤ��Ƥ���ڡ����θ��Ф���Ƶ�Ū����󤹤�
-link~
 action�ץ饰�����ƤӽФ���󥯤�ɽ��
-reverse~
 �ڡ������¤ӽ��ȿž�����߽�ˤ���
-pagename~
 �ѥ��Ͼʤ��ƥڡ���̾�Τ�ɽ��
-notemplate~
 �ƥ�ץ졼�ȥڡ����ϥꥹ�ȥ��åפ��ʤ�
*/

//���Ф����󥫡��ν�
//define('LS2_CONTENT_HEAD','#content:'); // html.php 1.35����
define('LS2_CONTENT_HEAD','#content_1_'); // html.php 1.36�ʹ�

//���Ф����󥫡��γ����ֹ�
//define('LS2_ANCHOR_ORIGIN',''); // html.php 1.35����
define('LS2_ANCHOR_ORIGIN',0); // html.php 1.36�ʹ�

function plugin_ls2_init() {
	global $_ls2_anchor;
	if (!isset($_ls2_anchor)) { $_ls2_anchor = 0; }
	$messages = array('_ls2_messages'=>array(
		'err_nopages' => '<p>\'$1\' �ˤϡ������ؤΥڡ����Ϥ���ޤ���</p>',
		'msg_title' => '\'$1\'�ǻϤޤ�ڡ����ΰ���',
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
		
	$params = array('link'=>FALSE,'title'=>FALSE,'include'=>FALSE,'reverse'=>FALSE,'_args'=>array(),'pagename'=>FALSE,'notemplate'=>FALSE,'relatedcount'=>FALSE,'depth'=>FALSE,'nonew'=>FALSE,'col'=>1,'_done'=>FALSE);
	//array_walk($args, 'ls2_check_arg', &$params);
	//�ʤ��� $args �Υ��С�����¿���� array_walk �Ǥ�PHP������뤳�Ȥ�����
	foreach($args as $key=>$val)
	{
		ls2_check_arg($val, $key, $params);
	}
	
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
	global $_list_left_margin, $_list_margin;
	
	$pages = ls2_get_child_pages($prefix,$params['depth']);
	//list($pages,$child_count) = explode(" ",ls2_get_child_pages($prefix,$params['depth']));
	if ($params['reverse']) $pages = array_reverse($pages);

	foreach ($pages as $page) { $params[$page] = 0; }

	$c_pages = count($pages);

	if ($c_pages == 0) { return str_replace('$1',htmlspecialchars($prefix),$_ls2_messages['err_nopages']); }

	if ($c_pages < $params['col']) $params['col'] = $c_pages;
	$rows = (int)(ceil($c_pages / $params['col']));
	if ($params['col'] === 1)
	{
		$width = "auto";
	}
	else
	{
		$width = ((int)(100 / $params['col'] - 1)) . "%";
	}
	
	$c_row = 1;
	$ret = "";
	$_style = $_list_left_margin + $_list_margin;
	$_style = " style=\"margin-left:". $_style ."px;padding-left:". $_style ."px;\"";

	foreach ($pages as $page)
	{
		if ($c_row === 1) $ret .= '<div style="float:left;width:'.$width.'"><ul'.$_style.'>';
		list($page,$child_count) = explode(" ",$page);
		$ret .= ls2_show_headings($page,$params,FALSE,$prefix,$child_count);
		if ($c_row === $rows)
		{
			$ret .= '</ul></div>'."\n";
			$c_row = 0;
		}
		$c_row++;
	}
	if ($c_row !== 1) $ret .= '</ul></div>'."\n";
	$ret .= '<div style="clear:both;"></div>'."\n";
	return $ret;
}

function ls2_show_headings($page,&$params,$include = FALSE,$prefix="",$child_count="") {
	global $script,$auto_template_name;
	global $_ls2_anchor, $_ls2_messages;
	global $_list_left_margin, $_list_margin;
	
	static $_auto_template_name = "";
	static $bef_name = "";
	
	if (!$_auto_template_name) $_auto_template_name = preg_quote($auto_template_name,'/');
	
	// �ƥ�ץ졼�ȥڡ�����ɽ�����ʤ����
	if ($params['notemplate'] && preg_match("/\/".$_auto_template_name."(_m)?$/",$page)) return;
	
	$ret = '';
	$rules = '/\(\(((?:(?!\)\)).)*)\)\)/';
	$is_done = (isset($params[$page]) and $params[$page] > 0); //�ڡ�����ɽ���ѤߤΤȤ�True
	if (!$is_done) { $params[$page] = ++$_ls2_anchor; }
	
	$name = strip_bracket($page);
	$title = $name.' '.get_pg_passage($page,FALSE);
	$href = get_url_by_name($page);

	//�ڡ���̾���ֿ�����-�פ����ξ��ϡ�*(**)�Ԥ�������Ƥߤ�
	$_name = "";
	$arg = array();
	if (preg_match("/(^|.*\/)[0-9\-]+$/",$name,$arg))
	{
		$_name_base = htmlspecialchars($arg[1]);
		$_name = get_heading($page);
	}

	if ($params['pagename'])
	{
		//���ڡ���̾�Ͼʤ� nao-pon
		$_name_base = "";
		if (strpos($name,"/") !== FALSE && $name != $prefix)
		{
			$name = str_replace($prefix."/","",$name);
			$_is_base = false;
		}
		else
		{
			$_is_base = true;
		}
		
		//ȴ���Ƥ���ڡ������ؤΥ����å�
		if (!$_is_base && $bef_name)
		{
			//���ԤοƳ���
			$bef_par = preg_replace("/(^|\/)[^\/]+$/","",$bef_name);
			//���ԤοƳ���
			$now_par = preg_replace("/(^|\/)[^\/]+$/","",$name);
			
			//�Ǥ������;ʬ�ʽ����򤵤��ʤ����ᡢ�ޤ��Ϥ����Ƚ��
			if ($bef_par != $now_par && $bef_name != $now_par)
			{
				//���ԤγƳ���
				$bef_lev = explode("/",$bef_name);
				//���ԤγƳ���
				$now_lev = explode("/",$name);
				array_pop($now_lev);
				
				$now_cnt = count($now_lev);
				if ($now_cnt > count($bef_lev))
				{
					$bef_lev = array_pad($bef_lev,$now_cnt,"");
				}
				
				for($i=0; $i<$now_cnt; $i++)
				{
					if ($bef_lev[$i] != $now_lev[$i])
					{
						$c_margin = $i * ($_list_margin + $_list_left_margin);
						$ret .= '<li class="list'.($i+1).'" style="margin-left:'.$c_margin.'px;">'.htmlspecialchars($now_lev[$i]).'</li>';
					}
				}
			}
		}
		//���ԤΥڡ����Ȥ��Ƶ���
		$bef_name = $name;
		
		//���ؤǥޡ���������
		$name = str_replace("/","\t",$name);//�ޥ���Х��Ȥ��θ����TAB���Ѵ�
		$c_count =count_chars($name);
		if ($_is_base) {
			$c_margin = 0; //���ڡ���
		} else {
			$c_margin = $c_count[9] * ($_list_margin + $_list_left_margin);//TAB�Υ����ɡ᣹
		}
		//[/(\t���Ѵ���)]�����򥫥å�
		$name = preg_replace("/.*\t/","",$name);

		$ret .= '<li class="list'.($c_count[9]+1).'" style="margin-left:'.$c_margin.'px;">';
	} else {
		$ret .= '<li style="margin-left;">';
	}
	
	$name = htmlspecialchars($name);
	if ($_name) $name = $_name_base.$_name;
	
	if ($params['relatedcount'])
		$name .= " (".links_get_related_count($page).")";
	
	$child_count = ($child_count)? " [<a href='$script?plugin=list&prefix=".rawurlencode(strip_bracket($page))."'>+".$child_count."</a>]":"";
	
	//New�ޡ����ղ�
	if (!$params['nonew'] && exist_plugin_inline("new"))
		$new_mark = do_plugin_inline("new","{$page}/,nolink","");
	
	if ($include) { $ret .= 'include '; }
	$ret .= '<a id="list_'.$params[$page].'" href="'.$href.'" title="'.$title.'">'.$name.'</a>'.$child_count.$new_mark;
	if ($params['title'] and $is_done) {
		$ret .= '<a href="#list_'.$params[$page].'">+</a></li>'."\n";
		return $ret;
	}
	
	if ($params['title'] || $params['include'])
	{
		$anchor = LS2_ANCHOR_ORIGIN;
		$_ret = '';
		$source = get_source($page);
		// ���Ф��θ�ͭID������
		$source = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m','$1$2',$source);
		foreach ($source as $line) {
			$matches = array();
			if ($params['title'] and preg_match('/^(\*+)(.*)()?$/',$line,$matches)) {
				$special = strip_tags(make_line_rules(inline($matches[2],TRUE)));
				$left = (strlen($matches[1]) - 1) * 16;
				$_ret .= '<li style="margin-left:'.$left.'px">'.$special.
					'<a href="'.$href.LS2_CONTENT_HEAD.$anchor.'">'.$_ls2_messages['msg_go'].'</a></li>'."\n";
				$anchor++;
			}
			else if ($params['include'] and preg_match('/^#include\((.+)\)/',$line,$matches) and is_page($matches[1]))
				$_ret .= ls2_show_headings($matches[1],$params,TRUE);
		}
		if ($_ret != '') { $ret .= "<ul>$_ret</ul>\n"; }
	}
	$ret .= '</li>'."\n";
	return $ret;
}
function ls2_get_child_pages($prefix,$depth=FALSE) {
	global $vars;
	
	$pages = array();
	foreach (array_diff(get_existpages_db(false,$prefix."/",0,"",false,false,true,true),array($prefix)) as $_page)
	{
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
//���ץ�������Ϥ���
function ls2_check_arg($val, $key, &$params)
{
	if ($val == '') { $params['_done'] = TRUE; return; }

	if (!$params['_done']) {
		foreach (array_keys($params) as $key)
		{
			if (strpos($val,':')) // PHP4.3.4��Apache2 �Ķ��ǲ��Τ�Apache�������Ȥ���𤬤��ä��Τ�
				list($_val,$thisval) = explode(":",$val);
			else
			{
				$_val = $val;
				$thisval = null;
			}
			if (strtolower($_val) == $key)
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
	$params['_args'][] = htmlspecialchars($val);
}
?>