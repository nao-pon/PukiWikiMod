<?php
/*
 * PukiWiki calendar_viewerプラグイン
 *
 *
 *$Id: calendar_viewer.inc.php,v 1.3 2003/06/28 16:11:13 nao-pon Exp $
  calendarrecentプラグインを元に作成
 */
/**
 *概要
  calendarプラグインやcalendar2プラグインで作成したページを一覧表示するためのプラグインです。
 *更新履歴
  -2002-11-13
  --前後へのリンクに年月や「次のn件」と表示するようにした。
 *使い方
  /// #calendar_viewer(pagename,(yyyy-mm|n|this),[mode],[separater])
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

 *todo
  past or future で月単位表示するときに、それぞれ来月、先月の一覧へのリンクを表示しないようにする

 */




function plugin_calendar_viewer_convert()
{
  global $WikiName,$BracketName,$vars,$get,$post,$hr,$script;
  global $anon_writable;
  global $comment_no;
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

	$cal2=0;


//echo "this!";
  //*引数の確認
  if(func_num_args()>=2){
    $func_vars_array = func_get_args();

    $pagename = $func_vars_array[0];

    if (isset($func_vars_array[3])){
      	if ($func_vars_array[3] == "cal2"){
			$cal2 = 1;
		} else {
			$date_sep = htmlspecialchars($func_vars_array[3]);
		}
    }
    if (preg_match("/[0-9]{4}".$date_sep."[0-9]{2}/",$func_vars_array[1])){
      //指定年月の一覧表示
      $page_YM = $func_vars_array[1];
      $limit_base = 0;
      $limit_page = 31;	//手抜き。31日分をリミットとする。
    }else if (preg_match("/this/si",$func_vars_array[1])){
      //今月の一覧表示
      $page_YM = date("Y".$date_sep."m");
      $limit_base = 0;
      $limit_page = 31;
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
      return "第2引数が変だよ";
    }
    if (isset($func_vars_array[2])&&preg_match("/past|view|future/si",$func_vars_array[2])){
      //モード指定
      $mode = $func_vars_array[2];
    }


  }else{
    return "引数指定してね";
  }

  //*一覧表示するページ名とファイル名のパターン　ファイル名には年月を含む
  if ($pagename == ""){
    //pagename無しのyyyy-mm-ddに対応するための処理
    $pagepattern = "";
    $pagepattern_len = 0;
    $filepattern = encode('[['.$page_YM);
    $filepattern_len = strlen($filepattern);
  }else{
    $pagepattern = strip_bracket($pagename) .'/';
    $pagepattern_len = strlen($pagepattern);
    $filepattern = encode('[['.$pagepattern.$page_YM);
    $filepattern_len = strlen($filepattern);
  }

  //echo "$pagename:$page_YM:$mode:$date_sep:$limit_base:$limit_page";
  //*ページリストの取得
  //echo $pagepattern."<br>";
  //echo $filepattern."<br>";

  $pagelist = array();
  if ($dir = @opendir(DATA_DIR))
    {
      while($file = readdir($dir))
        {
          if($file == ".." || $file == ".") continue;
          if(substr($file,0,$filepattern_len)!=$filepattern) continue;
          //echo "OK";
          $page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
          //$pageがカレンダー形式なのかチェック デフォルトでは、 yyyy-mm-dd
          $page = strip_bracket($page);
          if (plugin_calendar_viewer_isValidDate(substr($page,$pagepattern_len),$date_sep) == false) continue;
          //*mode毎に別条件ではじく
          //past modeでは未来のページはNG
          if (((substr($page,$pagepattern_len)) > date("Y".$date_sep."m".$date_sep."d"))&&($mode=="past") )continue;
          //future modeでは過去のページはNG
          if (((substr($page,$pagepattern_len)) < date("Y".$date_sep."m".$date_sep."d"))&&($mode=="future") )continue;
          //view modeならall OK
          $pagelist[] = $page;
        }
    }
  closedir($dir);
  
  //ナビバー作成ここから
  //?plugin=calendar_viewer&file=ページ名&date=yyyy-mm
  $enc_pagename = rawurlencode(substr($pagepattern,0,$pagepattern_len -1));

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
    $left_base  = $limit_base - $limit_pitch;
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
    $left_link = "<a href=\"". $script."?plugin=calendar2&amp;file=".$enc_pagename."&amp;date=".$left_YM."\">".$left_text."</a>";
  }else{
    $left_link = "";
  }
  if ($right_YM != ""){
    $right_link = "<a href=\"". $script."?plugin=calendar2&amp;file=".$enc_pagename."&amp;date=".$right_YM."\">".$right_text."</a>";
  }else {
    $right_link = "";
  }
} else {
  //リンク作成(通常)
  if ($left_YM != ""){
    $left_link = "<a href=\"". $script."?plugin=calendar_viewer&amp;file=".$enc_pagename."&amp;date=".$left_YM ."&amp;date_sep=".$date_sep."&amp;mode=".$mode."\">".$left_text."</a>";
  }else{
    $left_link = "";
  }
  if ($right_YM != ""){
    $right_link = "<a href=\"". $script."?plugin=calendar_viewer&amp;file=".$enc_pagename."&amp;date=".$right_YM ."&amp;date_sep=".$date_sep."&amp;mode=".$mode."\">".$right_text."</a>";
  }else {
    $right_link = "";
  }
}
  //past modeは<<新 旧>> 他は<<旧 新>>
  $pageurl = $script."?".rawurlencode("[[".strip_bracket($pagename)."]]");
  $navi_bar .= "<table width =\"100%\" class=\"style_calendar_navi\"><tr><td align=\"left\" width=\"33%\">";
  $navi_bar .= $left_link;
  $navi_bar .= "</td><td align=\"center\" width=\"34%\">";
  $navi_bar .= "<a href=\"".$pageurl."\"><b>".htmlspecialchars(strip_bracket($pagename))."</b></a>";
  $navi_bar .= "</td><td align=\"right\" width=\"33%\">";
  $navi_bar .= $right_link;
  $navi_bar .= "</td></tr></table>";
  
  //ナビバー作成ここまで

  //echo count($pagelist);
  //*ここからインクルード開始

  $tmppage = $vars["page"];
  //$tmp_related = $related;
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
  while ($tmp < $limit_page){
    if (empty($pagelist[$tmp])) break;
    $page = "[[" . $pagelist[$tmp] .  "]]";

    $get["page"] = $page;
    $post["page"] = $page;
    $vars["page"] = $page;
    
    //comment_no 初期化
    $comment_no = 0;

    $body = @join("",@file(get_filename(encode($page))));
    $body = "<div class=\"style_calendar_body\">".convert_html($body)."</div>";
    $link = "<a href=\"$script?cmd=read&amp;page=".rawurlencode($page)."\">".strip_bracket($page)."</a>";
    if ($anon_writable) $link .= " <a href=\"$script?cmd=edit&amp;page=".rawurlencode($page)."\"><font size=\"-2\">(編集)</font></a>";
    //$head = "<h1>$link</h1>\n";
    $head = "<h4>$link</h4>\n";
    $return_body .= $head . $body;

    $tmp++;
    $kensu++;
  }

  //表示データがあったらナビバー表示
  if ($kensu) $return_body .= $navi_bar;
  
  $get["page"] = $tmppage;
  $post["page"] = $tmppage;
  $vars["page"] = $tmppage;
  //$related = $tmp_related;



  return $return_body;
}

function plugin_calendar_viewer_action(){
  global $WikiName,$BracketName,$vars,$get,$post,$hr,$script;
  $date_sep = "-";


  $return_vars_array = array();

  $page = strip_bracket($vars['page']);
  $vars['page'] = '*';
  if(isset($vars['file'])) $vars['page'] = $vars['file'];

  $date_sep = $vars["date_sep"];

  $page_YM = $vars["date"];
  if ($page_YM == ""){
    $page_YM = date("Y".$date_sep."m");
  }
  $mode = $vars["mode"];

  $args_array = array($vars["page"], $page_YM,$mode, $date_sep);
  $return_vars_array["body"] = call_user_func_array("plugin_calendar_viewer_convert",$args_array);

  //$return_vars_array["msg"] = "calendar_viewer ".$vars["page"]."/".$page_YM;
  $return_vars_array["msg"] = "calendar_viewer ".htmlspecialchars($vars["page"]);
  if ($vars["page"] != ""){
    $return_vars_array["msg"] .= "/";
  }
  if(preg_match("/\*/",$page_YM)){
    //うーん、n件表示の時はなんてページ名にしたらいい？
  }else{
    $return_vars_array["msg"] .= htmlspecialchars($page_YM);
  }

  $vars['page'] = $page;
  return $return_vars_array;
}

function plugin_calendar_viewer_isValidDate($aStr, $aSepList="-/ .") {
  //$aSepList=""なら、yyyymmddとしてチェック（手抜き(^^;）
  if($aSepList == "") {
    //yyyymmddとしてチェック
    return checkdate(substr($aStr,4,2),substr($aStr,6,2),substr($aStr,0,4));
  }
  if( ereg("^([0-9]{2,4})[$aSepList]([0-9]{1,2})[$aSepList]([0-9]{1,2})$", $aStr, $m) ) {
    return checkdate($m[2], $m[3], $m[1]);
  }
  return false;
}

?>
