<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: popular.inc.php,v 1.4 2003/10/13 12:23:28 nao-pon Exp $
//

/*
 * PukiWiki popular �ץ饰����
 * (C) 2002, Kazunori Mizushima <kazunori@uc.netyou.jp>
 *
 * �͵��Τ���(������������¿��)�ڡ����ΰ����� recent �ץ饰����Τ褦��ɽ�����ޤ���
 * �̻�����Ӻ������̤��ư������뤳�Ȥ��Ǥ��ޤ���
 * counter �ץ饰����Υ�������������Ⱦ����ȤäƤ��ޤ���
 *
 * [������]
 * #popular
 * #popular(20)
 * #popular(20,FrontPage|MenuBar)
 * #popular(20,FrontPage|MenuBar,true)
 *
 * [����]
 * 1 - ɽ��������                             default 10
 * 2 - ɽ�������ʤ��ڡ���������ɽ��             default �ʤ�
 * 3 - ����(true)���̻�(false)�ΰ������Υե饰  default false
 */

// counter file : counter �ץ饰��������ꤷ�Ƥ����Τ�Ʊ���ˤ��Ʋ�������
if (!defined('COUNTER_DIR'))
	define('COUNTER_DIR', './counter/');

function plugin_popular_init()
{
	if (LANG == 'ja')
		$messages = array(
			'_popular_plugin_frame' => '<h5 class="side_label">�͵���%d��</h5><div>%s</div>',
			'_popular_plugin_today_frame' => '<h5 class="side_label" >������%d��</h5><div>%s</div>',
		);
	else
		$messages = array(
			'_popular_plugin_frame' => '<h5 class="side_label" >popular(%d)</h5><div>%s</div>',
			'_popular_plugin_today_frame' => '<h5 class="side_label" >today\'s(%d)</h5><div>%s</div>',
		);
	set_plugin_messages($messages);
}

function plugin_popular_convert()
{
	global $_popular_plugin_frame, $_popular_plugin_today_frame;
	global $script,$whatsnew,$non_list;
	
	$max = 10;
	$except = '';

	$array = func_get_args();
	$today = FALSE;

	switch (func_num_args()) {
	case 3:
		if ($array[2])
			$today = get_date('Y/m/d');
	case 2:
		$except = $array[1];
		$except = str_replace("&#124;","|",$except);
	case 1:
		$max = $array[0];
	}

	$counters = array();

	foreach (get_existpages(COUNTER_DIR,'.count') as $page) {
		if ($except != '' and ereg($except,$page)) {
			continue;
		}
		if ($page == $whatsnew or preg_match("/$non_list/",$page) or !is_page($page)) {
			continue;
		}
		// ��������
		if (!check_readable($page,false,false)) continue;
		
		$array = file(COUNTER_DIR.encode($page).'.count');
		$count = rtrim($array[0]);
		$date = rtrim($array[1]);
		$today_count = rtrim($array[2]);
		$yesterday_count = rtrim($array[3]);
		
		if ($today) {
			if ($today == $date) {
				// $page�����ͤ˸�����(���Ȥ���encode('BBS')=424253)�Ȥ���
				// array_splice()�ˤ�äƥ����ͤ��ѹ�����Ƥ��ޤ��Τ��ɤ�
				$counters["_$page"] = $today_count;
			}
		}
		else {
			$counters["_$page"] = $count;
		}
	}
	
	asort($counters, SORT_NUMERIC);
	$counters = array_splice(array_reverse($counters,TRUE),0,$max);
	
	$items = '';
	if (count($counters)) {
		$items = '<ul class="recent_list">';
		
		foreach ($counters as $page=>$count) {
			$page = htmlspecialchars(substr($page,1));
			$page_name = strip_bracket($page);
			$title = "$page_name<span class=\"counter\">($count)</span>";
			$s_page = htmlspecialchars($page);
			$items .= " <li>".make_pagelink($page)."<span class=\"counter\">($count)</span></li>\n";
			//$items .= " <li>".make_pagelink($page,"$s_page<span class=\"counter\">($count)</span>")."</li>\n";
			//$items .="<li><a href=\"".$script."?".rawurlencode($page)."\" title=\"$page_name ".get_pg_passage($page,false)."\">$title</a></li>\n";
			}
		$items .= '</ul>';
	}
	return sprintf($today ? $_popular_plugin_today_frame : $_popular_plugin_frame,count($counters),$items);
}

?>