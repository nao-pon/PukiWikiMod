<?php
/*
 * PukiWiki calendar_viewerプラグイン
 *
 *
 *$Id: calendar_viewer.inc.php,v 1.28 2006/03/06 06:20:30 nao-pon Exp $
  calendarrecentプラグインを元に作成
 */
/**
 *概要
  calendarプラグインやcalendar2プラグインで作成したページを一覧表示するためのプラグインです。
 *更新履歴
  -2002-11-13
  --前後へのリンクに年月や「次のn件」と表示するようにした。
 *使い方
  /// #calendar_viewer(pagename,(yyyy-mm|n|this),[mode],[separater],notoday)
 **pagename
  calendar or calendar2プラグインを記述してるページ名
 **(yyyy-mm|n|this)
  -yyyy-mm
  --yyyy-mmで指定した年月のページを一覧表示
  -n
  --n件の一覧表示
  -this
  --今月のページを一覧表示
 **[mode]
  省略可能です。省略時のデフォルトはpast
  -past
  --今日以前のページの一覧表示モード。更新履歴や日記向き
  -future
  --今日以降のページの一覧表示モード。イベント予定やスケジュール向き
  -view
  --過去から未来への一覧表示モード。表示抑止するページはありません。
  -[separater]
  省略可能。デフォルトは-。（calendar2なら省略でOK）
  --年月日を区切るセパレータを指定。
 **notoday
  -本日分を表示しないオプション

 *todo
  past or future で月単位表示するときに、それぞれ来月、先月の一覧へのリンクを表示しないようにする

 */

// initialize variables
function plugin_calendar_viewer_init() {
	if (LANG=='ja') {
		$_plugin_calendar_viewer_messages = array(
			'_calendar_viewer_msg_arg2' => '第二引数が変だよ',
			'_calendar_viewer_msg_noargs' => '引数を指定してね',
			'_calendar_viewer_msg_edit' => '編集',
			'_calendar_viewer_read_more' => '<< 続きを読む >>',
			'_calendar_viewer_contents' => '<h4>表示中のタイトル</h4>',
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
	//*デフォルト値をセット
	//基準となるページ名
	$pagename = "";
	//表示する件数制限
	$limit_page = 7;
	//一覧表示する年月
	$date_YM = "";
	//動作モード
	$mode = "past";
	//日付のセパレータ calendar2なら"-" calendarなら""
	$date_sep = "-";
	//本日分を表示しない
	$notuday = false;
	


	$cal2=0;


//echo "this!";
	//*引数の確認
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
			//指定年月の一覧表示
			$page_YM = $func_vars_array[1];
			$limit_base = 0;
			$limit_page = 310;	//手抜き。31日分×10ページをリミットとする。
		}else if (preg_match("/this/si",$func_vars_array[1])){
			//今月の一覧表示
			$page_YM = date("Y".$date_sep."m");
			$limit_base = 0;
			$limit_page = 310;
		}else if (preg_match("/^[0-9]+$/",$func_vars_array[1])){
			//n日分表示
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
			//モード指定
			$mode = $func_vars_array[2];
		}


	}else{
		return $_calendar_viewer_msg_noargs;
	}
	
	//本日のページ名
	$today_prefix = date("Y{$date_sep}m{$date_sep}d");

	//*一覧表示するページ名とファイル名のパターン　ファイル名には年月を含む
	if ($pagename == ""){
		//pagename無しのyyyy-mm-ddに対応するための処理
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
	//*ページリストの取得
	//echo $pagepattern."<br>";
	//echo $filepattern."<br>";

	$pagelist = array();
	$datelength = strlen($date_sep)*2 + 8;
	foreach(array_diff(get_existpages_db(false,$filepattern,0,"",false,false,true,true),array($_page)) as $page)
	{
		//if(substr($page,0,$filepattern_len)!=$filepattern) continue;
		//$pageがカレンダー形式なのかチェック デフォルトでは、 yyyy-mm-dd-([1-9])?
		if (plugin_calendar_viewer_isValidDate(substr($page,$pagepattern_len),$date_sep) == false) continue;
		//本日分は？
		if ($notoday && strpos($page,$today_prefix) !== false) continue;
		//*mode毎に別条件ではじく
		//past modeでは未来のページはNG
		if (((substr($page,$pagepattern_len,$datelength)) > date("Y".$date_sep."m".$date_sep."d"))&&($mode=="past") )continue;
		//future modeでは過去のページはNG
		if (((substr($page,$pagepattern_len,$datelength)) < date("Y".$date_sep."m".$date_sep."d"))&&($mode=="future") )continue;
		//view modeならall OK
		if (strlen(substr($page,$pagepattern_len)) == $datelength)
			$pagelist[] = $page.$date_sep."-";
		else
			$pagelist[] = $page;
	}
	
	//ナビバー作成ここから
	//?plugin=calendar_viewer&file=ページ名&date=yyyy-mm
	$enc_pagename = rawurlencode(substr($pagepattern,0,$pagepattern_len -1));
	$co_tag = ($contents_lev)? "&amp;co={$contents_lev}" : "";

	if ($page_YM != ""){
		//年月表示時
		$date_sep_len = strlen($date_sep);
		$this_year = substr($page_YM,0,4);
		$this_month = substr($page_YM,4+$date_sep_len,2);
		//次月
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

		//前月
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
		//n件表示時
		if ($limit_base >= count($pagelist)){
			$right_YM = "";
		}else{
			$right_base = $limit_base + $limit_pitch;
			$right_YM = $right_base ."*".$limit_pitch;
			$right_text = "次の".$limit_pitch."件&gt;&gt;";
		}
		$left_base	= $limit_base - $limit_pitch;
		if ($left_base >= 0) {
			$left_YM = $left_base . "*" . $limit_pitch;
			$left_text = "&lt;&lt;前の".$limit_pitch."件";
			
		}else{
			$left_YM = "";
		}

	}
	
	if ($cal2 == 1){
		//リンク作成(cal2用)
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
		//リンク作成(通常)
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
	//past modeは<<新 旧>> 他は<<旧 新>>
	$navi_bar .= "<table style=\"width:100%;\" class=\"style_calendar_navi\"><tr><td align=\"left\" style=\"width:33%;\">";
	$navi_bar .= $left_link;
	$navi_bar .= "</td><td align=\"center\" style=\"width:34%;\">";
	$navi_bar .= make_pagelink($pagename,strip_bracket($pagename));
	$navi_bar .= "</td><td align=\"right\" style=\"width:33%;\">";
	$navi_bar .= $right_link;
	$navi_bar .= "</td></tr></table>";
	
	//ナビバー作成ここまで

	//*ここからインクルード開始

	$return_body = "";
	
	//ナビバー
	$return_body .= $navi_bar;
	
	//まずソート
	if ($mode == "past"){
		//past modeでは新→旧
		rsort ($pagelist);
	}else {
		//view mode と future mode では、旧→新
		sort ($pagelist);
	}

	//$limit_pageの件数までインクルード
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


	//インクルード
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
	//表示データがあったらナビバー表示
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
	//$aSepList=""なら、yyyymmddとしてチェック（手抜き(^^;）
	if($aSepList == "") {
		//yyyymmddとしてチェック
		return checkdate(substr($aStr,4,2),substr($aStr,6,2),substr($aStr,0,4));
	}
	$m = array();
	if( ereg("^([0-9]{2,4})[$aSepList]([0-9]{1,2})[$aSepList]([0-9]{1,2})([$aSepList][0-9])?$", $aStr, $m) ) {
		return checkdate($m[2], $m[3], $m[1]);
	}
	return false;
}

?>