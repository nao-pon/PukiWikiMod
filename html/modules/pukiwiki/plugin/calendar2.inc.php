<?php
// $Id: calendar2.inc.php,v 1.28 2006/01/17 00:42:33 nao-pon Exp $
// *引数にoffと書くことで今日の日記を表示しないようにした。

// initialize plug-in
function plugin_calendar2_init() {
	if (LANG=='ja') {
		$_plugin_calendar2_messages = array(
			'_calendar2_msg_nextmonth' => '前月',
			'_calendar2_msg_prevmonth' => '次月',
			'_calendar2_msg_detail' => '%sの詳細',
			'_calendar2_msg_month' => '月',
			'_calendar2_msg_day' => '日',
			'_calendar2_msg_write' => '[書き込む]',
			'_calendar2_plugin_edit' => '[この日記を編集]',
			'_calendar2_plugin_empty' => '%sは書いていません。(^^ゞ',
			'_calendar2_msg_write_more' => '[記事を追加する]',
			'_calendar2_msg_read_more' => '<< 続きを読む >>',
		);
	} else {
		$_plugin_calendar2_messages = array(
			'_calendar2_msg_nextmonth' => 'Next',
			'_calendar2_msg_prevmonth' => 'Previous',
			'_calendar2_msg_detail' => 'Details of %s',
			'_calendar2_msg_month' => '/',
			'_calendar2_msg_day' => '',
			'_calendar2_msg_write' => '[Write]',
			'_calendar2_plugin_edit' => '[edit]',
			'_calendar2_plugin_empty' => '%s is empty.',
			'_calendar2_msg_write_more' => '[Write more]',
			'_calendar2_msg_read_more' => '<< Read more >>',
		);
	}
	set_plugin_messages($_plugin_calendar2_messages);
}

function plugin_calendar2_convert()
{
	global $_calendar2_msg_nextmonth, $_calendar2_msg_prevmonth, $_calendar2_msg_write,$_calendar2_msg_write_more,$_calendar2_msg_read_more;
	global $_calendar2_msg_detail, $_calendar2_msg_month, $_calendar2_msg_day;
	global $script,$weeklabels,$vars,$command,$WikiName,$BracketName,$post,$get;
	global $_calendar2_plugin_edit, $_calendar2_plugin_empty, $anon_writable, $_msg_month;
	global $wiki_user_dir,$comment_no,$h_excerpt,$digest;
	
	global $use_xoops_comments,$_msg_pagecomment,$_msg_trackback;
	
	$today_view = true;
	$category_view = "";
	$contents_lev = $act = 0;
	$args = func_get_args();
	
	if(func_num_args() == 0)
	{
		$date_str = date("Ym");
		$pre = strip_bracket($vars['page']);
		$prefix = strip_bracket($vars['page'])."/";
	}
	else{
		foreach ($args as $arg){
			if(is_numeric($arg) && (strlen($arg) >= 6 && strlen($arg) <= 8)){
				$date_str = $arg;
				$act = 1;
			}
			else if($arg == "off"){
				$today_view = false;
			}
			else if(strtolower(substr($arg,0,9)) == "category:"){
				$category_view = htmlspecialchars(substr($arg,8));
			}
			else if(strtolower(substr($arg,0,9)) == "contents:"){
				$contents_lev = (int)substr($arg,9);
			}
			else {
				$pre = strip_bracket($arg);
				$prefix = strip_bracket($arg)."/";
			}
		}
		if(empty($date_str)) $date_str = date("Ym");
		if(empty($pre)){
			$pre = strip_bracket($vars['page']);
			$prefix = strip_bracket($vars['page'])."/";
		}
	}
	
	$today_tag = ($act)? "<small>[".make_pagelink($pre,$pre.".".Today)."]</small>" : "";
	
	if($pre == "*") {
		$prefix = '';
		$pre = '';
	}
	$prefix_ = rawurlencode($pre);
	$prefix = strip_tags($prefix);
	
	if(!$command) $cmd = "read";
	else          $cmd = $command;
	
	
	$yr = substr($date_str,0,4);
	$mon = substr($date_str,4,2);
	$now_day = substr($date_str,6,2);
	$single_day = true;
	
	if (!$now_day){
		$single_day = false;
		if($yr != date("Y") || $mon != date("m")) {
			$now_day = 1;
			$other_month = 1;
		}
		else {
			$now_day = date("d");
			$other_month = 0;
		}
	}
	//echo "$mon-$now_day-$yr<br>";
	$today = getdate(mktime(0,0,0,$mon,$now_day,$yr));
	
	$m_num = $today[mon];
	$d_num = $today[mday];
	$year = $today[year];
	if (!$m_num) $m_num = 1;
	if (!$year) $year = 1970;
	$f_today = getdate(mktime(0,0,0,$m_num,1,$year));
	$wday = $f_today[wday];
	$day = 1;
	$fweek = true;

	$m_name = "$year.$m_num ($cmd)";
	
	if(!preg_match("/^(($WikiName)|($BracketName))$/",$pre))
		$prefix_url = "[[".$pre."]]";
	else
		$prefix_url = $pre;

	$prefix_url = rawurlencode($prefix_url);
	$pre = strip_bracket($pre);
	
	$ret .= '<table border="0" style="width:100%;"><tr><td style="text-align:left;vertical-align:top">';
	
	$y = substr($date_str,0,4)+0;
	$m = substr($date_str,4,2)+0;
	
	$prev_date_str = sprintf("%04d%02d",$y,$m-1);
	if($m-1<1) {
		$prev_date_str = sprintf("%04d%02d",$y-1,12);
	}
	$next_date_str = sprintf("%04d%02d",$y,$m+1);
	if($m+1>12) {
		$next_date_str = sprintf("%04d%02d",$y+1,1);
	}
	
	if($prefix == "") {
		$ret .= '
<table class="style_calendar" cellspacing="1" border="0" style="width:150px;">
  <tr>
    <td align="middle" class="style_td_caltop" colspan="7">
      <table style="width:100%;"><tr><td style="text-align:left;vertical-align:top;" nowrap>
      <a href="'.$script.'?plugin=calendar2&amp;file='.$prefix_.'&amp;date='.$prev_date_str.'" title="'.$_calendar2_msg_prevmonth.'"><img src="./image/prev.png" alt="Prev" /></a>
      </td><td style="text-align:center;vertical-align:top;font-size:11px;">
       <form method="GET" action="'.$script.'">
        <input type="hidden" name="plugin" value="calendar2">
        <input type="hidden" name="file" value="'.$pre.'">
        <select name="year" style="font-size:11px;">';
		for ($i = 1970 ; $i < 2038 ; $i++){
			if ($i == $year){
				$ret .= '<option value="'.$i.'" selected>'.$i;
			} else {
				$ret .= '<option value="'.$i.'">'.$i;
			}
		}
		$ret .= '
        </select>
        <select name="month" style="font-size:11px;">';
		for ($i = 1 ; $i <= 12 ; $i++){
			if ($i == $m_num){
				$ret .= '<option value="'.$i.'" selected>'.$_msg_month[$i];
			} else {
				$ret .= '<option value="'.$i.'">'.$_msg_month[$i];
			}
		}
        $ret .= '
        </select>
        <input type="submit" value="Go" style="font-size:11px;">
       </td></form><td style="text-align:left;vertical-align:top;" nowrap>
      <a href="'.$script.'?plugin=calendar2&amp;file='.$prefix_.'&amp;date='.$next_date_str.'" title="'.$_calendar2_msg_nextmonth.'"><img src="./image/next.png" alt="Next" /></a>
      </td></tr></table>
    </td>
  </tr>
  <tr>
';
	}
	else {
		$ret .= '
<table class="style_calendar" cellspacing="1" border="0">
  <tr>
    <td align="middle" class="style_td_caltop" colspan="7">
      <table style="width:100%;"><tr><td style="text-align:left;vertical-align:top;" nowrap>
      <a href="'.$script.'?plugin=calendar2&amp;file='.$prefix_.'&amp;date='.$prev_date_str.'&amp;ca='.rawurlencode($category_view).'&amp;co='.$contents_lev.'" title="'.$_calendar2_msg_prevmonth.'"><img src="./image/prev.png" alt="Prev" /></a>
      </td><td style="text-align:center;vertical-align:top;font-size:11px;">
       <form method="GET" action="'.$script.'">
        <input type="hidden" name="plugin" value="calendar2">
        <input type="hidden" name="file" value="'.$pre.'">
        <input type="hidden" name="ca" value="'.$category_view.'">
        <input type="hidden" name="co" value="'.$contents_lev.'">
        <select name="year" style="font-size:11px;">';
		for ($i = 1970 ; $i < 2038 ; $i++){
			if ($i == $year){
				$ret .= '<option value="'.$i.'" selected>'.$i;
			} else {
				$ret .= '<option value="'.$i.'">'.$i;
			}
		}
		$ret .= '
        </select>
        <select name="month" style="font-size:11px;">';
		for ($i = 1 ; $i <= 12 ; $i++){
			if ($i == $m_num){
				$ret .= '<option value="'.$i.'" selected>'.$_msg_month[$i];
			} else {
				$ret .= '<option value="'.$i.'">'.$_msg_month[$i];
			}
		}
        $ret .= '
        </select>
        <input type="submit" value="Go" style="font-size:11px;">
      </td></form><td style="text-align:left;vertical-align:top;" nowrap>
      <a href="'.$script.'?plugin=calendar2&amp;file='.$prefix_.'&amp;date='.$next_date_str.'&amp;ca='.rawurlencode($category_view).'&amp;co='.$contents_lev.'" title="'.$_calendar2_msg_nextmonth.'"><img src="./image/next.png" alt="Next" /></a>
      </td></tr></table>
      '.$today_tag.'
    </td>
  </tr>
  <tr>
';
	}
	$week_i = 0;
	foreach($weeklabels as $label)
	{
		$ret .= '<td align="middle" class="style_td_week">';
    	if ($week_i == 0) {
			$ret .='<div class="small" style="text-align:center"><strong><span class="style_sunday_font">'.$label.'</span></strong></div>';
		} else if ($week_i == 6){
			$ret .='<div class="small" style="text-align:center"><strong><span class="style_satday_font">'.$label.'</span></strong></div>';
		} else {
			$ret .='<div class="small" style="text-align:center"><strong>'.$label.'</strong></div>';
      	}
    	$ret .='</td>';
		$week_i++;
	}

	$ret .= "</tr>\n<tr>\n";
	//echo "$m_num-$day-$year";
	while(checkdate($m_num,$day,$year))
	{
		//echo "ok!";
		$dt = sprintf("%4d-%02d-%02d", $year, $m_num, $day);
		$linkdt = sprintf("%4d%02d%02d", $year, $m_num, $day);
		$name = "$prefix$dt";
		$page = "[[$prefix$dt]]";
		$page_url = rawurlencode("[[$prefix$dt]]");
		$holiday = check_holiday($year,$m_num,$day);
		$title_tag = $name;
		if ($holiday) $title_tag .= "[".get_holiday($holiday)."]";
		
		if($cmd == "edit") $refer = "&amp;refer=$page_url";
		else               $refer = "";
		
		$a_tag = "<a href=\"$script?plugin=calendar2&amp;file=$prefix_&amp;date=$linkdt&amp;ca=".rawurlencode($category_view)."&amp;co={$contents_lev}\" title=\"$title_tag\" class=\"small\">";
		$moblog_page = "[[".strip_bracket($page)."-0]]";
		if((!is_page($page) && !is_page($moblog_page)) || ((is_page($page) || is_page($moblog_page)) && !check_readable($page,false,false))){
			$td_style = "";
			$link = "$a_tag<span class=\"_DAY_STYLE_\">$day</span></a>";
		}else{
			$td_style = " style=\"background-image:url(image/pencil.gif);background-repeat:no-repeat;\"";
			$link = "$a_tag<span class=\"_DAY_STYLE_\"><strong>$day</strong></span></a>";
		}

		if($fweek)
		{
			for($i=0;$i<$wday;$i++)
			{ // Blank 
				$ret .= "    <td align=\"center\" class=\"style_td_blank\">&nbsp;</td>\n"; 
			} 
		$fweek=false;
		}

		if($wday == 0 && $day > 1) $ret .= "  </tr><tr>\n";
		if($wday == 0 || ($holiday))
		{
			//  Sunday 
			$link = str_replace("_DAY_STYLE_","style_sunday_font",$link);
			if(!$other_month && ($day == $today[mday]) && ($m_num == $today[mon]) && ($year == $today[year])){
				$ret .= "    <td align=\"center\" class=\"style_td_today\"$td_style nowrap><strong>$link</strong></td>\n";
			} else {
				$ret .= "    <td align=\"center\" class=\"style_td_sun\"$td_style nowrap>$link</td>\n";
			}
		}
		else if($wday == 6)
		{
			//  Saturday 
			$link = str_replace("_DAY_STYLE_","style_satday_font",$link);
			if(!$other_month && ($day == $today[mday]) && ($m_num == $today[mon]) && ($year == $today[year])){
				$ret .= "    <td align=\"center\" class=\"style_td_today\"$td_style nowrap><strong>$link</strong></td>\n";
			} else {
				$ret .= "    <td align=\"center\" class=\"style_td_sat\"$td_style nowrap>$link</td>\n";
			}
		}
		else
		{
			// Weekday 
			$link = str_replace("_DAY_STYLE_","style_weekday_font",$link);
			if(!$other_month && ($day == $today[mday]) && ($m_num == $today[mon]) && ($year == $today[year])){
				$ret .= "    <td align=\"center\" class=\"style_td_today\"$td_style nowrap><strong>$link</strong></td>\n";
			} else {
				$ret .= "    <td align=\"center\" class=\"style_td_day\"$td_style nowrap>$link</td>\n";
			}
		}
		$day++;
		$wday++;
		$wday = $wday % 7;
	}
	if($wday > 0)
	{
		while($wday < 7)
		{ // Blank 
			$ret .= "    <td align=\"center\" class=\"style_td_blank\">&nbsp;</td>\n";
		$wday++;
		} 
	}

	$ret .= "  </tr>\n</table>\n";
	if ($today_view == true){
		$page = sprintf("%s%4d-%02d-%02d", $prefix, $today[year], $today[mon], $today[mday]);
		$h_date = sprintf("%4d-%02d-%02d", $today[year], $today[mon], $today[mday]);
		global $trackback;
		$str = "<div class = \"style_calendar_date\">".$h_date."</div>";
		$str .= "<!--contents list-->";
		$__page = "";
		$page_found = false;
		$p_count = 0;
		$ct_list = "";
		for ($i=-1;$i<10;$i++)
		{
			$daynum = ($i !== -1)? "-".$i:"";
			$_page = $page.$daynum;
			// 閲覧権限チェック＋
			if (is_page($_page) && check_readable($_page,false,false))
			{
				$p_count ++;
				$user_tag = get_pg_auther_name($_page);
				make_user_link($user_tag,"",true);
				$user_tag = make_link($user_tag);
				$show_tag = "by ".$user_tag." at ".get_makedate_byname($_page)." ".make_pagelink($_page,"<img src=\"./image/link.gif\" />");
				$comments_tag = ($use_xoops_comments)? " [ ".make_pagelink($_page,$_msg_pagecomment."(".get_pagecomment_count(get_pgid_by_name($_page)).")",'#page_comments')." ]" : "";
				$tb_tag = ($trackback)? " [ ".make_pagelink($_page,$_msg_trackback."(".tb_count($_page).")",'#tb_body')." ]" : "";
				$info_tag = "<div style=\"text-align:right\">by $user_tag at ".get_makedate_byname($_page)." ".make_pagelink($_page,"<img src=\"./image/link.gif\" />")."<small>".$comments_tag.$tb_tag."</small></div>";
				
				//インクルード
				list($_body,$_contents) = include_page($_page,TRUE);
				$str .= $info_tag.$_body;
				$ct_list .= $_contents;
				
				if (check_editable($_page,FALSE,FALSE)) $str .= "<a class=\"small\" href=\"$script?cmd=edit&amp;page=".rawurlencode($_page)."\">$_calendar2_plugin_edit</a>";
				$str .= "<div style=\"clear:both;\"></div><hr />";
				$page_found = true;
			}
			else
			{
				if ($i > 0)
				{
					if (!$other_month) {
						if ($i === 0 && !$__page)
							$page_url = rawurlencode($page."-1");
						else
							$page_url = ($__page)? rawurlencode($__page) : rawurlencode($_page);
						
						$up_freeze_info = get_freezed_uppage($page);
						
						if (!$page_found)
						{
							$str .= sprintf($_calendar2_plugin_empty,$today[mon].$_calendar2_msg_month.$today[mday].$_calendar2_msg_day);
							if (!$up_freeze_info[4]) $str .= "<p><a href=\"$script?cmd=$cmd&amp;page=$page_url$refer\" class=\"small\">".$_calendar2_msg_write."</a></p>";
						}
						else
						{
							if (!$up_freeze_info[4]) $str .= "<p><a href=\"$script?cmd=$cmd&amp;page=$page_url$refer\" class=\"small\">".$_calendar2_msg_write_more."</a></p>";
						}
					} else {
						$str = "";
					}
					break;
				}
				else
				{
					if ($i === -1)
						$__page = $page;
				}
			}
		}
		
	}else{
		$str = "";
	}
	
	if ($contents_lev)
	{
		$str = str_replace("<!--contents list-->",select_contents_by_level($ct_list,$contents_lev),$str);
	}
	
	$categorys = "";
	if ($category_view)
	{
		$pcon = new pukiwiki_converter();
		$pcon->safe = TRUE;
		$pcon->page = $vars['page'];
		$pcon->string = "****Categorys\n#ls2($category_view,pagename,notemplate,relatedcount)";
		$categorys = $pcon->convert();
		unset($pcon);
	}
	
	$_contents = $_body = "";
	if (exist_plugin_convert("calendar_viewer") && ($vars['file'] != "") && !$single_day)
	{
		$_co_lev = ($contents_lev)? ",contents:{$contents_lev}":"";
		$aryargs = "[[".$vars['file']."]],".substr($vars['date'],0,4)."-".substr($vars['date'],4,2).",view,cal2".$_co_lev;
		do_plugin_init('calendar_viewer');
		list($_contents,$_body) = array_pad(preg_split("#<\!\-\-/contents list\-\->#",plugin_calendar_viewer_convert(explode(',',$aryargs))),2,"");
		if (!$_body)
		{
			$_body = $_contents;
			$_contents = "";
		}
	}
	
	$ret .= "$categorys</td><td style=\"text-align:left;vertical-align:top;width:100%;\">".$str.$_contents."</td></tr></table>";
	$ret .= $_body;
	unset($aryargs);

	return $ret;
}

function plugin_calendar2_action()
{
	global $command,$vars,$h_excerpt;
	global $xoopsModule, $xoopsUser, $modifier, $hide_navi, $anon_writable;
	
	$command = 'read';
	if (!isset($vars['date'])) $vars['date']=sprintf("%04d%02d",$vars['year'],$vars['month']);

	$vars['page'] = (empty($vars['file']))? "" : add_bracket($vars['file']);
	
	if (!is_page($vars['page']))
		return array('msg'=>'ERROR: There is no page.','body'=>'ERROR: There is no page.');
	
	$date = $vars['date'];
	$off = ",off";
	
	if($date=='')
		$date = date("Ym");
	
	if (substr($date,6,2))
	{
		$yy = sprintf("%04d.%02d.%02d",substr($date,0,4),substr($date,4,2),substr($date,6,2));
		$off = "";
	}
	else
	{
		$yy = sprintf("%04d.%02d",substr($date,0,4),substr($date,4,2));
	}
	
	//$aryargs = $vars['page'].",".$date."off,";
	$aryargs = $vars['page'].",".$date.$off;
	
	if (isset($vars['category']) && ($vars['category']))
		$aryargs .= ",Category".rawurldecode($vars['category']);
	if (isset($vars['ca']) && ($vars['ca']))
		$aryargs .= ",Category".rawurldecode($vars['ca']);
	
	if (isset($vars['co']) && ($vars['co']))
		$aryargs .= ",contents:".(int)($vars['co']);
	
	$ret["msg"] = htmlspecialchars(strip_bracket($vars['page']))." / ".$yy;
	$ret["body"] = do_plugin_convert("calendar2",$aryargs);
	$h_excerpt = "";
	
	return $ret;
}

//==============================================================================
// Calendar Ver1.0
// Copyright (C) 2002 Kenichi OHWADA All Rights Reserved. 
// http://ohwada.net/index-jp.html
// 2002.09.21 K.OHWADA
//==============================================================================
// === 祝日かどうか判定する ===
function check_holiday($year,$mon,$mday)
{
  // for internationalization
  if (LANG!="ja") return 0;

  $wday = date( "w", mktime(0,0,0,$mon,$mday,$year,0) );

  $day  = 100*$mon + $mday;

// 春分日・秋分日
// 1980年から2099年に適用する
// http://www.town.bisei.okayama.jp/stardb/cal/data/cal0031.html
  $tmp = (int)(20.8431 + 0.242194*($year-1980) - (int)(($year-1980)/4));
  $syunbun      = 300 + $tmp;
  $syunbun_next = $syunbun + 1;

  $tmp = (int)(23.2488 + 0.242194*($year-1980) - (int)(($year-1980)/4));
  $shubun      = 900 + $tmp;
  $shubun_next = $shubun + 1;

// 元旦
  if      ($day== 101)               return  1;
  elseif (($day== 102)&&($wday==1))  return 16;

// 建国記念日
  elseif  ($day== 211)               return  3;
  elseif (($day== 212)&&($wday==1))  return 16;

// 春分の日
  elseif  ($day==$syunbun)                    return  4;
  elseif (($day==$syunbun_next)&&($wday==1))  return 16;

// みどりの日 1989年より前は天皇誕生日
  elseif  (($year>=1989)&&($day== 429))               return  5;
  elseif (($year>=1989)&&($day== 430)&&($wday==1))  return 16;
  elseif  (($year<1989)&&($day== 429))               return 14;
  elseif (($year<1989)&&($day== 430)&&($wday==1))  return 16;

// 憲法記念日
  elseif ($day== 503)  return  6;

// 国民の休日
  elseif ($day== 504)  return 15;
  elseif (($year==2009)&&($day== 922))  return 15;

// 子供の日
  elseif  ($day== 505)               return  7;
  elseif (($day== 506)&&($wday==1))  return 16;

// 秋分の日
  elseif  ($day==$shubun )                    return 10;
  elseif (($day==$shubun_next )&&($wday==1))  return 16;

// 文化の日
  elseif  ($day==1103)               return 12;
  elseif (($day==1104)&&($wday==1))  return 16;

// 勤労感謝の日
  elseif  ($day==1123)               return 13;
  elseif (($day==1124)&&($wday==1))  return 16;

// 天皇誕生日 1989年以降
  elseif  (($year>=1989)&&($day==1223))               return 14;
  elseif (($year>=1989)&&($day==1224)&&($wday==1))  return 16;


// 平成１０年法律第１４１号
// 成人の日を1/15から1月の第2月曜日に,体育の日を10/10から10月の第2月曜日に改める
// 平成12年1月1日(2000年)から施行する
// http://www2s.biglobe.ne.jp/~law/law/ldb/H10H0141.htm

  elseif (($year< 2000)&&($day== 115))                           return  2;
  elseif (($year>=2000)&&($day>= 108)&&($day<= 114)&&($wday==1)) return  2;
  elseif (($year< 2000)&&($day== 116)&&($wday==1))               return 16;

  elseif (($year< 2000)&&($day==1010))                           return 11;
  elseif (($year>=2000)&&($day>=1008)&&($day<=1014)&&($wday==1)) return 11;
  elseif (($year< 2000)&&($day==1011)&&($wday==1))               return 16;


// 平成７年法律第２２号
// 海の日 7/20
// 平成8年1月1日(1996年)から施行する。
  elseif (($year>=1996)&&($year< 2003)&&($day== 720))             return  8;
  elseif (($year>=1996)&&($year< 2003)&&($day== 721)&&($wday==1)) return 16;


// 平成１３年法律第５９号
// 海の日を7/20から7月の第3月曜日に,敬老の日の9/15を9月の第3月曜日に改める
// 平成15年1月1日(2003年)から施行する。
// http://www2s.biglobe.ne.jp/~law/law/ldb/H13H0059.htm

  elseif (($year>=2003)&&($day>= 715)&&($day<= 721)&&($wday==1)) return  8;

  elseif (($year< 2003)&&($day== 915))                           return  9;
  elseif (($year>=2003)&&($day>= 915)&&($day<= 921)&&($wday==1)) return  9;
  elseif (($year< 2003)&&($day== 916)&&($wday==1))               return 16;

  return 0;
}

// === 祝日の名前を得る ===
function get_holiday($i)
{
  $holiday = array("","元日","成人の日","建国記念日","春分の日",
    "みどりの日","憲法記念日","こどもの日","海の日","敬老の日",
    "秋分の日","体育の日","文化の日","勤労感謝の日","天皇誕生日",
    "国民の休日","振替休日");

  return $holiday[$i];
}

?>