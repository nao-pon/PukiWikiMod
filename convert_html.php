<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: convert_html.php,v 1.28 2004/08/21 04:26:51 nao-pon Exp $
/////////////////////////////////////////////////
function convert_html($string,$is_intable=false,$page_cvt=false,$cache=false)
{
	global $vars,$related_link,$noattach,$noheader,$h_excerpt,$no_plugins,$X_uid,$foot_explain,$wiki_ads_shown,$content_id,$wiki_strong_words,$wiki_head_keywords;
	global $X_uname;
	
	static $convert_load = 0;
	$convert_load++;
	
	$wiki_strong_words = array();
	
	if ($page_cvt)
	{
		$page = add_bracket($string);
		$h_excerpt = "";
		$filename = PAGE_CACHE_DIR.encode($page).".txt";
		if (!$X_uid && file_exists($filename) && ($cache || (filemtime($filename) + PAGE_CACHE_MIN * 60) > time()))
		{
			$htmls = file($filename);
			$var_data = unserialize(rtrim(array_shift($htmls)));
			if (!is_array($var_data)) $var_data = array();
			$related_link = $var_data[0];
			$noattach =  $var_data[1];
			$noheader =  $var_data[2];
			$h_excerpt =  $var_data[3];
			$wiki_ads_shown =  $var_data[4];
			$vars['is_rsstop'] =  $var_data[5];
			$foot_explain = explode("\t",$var_data[6]);
			$wiki_strong_words =  $var_data[7];
			
			$wiki_head_keywords = array_merge($wiki_head_keywords,$wiki_strong_words);
			
			$convert_load--;
			
			$str = join('',$htmls);
			//̾�����ִ�
			$str = str_replace(WIKI_NAME_DEF,$X_uname,$str);
			return $str;
		}
		else
		{
			$string = join("",get_source($page));
		}
	}
	$string = preg_replace("/(^|\n)#newfreeze(\n|$)/","$1",$string);
	
	if (is_array($string)) $string = join('',$string);
	$body = new convert();
	
	$result_last = $body->to_html($string);
	
	// ����饤�����(������ϡ���ǽ�������)
	if (!$is_intable) $result_last = $body->inline2($result_last);

	if (!in_array("related",$no_plugins))
	{
		if ($is_intable)
			$result_last = preg_replace("/^#related/","\x08#related",$result_last);
		else
			$result_last = preg_replace("/(^|\x08)#related/e",'make_related($vars["page"],TRUE)',$result_last);
	}
	
	$result_last = preg_replace("/^#contents/",$body->contents,$result_last);
	
	$tmp = $result_last;
	$result_last = preg_replace("/^#norelated$/","",$result_last);
	if($tmp != $result_last) $related_link = 0;

	$tmp = $result_last;
	$result_last = preg_replace("/^#noattach$/","",$result_last);
	if($tmp != $result_last) $noattach = 1;

	$tmp = $result_last;
	$result_last = preg_replace("/^#noheader$/","",$result_last);
	if($tmp != $result_last) $noheader = 1;
	
	unset($tmp);
	
	
	// ���󤫤��᤹
	if (!$is_intable)
		$str = join("\n", $result_last);
	else
		$str = join("\r", $result_last);
	
	// WikiName�޻ߤ�!����
	//$WikiName_ORG = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	//$str = preg_replace("/!($WikiName_ORG)/", "$1", $str);
	
	//�����Ѥ߻����" "���� nao-pon
	$str = preg_replace("/(^|\n) /", "$1", $str);
	
	//������ɶ�Ĵ
	$wiki_strong_words = array_unique($wiki_strong_words);
	keyword_to_strong(&$str,$wiki_strong_words);
	$wiki_head_keywords = array_merge($wiki_head_keywords,$wiki_strong_words);
	
	//�����ȥ�������Ȥǥڡ�������С��Ȼ����
	if (!$X_uid && $page_cvt && !$cache)
	{
		//�ޥ���ɥᥤ���б�
		$str = preg_replace("/(<[^>]+(href|action|src)=(\"|'))https?:\/\/".$_SERVER["HTTP_HOST"]."(:[\d]+)?/i","$1",$str);
		
		//toprss�ϥ��󥯥롼�ɥڡ����Ͼ��0
		//#toprss�򵭽Ҥ����ڡ��������󥯥롼�ɤ��줿�������⾯���Ĥ뤬
		//calendar_viewer �ʤɤǥ��󥯥롼�ɤ��줿�ڡ����˸��ڡ������ͤ����å�
		//����Ƥ��ޤ�����Τۤ����礭���Τǡ��Ȥꤢ�����������롣
		$rsstop_set = ($convert_load === 1)? $vars['is_rsstop'] : 0;
		
		$var_data = array();
		$var_data[0] = $related_link;
		$var_data[1] = $noattach;
		$var_data[2] = $noheader;
		$var_data[3] = $h_excerpt;
		$var_data[4] = $wiki_ads_shown;
		$var_data[5] = $vars['is_rsstop'];
		$var_data[6] = preg_replace("/\x0D\x0A|\x0D|\x0A/","\t",join("\t",$foot_explain));
		$var_data[7] = ($convert_load === 1)? $wiki_head_keywords : $wiki_strong_words;
		$html = serialize($var_data)."\n".$str;
		
		//����å���񤭹���
		if ($fp = @fopen($filename,"w"))
		{
			fputs($fp,$html);
			fclose($fp);
		}
	}

	$convert_load--;
	
	//̾�����ִ�
	$str = str_replace(WIKI_NAME_DEF,$X_uname,$str);
	
	//������󥻥åȤ��Ƥߤ�
	unset ($body,$cnts_plain,$arykeep,$result_last,$html);
	
	return $str;
	
}

class convert
{
	var $contents;
	
	function get_contents()
	{
		return $this->contents;
	}
	// �ƥ��������Τ�HTML���Ѵ�����
	function to_html($string)
	{
		global $hr,$script,$page,$vars,$top;
		global $note_id,$foot_explain,$digest,$note_hr;
		global $str_rules,$line_rules,$strip_link_wall;
		global $WikiName,$InterWikiName, $BracketName;
		global $_table_left_margin,$_table_right_margin;
		global $anon_writable,$h_excerpt;
		global $no_plugins,$nowikiname;
		
		// �ơ��֥륻����ե饰
		static $is_intable = 0;
		
		$_freeze = is_freeze($vars['page']);

		global $content_id;
		$content_id_local = ++$content_id;
		$content_count = 0;
		
		//if (is_array($sting)) $string = join('',$string);

		$string = preg_replace("/((\x0D\x0A)|(\x0D)|(\x0A))/","\n",$string);

		$start_mtime = getmicrotime();

		$digest = md5(@join("",get_source($vars["page"])));

		$result = array();
		$saved = array();
		$arycontents = array();

		//$string = preg_replace("/^#freeze\n/","",$string);
		//$string = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$string);
		
		// �ڡ���������
		delete_page_info($string);


		//����Ϥʤ���ä�����ɬ�פʤ��ΤȤ��⤦�ΤǤȤꤢ���������ȥ����� 03/06/29
		//$string = str_replace("&br; ","~\n ",$string);

		//ɽ��ս��Ƚ��Τ���ɽ��ɽ�δ֤϶��Ԥ�2��ɬ��
		$string = str_replace("|\n\n|","|\n\n\n|",$string);
		//ɽ��Ϥ��٤��ִ�
		$string = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('->\n','___td_br___','$2')).'$3'",$string);
		//ɽ��ɽ�δ֤϶���2�Ԥ�1�Ԥ��᤹
		$string = str_replace("|\n\n\n|","|\n\n|",$string);
		
		//~\n��&br;���Ѵ�����1�ԤȤ��ƽ��� nao-pon 03/06/25
		// ���Ԥ򶴤�Ǥ�URL�ʤɤ���󤷤Ƥ���Ⱦ�꤯�ڤ�ʬ�����ʤ��Τ�\t������ 03/06/29
		//  (���ڡ���)*#�ǻϤޤ�ԤϽ������ʤ��� 03/07/12
		$string = str_replace("\n","\n\x08",$string);
		$string = preg_replace("/(^|\x08)([^ *#].*)~\n/","$2\t&br;\t",$string);
		$string = str_replace("\x08","",$string);
		
		// #category��μ¤˥֥�å����ǤȤ��뤿����ԤǶ���
		$string = preg_replace("/(^|\n)(\#category\(.*\))(\n|$)/","$1\n$2\n$3",$string);
		
		//��ñ�̤�����˳�Ǽ
		$lines = split("\n", $string);
		// �ƹԤι�Ƭ�񼰤��Ǽ
		$headform = array();
		// ���ߤιԿ�������Ƥ�����
		$_cnt = 0;
		// �֥�å���Ƚ��ե饰
		$_p = FALSE;
		$_bq = FALSE;
		// �����Ѥ߹�Ƚ��ե饰
		$_pre = 0;
		// ���顼�͡��������ɽ��
		$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";

		$table = 0;
		
		$pre_id = $c_pre_line = 0;
		
		if(preg_match("/#contents/",$string))
			$top_link = "<a href=\"#contents_$content_id_local\">$top</a>";

		foreach ($lines as $line)
		{
			// #category������˥���С���
			if(preg_match("/^\#category\((.*)\)$/",$line,$out))
			{
				if(exist_plugin_convert("category"))
					$line = do_plugin_convert("category",$out[1]);
			}
			
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
					$td_fcolor = $td_color = $td_width = $td_align = array();
					array_push($result, "</table></div>".$table_around."");
				}
			}
			if($line == "<<<")
			{
				if ($_pre === 0)
				{
					array_push($result, "<pre style=\"\">");
					$pre_id ++;
				}
				else
					array_push($result, "<pre>");
				$line = "";
				if (!$_pre) $_pre_headform = $headform[$_cnt-1];
				$_pre ++;
			}
			if ($_pre)
				$c_pre_line ++;

			$comment_out = (isset($comment_out[1]))? $comment_out[1] : "";

			// ��Ƭ�񼰤��ɤ�����Ƚ��
			$line_head = substr($line,0,1);
			if
			(
			!$_pre &&
				(
				$line_head == ' ' || 
				$line_head == ':' || 
				$line_head == '>' || 
				$line_head == '-' || 
				$line_head == '+' || 
				$line_head == '|' || 
				$line_head == '*' || 
				$line_head == '#' || 
				$comment_out != ''
				)
			)
			{
				if($headform[$_cnt-1] == '' && $_p){
					array_push($result, "</p>");
					$_p = FALSE;
				}
				if($line_head != '>' && $_bq){
					array_push($result, "</p>");
					$_bq = FALSE;
				}

				if(preg_match("/^\#([^\(]+)(.*)$/",$line,$out)){
					if(exist_plugin_convert($out[1],$no_plugins))
					{
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
				else if(preg_match("/^(\*{1,6})(.*)/",$line,$out))
				{
					$result = array_merge($result,$saved); $saved = array();
					$headform[$_cnt] = $out[1];
					$str = inline($out[2]);
					// <title>��
					if (!$h_excerpt) 
					{
						$h_excerpt = trim(strip_htmltag(preg_replace("/<a[^>]+>\?<\/a>/","",make_link(preg_replace("/\(\(((?:(?!\)\)).)*)\)\)/x","",$str)))));
					}
					//$level = strlen($out[1]) + 1;
					$level = strlen($out[1]);

					///// ParaeEdit /////
					$_tag = "<h$level><a name=\"content_{$content_id_local}_$content_count\"></a>$str $top_link</h$level>";
					if ($content_id_local == 1 && !$_freeze && $anon_writable) {
						$para_num = $content_count + 1;
						$para_link = "$script?cmd=edit&amp;id=$para_num&amp;page=" . rawurlencode($vars[page]);
						$para_link = "\x1c".sprintf(_EDIT_LINK, $para_link)."\x1d";
						$_replaced = _PARAEDIT_LINK_POS;
						eval(" \$_replaced = \"$_replaced\"; ");
						$_tag = preg_replace("/(<h\d.*?>)(.*)(<\/h\d>)/", $_replaced, $_tag);
					}
					array_push($result, $_tag);
					///// ParaeEdit /////
					
					$arycontents[] = str_repeat("-",$level)."<a href=\"#content_{$content_id_local}_$content_count\">".strip_htmltag(make_line_rules(inline($out[2],TRUE)))."</a>\n";
					$content_count++;
				}
				else if(preg_match("/^(-+)(.*)/",$line,$out))
				{
					$headform[$_cnt] = $out[1];
					if($out[1]=="----" && (preg_match("/^\d+%?$/",$out[2]) || !$out[2]))
					{
						$result = array_merge($result,$saved); $saved = array();
						$hr_tmp = $hr;
						if ($out[2]) $hr_tmp = ereg_replace("(<.*)(>)","\\1 width=".htmlspecialchars($out[2])."\\2",$hr_tmp);
						array_push($result, $hr_tmp);
					}
					else
					{
						list_push($result,$saved,'ul', strlen($out[1]));
						array_push($result, '<li>'.inline($out[2]));
					}
				}
				else if(preg_match("/^(\++)(.*)/",$line,$out))
				{
					$headform[$_cnt] = $out[1];
					list_push($result,$saved,'ol', strlen($out[1]));
					array_push($result, '<li>'.inline($out[2]));
				}
				//else if (preg_match("/^:([^:]+):(.*)/",$line,$out))
				else if (preg_match("/^:((?:\[\[.*]]|(?::\/\/|[^:])*)):(.*)/",$line,$out))
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
				else if(preg_match("/^([ \t]+.*)/",$line,$out))
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
								$table_sheet .= "margin-left:{$_table_left_margin}px;margin-right:auto;";
							} else {
								$table_sheet .= "margin-left:auto;margin-right:{$_table_right_margin}px;";
							}
						}
						if (preg_match("/T(CENTER)/i",$out[1],$reg)) {
							$table_style .= " align=\"".strtolower($reg[1])."\"";
							$div_style = " style=\"text-align:".strtolower($reg[1])."\"";
							$table_sheet .= "margin-left:auto;margin-right:auto;";
							$table_around = "";
						}
						if (preg_match("/T(LEFT|CENTER|RIGHT)?:([0-9]+[%]?)/i",$out[1],$reg)) {
							//if (!strpos("%",$reg[2])) $reg[2] .= "px";
							$table_sheet .= "width:".$reg[2].";";
						}
						$out[1] = preg_replace("/^(TLEFT|TCENTER|TRIGHT|T):([0-9]+[%]?)?/i","",$out[1]);
						
						$arytable = explode("|",$out[1]);
						$i = 0;
						$td_width = $td_align = $td_valign = array();
						foreach($arytable as $td){
							$i++;
							//echo "DEB:($i)$td<br />";
							// ���뵬��ʸ��������
							if (preg_match("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
								if ($tmp[1]==="0") $tmp[1]="transparent";
								$td_fcolor[$i] = "color:".$tmp[1].";";
								$td = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","FC:$2",$td);
								$td = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
							}
							// ���뵬���طʿ�����
							if (preg_match("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
								if ($tmp[1]==="0") $tmp[1]="transparent";
								$td_color[$i] = "background-color:".$tmp[1].";";
								$td = preg_replace("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","BC:$2",$td);
								$td = preg_replace("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
							}
							// ���뵬���طʲ����
							if (preg_match("/(?:SC|BC):\(([^),]*)(,once|,1)?\)/i",$td,$tmp)) {
								$tmp[1] = str_replace("http","HTTP",$tmp[1]);
								$td_color[$i] .= "background-image: url(".$tmp[1].");";
								if ($tmp[2]) $td_color[$i] .= "background-repeat: no-repeat;";
								$td = preg_replace("/(?:SC|BC):\(([^),]*)(,once|,1)?\)/i","",$td);
							}
							// ���뵬��ʸ��·����������
							if (preg_match("/(LEFT|CENTER|RIGHT)?:(TOP|MIDDLE|BOTTOM)?(?::)?([0-9]+[%]?)?/i",$td,$tmp)) {
								if ($tmp[3]) $td_width[$i] = "width:".$tmp[3].";";
								if ($tmp[1]) $td_align[$i] = "text-align:".strtolower($tmp[1]).";";
								if ($tmp[2]) $td_valign[$i] = "vertical-align:".strtolower($tmp[2]).";";
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
								// ����ʸ��������
								if (preg_match("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
									if ($tmp[1]==="0") $tmp[1]="transparent";
									$sell_sheet .= "color:".$tmp[1].";";
									$td = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","FC:$2",$td);
									$td = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
								} else {
									if ($td_fcolor[$i]) $sell_sheet .= $td_fcolor[$i];
								}
								// �����طʿ�����
								if (preg_match("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i",$td,$tmp)) {
									if ($tmp[1]==="0") $tmp[1]="transparent";
									$sell_sheet .= "background-color:".$tmp[1].";";
									$td = preg_replace("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","BC:$2",$td);
									$td = preg_replace("/(?:SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
								} else {
									if ($td_color[$i]) $sell_sheet .= $td_color[$i];
								}
								// �����طʲ����
								if (preg_match("/(?:SC|BC):\(([^),]*)(,once|,1)?\)/i",$td,$tmp)) {
									$tmp[1] = str_replace("http","HTTP",$tmp[1]);
									$sell_sheet .= "background-image: url(".$tmp[1].");";
									if ($tmp[2]) $sell_sheet .= "background-repeat: no-repeat;";
									$td = preg_replace("/(?:SC|BC):\(([^),]*)(,once|,1)?\)/i","",$td);
								}
								// ������ʸ��·������
								if (preg_match("/^(LEFT|CENTER|RIGHT)?(:)(TOP|MIDDLE|BOTTOM)?([^\r]*)$/i",$td,$tmp)) {
									if ($tmp[1]) {
										$sell_sheet .= "text-align:".strtolower($tmp[1]).";";
									} else {
										//������
										if ($td_name == "td") $sell_sheet .= $td_align[$i];
									}
									if ($tmp[3]) {
										$sell_sheet .= "vertical-align:".strtolower($tmp[3]).";";
									} else {
										//������
										if ($td_name == "td") $sell_sheet .= $td_valign[$i];
									}
									$td = (!$tmp[1] && !$tmp[3])? $tmp[2].$tmp[4] : $tmp[4];
								} else {
									if ($td_name == "td") {
										$sell_sheet .= $td_align[$i];
										$sell_sheet .= $td_valign[$i];
									}
								}
								$sell_sheet .= $td_width[$i];
								if ($sell_sheet) $sell_sheet=" style=\"".$sell_sheet."\"";
								if ($_colspan == 1){
									array_push($result,"<$td_name class=\"style_$td_name\"$style$sell_sheet>");
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
									(ereg("___td_br___",$td)) //ʣ���Ԥ�̵���
									&& 
									(!preg_match("/#contents/",$td)) //����
								) {
									$td_lines = ereg_replace("___td_br___","\n",$td);//this
									
									//array_push($result,"\t".convert_html($td_lines,true));
									//������ΰ� "\x08"
									$is_intable ++;
									array_push($result,"\x08".convert_html($td_lines,$is_intable));
									$is_intable --;
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

			}
			else if($line == ">>>" && $_pre)
			{
				$_pre --;
				$j_script = "";
				if (!$_pre)
				{
					$c_pre_line = ($c_pre_line-2) * 14 + 32;
					if ($c_pre_line > 420) $c_pre_line = 420;
					array_splice ($result, array_search ( "<pre style=\"\">", $result), 1, "<pre id=\"code_area{$content_id}_{$pre_id}\" style=\"height:".$c_pre_line."px;\" class=\"multi\">");
					$c_pre_line = 0;
					$j_script = "<script type=\"text/javascript\"><!--\nh_pukiwiki_make_copy_button('code_area".$content_id."_".$pre_id."');\n--></script>";
					$headform[$_cnt] = $_pre_headform;
				}
				$_result = array_pop($result);
				if ($_result == "</pre>")
					array_push($result, "</pre></pre>".$j_script);
				else
				{
					array_push($result, $_result);
					array_push($result, "</pre>".$j_script);
				}
			}
			else
			{
				$headform[$_cnt] = '';
				if (!$_pre)
				{
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
					else if( substr($line,0,1) != '' && !$_p && !$is_intable) {
						array_push($result, "<p>");
						$_p = TRUE;
					}
					else if( substr($line,0,1) == '' && !$_p && $is_intable){
						$_tmp = array_pop($result);
						if($_tmp != "</p>") {
							array_push($result, $_tmp,"<p>");
							$_p = TRUE;
						}
						else
							array_push($result, $_tmp);
					}
				}
				if( substr($line,0,1) != '' ){
					if ($_pre)
					{
						// ~\n�ǷҤ����Ԥ򸵤��᤹
						$line = str_replace("\t&br;\t","~\n",$line);
						// &#x7c; ���᤹
						$line = str_replace("&#x7c;","|",$line);
						
						$_result = array_pop($result);
						if ($_result == "</pre>")
							array_push($result, " </pre>".htmlspecialchars($line,ENT_NOQUOTES));
						else
						{
							array_push($result, $_result);
							array_push($result, " ".htmlspecialchars($line,ENT_NOQUOTES));
						}

						//array_push($result, " ".htmlspecialchars($line,ENT_NOQUOTES));
					}
					else
						array_push($result, inline($line));
				}

			}
		$_cnt++;
		}
		if ($_pre) array_push($result, str_repeat("</pre>",$_pre));
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
				if(preg_match("/^(-+)(.*)/",$line,$out))
				{
					list_push($result,$saved,'ul', strlen($out[1]));
					array_push($result, '<li>'.$out[2]);
				}
			}
			$result = array_merge($result,$saved); $saved = array();
			
			$this->contents = "<a name=\"contents_$content_id_local\"></a>\n";
			$this->contents .= join("\n",$result);
			if($strip_link_wall)
			{
				$this->contents = preg_replace("/\[\[([^\]:]+):(.+)\]\]/","$1",$this->contents);
				$this->contents = preg_replace("/\[\[([^\]]+)\]\]/","$1",$this->contents);
			}
		}
		unset ($result,$saved);//�������󤷤Ƥߤ�
		return $result_last;

	}
	
	// ����饤�����ǤΥѡ��� (��󥯡���Ϣ���������Ф�����)
	function inline2($str)
	{
		global $WikiName,$BracketName,$InterWikiName,$vars,$related,$related_link,$script,$noattach,$noheader;
		
		// ��󥯽���
		if (is_array($str))
		{
			$_str = array();
			foreach ($str as $line)
			{
				//echo htmlspecialchars($line)."<hr />";
				if (preg_match("/^\x08/",$line)) //�ơ��֥륻���档����ˤ��ƺƵ�����
					$_str[] = join("\n",$this->inline2(split("\r",preg_replace("/^\x08/","",$line))));
				elseif (!strip_tags($line) || preg_match("/^[ #\s\t]/",$line))
					$_str[] = $line;
				else
					$_str[] = make_link($line);
			}
			$str = $_str;
			unset($_str);
		}
		else
			$str = make_link($str);
		
		return $str;
	}
}

//////////////////////////////////////////////

// ����饤�����ǤΥѡ��� (���)
function inline($line,$remove = FALSE)
{
	$line = htmlspecialchars($line);
	//if ($remove) $line = preg_replace("/\(\(((?:(?!\)\)).)*)\)\)/x","",$line);
	if ($remove) $line = make_link(preg_replace("/\(\(((?:(?!\)\)).)*)\)\)/x","",$line));
	return $line;
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

//������ɶ�Ĵ
function keyword_to_strong(&$str,$words=array())
{
	if (!$words || !$str) return ;
	$keys = array();
	foreach ($words as $word)
	{
		$keys[$word] = strlen($word);
	}
	arsort($keys,SORT_NUMERIC);
	$keys = get_search_words(array_keys($keys));
	foreach ($keys as $key=>$pattern)
	{
		$s_key = htmlspecialchars($key);
		$pattern = ($s_key{0} == '&') ?
			"/(<[^>]*>)|($pattern)/" :
			"/(<[^>]*>|&(?:#[0-9]+|#x[0-9a-f]+|[0-9a-zA-Z]+);)|($pattern)/";
		$str = preg_replace_callback($pattern,
			create_function('$arr',
				'return $arr[1] ? $arr[1] : "<strong>{$arr[2]}</strong>";'),$str);
	}
	
	return ;
}
?>
