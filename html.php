<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: html.php,v 1.5 2003/07/02 00:56:44 nao-pon Exp $
/////////////////////////////////////////////////

// 本文をページ名から出力
function catbodyall($page,$title="",$pg="")
{
	if($title === "") $title = strip_bracket($page);
	if($pg === "") $pg = make_search($page);

	$body = join("",get_source($page));
	$body = convert_html($body);

	header_lastmod($vars["page"]);
	catbody($title,$pg,$body);
	die();
}

// 本文を出力
function catbody($title,$page,$body)
{
	global $script,$vars,$arg,$do_backup,$modifier,$modifierlink,$defaultpage,$whatsnew,$hr;
	global $date_format,$weeklabels,$time_format,$related_link;
	global $HTTP_SERVER_VARS,$cantedit;
	global $longtaketime;
	global $foot_explain, $note_hr, $_msg_word, $search_word_color;

	if($vars["page"] && !arg_check("backup") && $vars["page"] != $whatsnew)
	{
		$is_page = 1;
	}

 	$link_add = "$script?cmd=add&amp;page=".rawurlencode($vars["page"]);
 	$link_edit = "$script?cmd=edit&amp;page=".rawurlencode($vars["page"]);
 	$link_diff = "$script?cmd=diff&amp;page=".rawurlencode($vars["page"]);
	$link_top = "$script?$defaultpage";
	$link_list = "$script?cmd=list";
	$link_filelist = "$script?cmd=filelist";
	$link_search = "$script?cmd=search";
	$link_whatsnew = "$script?$whatsnew";
 	$link_backup = "$script?cmd=backup&amp;page=".rawurlencode($vars["page"]);
	$link_help = "$script?cmd=help";

	if(is_page($vars["page"]) && $is_page)
	{
		$fmt = @filemtime(get_filename(encode($vars["page"])));
	}

	if(is_page($vars["page"]) && $related_link && $is_page && !arg_check("edit") && !arg_check("freeze") && !arg_check("unfreeze"))
	{
		$related = make_related($vars["page"],FALSE);
	}

	if(is_page($vars["page"]) && !in_array($vars["page"],$cantedit) && !arg_check("backup") && !arg_check("edit") && !$vars["preview"])
	{
		$is_read = TRUE;
	}

	//単語検索
	if ($search_word_color and array_key_exists('word',$vars))
	{
		$search_word = '';
		$words = array_flip(array_splice(preg_split('/\s+/',$vars['word'],-1,PREG_SPLIT_NO_EMPTY),0,10));
		$keys = array();
		foreach ($words as $word=>$id)
		{
			$keys[$word] = strlen($word);
		}
		arsort($keys,SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys));
		$id = 0;
		foreach ($keys as $key=>$pattern)
		{
			$s_key = htmlspecialchars($key);
			$search_word .= " <strong class=\"word$id\">$s_key</strong>";
			$pattern = ($s_key{0} == '&') ?
				"/(<[^>]*>)|($pattern)/" :
				"/(<[^>]*>|&(?:#[0-9]+|#x[0-9a-f]+|[0-9a-zA-Z]+);)|($pattern)/";
			$body = preg_replace_callback($pattern,
				create_function('$arr',
					'return $arr[1] ? $arr[1] : "<strong class=\"word'.$id.'\">{$arr[2]}</strong>";'),$body);
			$id++;
		}
		$body = "<div class=\"small\">$_msg_word$search_word</div>$hr\n$body";
	}

	$longtaketime = getmicrotime() - MUTIME;
	$taketime = sprintf("%01.03f",$longtaketime);

	if ($foot_explain)
		$body .= "\n$note_hr\n".join("\n",inline2($foot_explain));

	if(!file_exists(SKIN_FILE)||!is_readable(SKIN_FILE))
	  die_message(SKIN_FILE."(skin file) is not found.");
	require(SKIN_FILE);
}

// テキスト本体をHTMLに変換する
function convert_html($string)
{
	global $hr,$script,$page,$vars,$top;
	global $note_id,$foot_explain,$digest,$note_hr;
	global $user_rules,$str_rules,$line_rules,$strip_link_wall;
	global $WikiName,$InterWikiName, $BracketName;

	global $content_id;
	$content_id_local = ++$content_id;
	$content_count = 0;

	$string = rtrim($string);
	$string = preg_replace("/((\x0D\x0A)|(\x0D)|(\x0A))/","\n",$string);

	$start_mtime = getmicrotime();

	$digest = md5(@join("",get_source($vars["page"])));

	$result = array();
	$saved = array();
	$arycontents = array();

	//$string = preg_replace("/^#freeze\n/","",$string);
	$string = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$string);

	//~\nは&br;に変換して1行として処理 nao-pon 03/06/25
	// 改行を挟んででURLなどが列挙してあると上手く切り分けられないので\tを挿入 03/06/29
	$string = str_replace("~\n","\t&br;\t",$string);

	//これはなんだったけ？必要ないのとおもうのでとりあえずコメントアウト 03/06/29
	//$string = str_replace("&br; ","~\n ",$string);

	//表内箇所の判定のため表と表の間は空行が2行必要
	$string = str_replace("|\n\n|","|\n\n\n|",$string);
	//表内はすべて置換
	$string = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('->\n','&br;','$2')).'$3'",$string);
	//表と表の間は空行2行を1行に戻す
	$string = str_replace("|\n\n\n|","|\n\n|",$string);
	
	//行単位の配列に格納
	$lines = split("\n", $string);
	// 各行の行頭書式を格納
	$headform = array();
	// 現在の行数を入れておこう
	$_cnt = 0;
	// ブロックの判定フラグ
	$_p = FALSE;
	$_bq = FALSE;
	// カラーネームの正規表現
	$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";

	$table = 0;

	if(preg_match("/#contents/",$string))
		$top_link = "<a href=\"#contents_$content_id_local\">$top</a>";

	foreach ($lines as $line)
	{
		if(!preg_match("/^\/\/(.*)/",$line,$comment_out) && $table != 0)
		{
			if(!preg_match("/^\|(.+)\|(c|h)?$/",$line,$out) or
				$table != count(table_inc_add(explode("|",$out[1]))))
			{
				$table = 0;
				$table_style = "";
				$table_sheet = "";
				$sell_sheet = "";
				$td_color = $td_width = $td_align = array();
				array_push($result, "</table></div>".$table_around."");
			}
		}

		$comment_out = $comment_out[1];

		// 行頭書式かどうかの判定
		$line_head = substr($line,0,1);
		if(	$line_head == ' ' || 
			$line_head == ':' || 
			$line_head == '>' || 
			$line_head == '-' || 
			$line_head == '+' || 
			$line_head == '|' || 
			$line_head == '*' || 
			$line_head == '#' || 
			$comment_out != ''
		) {
			if($headform[$_cnt-1] == '' && $_p){
				array_push($result, "</p>");
				$_p = FALSE;
			}
			if($line_head != '>' && $_bq){
				array_push($result, "</p>");
				$_bq = FALSE;
			}

			if(preg_match("/^\#([^\(]+)(.*)$/",$line,$out)){
				if(exist_plugin_convert($out[1])) {
					$result = array_merge($result,$saved); $saved = array();
					
					if($out[2]) {
						$_plugin = preg_replace("/^\#([^\(]+)\((.*)\)$/ex","do_plugin_convert('$1','$2')",$line);
					} else {
						$_plugin = preg_replace("/^\#([^\(]+)$/ex","do_plugin_convert('$1','$2')",$line);
					}
					// 先頭に空白を入れることによりとりあえずpreの扱いと同様にinline2の働きを抑える、う〜ん、無茶。
					array_push($result,"\t$_plugin");
				} else {
					array_push($result, htmlspecialchars($line));
				}
			}
			else if(preg_match("/^(\*{1,3})(.*)/",$line,$out))
			{
				$result = array_merge($result,$saved); $saved = array();
				$headform[$_cnt] = $out[1];
				$str = inline($out[2]);
				
				//$level = strlen($out[1]) + 1;
				$level = strlen($out[1]);

				array_push($result, "<h$level><a name=\"content_{$content_id_local}_$content_count\"></a>$str $top_link</h$level>");
				//$arycontents[] = str_repeat("-",$level-1)."<a href=\"#content_{$content_id_local}_$content_count\">".strip_htmltag(make_user_rules(inline($out[2],TRUE)))."</a>\n";
				$arycontents[] = str_repeat("-",$level)."<a href=\"#content_{$content_id_local}_$content_count\">".strip_htmltag(make_user_rules(inline($out[2],TRUE)))."</a>\n";
				$content_count++;
			}
			else if(preg_match("/^(-{1,4})(.*)/",$line,$out))
			{
				$headform[$_cnt] = $out[1];
				if(strlen($out[1]) == 4)
				{
					$result = array_merge($result,$saved); $saved = array();
					$hr_tmp = $hr;
					if ($out[2]) $hr_tmp = ereg_replace("(<.*)(>)","\\1 width=$out[2]\\2",$hr_tmp);
					array_push($result, $hr_tmp);
				}
				else
				{
					list_push($result,$saved,'ul', strlen($out[1]));
					array_push($result, '<li>'.inline($out[2]));
				}
			}
			else if(preg_match("/^(\+{1,3})(.*)/",$line,$out))
			{
				$headform[$_cnt] = $out[1];
				list_push($result,$saved,'ol', strlen($out[1]));
				array_push($result, '<li>'.inline($out[2]));
			}
			else if (preg_match("/^:([^:]+):(.*)/",$line,$out))
			{
				$headform[$_cnt] = ':'.$out[1].':';
				back_push($result,$saved,'dl', 1);
				array_push($result, '<dt>' . inline($out[1]) . '</dt>', '<dd>' . inline($out[2]) . '</dd>');
			}
			else if(preg_match("/^(>{1,3})(.*)/",$line,$out))
			{
				$headform[$_cnt] = $out[1];
				back_push($result,$saved,'blockquote', strlen($out[1]));
				// ここのあたりで自前でback_pushかけてる感じ。無茶苦茶…
				if($headform[$_cnt-1] != $headform[$_cnt] ) {
					if(!$_bq) {
						array_push($result, "<p class=\"quotation\">");
						$_bq = TRUE;
					}
					else if(substr($headform[$_cnt-1],0,1) == '>'){
						$_level_diff = abs( strlen($out[1]) - strlen($headform[$_cnt-1]) );
						if( $_level_diff == 1 ){
							$i = array_pop($result);
							array_push($result, "</p>");
							array_push($result,$i);
							array_push($result, "<p class=\"quotation\">");
							$_bq = TRUE;
						} else {
							$i = array();
							$i[] = array_pop($result);
							$i[] = array_pop($result);
							array_push($result, "</p>");
							$result = array_merge($result,$i);
							array_push($result, "<p class=\"quotation\">");
							$_bq = TRUE;
						}
					}
				}
				array_push($result, ltrim(inline($out[2])));
			}
			else if(preg_match("/^(\s+.*)/",$line,$out))
			{
				$headform[$_cnt] = ' ';
				back_push($result,$saved,'pre', 1);
				array_push($result, htmlspecialchars($out[1],ENT_NOQUOTES));
			}
			else if(preg_match("/^\|(.+)\|(c|h)?$/",$line,$out))
			{
				$headform[$_cnt] = '|';
				$arytable = table_inc_add(explode("|",$out[1]));

				//1行目行末が c なら書式設定 拡張書式 by nao-pon
				if ((!$table) && ($out[2] == "c")) { 
					//$table_around = "<br clear=all /><br />";
					$table_around = "<br clear=all />";
					// 回り込み指定
					if (preg_match("/AROUND/i",$out[1])) $table_around = "";
					// ボーダー指定
					if (preg_match("/B:([0-9]+),?([0-9]*)(one|two|boko|deko|in|out|dash|dott)?/i",$out[1],$reg)) {
						if (preg_match("/one/i",$reg[3])) $border_type = "solid";
						else if (preg_match("/two/i",$reg[3])) $border_type = "double";
						else if (preg_match("/boko/i",$reg[3])) $border_type = "groove";
						else if (preg_match("/deko/i",$reg[3])) $border_type = "ridge";
						else if (preg_match("/in/i",$reg[3])) $border_type = "inset";
						else if (preg_match("/out/i",$reg[3])) $border_type = "outset";
						else if (preg_match("/dash/i",$reg[3])) $border_type = "dashed";
						else if (preg_match("/dott/i",$reg[3])) $border_type = "dotted";
						else $border_type = "outset";
						//$table_style .= " border=\"".$reg[1]."\"";
						if ($reg[1]==="0"){
							$table_sheet .= "border:none;";
						} else {
							$table_sheet .= "border:".$border_type." ".$reg[1]."px;";
						}
						if ($reg[2]!=""){
							$table_style .= " cellspacing=\"".$reg[2]."\"";
						} else {
							$table_style .= " cellspacing=\"1\"";
						}
						$out[1] = preg_replace("/B:([0-9]+),?([0-9]*)(one|two|boko|deko|in|out|dash|dott)?/i","",$out[1]);
					} else {
						$table_style .= " border=\"0\" cellspacing=\"1\"";
						//$table_style .= " cellspacing=\"1\"";
						//$table_sheet .= "border:none;";
					}
					// ボーダー色指定
					if (preg_match("/BC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$out[1],$reg)) {
						$table_sheet .= "border-color:".$reg[1].";";
						$out[1] = preg_replace("/BC:(#?[0-9abcdef]{6}?|$colors_reg)/i","",$out[1]);
					}
					// テーブル背景色指定
					if (preg_match("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$out[1],$reg)) {
						if ($reg[1]==="0") $reg[1]="transparent";
						$table_sheet .= "background-color:".$reg[1].";";
						$out[1] = preg_replace("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","TC:$2",$out[1]);
						$out[1] = preg_replace("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$out[1]);
					}
					// テーブル背景画像指定
					if (preg_match("/TC:\(([^),]*)(,once|,1)?\)/i",$out[1],$reg)) {
						$reg[1] = str_replace("http","HTTP",$reg[1]);
						$table_sheet .= "background-image: url(".$reg[1].");";
						if ($reg[2]) $table_sheet .= "background-repeat: no-repeat;";
						$out[1] = preg_replace("/TC:\(([^),]*)(,once|,1)?\)/i","",$out[1]);
					}
					// 配置・幅指定
					if (preg_match("/T(LEFT|RIGHT)/i",$out[1],$reg)) $table_style .= " align=\"".strtolower($reg[1])."\"";
					if (preg_match("/T(CENTER)/i",$out[1],$reg)) {
						$table_style .= " align=\"".strtolower($reg[1])."\"";
						$table_around = "";
					}
					if (preg_match("/T(LEFT|CENTER|RIGHT)?:([0-9]+[%]?)/i",$out[1],$reg)) {
						//if (!strpos("%",$reg[2])) $reg[2] .= "px";
						$table_sheet .= "width:".$reg[2].";";
					}
					$out[1] = preg_replace("/^(TLEFT|TCENTER|TRIGHT|T):([0-9]+[%]?)?/i","",$out[1]);
					
					$arytable = explode("|",$out[1]);
					$i = 0;
					$td_width = $td_align = array();
					foreach($arytable as $td){
						$i++;
						//echo "DEB:($i)$td<br />";
						// セル規定背景色指定
						if (preg_match("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
							if ($tmp[1]==="0") $tmp[1]="transparent";
							$td_color[$i] = "background-color:".$tmp[1].";";
							$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","SC:$2",$td);
							$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
						}
						// セル規定背景画指定
						if (preg_match("/SC:\(([^),]*)(,once|,1)?\)/i",$td,$tmp)) {
							$tmp[1] = str_replace("http","HTTP",$tmp[1]);
							$td_color[$i] .= "background-image: url(".$tmp[1].");";
							if ($tmp[2]) $td_color[$i] .= "background-repeat: no-repeat;";
							$td = preg_replace("/SC:\(([^),]*)(,once|,1)?\)/i","",$td);
						}
						// セル規定文字揃え、幅指定
						if (preg_match("/(LEFT|CENTER|RIGHT)?:([0-9]+[%]?)?/",$td,$tmp)) {
							if ($tmp[2]) $td_width[$i] = " width=\"".$tmp[2]."\"";
							if ($tmp[1]) $td_align[$i] = " align=\"".strtolower($tmp[1])."\"";
						}
					}
				} else {
					//$arytable = explode("|",$out[1]);
					if(!$table)
					{
						if (!$table_style) $table_style = " border=\"0\" cellspacing=\"1\"";
						$result = array_merge($result,$saved); $saved = array();
						array_push($result,"<div><table class=\"style_table\"$table_style style=\"$table_sheet\">");
						$table = count($arytable);
					}

					if ($out[2] == "h"){
						$td_name = "th";
					} else {
						$td_name = "td";
					}
					
					array_push($result,"<tr>");
					$i = 0;
					$_colspan = 1;
					$style = "";
					foreach($arytable as $td)
					{
						$i++;
						if ($td == ">"){
							$_colspan = $_colspan + 1;
						} else {
							// セル背景色指定
							if (preg_match("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
								if ($tmp[1]==="0") $tmp[1]="transparent";
								$sell_sheet .= "background-color:".$tmp[1].";";
								$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","SC:$2",$td);
								$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
							} else {
								if ($td_color[$i]) $sell_sheet .= $td_color[$i];
							}
							// セル背景画指定
							if (preg_match("/SC:\(([^),]*)(,once|,1)?\)/i",$td,$tmp)) {
								$tmp[1] = str_replace("http","HTTP",$tmp[1]);
								$sell_sheet .= "background-image: url(".$tmp[1].");";
								if ($tmp[2]) $sell_sheet .= "background-repeat: no-repeat;";
								$td = preg_replace("/SC:\(([^),]*)(,once|,1)?\)/i","",$td);
							}
							// セル内文字揃え指定
							if (preg_match("/^(LEFT|CENTER|RIGHT)?(:)(TOP|MIDDLE|BOTTOM)?([^\r]*)$/",$td,$tmp)) {
								if ($tmp[1]) {
									$style = ' align="'.strtolower($tmp[1]).'"';
								} else {
									if ($td_name == "td") $style = $td_align[$i];
								}
								if ($tmp[3]) {
									$style .= ' valign="'.strtolower($tmp[3]).'"';
								} else {
									//まだ規定値は準備中
								}
								$td = (!$tmp[1] && !$tmp[3])? $tmp[2].$tmp[4] : $tmp[4];
							} else {
								if ($td_name == "td") $style = $td_align[$i];
							}
							if ($sell_sheet) $sell_sheet=" style=\"".$sell_sheet."\"";
							if ($_colspan == 1){
								array_push($result,"<$td_name class=\"style_$td_name\"$style$td_width[$i]$sell_sheet>");
							} else {
								array_push($result,"<$td_name class=\"style_$td_name\"$style colspan=\"$_colspan\"$sell_sheet>");
							}

							// テーブル内で書式有効化のため再帰処理する条件
							// でも単純に再帰処理して問題ないかな？ by nao-pon
							$td_head = substr($td,0,1);
							if(	$td_head == ' ' || 
								$td_head == ':' || 
								$td_head == '>' || 
								$td_head == '-' || 
								$td_head == '+' || 
								$td_head == '|' || 
								$td_head == '*' || 
								$td_head == '#' || 
								(ereg("&br;",$td)) //複数行は無条件
								&& 
								(!preg_match("/#contents/",$td)) //除外
							) {
								$td_lines = ereg_replace("&br;","\n",$td);//this
								
								array_push($result,"\t".convert_html($td_lines));
							} else {
								array_push($result,ltrim(inline($td)));
							}

							//array_push($result,ltrim(inline($td)));
							array_push($result,"</$td_name>");
							$sell_sheet = "";
						}
					}
					array_push($result,"</tr>");
				}

			}
			else if(strlen($comment_out) != 0)
			{
				$headform[$_cnt] = '//';
#				array_push($result," <!-- ".htmlspecialchars($comment_out)." -->");
			}

		} else {

			$headform[$_cnt] = '';
			if($headform[$_cnt-1] != $headform[$_cnt]){
				if(array_values($saved)){
					if( $_bq ){
						array_unshift($saved, "</p>");
						$_bq = FALSE;
					}
					$i = array_pop($saved);
					array_push($saved,$i);
					$result = array_merge($result,$saved); $saved = array();
				}
				if( substr($line,0,1) == '' && !$_p){
					array_push($result, "<p>");
					$_p = TRUE;
				}
				else if( substr($line,0,1) != '' && $_p){
					array_push($result, "</p>");
					$_p = FALSE;
				}
			}
			
			if (preg_match("/^(LEFT|CENTER|RIGHT):(.*)$/",$line,$tmp)) {
				if ($_p)
					array_push($result,"</p>");
				array_push($result,'<div class="p_'.strtolower($tmp[1]).'">');
				array_push($result,inline($tmp[2]));
				array_push($result,"</div>");
				$line = '';
				$_p = FALSE;
			}
			if( substr($line,0,1) == '' && $_p){
				$_tmp = array_pop($result);
				if($_tmp == "<p>") {
					$_tmp = '<p class="empty">';
				}
				array_push($result, $_tmp, "</p>");
				$_p = FALSE;
			}
			else if( substr($line,0,1) != '' && !$_p) {
				array_push($result, "<p>");
					$_p = TRUE;
			}
			if( substr($line,0,1) != '' ){
				array_push($result, inline($line));
			}

		}

		$_cnt++;
	}

	if($_p) array_push($result, "</p>");
	if($_bq) {
		array_push($result, "</p>");
	}
	if($table) array_push($result, "</table></div>");
	
	$result_last = $result = array_merge($result,$saved); $saved = array();

	if($content_count != 0)
	{
		$result = array();
		$saved = array();

		foreach($arycontents as $line)
		{
			if(preg_match("/^(-{1,3})(.*)/",$line,$out))
			{
				list_push($result,$saved,'ul', strlen($out[1]));
				array_push($result, '<li>'.$out[2]);
			}
		}
		$result = array_merge($result,$saved); $saved = array();
		
		$contents = "<a name=\"contents_$content_id_local\"></a>\n";
		$contents .= join("\n",$result);
		if($strip_link_wall)
		{
			$contents = preg_replace("/\[\[([^\]:]+):(.+)\]\]/","$1",$contents);
			$contents = preg_replace("/\[\[([^\]]+)\]\]/","$1",$contents);
		}
	}

	$result_last = inline2($result_last);
	
	$result_last = preg_replace("/^#contents/",$contents,$result_last);

	$str = join("\n", $result_last);

#	$str = preg_replace("/&((amp)|(quot)|(nbsp)|(lt)|(gt));/","&$1;",$str);
	//$str = preg_replace("/!($WikiName)/", "$1", $str);
	$WikiName_ORG = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	$str = preg_replace("/!($WikiName_ORG)/", "$1", $str);
	//整形済み指定の" "を削除 nao-pon
	$str = preg_replace("/(^|\n) /", "$1", $str);

	return $str;
}

// $tagのタグを$levelレベルまで詰める。
function back_push(&$result,&$saved,$tag, $level)
{
	while (count($saved) > $level) {
		array_push($result, array_shift($saved));
	}
	if ($saved[0] != "</$tag>") {
		$result = array_merge($result,$saved); $saved = array();
	}
	while (count($saved) < $level) {
		array_unshift($saved, "</$tag>");
		array_push($result, "<$tag>");
	}
}

function list_push(&$result,&$saved,$tag,$level) {
	global $_list_left_margin, $_list_margin, $_list_pad_str;
	$cont = true;
	$open = "<$tag%s>";
	$close = "</li></$tag>";
	
	while (count($saved) > $level or
		(count($saved) > 0 and $saved[0] != $close)) {
		array_push($result, array_shift($saved));
	}
	
	$margin = $level - count($saved);
	
	while (count($saved) < ($level - 1)) {
		array_unshift($saved, ''); //count($saved)を増やすためのdummy
	}
	
	if (count($saved) < $level) {
		$cont = false;
		array_unshift($saved, $close);
		
		$left = $margin * $_list_margin;
		if ($level == $margin) $left += $_list_left_margin;
		$str = sprintf($_list_pad_str, $level, $left, $left);
		array_push($result, sprintf($open, $str));
	}
	
	if ($cont)
		array_push($result, '</li>');
}

// インライン要素のパース (注釈)
function inline($line,$remove=FALSE)
{
	$line = htmlspecialchars($line);
	
	$replace = $remove ? '' : 'make_note(\'$1\')';
	$line = preg_replace("/\(\(((?:(?!\)\)).)*)\)\)/ex",$replace,$line);

	return $line;
}

// インライン要素のパース (リンク、関連一覧、見出し一覧)
function inline2($str)
{
	global $WikiName,$BracketName,$InterWikiName,$vars,$related,$related_link,$script;
	$cnts_plain = array();
	$arykeep = array();

	for($cnt=0;$cnt<count($str);$cnt++)
	{
		if(preg_match("/^(\s)/",$str[$cnt]))
		{
			$arykeep[$cnt] = $str[$cnt];
			$str[$cnt] = "";
			$cnts_plain[] = $cnt;
		}
	}
	// インラインプラグイン前処理(インラインのパラメータはmake_linkを抑制)
	$str = preg_replace("/(&amp;[^(){}; ]+\()([^(){};]*)(\)([^;]*)?;)/","$1[[$2]]$3",$str);
	
	// リンク処理
	$str = make_link($str);

	// インラインプラグイン後処理(元に戻す)
	$str = preg_replace("/(&amp;[^(){}; ]+\()([^(){}]*)(\)([^;]*)?;)/e","'$1'.inline_after('$2').'$3'",$str);

	// インラインプラグイン

	//foreach($str as $tmp) echo htmlspecialchars($tmp)."<br>";

	$str = preg_replace("/&amp;([^(){};]+)(\(([^(){}]*)\))?(\{(.*)\})?;/ex","inline3('$1','$3','$5','$0')",$str);
	
	$str = preg_replace("/#related/e",'make_related($vars["page"],TRUE)',$str);
	$str = make_user_rules($str);

	$tmp = $str;
	$str = preg_replace("/^#norelated$/","",$str);
	if($tmp != $str)
		$related_link = 0;

	foreach($cnts_plain as $cnt)
		$str[$cnt] = $arykeep[$cnt];

	return $str;
}

// インラインプラグイン用エスケープ後処理
function inline_after($text)
{
	//echo $text."<br>";
	$text = strip_tags($text);
	$text = preg_replace("/(.*)\?$/","$1",$text);
	return $text;
}

// インラインプラグインの処理
function inline3($name,$arg,$body,$all)
{
	//&hoge(){...}; &fuga(){...}; のbodyが'...}; &fuga(){...'となるので、前後に分ける
	$after = '';
	if (preg_match("/^ ((?!};).*?) }; (.*?) &amp; (\w+) (?: \( ([^()]*) \) )? { (.+)$/x",$body,$matches))
	{
		$body = $matches[1];
		$after = inline3($matches[3],$matches[4],$matches[5],$matches[0]);
		$after = $matches[2].$after;
		if ($arg) {
			$all = "&amp;".$name."(".$arg."){".$body."};".$after;
		} else {
			$all = "&amp;".$name."{".$body."};".$after;
		}
	}
	if(exist_plugin_inline($name)) {
		//echo htmlspecialchars("$name:$arg:$body")."<br>";
		$str = do_plugin_inline($name,$arg,$body);
		if ($str !== FALSE){ //成功
			return $str.$after;
		}
	}
	// プラグインが存在しないか、変換に失敗
	return $all;
}
// インラインプラグイン用エスケープ後処理
function inline_out($a,$b,$c)
{
	$b = strip_tags($b);
	return $a.$b.$c;
}

// ページネームの取得
function get_page_name(){
	global $non_list,$whatsnew;
	
	$tmpnames = array();
	$retval = array();
//	$retval = "";
	$files = get_existpages();	
	foreach($files as $page) {
		if(preg_match("/$non_list/",$page)) continue;
		if($page == $whatsnew) continue;
		$tmpnames[strip_bracket($page)] = (function_exists('mb_strlen'))? mb_strlen(strip_bracket($page)) : strlen(strip_bracket($page));
	}
	arsort ($tmpnames);
	reset ($tmpnames);
/*	
	foreach($tmpnames as $name => $mojisu){
		$retval[] = $name;
	}
*/
	return array_keys($tmpnames);
}
// 一覧の取得
function get_list($withfilename)
{
	global $script,$list_index,$top,$non_list,$whatsnew;
	global $_msg_symbol,$_msg_other;
	
	$retval = array();
	$files = get_existpages();
	foreach($files as $page) {
		if(preg_match("/$non_list/",$page) && !$withfilename) continue;
		if($page == $whatsnew) continue;
		$page_url = rawurlencode($page);
		$page2 = strip_bracket($page);
		$pg_passage = get_pg_passage($page);
		$file = encode($page).".txt";
		$retval[$page2] .= "<li><a href=\"$script?$page_url\">".htmlspecialchars($page2,ENT_QUOTES)."</a>$pg_passage";
		if($withfilename)
		{
			$retval[$page2] .= "<ul><li>$file</li></ul>\n";
		}
		$retval[$page2] .= "</li>\n";
	}
	
	$retval = list_sort($retval);
	
	if($list_index)
	{
		$head_str = "";
		$etc_sw = 0;
		$symbol_sw = 0;
		$top_link = "";
		$link_counter = 0;
		foreach($retval as $page => $link)
		{
			$head = substr($page,0,1);
			if($head_str != $head && !$etc_sw)
			{
				$retval2[$page] = "";
				
				if(preg_match("/([A-Z])|([a-z])/",$head,$match))
				{
					if($match[1])
						$head_nm = "High_$head";
					else
						$head_nm = "Low_$head";
					
					if($head_str) $retval2[$page] = "</ul></li>\n";
					$retval2[$page] .= "<li><a href=\"#top_$head_nm\" name=\"$head_nm\"><strong>$head</strong></a>\n<ul>\n";
					$head_str = $head;
					if($link_counter) $top_link .= "|";
					$link_counter = $link_counter + 1;
					$top_link .= "<a href=\"#$head_nm\" name=\"top_$head_nm\"><strong>&nbsp;".$head."&nbsp;</strong></a>";
					if($link_counter==16) {
					        $top_link .= "<br />";
						$link_counter = 0;
					}
				}
				else if(preg_match("/[ -~]/",$head))
				{
					if(!$symbol_sw)
					{
						if($head_str) $retval2[$page] = "</ul></li>\n";
						$retval2[$page] .= "<li><a href=\"#top_symbol\" name=\"symbol\"><strong>$_msg_symbol</strong></a>\n<ul>\n";
						$head_str = $head;
						if($link_counter) $top_link .= "|";
						$link_counter = $link_counter + 1;
						$top_link .= "<a href=\"#symbol\" name=\"top_symbol\"><strong>$_msg_symbol</strong></a>";
						$symbol_sw = 1;
					}
				}
				else
				{
					if($head_str) $retval2[$page] = "</ul></li>\n";
					$retval2[$page] .= "<li><a href=\"#top_etc\" name=\"etc\"><strong>$_msg_other</strong></a>\n<ul>\n";
					$etc_sw = 1;
					if($link_counter) $top_link .= "|";
					$link_counter = $link_counter + 1;
					$top_link .= "<a href=\"#etc\" name=\"top_etc\"><strong>$_msg_other</strong></a>";
				}
			}
			$retval2[$page] .= $link;
		}
		$retval2[] = "</ul></li>\n";
		
		$top_link = "<div style=\"text-align:center\"><a name=\"top\"></a>$top_link</div><br />\n<ul>";
		
		array_unshift($retval2,$top_link);
	}
	else
	{
		$retval2 = $retval;
		
		$top_link = "<ul>";
		
		array_unshift($retval2,$top_link);
	}
	
	return join("",$retval2)."</ul>";
}

// 編集フォームの表示
function edit_form($postdata,$page,$add=0)
{
	global $script,$rows,$cols,$hr,$vars,$function_freeze;
	global $_btn_addtop,$_btn_preview,$_btn_update,$_btn_freeze,$_msg_help,$_btn_notchangetimestamp,$_btn_enter_enable,$_btn_autobracket_enable,$_btn_freeze_enable,$_btn_auther_id;
	global $whatsnew,$_btn_template,$_btn_load,$non_list,$load_template_func;
	global $freeze_check,$create_uid,$author_uid,$X_admin,$X_uid,$freeze_tag;
	
	$digest = md5(@join("",get_source($page)));
	$create_uid = (isset($create_uid))? $create_uid : $X_uid ;

	if($add)
	{
		$addtag = '<input type="hidden" name="add" value="true" />';
		$add_top = '<input type="checkbox" name="add_top" value="true" /><span class="small">'.$_btn_addtop.'</span>';
	}

	if($vars["help"] == "true")
		$help = $hr.catrule();
	else
 		$help = "<br />\n<ul><li><a href=\"$script?cmd=edit&amp;help=true&amp;page=".rawurlencode($page)."\">$_msg_help</a></ul></li>\n";

	$allow_edit_tag = $freeze_tag = '';
	//if($function_freeze){
		//$str_freeze = '<input type="submit" name="freeze" value="'.$_btn_freeze.'" accesskey="f" />';
		if (($X_uid && $X_uid == $author_uid) || $X_admin) {
			$freeze_tag = '<input type="hidden" name="f_create_uid" value="'.htmlspecialchars($create_uid).'" /><input type="checkbox" name="freeze" value="true" '.$freeze_check.'/><span class="small">'.$_btn_freeze_enable.'</span>';
			$allow_edit_tag = allow_edit_form();
		}
	//}
	if ($X_admin){
		$auther_tag = '  [ '.$_btn_auther_id.'<input type="text" name="f_author_uid" size="3" value="'.htmlspecialchars($author_uid).'" /> ]';
	} else {
		$auther_tag = '<input type="hidden" name="f_author_uid" value="'.htmlspecialchars($author_uid).'" />';
	}
	
	if($load_template_func)
	{
		$vals = array();

		$files = get_existpages();
		foreach($files as $pg_org) {
			if($pg_org == $whatsnew) continue;
			if(preg_match("/$non_list/",$pg_org)) continue;
			$name = strip_bracket($pg_org);
			$s_name = htmlspecialchars($name);
			$s_org = htmlspecialchars($pg_org);
			$vals[$name] = "    <option value=\"$s_org\">$s_name</option>";
		}
		@ksort($vals);
		
		$template = "   <select name=\"template_page\">\n"
			   ."    <option value=\"\">-- $_btn_template --</option>\n"
			   .join("\n",$vals)
			   ."   </select>\n"
			   ."   <input type=\"submit\" name=\"template\" value=\"$_btn_load\" accesskey=\"r\" /><br />\n";

		if($vars["refer"]) $refer = $vars["refer"]."\n\n";
	}

return '
<form action="'.$script.'" method="post">
'.$addtag.'
<table cellspacing="3" cellpadding="0" border="0" width="100%">
 <tr>
  <td align="right">
'.$template.'
  </td>
 </tr>
 <tr><td>
   <input type="checkbox" name="enter_enable" value="true" checked /><span class="small">'.$_btn_enter_enable.'</span> 
   <input type="checkbox" name="auto_bra_enable" value="true" checked /><span class="small">'.$_btn_autobracket_enable.'</span>
 </td></tr>
 <tr>
  <td align="right">
   <input type="hidden" name="page" value="'.htmlspecialchars($page).'" />
   <input type="hidden" name="digest" value="'.htmlspecialchars($digest).'" />
   <textarea name="msg" rows="'.$rows.'" cols="'.$cols.'" wrap="virtual">
'.htmlspecialchars($refer.$postdata).'</textarea>
  </td>
 </tr>
 <tr>
  <td>
   <input type="submit" name="preview" value="'.$_btn_preview.'" accesskey="p" />
   <input type="submit" name="write" value="'.$_btn_update.'" accesskey="s" />
   '.$add_top.'
   <input type="checkbox" name="notimestamp" value="true" /><span style="small">'.$_btn_notchangetimestamp.'</span>
   '.$auther_tag.'
  </td>
 </tr>
</table>
'.$allow_edit_tag.'
</form>
<!--
<form action="'.$script.'?cmd=freeze" method="post">
<div>
<input type="hidden" name="page" value="'.htmlspecialchars($vars["page"]).'" />
'.$str_freeze.'
</div>
</form>
-->
' . $help;
}

// 関連するページ
function make_related($page,$_isrule)
{
	global $related_str,$rule_related_str,$related,$_make_related,$vars;

	$page_name = strip_bracket($vars["page"]);
	$new_arylerated = array();
	if(!is_array($_make_related))
	{
		$aryrelated = do_search($page,"OR",1);

		if(is_array($aryrelated))
		{
			foreach($aryrelated as $key => $val)
			{
				//$new_arylerated[$key.md5($val)] = $val;
				$new_arylerated[$val] = $key;
			}
		}

		if(is_array($related))
		{
			foreach($related as $key => $val)
			{
				//$new_arylerated[$key.md5($val)] = $val;
				$new_arylerated[$val] = $key;
			}
		}

		//@krsort($new_arylerated);
		//$_make_related = @array_unique($new_arylerated);
		$_make_related = $new_arylerated;
		@arsort($_make_related);
	}

	if($_isrule)
	{
		if(is_array($_make_related))
		{
			foreach($_make_related as $str => $val)
			{
				preg_match("/<a\shref=\"([^\"]+)\">([^<]+)<\/a>(.*)/",$str,$out);
				
				if($out[3]) $title = " title=\"$out[2] $out[3]\"";
				
				$aryret[$out[2]] = "<a href=\"$out[1]\"$title>$out[2]</a>";
			}
			@ksort($aryret);
		}
	}
	else
	{
		$aryret = array_keys($_make_related);
	}
	if($_isrule) $str = $rule_related_str;
	else         $str = $related_str;

	return @join($str,$aryret);
}

// 注釈処理
function make_note($str)
{
	global $note_id,$foot_explain;

	$str = preg_replace("/^\(\(/","",$str);
	$str = preg_replace("/\s*\)\)$/","",$str);

	$str= str_replace("\\'","'",$str);

	$str = make_user_rules($str);

	$foot_explain[] = "<a name=\"notefoot_$note_id\" href=\"#notetext_$note_id\" class=\"note_super\">*$note_id</a> <span class=\"small\">$str</span><br />\n";
	$note =  "<a name=\"notetext_$note_id\" href=\"#notefoot_$note_id\" class=\"note_super\">*$note_id</a>";
	$note_id++;

	return $note;
}

// リンクを付加する
function make_link($name,$page = '')
{
	return p_make_link($name,$page);
}

// ユーザ定義ルール(ソースを置換する)
function user_rules_str($str)
{
	global $str_rules;

	$arystr = split("\n",$str);

	// 日付・時刻置換処理
	foreach($arystr as $str)
	{
		if(substr($str,0,1) != " ")
		{
			foreach($str_rules as $rule => $replace)
			{
				$str = preg_replace("/$rule/",$replace,$str);
			}
		}
		$retvars[] = $str;
	}

	return join("\n",$retvars);
}

// ユーザ定義ルール(ソースは置換せずコンバート)
function make_user_rules($str)
{
	global $user_rules;

	foreach($user_rules as $rule => $replace)
	{
		$str = preg_replace("/$rule/",$replace,$str);
	}

	return $str;
}

// HTMLタグを取り除く
function strip_htmltag($str)
{
	//$str = preg_replace("/<a[^>]+>\?<\/a>/","",$str);
	return preg_replace("/<[^>]+>/","",$str);
}

// ページ名からページ名を検索するリンクを作成
function make_search($page)
{
	global $script,$WikiName;

	$name = strip_bracket($page);
	$url = rawurlencode($page);

	//WikiWikiWeb like...
	//if(preg_match("/^$WikiName$/",$page))
	//	$name = preg_replace("/([A-Z][a-z]+)/","$1 ",$name);

 	return "<a href=\"$script?cmd=search&amp;word=$url\">".htmlspecialchars($name)."</a> ";
}

// テーブル入れ子用の連結
function table_inc_add ($arytable)
{
	//+-で囲んだ場合は、同じセル内＝テーブルを入れ子にできる。
	$td_level = 0 ;
	$lines_tmp = array();
	$td_tmp = "";
	foreach($arytable as $td){
		if (preg_match("/^\}([^|]*)$/",$td,$reg)) {
			$td_level += 1;
			if ($td_level == 1) $td = $reg[1];
		}
		if (preg_match("/^([^|]*)\{$/",$td,$reg)) {
			$td_level -= 1;
			if ($td_level == 0) $td = $reg[1];
		}
		if ($td_level) {
			if ($td_level == 1){
				//表内であるかの判定
				if (preg_match("/^.*&br;$/",$td) || preg_match("/^&br;.*$/",$td)) {
					$rep_str = "\n";
				} else {
					$rep_str = "->\n";
				}
				$td = preg_replace("/&br;([ #\-+*]|(&br;)+)/e","str_replace('&br;','$rep_str','$0')",$td);
				$td_tmp .= str_replace("~&br;","~$rep_str",$td)."|";//ok
				
			} else {
				$td_tmp .= str_replace("&br;","->\n",$td)."|";
			}
		} else {
			$td_tmp .= $td;//ok
			$lines_tmp[] = $td_tmp;
			$td_tmp = "";
		}
	}
	return $lines_tmp;
}
//編集権限フォーム
function allow_edit_form($allow_groups=NULL,$allow_users=NULL) {
	//global $xoopsUser;
	global $wiki_writable,$X_uid,$vars;
	global $_btn_allow_memo,$_btn_allow_header,$_btn_allow_group,$_btn_allow_user,$_btn_allow_memo_t,$_btn_allow_deny,$freeze_tag;

	//ページの編集権限を得る
	if (is_null($allow_groups) || is_null($allow_users)) $allows = get_pg_allow_editer($vars['page']);
	if (is_null($allow_groups) && $allows['group']) $allow_groups = explode(",",$allows['group']);
	if (is_null($allow_users) && $allows['user']) $allow_users = explode(",",$allows['user']);
	
	//ゲストが投稿不可の設定の場合「ゲスト」グループのメッセージを表示しない
	//$_btn_allow_guest = ($wiki_writable === 0)? $_btn_allow_guest : "";
	
	$ret = "<hr>";
	$ret .= "<table class='style_table'><tr><th colspan='3'>$freeze_tag</th></tr>";
	$ret .= "<tr><th class='style_th'>$_btn_allow_group</th><th class='style_th'>$_btn_allow_user</th><th class='style_th'>$_btn_allow_memo_t</th></tr>";
	$ret .= "<tr><td class='style_td'>";

	$groups = X_get_group_list();
	$mygroups = X_get_groups();

	// グループの名前をサイトの設定に書き換え
	//$_btn_allow_memo = str_replace("_GUEST_ALLOW_",$_btn_allow_guest,$_btn_allow_memo);
	$_btn_allow_memo = str_replace("_LOGDINUSER_",$groups[2],$_btn_allow_memo);
	//$_btn_allow_memo = str_replace("_GUREST_",$groups[3],$_btn_allow_memo);

	// グループ一覧表示
	$ret .= "<select  size='10' name='gids[]' id='gids[]' multiple='multiple'>";
	if (!is_array($allow_groups)){
		$sel = " selected";
	} else {
		$sel = (in_array("0",$allow_groups))? " selected" : "";
	}
	$ret .= "<option value='0'$sel>$_btn_allow_deny</option>";
	foreach ($groups as $gid => $gname){
		if ($gid !== 1 && $gid !== 3 && in_array($gid,$mygroups)){
			$sel = (in_array($gid,$allow_groups))? " selected" : "";
			$ret .= "<option value='".$gid."'".$sel.">$gname</option>";
		}
	}
	$ret .= "</select></td>";
	$ret .= "<td class='style_td'>";
	$allusers = X_get_users();
	asort($allusers);

	// ユーザ一覧表示
	$ret .= "<select  size='10' name='aids[]' id='aids[]' multiple='multiple'>";
	if (!is_array($allow_users)){
		$sel = " selected";
	} else {
		$sel = (in_array("0",$allow_users))? " selected" : "";
	}
	$ret .= "<option value='0' $sel>$_btn_allow_deny</option>";
	foreach ($allusers as $uid => $uname){
			$sel = (in_array($uid,$allow_users))? " selected" : "";
			if ($uid != $X_uid) $ret .= "<option value='".$uid."'$sel>$uname</option>";
	}
	$ret .= "</select></td><td class='style_td'>".$_btn_allow_memo."</td></tr></table>";
	
	return $ret;

}
?>
