<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: convert_html.php,v 1.1 2003/07/21 14:21:15 nao-pon Exp $
/////////////////////////////////////////////////
function convert_html($string)
{
	$body = new convert();
	$result_last = $body->to_html($string);
	
	// ����饤��������ʤ��Ԥ�����
	$cnts_plain = array();
	$arykeep = array();
	for($cnt=0;$cnt<count($result_last);$cnt++)
	{
		if(preg_match("/^(\s)/",$result_last[$cnt]))
		{
			$arykeep[$cnt] = $result_last[$cnt];
			$result_last[$cnt] = "";
			$cnts_plain[] = $cnt;
		}
	}

	// ����饤��ץ饰����
	$result_last = preg_replace("/&amp;(\w+)(\(((?:(?!\)[;{]).)*)\))?(\{(.*)\})?;/ex","\$body->inline3('$1','$3','$5','$0')",$result_last);
	
	// ����饤�����(����ղäʤ�)
	$result_last = $body->inline2($result_last);
	
	// ����饤��������ʤ��ä��Ԥ��᤹
	foreach($cnts_plain as $cnt)
		$result_last[$cnt] = $arykeep[$cnt];
	
	// ���󤫤��᤹
	$str = join("\n", $result_last);
	
	// WikiName�޻ߤ�!����
	$WikiName_ORG = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	$str = preg_replace("/!($WikiName_ORG)/", "$1", $str);
	
	//�����Ѥ߻����" "���� nao-pon
	$str = preg_replace("/(^|\n) /", "$1", $str);
	
	//������󥻥åȤ��Ƥߤ�
	//unset ($body,$cnts_plain,$arykeep);

	return $str;
	
}

class convert
{
	// �ƥ��������Τ�HTML���Ѵ�����
	function to_html($string)
	{
		global $hr,$script,$page,$vars,$top;
		global $note_id,$foot_explain,$digest,$note_hr;
		global $user_rules,$str_rules,$line_rules,$strip_link_wall;
		global $WikiName,$InterWikiName, $BracketName;
		global $_table_left_margin,$_table_right_margin;

		global $content_id;
		$content_id_local = ++$content_id;
		$content_count = 0;
		
		//if (is_array($sting)) $string = join('',$string);

		$string = rtrim($string);
		$string = preg_replace("/((\x0D\x0A)|(\x0D)|(\x0A))/","\n",$string);

		$start_mtime = getmicrotime();

		$digest = md5(@join("",get_source($vars["page"])));

		$result = array();
		$saved = array();
		$arycontents = array();

		//$string = preg_replace("/^#freeze\n/","",$string);
		$string = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$string);


		//����Ϥʤ���ä�����ɬ�פʤ��ΤȤ��⤦�ΤǤȤꤢ���������ȥ����� 03/06/29
		//$string = str_replace("&br; ","~\n ",$string);

		//ɽ��ս��Ƚ��Τ���ɽ��ɽ�δ֤϶��Ԥ�2��ɬ��
		$string = str_replace("|\n\n|","|\n\n\n|",$string);
		//ɽ��Ϥ��٤��ִ�
		$string = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('->\n','&br;','$2')).'$3'",$string);
		//ɽ��ɽ�δ֤϶���2�Ԥ�1�Ԥ��᤹
		$string = str_replace("|\n\n\n|","|\n\n|",$string);
		
		//~\n��&br;���Ѵ�����1�ԤȤ��ƽ��� nao-pon 03/06/25
		// ���Ԥ򶴤�Ǥ�URL�ʤɤ���󤷤Ƥ���Ⱦ�꤯�ڤ�ʬ�����ʤ��Τ�\t������ 03/06/29
		//  (���ڡ���)*#�ǻϤޤ�ԤϽ������ʤ��� 03/07/12
		$string = str_replace("\n","\n\x08",$string);
		$string = preg_replace("/(^|\x08)([^ *#].*)~\n/","$2\t&br;\t",$string);
		$string = str_replace("\x08","",$string);
		//��ñ�̤�����˳�Ǽ
		$lines = split("\n", $string);
		// �ƹԤι�Ƭ�񼰤��Ǽ
		$headform = array();
		// ���ߤιԿ�������Ƥ�����
		$_cnt = 0;
		// �֥��å���Ƚ��ե饰
		$_p = FALSE;
		$_bq = FALSE;
		// ���顼�͡��������ɽ��
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
					$div_style = "";
					$table_sheet = "";
					$sell_sheet = "";
					$td_color = $td_width = $td_align = array();
					array_push($result, "</table></div>".$table_around."");
				}
			}

			$comment_out = $comment_out[1];

			// ��Ƭ�񼰤��ɤ�����Ƚ��
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
						// ��Ƭ�˶��������뤳�Ȥˤ��Ȥꤢ����pre�ΰ�����Ʊ�ͤ�inline2��Ư�����ޤ��롢������̵�㡣
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
					// �����Τ�����Ǽ�����back_push�����Ƥ봶����̵������
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

					//1���ܹ����� c �ʤ������ ��ĥ�� by nao-pon
					if ((!$table) && ($out[2] == "c")) { 
						//$table_around = "<br clear=all /><br />";
						$table_around = "<br clear=all />";
						// �����߻���
						if (preg_match("/AROUND/i",$out[1])) $table_around = "";
						// �ܡ���������
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
						// �ܡ�����������
						if (preg_match("/BC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$out[1],$reg)) {
							$table_sheet .= "border-color:".$reg[1].";";
							$out[1] = preg_replace("/BC:(#?[0-9abcdef]{6}?|$colors_reg)/i","",$out[1]);
						}
						// �ơ��֥��طʿ�����
						if (preg_match("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$out[1],$reg)) {
							if ($reg[1]==="0") $reg[1]="transparent";
							$table_sheet .= "background-color:".$reg[1].";";
							$out[1] = preg_replace("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","TC:$2",$out[1]);
							$out[1] = preg_replace("/TC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$out[1]);
						}
						// �ơ��֥��طʲ�������
						if (preg_match("/TC:\(([^),]*)(,once|,1)?\)/i",$out[1],$reg)) {
							$reg[1] = str_replace("http","HTTP",$reg[1]);
							$table_sheet .= "background-image: url(".$reg[1].");";
							if ($reg[2]) $table_sheet .= "background-repeat: no-repeat;";
							$out[1] = preg_replace("/TC:\(([^),]*)(,once|,1)?\)/i","",$out[1]);
						}
						// ���֡�������
						if (preg_match("/T(LEFT|RIGHT)/i",$out[1],$reg)) {
							$table_align = strtolower($reg[1]);
							$table_style .= " align=\"".$table_align."\"";
							$div_style = " style=\"text-align:".$table_align."\"";
							if ($table_align == "left"){
								$table_sheet = "margin-left:{$_table_left_margin}px;margin-right:auto;";
							} else {
								$table_sheet = "margin-left:auto;margin-right:{$_table_right_margin}px;";
							}
						}
						if (preg_match("/T(CENTER)/i",$out[1],$reg)) {
							$table_style .= " align=\"".strtolower($reg[1])."\"";
							$div_style = " style=\"text-align:".strtolower($reg[1])."\"";
							$table_sheet = "margin-left:auto;margin-right:auto;";
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
							// ���뵬���طʿ�����
							if (preg_match("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
								if ($tmp[1]==="0") $tmp[1]="transparent";
								$td_color[$i] = "background-color:".$tmp[1].";";
								$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","SC:$2",$td);
								$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
							}
							// ���뵬���طʲ����
							if (preg_match("/SC:\(([^),]*)(,once|,1)?\)/i",$td,$tmp)) {
								$tmp[1] = str_replace("http","HTTP",$tmp[1]);
								$td_color[$i] .= "background-image: url(".$tmp[1].");";
								if ($tmp[2]) $td_color[$i] .= "background-repeat: no-repeat;";
								$td = preg_replace("/SC:\(([^),]*)(,once|,1)?\)/i","",$td);
							}
							// ���뵬��ʸ��·����������
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
							array_push($result,"<div class=\"ie5\" $div_style><table class=\"style_table\"$table_style style=\"$table_sheet\">");
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
								// �����طʿ�����
								if (preg_match("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
									if ($tmp[1]==="0") $tmp[1]="transparent";
									$sell_sheet .= "background-color:".$tmp[1].";";
									$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","SC:$2",$td);
									$td = preg_replace("/SC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
								} else {
									if ($td_color[$i]) $sell_sheet .= $td_color[$i];
								}
								// �����طʲ����
								if (preg_match("/SC:\(([^),]*)(,once|,1)?\)/i",$td,$tmp)) {
									$tmp[1] = str_replace("http","HTTP",$tmp[1]);
									$sell_sheet .= "background-image: url(".$tmp[1].");";
									if ($tmp[2]) $sell_sheet .= "background-repeat: no-repeat;";
									$td = preg_replace("/SC:\(([^),]*)(,once|,1)?\)/i","",$td);
								}
								// ������ʸ��·������
								if (preg_match("/^(LEFT|CENTER|RIGHT)?(:)(TOP|MIDDLE|BOTTOM)?([^\r]*)$/",$td,$tmp)) {
									if ($tmp[1]) {
										$style = ' align="'.strtolower($tmp[1]).'"';
									} else {
										if ($td_name == "td") $style = $td_align[$i];
									}
									if ($tmp[3]) {
										$style .= ' valign="'.strtolower($tmp[3]).'"';
									} else {
										//�ޤ������ͤϽ�����
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

								// �ơ��֥���ǽ�ͭ�����Τ���Ƶ�����������
								// �Ǥ�ñ��˺Ƶ�������������ʤ����ʡ� by nao-pon
								$td_head = substr($td,0,1);
								if(	$td_head == ' ' || 
									$td_head == ':' || 
									$td_head == '>' || 
									$td_head == '-' || 
									$td_head == '+' || 
									$td_head == '|' || 
									$td_head == '*' || 
									$td_head == '#' || 
									(ereg("&br;",$td)) //ʣ���Ԥ�̵���
									&& 
									(!preg_match("/#contents/",$td)) //����
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
		$result_last = preg_replace("/^#contents/",$contents,$result_last);
		
		return $result_last;

	}
	
	// ����饤�����ǤΥѡ��� (��󥯡���Ϣ���������Ф�����)
	function inline2($str)
	{
		global $WikiName,$BracketName,$InterWikiName,$vars,$related,$related_link,$script;

		// ��󥯽���
		$str = make_link($str);
		$str = str_replace(array("\x1c","\x1d"),"",$str);
		
		$str = preg_replace("/#related/e",'make_related($vars["page"],TRUE)',$str);
		$str = make_user_rules($str);

		$tmp = $str;
		$str = preg_replace("/^#norelated$/","",$str);
		if($tmp != $str)
			$related_link = 0;

		return $str;
	}
	// ����饤��ץ饰����ν���
	function inline3($name,$arg,$body,$all)
	{
		$name = stripslashes($name);
		$arg = stripslashes($arg);
		$body = stripslashes($body);
		$all = stripslashes($all);
		//&hoge(){...}; &fuga(){...}; ��body��'...}; &fuga(){...'�Ȥʤ�Τǡ������ʬ����
		$after = '';
		//if (preg_match("/^ ((?!};).*?) }; (.*?) &amp; (\w+) (?: \( ( [^()]          *) \) )? { (.+)$/x",$body,$matches))
		if (preg_match("/^ ((?!};).*?) }; (.*?) &amp; (\w+) (?: \( ( (?:(?!\)[;{]).)*) \) )? { (.+)$/x",$body,$matches))
		{
			$body = $matches[1];
			$after = $this->inline3($matches[3],$matches[4],$matches[5],$matches[0]);
			$after = $matches[2].$after;
			if ($arg) {
				$all = "&amp;".$name."(".$arg."){".$body."};".$after;
			} else {
				$all = "&amp;".$name."{".$body."};".$after;
			}
		}
		if(exist_plugin_inline($name)) {
			$str = do_plugin_inline($name,$arg,$body);
			if ($str !== FALSE){ //����
				return "\x1c".$str."\x1d".$after;
			}
		}
		// �ץ饰����¸�ߤ��ʤ������Ѵ��˼���
		return $all;
	}

}

//////////////////////////////////////////////

// ����饤�����ǤΥѡ��� (����)
function inline($line,$remove=FALSE)
{
	$line = htmlspecialchars($line);
	
	$replace = $remove ? '' : 'make_note(\'$1\')';
	$line = preg_replace("/\(\(((?:(?!\)\)).)*)\)\)/ex",$replace,$line);

	return $line;
}

// �������
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

// $tag�Υ�����$level��٥�ޤǵͤ�롣
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
		array_unshift($saved, ''); //count($saved)�����䤹�����dummy
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

?>