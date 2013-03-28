<?php
/*
 * PukiWiki �ǿ���?���ɽ������ץ饰����
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 * 
 * �ѹ�����:
 *  2002.04.08: pat���󡢤ߤΤ뤵��λ�Ŧ�ˤ�ꡢ����褬���ܸ�ξ���
 *              ������Τ���
 * 
 *  2002.06.17: plugin_recent_init()������
 *  2002.07.02: <ul>�ˤ����Ϥ��ѹ�����¤��
 *
 * $id$
 */

function plugin_recent_init()
{
	if (LANG == "ja") {
		$_plugin_recent_messages = array(
    '_recent_plugin_frame'=>'<h5 class="side_label" style="margin:auto;margin-top:0px;margin-bottom:.5em">%s�ǿ���%d��</h5>%s');
  } else {
		$_plugin_recent_messages = array(
    '_recent_plugin_frame'=>'<h5 class="side_label" style="margin:auto;margin-top:0px;margin-bottom:.5em">%sRecent(%d)</h5>%s');
	}
  set_plugin_messages($_plugin_recent_messages);
}

function plugin_recent_convert()
{
	global $_recent_plugin_frame;
	global $WikiName,$BracketName,$script,$whatsnew,$X_admin;
	global $_list_left_margin, $_list_margin;
	
	$recent_lines = 0;
	$prefix = "";
	if(func_num_args()>0) {
		$args = func_get_args();
		$recent_lines = (int)$args[0];
		$prefix = $args[0];
		$prefix = preg_replace("/\/$/","",$prefix);
		if (is_page($prefix))
		{
			$recent_lines = isset($args[1])? (int)$args[1] : 0;
		}
		else if (isset($args[1]))
		{
			$prefix = $args[1];
			$prefix = preg_replace("/\/$/","",$prefix);
			if (is_page($prefix))
			{
				$recent_lines = isset($args[0])? (int)$args[0] : 0;
			}
			else
				$prefix = "";
		}
		else
			$prefix = "";
	}
	if (!$recent_lines) $recent_lines = 10;

	if ($prefix)
	{
		$prefix = strip_bracket($prefix);
		$_prefix = $prefix . "/";
	}
	else
	{
		$_prefix = "";
	}
	$pages = get_existpages_db(false,$_prefix,$recent_lines," ORDER BY editedtime DESC",true);
	if ($pages)
	{
		$_style = $_list_left_margin + $_list_margin;
		$_style = " style=\"margin-left:". $_style ."px;padding-left:". $_style ."px;\"";

		$date = $items = "";
		$cnt = 0;
		foreach ($pages as $page)
		{
			$_date = get_filetime($page);
			if(date("Y-n-j",$_date) != $date) {
					if($date != "") {
						$items .= "</ul>";
					}
					$date = date("Y-n-j",$_date);
					$items .= "<div class=\"recent_date\">".$date."</div><ul class=\"recent_list\"{$_style}>";
			}
			
			if ($prefix)
			{
				$_p = replace_pagename_d2s($page);
				$prefix = replace_pagename_d2s($prefix);
				$pg_link = make_pagelink($page,preg_replace("/^".preg_quote($prefix,"/")."\//","",$_p));
			}
			else
				$pg_link = make_pagelink($page);
			
			$items .="<li>".$pg_link."</li>\n";
			$cnt++;
		}
	}

	$items .="</ul>";
	if ($prefix) $prefix = make_pagelink($prefix).": ";
	return sprintf($_recent_plugin_frame,$prefix,$cnt,$items);
}
?>