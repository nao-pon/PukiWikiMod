<?php
/*
 * PukiWiki calendar_viewer�ץ饰����
 *
 *
 *$Id: calendar_viewer.inc.php,v 1.28 2006/03/06 06:20:30 nao-pon Exp $
  calendarrecent�ץ饰����򸵤˺���
 */
/**
 *����
  calendar�ץ饰�����calendar2�ץ饰����Ǻ��������ڡ��������ɽ�����뤿��Υץ饰����Ǥ���
 *��������
  -2002-11-13
  --����ؤΥ�󥯤�ǯ���ּ���n��פ�ɽ������褦�ˤ�����
 *�Ȥ���
  /// #calendar_viewer(pagename,(yyyy-mm|n|this),[mode],[separater],notoday)
 **pagename
  calendar or calendar2�ץ饰����򵭽Ҥ��Ƥ�ڡ���̾
 **(yyyy-mm|n|this)
  -yyyy-mm
  --yyyy-mm�ǻ��ꤷ��ǯ��Υڡ��������ɽ��
  -n
  --n��ΰ���ɽ��
  -this
  --����Υڡ��������ɽ��
 **[mode]
  ��ά��ǽ�Ǥ�����ά���Υǥե���Ȥ�past
  -past
  --���������Υڡ����ΰ���ɽ���⡼�ɡ������������������
  -future
  --�����ʹߤΥڡ����ΰ���ɽ���⡼�ɡ����٥��ͽ��䥹�����塼�����
  -view
  --����̤��ؤΰ���ɽ���⡼�ɡ�ɽ���޻ߤ���ڡ����Ϥ���ޤ���
  -[separater]
  ��ά��ǽ���ǥե���Ȥ�-����calendar2�ʤ��ά��OK��
  --ǯ��������ڤ륻�ѥ졼������ꡣ
 **notoday
  -����ʬ��ɽ�����ʤ����ץ����

 *todo
  past or future �Ƿ�ñ��ɽ������Ȥ��ˡ����줾�������ΰ����ؤΥ�󥯤�ɽ�����ʤ��褦�ˤ���

 */

// initialize variables
function plugin_calendar_viewer_init() {
	if (LANG=='ja') {
		$_plugin_calendar_viewer_messages = array(
			'_calendar_viewer_msg_arg2' => '����������Ѥ���',
			'_calendar_viewer_msg_noargs' => '��������ꤷ�Ƥ�',
			'_calendar_viewer_msg_edit' => '�Խ�',
			'_calendar_viewer_read_more' => '<< ³�����ɤ� >>',
			'_calendar_viewer_contents' => '<h4>ɽ����Υ����ȥ�</h4>',
		);
	} else {
		$_plugin_calendar_viewer_messages = array(
			'_calendar_viewer_msg_arg2' => 'check the second argument',
			'_calendar_viewer_msg_noargs' => 'argument not found',
			'_calendar_viewer_msg_edit' => 'Edit',
			'_calendar_viewer_read_more' => '<< Read more >>',
			'_calendar_viewer_contents' => '<h4>Title Lists</h4>',
		);
	}
	set_plugin_messages($_plugin_calendar_viewer_messages);
}


function plugin_calendar_viewer_convert($func_vars_array="")
{
	global $_calendar_viewer_msg_arg2, $_calendar_viewer_msg_noargs, $_calendar_viewer_msg_edit, $_calendar_viewer_read_more,$_calendar_viewer_contents;
	global $WikiName,$BracketName,$vars,$get,$post,$hr,$script,$trackback;
	global $anon_writable,$wiki_user_dir;
	global $comment_no,$h_excerpt,$digest,$use_xoops_comments;
	
	global $_msg_pagecomment,$_msg_trackback;
	
	//return false;
	//*�ǥե�����ͤ򥻥å�
	//���Ȥʤ�ڡ���̾
	$pagename = "";
	//ɽ������������
	$limit_page = 7;
	//����ɽ������ǯ��
	$date_YM = "";
	//ư��⡼��
	$mode = "past";
	//���դΥ��ѥ졼�� calendar2�ʤ�"-" calendar�ʤ�""
	$date_sep = "-";
	//����ʬ��ɽ�����ʤ�
	$notuday = false;
	


	$cal2=0;


//echo "this!";
	//*�����γ�ǧ
	if(func_num_args()>=2 || is_array($func_vars_array))
	{
		if (!is_array($func_vars_array)) $func_vars_array = func_get_args();
		$_options = array();
	foreach($func_vars_array as $option)
	{
		if (strtolower($option) == "notoday")
			$notoday = true;
		else if(strtolower(substr($option,0,9)) == "contents:")
			$contents_lev = (int)substr($option,9);
		else
			$_options[] = $option;
	}
	$func_vars_array = $_options;
	unset($_options,$option);
	
		$pagename = $func_vars_array[0];
		if (strtolower($pagename) == "this")
			$pagename = $vars['page'];
		else
				$pagename = add_bracket($pagename);

		if (isset($func_vars_array[3])){
			if ($func_vars_array[3] == "cal2"){
				$cal2 = 1;
			} else {
				$date_sep = htmlspecialchars($func_vars_array[3]);
			}
		}
		$reg_array = array();
		if (preg_match("/[0-9]{4}".$date_sep."[0-9]{2}/",$func_vars_array[1])){
			//����ǯ��ΰ���ɽ��
			$page_YM = $func_vars_array[1];
			$limit_base = 0;
			$limit_page = 310;	//��ȴ����31��ʬ��10�ڡ������ߥåȤȤ��롣
		}else if (preg_match("/this/si",$func_vars_array[1])){
			//����ΰ���ɽ��
			$page_YM = date("Y".$date_sep."m");
			$limit_base = 0;
			$limit_page = 310;
		}else if (preg_match("/^[0-9]+$/",$func_vars_array[1])){
			//n��ʬɽ��
			$limit_pitch = $func_vars_array[1];
			$limit_page = $limit_pitch;
			$limit_base = 0;
			$page_YM = "";
		}else if (preg_match("/([0-9]+)\*([0-9]+)/",$func_vars_array[1],$reg_array)){
			$limit_pitch = $reg_array[2];
			$limit_page = $reg_array[1] + $limit_pitch;
			$limit_base = $reg_array[1];
			$page_YM = "";
		}else{
			return $_calendar_viewer_msg_arg2."($func_vars_array[1])";
		}
		if (isset($func_vars_array[2])&&preg_match("/past|view|future/si",$func_vars_array[2])){
			//�⡼�ɻ���
			$mode = $func_vars_array[2];
		}


	}else{
		return $_calendar_viewer_msg_noargs;
	}
	
	//�����Υڡ���̾
	$today_prefix = date("Y{$date_sep}m{$date_sep}d");

	//*����ɽ������ڡ���̾�ȥե�����̾�Υѥ����󡡥ե�����̾�ˤ�ǯ���ޤ�
	if ($pagename == ""){
		//pagename̵����yyyy-mm-dd���б����뤿��ν���
		$pagepattern = "";
		$pagepattern_len = 0;
		$filepattern = $page_YM;
		$filepattern_len = strlen($filepattern);
	}else{
		$_page = strip_bracket($pagename);
		$pagepattern = $_page .'/';
		$pagepattern_len = strlen($pagepattern);
		$filepattern = $pagepattern.$page_YM;
		$filepattern_len = strlen($filepattern);
	}

	//echo "$pagename:$page_YM:$mode:$date_sep:$limit_base:$limit_page";
	//*�ڡ����ꥹ�Ȥμ���
	//echo $pagepattern."<br>";
	//echo $filepattern."<br>";

	$pagelist = array();
	$datelength = strlen($date_sep)*2 + 8;
	foreach(array_diff(get_existpages_db(false,$filepattern,0,"",false,false,true,true),array($_page)) as $page)
	{
		//if(substr($page,0,$filepattern_len)!=$filepattern) continue;
		//$page���������������ʤΤ������å� �ǥե���ȤǤϡ� yyyy-mm-dd-([1-9])?
		if (plugin_calendar_viewer_isValidDate(substr($page,$pagepattern_len),$date_sep) == false) continue;
		//����ʬ�ϡ�
		if ($notoday && strpos($page,$today_prefix) !== false) continue;
		//*mode����̾��ǤϤ���
		//past mode�Ǥ�̤��Υڡ�����NG
		if (((substr($page,$pagepattern_len,$datelength)) > date("Y".$date_sep."m".$date_sep."d"))&&($mode=="past") )continue;
		//future mode�Ǥϲ��Υڡ�����NG
		if (((substr($page,$pagepattern_len,$datelength)) < date("Y".$date_sep."m".$date_sep."d"))&&($mode=="future") )continue;
		//view mode�ʤ�all OK
		if (strlen(substr($page,$pagepattern_len)) == $datelength)
			$pagelist[] = $page.$date_sep."-";
		else
			$pagelist[] = $page;
	}
	
	//�ʥӥС�������������
	//?plugin=calendar_viewer&file=�ڡ���̾&date=yyyy-mm
	$enc_pagename = rawurlencode(substr($pagepattern,0,$pagepattern_len -1));
	$co_tag = ($contents_lev)? "&amp;co={$contents_lev}" : "";

	if ($page_YM != ""){
		//ǯ��ɽ����
		$date_sep_len = strlen($date_sep);
		$this_year = substr($page_YM,0,4);
		$this_month = substr($page_YM,4+$date_sep_len,2);
		//����
		$next_year = $this_year;
		$next_month = $this_month + 1;
		if ($next_month >12){
			$next_year ++;
			$next_month = 1;
		}
		$next_YM_T = $next_YM = sprintf("%04d%s%02d",$next_year,$date_sep,$next_month);
		if ($cal2 == 1 ) {
			$next_YM = sprintf("%04d%02d",$next_year,$next_month);
		}

		//����
		$prev_year = $this_year;
		$prev_month = $this_month -1;
		if ($prev_month < 1){
			$prev_year --;
			$prev_month = 12;
		}
		$prev_YM_T = $prev_YM = sprintf("%04d%s%02d",$prev_year,$date_sep,$prev_month);
		if ($cal2 == 1 ) {
			$prev_YM = sprintf("%04d%02d",$prev_year,$prev_month);
		}

		if ($mode == "past"){
			$right_YM = $prev_YM;
			$right_text = $prev_YM_T."&gt;&gt;";
			$left_YM = $next_YM;
			$left_text = "&lt;&lt;".$next_YM_T;
		}else{
			$left_YM = $prev_YM;
			$left_text = "&lt;&lt;".$prev_YM_T;
			$right_YM = $next_YM;
			$right_text = $next_YM_T."&gt;&gt;";
		}
	}else{
		//n��ɽ����
		if ($limit_base >= count($pagelist)){
			$right_YM = "";
		}else{
			$right_base = $limit_base + $limit_pitch;
			$right_YM = $right_base ."*".$limit_pitch;
			$right_text = "����".$limit_pitch."��&gt;&gt;";
		}
		$left_base	= $limit_base - $limit_pitch;
		if ($left_base >= 0) {
			$left_YM = $left_base . "*" . $limit_pitch;
			$left_text = "&lt;&lt;����".$limit_pitch."��";
			
		}else{
			$left_YM = "";
		}

	}
	
	if ($cal2 == 1){
		//��󥯺���(cal2��)
		if ($left_YM != ""){
			$left_link = "<a href=\"". $script."?plugin=calendar2&amp;file=".$enc_pagename."&amp;date=".$left_YM.$co_tag."\">".$left_text."</a>";
		}else{
			$left_link = "";
		}
		if ($right_YM != ""){
			$right_link = "<a href=\"". $script."?plugin=calendar2&amp;file=".$enc_pagename."&amp;date=".$right_YM.$co_tag."\">".$right_text."</a>";
		}else {
			$right_link = "";
		}
	} else {
		//��󥯺���(�̾�)
		if ($left_YM != ""){
			$left_link = "<a href=\"". $script."?plugin=calendar_viewer&amp;file=".$enc_pagename."&amp;date=".$left_YM.$co_tag."&amp;date_sep=".$date_sep."&amp;mode=".$mode."\">".$left_text."</a>";
		}else{
			$left_link = "";
		}
		if ($right_YM != ""){
			$right_link = "<a href=\"". $script."?plugin=calendar_viewer&amp;file=".$enc_pagename."&amp;date=".$right_YM.$co_tag."&amp;date_sep=".$date_sep."&amp;mode=".$mode."\">".$right_text."</a>";
		}else {
			$right_link = "";
		}
	}
	//past mode��<<�� ��>> ¾��<<�� ��>>
	$navi_bar .= "<table style=\"width:100%;\" class=\"style_calendar_navi\"><tr><td align=\"left\" style=\"width:33%;\">";
	$navi_bar .= $left_link;
	$navi_bar .= "</td><td align=\"center\" style=\"width:34%;\">";
	$navi_bar .= make_pagelink($pagename,strip_bracket($pagename));
	$navi_bar .= "</td><td align=\"right\" style=\"width:33%;\">";
	$navi_bar .= $right_link;
	$navi_bar .= "</td></tr></table>";
	
	//�ʥӥС����������ޤ�

	//*�������饤�󥯥롼�ɳ���

	$return_body = "";
	
	//�ʥӥС�
	$return_body .= $navi_bar;
	
	//�ޤ�������
	if ($mode == "past"){
		//past mode�ǤϿ�����
		rsort ($pagelist);
	}else {
		//view mode �� future mode �Ǥϡ��좪��
		sort ($pagelist);
	}

	//$limit_page�η���ޤǥ��󥯥롼��
	$tmp = $limit_base;
	$kensu = 0;
	$ct_list = "";
	while ($tmp < $limit_page){
		if (!isset($pagelist[$tmp])) break;
		$pagelist[$tmp] = preg_replace("/{$date_sep}-$/","",$pagelist[$tmp]);
		$page = "[[" . $pagelist[$tmp] .	"]]";

		$user_tag = get_pg_auther_name($page);
		make_user_link($user_tag,"",true);
		$user_tag = make_link($user_tag);
		$comments_tag = ($use_xoops_comments)? " [ ".make_pagelink($page,$_msg_pagecomment."(".get_pagecomment_count(get_pgid_by_name($page)).")",'#page_comments')." ]" : "";
		$tb_tag = ($trackback)? " [ ".make_pagelink($page,$_msg_trackback."(".tb_count($page).")",'#tb_body')." ]" : "";
		$info_tag = "<div style=\"text-align:right\">by $user_tag at ".get_makedate_byname($page)." ".make_pagelink($page,"<img src=\"./image/link.gif\" />")."<small>".$comments_tag.$tb_tag."</small></div>";


	//���󥯥롼��
	list($_body,$_contents) = include_page($page,TRUE);
	$ct_list .= $_contents;
	$body = "<div class=\"style_calendar_body\" style=\"clear:both;\"><div style=\"width:100%;\">".$info_tag.$_body."</div></div>";

		$link = make_pagelink($page,preg_replace("/^.*\//","",strip_bracket($page)));
		if (check_editable($page,FALSE,FALSE)) $link .= " <a href=\"$script?cmd=edit&amp;page=".rawurlencode($page)."\"><font size=\"-2\">(".$_calendar_viewer_msg_edit.")</font></a>";
		$head = "<div class = \"style_calendar_date\" style=\"clear:both;\">$link</div>\n";
		$return_body .= $head . $body;
		$tmp++;
		$kensu++;
	}
	if ($contents_lev)
	{
		$return_body = "<!--contents list-->".$_calendar_viewer_contents.select_contents_by_level($ct_list,$contents_lev)."<!--/contents list-->".$return_body;
	}
	//ɽ���ǡ��������ä���ʥӥС�ɽ��
	if ($kensu) $return_body .= $navi_bar;
	
	return $return_body;
}

function plugin_calendar_viewer_action(){
	global $WikiName,$BracketName,$vars,$get,$post,$hr,$script;
	$date_sep = "-";


	$return_vars_array = array();

	$vars['page'] = (empty($vars['file']))? "" : add_bracket($vars['file']);

	$date_sep = $vars["date_sep"];

	$page_YM = $vars["date"];
	if ($page_YM == ""){
		$page_YM = date("Y".$date_sep."m");
	}
	$mode = $vars["mode"];

	$args_array = array($vars["page"], $page_YM,$mode, $date_sep);
	
	if (!empty($vars['co'])) $args_array[] = "contents:".(int)$vars['co'];
	
	$return_vars_array["body"] = call_user_func_array("plugin_calendar_viewer_convert",$args_array);

	$return_vars_array["msg"] = htmlspecialchars(strip_bracket($vars["page"]));
	
	if(preg_match("/\*/",$page_YM))
	{
		$count = explode("*",$page_YM);
		$count[0] = (int)$count[0];
		$count[1] = (int)$count[1];
		$return_vars_array["msg"] .= " / Recent ".$count[0]." - ".($count[0] + $count[1]);
	}
	else
	{
		$return_vars_array["msg"] .= " / ".htmlspecialchars($page_YM);
	}

	return $return_vars_array;
}

function plugin_calendar_viewer_isValidDate($aStr, $aSepList="-/ .") {
	//$aSepList=""�ʤ顢yyyymmdd�Ȥ��ƥ����å��ʼ�ȴ��(^^;��
	if($aSepList == "") {
		//yyyymmdd�Ȥ��ƥ����å�
		return checkdate(substr($aStr,4,2),substr($aStr,6,2),substr($aStr,0,4));
	}
	$m = array();
	if( ereg("^([0-9]{2,4})[$aSepList]([0-9]{1,2})[$aSepList]([0-9]{1,2})([$aSepList][0-9])?$", $aStr, $m) ) {
		return checkdate($m[2], $m[3], $m[1]);
	}
	return false;
}

?>