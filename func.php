<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: func.php,v 1.14 2003/07/23 23:55:42 nao-pon Exp $
/////////////////////////////////////////////////
// ʸ���󤬥ڡ���̾���ɤ���
function is_pagename($str)
{
	global $BracketName,$WikiName;
	//$is_pagename = (!is_interwiki($str) and preg_match("/^(?!\/)$BracketName$(?<!\/$)/",$str)
	//	and !preg_match('/(^|\/)\.{1,2}(\/|$)/',$str));
	if (!$str) return false;
	$str = add_bracket($str);
	$is_pagename = (preg_match("/^(?!\/)($BracketName|$WikiName)$(?<!\/$)/",$str)
		and !preg_match('/(^|\/)\.{1,2}(\/|$)/',$str));
	
	if (defined('SOURCE_ENCODING'))
	{
		if (SOURCE_ENCODING == 'UTF-8')
		{
			$is_pagename = ($is_pagename and preg_match('/^(?:[\x00-\x7F]|(?:[\xC0-\xDF][\x80-\xBF])|(?:[\xE0-\xEF][\x80-\xBF][\x80-\xBF]))+$/',$str)); // UTF-8
		}
		else if (SOURCE_ENCODING == 'EUC-JP')
		{
			$is_pagename = ($is_pagename and preg_match('/^(?:[\x00-\x7F]|(?:[\x8E\xA1-\xFE][\xA1-\xFE])|(?:\x8F[\xA1-\xFE][\xA1-\xFE]))+$/',$str)); // EUC-JP
		}
	}
	
	return $is_pagename;
}

// �������Ÿ������
function get_search_words($words,$special=FALSE)
{
	$retval = array();
	// Perl��� - �������ѥ�����ޥå�������
	// http://www.din.or.jp/~ohzaki/perl.htm#JP_Match
	$eucpre = $eucpost = '';
	if (SOURCE_ENCODING == 'EUC-JP')
	{
		$eucpre = '(?<!\x8F)';
		// # JIS X 0208 �� 0ʸ���ʾ�³���� # ASCII, SS2, SS3 �ޤ��Ͻ�ü
		$eucpost = '(?=(?:[\xA1-\xFE][\xA1-\xFE])*(?:[\x00-\x7F\x8E\x8F]|\z))';
	}
	// $special : htmlspecialchars()���̤���
	$quote_func = create_function('$str',$special ?
		'return preg_quote($str,"/");' :
		'return preg_quote(htmlspecialchars($str),"/");'
	);
	// LANG=='ja'�ǡ�mb_convert_kana���Ȥ������mb_convert_kana�����
	$convert_kana = create_function('$str,$option',
		(LANG == 'ja' and function_exists('mb_convert_kana')) ?
			'return mb_convert_kana($str,$option);' : 'return $str;'
	);
	
	foreach ($words as $word)
	{
		// �ѿ�����Ⱦ��,�������ʤ�����,�Ҥ餬�ʤϥ������ʤ�
		$word_zk = $convert_kana($word,'aKCV');
		$chars = array();
		for ($pos = 0; $pos < mb_strlen($word_zk);$pos++)
		{
			$char = mb_substr($word_zk,$pos,1);
			$arr = array($quote_func($char));
			if (strlen($char) == 1) // �ѿ���
			{
				$_char = strtoupper($char); // ��ʸ��
				$arr[] = $quote_func($_char);
				$arr[] = $quote_func($convert_kana($_char,"A")); // ����
				$_char = strtolower($char); // ��ʸ��
				$arr[] = $quote_func($_char);
				$arr[] = $quote_func($convert_kana($_char,"A")); // ����
			}
			else // �ޥ���Х���ʸ��
			{
				$arr[] = $quote_func($convert_kana($char,"c")); // �Ҥ餬��
				$arr[] = $quote_func($convert_kana($char,"k")); // Ⱦ�ѥ�������
			}
			$chars[] = '(?:'.join('|',array_unique($arr)).')';
		}
		$retval[$word] = $eucpre.join('',$chars).$eucpost;
	}
	return $retval;
}
// ���� 1.4
function do_search($word,$type='AND',$non_format=FALSE)
{
	global $script,$vars,$whatsnew,$non_list,$search_non_list;
	global $_msg_andresult,$_msg_orresult,$_msg_notfoundresult;
	
	$database = array();
	$retval = array();

	$b_type = ($type == 'AND'); // AND:TRUE OR:FALSE
	$keys = get_search_words(preg_split('/\s+/',$word,-1,PREG_SPLIT_NO_EMPTY));
	
	$_pages = get_existpages();
	$pages = array();
	
	foreach ($_pages as $page)
	{
		if ($page == $whatsnew
			or (!$search_non_list and preg_match("/$non_list/",$page)))
		{
			continue;
		}
		
		$source = get_source($page);
		if (!$non_format)
		{
			array_unshift($source,$page); // �ڡ���̾�⸡���оݤ�
		}
		
		$b_match = FALSE;
		foreach ($keys as $key)
		{
			$tmp = preg_grep("/$key/",$source);
			$b_match = (count($tmp) > 0);
			if ($b_match xor $b_type)
			{
				break;
			}
		}
		if ($b_match)
		{
			$pages[$page] = get_filetime(encode($page));
			if ($non_format){
				$page_url = rawurlencode($page);
				$link_tag="<a href=\"$script?$page_url\">".htmlspecialchars(strip_bracket($page), ENT_QUOTES)."</a>";
				$link_tag .= get_pg_passage($page);
				//$tm = @filemtime(get_filename(encode($page)));
				$tm = "t".$pages[$page];
				$nf_ret[$tm] = $link_tag;
			}
		}
	}
	if ($non_format)
	{
		return $nf_ret;
		//return array_keys($pages);
	}
	$r_word = rawurlencode($word);
	$s_word = htmlspecialchars($word);
	if (count($pages) == 0)
	{
		return str_replace('$1',$s_word,$_msg_notfoundresult);
	}
	ksort($pages);
	$retval = "<ul>\n";
	foreach ($pages as $page=>$time)
	{
		$page2 = strip_bracket($page);
		$r_page = rawurlencode($page2);
		$s_page = htmlspecialchars($page2);
		$passage = get_pg_passage($page);
		$retval .= " <li><a href=\"$script?cmd=read&amp;page=$r_page&amp;word=$r_word\">$s_page</a>$passage</li>\n";
	}
	$retval .= "</ul>\n";
	
	$retval .= str_replace('$1',$s_word,str_replace('$2',count($pages),
		str_replace('$3',count($_pages),$b_type ? $_msg_andresult : $_msg_orresult)));
	
	return $retval;
}

// �ץ����ؤΰ����Υ����å�
function arg_check($str)
{
	global $arg,$vars;

	return preg_match("/^".$str."/",$vars["cmd"]);
}

// �ڡ����ꥹ�ȤΥ�����
function list_sort($values)
{
	if(!is_array($values)) return array();
	
	// ksort�Τߤ��ȡ�[[���ܸ�]]��[[��ʸ��]]����ʸ���Τߡ��˽���¤��ؤ�����
	ksort($values);

	$vals1 = array();
	$vals2 = array();
	$vals3 = array();

	// ��ʸ���Τߡ�[[��ʸ��]]��[[���ܸ�]]���ν���¤��ؤ���
	foreach($values as $key => $val)
	{
		if(preg_match("/\[\[[^\w]+\]\]/",$key))
			$vals3[$key] = $val;
		else if(preg_match("/\[\[[\W]+\]\]/",$key))
			$vals2[$key] = $val;
		else
			$vals1[$key] = $val;
	}
	return array_merge($vals1,$vals2,$vals3);
}

// �ڡ���̾�Υ��󥳡���
function encode($key)
{
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);
	
	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}

// �ե�����̾�Υǥ�����
function decode($key)
{
	$dekey = '';
	
	for($i=0;$i<strlen($key);$i+=2)
	{
		$ch = substr($key,$i,2);
		$dekey .= chr(intval("0x".$ch,16));
	}
	return $dekey;
}

// InterWikiName List �β��(����:����������)
function open_interwikiname_list()
{
	global $interwiki;
	
	$retval = array();
	$aryinterwikiname = get_source($interwiki);

	$cnt = 0;
	foreach($aryinterwikiname as $line)
	{
		//if(preg_match("/\[((https?|ftp|news)(\:\/\/[[:alnum:]\+\$\;\?\.%,!#~\*\/\:@&=_\-]+))\s([^\]]+)\]\s?([^\s]*)/",$line,$match))
		if(preg_match("/\[(((?:https?|ftp|news):\/\/|\.\.?\/)([[:alnum:]\+\$\;\?\.%,!#~\*\/\:@&=_\-]+))\s([^\]]+)\]\s?([^\s]*)/",$line,$match))
		{
			$retval[$match[4]]["url"] = $match[1];
			$retval[$match[4]]["opt"] = $match[5];
		}
	}

	return $retval;
}

// [[ ]] �������
function strip_bracket($str)
{
	global $strip_link_wall;
	
	if($strip_link_wall)
	{
	  if(preg_match("/^\[\[(.*)\]\]$/",$str,$match)) {
	    $str = $match[1];
	  }
	}
	return $str;
}

// [[ ]] ���ղä���
function add_bracket($str){
	global $WikiName;
	if (!preg_match("/^".$WikiName."$/",$str)){
		if (!preg_match("/\[\[.*\]\]/",$str)) $str = "[[".$str."]]";
	}
	return $str;
}

// �ƥ����������롼���ɽ������
function catrule()
{
	global $rule_body;
	return $rule_body;
}

// ���顼��å�������ɽ������
function die_message($msg)
{
	$title = $page = "Runtime error";

	$body = "<h3>Runtime error</h3>\n";
	$body .= "<strong>Error message : $msg</strong>\n";

	if(defined(SKIN_FILE) && file_exists(SKIN_FILE) && is_readable(SKIN_FILE)) {
	  catbody($title,$page,$body);
	}
	else {
	  header("Content-Type: text/html; charset=euc-jp");
	  print <<<__TEXT__
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>$title</title>
<meta http-equiv="content-type" content="text/html; charset=euc-jp">
</head>
<body>$body</body>
</html>
__TEXT__;
	}
	die();
}

// ���߻����ޥ������äǼ���
function getmicrotime()
{
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$sec + (float)$usec);
}

// ����������
function get_date($format,$timestamp = NULL)
{
	$time = ($timestamp === NULL) ? UTIME : $timestamp;
	$time += ZONETIME;
	
	$format = preg_replace('/(?<!\\\)T/',preg_replace('/(.)/','\\\$1',ZONE),$format);
	
	return date($format,$time);
}

// ����ʸ�������
function format_date($val, $paren = FALSE)
{
	global $date_format,$time_format,$weeklabels;
	
	$val += ZONETIME;
	
	$ins_date = date($date_format,$val);
	$ins_time = date($time_format,$val);
	$ins_week = '('.$weeklabels[date('w',$val)].')';
	
	$ins = "$ins_date $ins_week $ins_time";
	return $paren ? "($ins)" : $ins;
}

// �в����ʸ�������
function get_passage($time)
{
	static $units = array('s'=>60,'m'=>60,'h'=>24,'d'=>1);
	
	$time = UTIME - $time;
	
	foreach ($units as $unit=>$card)
	{
		if ($time < $card)
		{
			break;
		}
		$time /= $card;
	}
	$time = floor($time);
	
	return "($time$unit)";
}

// ��ʬ�κ���
function do_diff($strlines1,$strlines2)
{
	//���ԥ���������
	$strlines1 = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$strlines1);
	$strlines2 = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$strlines2);
	
	$lines1 = split("\n",$strlines1);
	$lines2 = split("\n",$strlines2);
	
	$same_lines = $diff_lines = $del_lines = $add_lines = $retdiff = array();
	
	if(count($lines1) > count($lines2)) { $max_line = count($lines1)+2; }
	else                                { $max_line = count($lines2)+2; }

	//$same_lines = array_intersect($lines1,$lines2);

	$diff_lines2 = array_diff($lines2,$lines1);
	$diff_lines = array_merge($diff_lines2,array_diff($lines1,$lines2));

	foreach($diff_lines as $line)
	{
		$index = array_search($line,$lines1);
		if($index > -1)
		{
			$del_lines[$index] = $line;
		}
		
		//$index = array_search($line,$lines2);
		//if($index > -1)
		//{
		//	$add_lines[$index] = $line;
		//}
	}

	$cnt=0;
	foreach($lines2 as $line)
	{
		$line = rtrim($line);
		
		while($del_lines[$cnt])
		{
			$retdiff[] = "- ".$del_lines[$cnt];
			$del_lines[$cnt] = "";
			$cnt++;
		}
		
		if(in_array($line,$diff_lines))
		{
			$retdiff[] = "+ $line";
		}
		else
		{
			$retdiff[] = "  $line";
		}		

		$cnt++;
	}
	
	foreach($del_lines as $line)
	{
		if(trim($line))
			$retdiff[] = "- $line";
	}

	return join("\n",$retdiff);
}


// ��ʬ�κ���
function do_update_diff($oldstr,$newstr)
{
	$oldlines = split("\n",$oldstr);
	$newlines = split("\n",$newstr);
	
	$retdiff = $props = array();
	$auto = true;
	
	foreach($newlines as $newline) {
	  $flg = false;
	  $cnt = 0;
	  foreach($oldlines as $oldline) {
	    if($oldline == $newline) {
	      if($cnt>0) {
		for($i=0; $i<$cnt; ++$i) {
		  array_push($retdiff,array_shift($oldlines));
		  array_push($props,'! ');
		  $auto = false;
		}
	      }
	      array_push($retdiff,array_shift($oldlines));
	      array_push($props,'');
	      $flg = true;
	      break;
	    }
	    $cnt++;
	  }
	  if(!$flg) {
	    array_push($retdiff,$newline);
	    array_push($props,'+ ');
	  }
	}
	foreach($oldlines as $oldline) {
	  array_push($retdiff,$oldline);
	  array_push($props,'! ');
	  $auto = false;
	}
	if($auto) {
	  return array(join("\n",$retdiff),$auto);
	}

	$ret = '';
	foreach($retdiff as $line) {
	  $ret .= array_shift($props) . $line . "\n";
	}
	return array($ret,$auto);
}

/*
�ѿ����null(\0)�Х��Ȥ�������
PHP��fopen("hoge.php\0.txt")��"hoge.php"�򳫤��Ƥ��ޤ��ʤɤ����ꤢ��

http://ns1.php.gr.jp/pipermail/php-users/2003-January/012742.html
[PHP-users 12736] null byte attack
*/ 
function sanitize_null_character($param)
{
	if (is_array($param))
	{
		$result = array();
		foreach ($param as $key => $value)
		{
			$key = sanitize_null_character($key);
			$result[$key] = sanitize_null_character($value);
		}
	}
	else
	{
		$result = str_replace("\0",'',$param);
	}
	return $result;
}

// URL���ɤ���
function is_url($text) {
	return preg_match('/^(https?|ftp|news)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $text);
}

// for PukiWikiMod
function js_redirect($location) {
	echo "
		<script language=\"JavaScript\">
		<!--
			location.href = \"$location\";
		// -->
		</script>
		<noscript>
		<p>�֥饦���� JavaScript �����ѤǤ��ޤ���</p>
		<hr />
		<p>����Υڡ����إ����פ���ˤϡ��ʲ��Υ�󥯤򥯥�å����Ƥ���������<br />
		<a href=\"$location\">$location</a></p>
		<p>[ <a href=\"$script\">PukiWiki �ȥåפ����</a> ]</p>
		<hr />
		";
}

//���Ԥ�ͭ���ˤ��� by nao-pon
function auto_br($msg){
		//*ɽ����Ի���ʸ���������
		//ɽ��ɽ�δ֤϶��Ԥ�2��ɬ��
		$msg = str_replace("|\n\n|","|\n\n\n|",$msg);
		//�ޤ��ϡ�ɽ��� -> �������
		$msg = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('->\n','\n','$2')).'$3'",$msg);
		//�Ȥꤢ����ɽ��β��ԤϤ��٤��ִ�
		$msg = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('\n','->\n','$2')).'$3'",$msg);
		//�����ƹԶ��ڤβս�ϡ�������
		$msg = preg_replace("/\|(c|h)?(->\n)+\|/e","str_replace('->','','$0')",$msg);		
		//ɽ��ɽ�δ֤϶���2�Ԥ�1�Ԥ��᤹
		$msg = str_replace("|\n\n\n|","|\n\n|",$msg);

		//�Ԥ�������Ѵ�����1����˽���
		$post_lines = array();
		$post_lines = split("\n",$msg);
		//�ơ��֥��б��Τ����ѹ��ͽ�����ʣ���ˤʤäƤ��ޤä���
		$msg = "";
		foreach($post_lines as $line){
			if (substr($line,0,1) != ' ') {
				//���礨����������~����
				$line = preg_replace("/~(->)?$/","\\1",$line);
				if (preg_match("/^(.*\|(\{)?(LEFT|CENTER|RIGHT)?(:)?(TOP|MIDDLE|BOTTOM)?)(.*)$/",$line,$reg)){
					//$line = $reg[1] . preg_replace("/(^[^ #\-+*hc].*?)(->)?$/","$1~$2",$reg[6]);
					$line = $reg[1] . preg_replace("/(^[^ #*hc].*?)(->)?$/","$1~$2",$reg[6]);
				} else {
					//$line = preg_replace("/(^[^ #\-+*].*?)(->)?$/","$1~$2",$line);
					$line = preg_replace("/(^[^ #*].*?)(->)?$/","$1~$2",$line);
				}
			}
			$msg .= $line."\n";
		}
		$msg = rtrim($msg);

		//;ʬ��~����
		$msg = preg_replace("/~(->)?\n((?:[#\-+*|:>\n]|\/\/))/","\\1\n\\2",$msg);
		$msg = preg_replace("/((?:^|\n)[^ ][^\n~]*)~((:?->)?\n )/","\\1\\2",$msg);
		$msg = preg_replace("/((?:^|\n)(?:----|\/\/)(?:.*)?)~((:?->)?\n)/","\\1\\2",$msg);
		$msg = preg_replace("/~$/","",$msg);

		//����������ʸ�����ִ�
		$msg = str_replace("{|}",chr(29).chr(28),$msg);
		$msg = str_replace("|}",chr(28),$msg);
		$msg = str_replace("{|",chr(29),$msg);

		//�������ϲ��Ԥˤ��٤�->���ղä��롣
		$msg = preg_replace("/\x1c[^\x1d]*(\x1c[^\x1c\x1d]*?\x1d)?[^\x1c]*\x1d/e","str_replace('->\n','\n','$0')",$msg);//ok
		$msg = preg_replace("/\x1c[^\x1d]*(\x1c[^\x1c\x1d]*?\x1d)?[^\x1c]*\x1d/e","str_replace('\n','->\n','$0')",$msg);//ok
		//���������ʸ�����鸵��ʸ�����᤹��
		$msg = str_replace(chr(29).chr(28),"{|}",$msg);
		$msg = str_replace(chr(28),"|}",$msg);
		$msg = str_replace(chr(29),"{|",$msg);
		
		return $msg;
}
// �����ȥ֥饱�å� by nao-pon
function auto_braket($msg,$tgt_name){
	$WikiName_ORG = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	$http_URL_regex = '(s?https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)';
	$mail_ADR_regex = "((mailto:)?([0-9A-Za-z._-]+@[0-9A-Za-z.-]+))";
	
	//InterWikiName�� [ ] �ǰϤ�Τǥ���������
	if ($tgt_name == "InterWikiName"){
		$msg = preg_replace("/(\[[^[\]]+\])/","[[$1]]",$msg);
	} else {
		// InterWikiName�ڡ����ʳ�
		// URL E-mail �ϥ���������
		$msg = ereg_replace($http_URL_regex,"[[\\1]]",$msg);
		$msg = eregi_replace($mail_ADR_regex, "[[\\1]]", $msg);
	}
	// #�ǻϤޤ�ץ饰����ԡ�//�ǻϤޤ��å������Ԥ�������ʤ��٤�������
	//$msg=preg_replace("/(^|\n)( |#|\/\/)(.*)(\n|$)/","\\1\\2[[\\3]]\\4",$msg);
	$msg=preg_replace("/(^|\n)( |#|\/\/)(.*)/","\\1\\2[[\\3]]",$msg);
	// ����饤��ץ饰���� ����������
	$msg = preg_replace("/(&.*?;)/","[[$1]]",$msg);
	
	//����������ʸ�����ִ�
	$msg = str_replace("[[",chr(28),$msg);
	$msg = str_replace("]]",chr(29),$msg);

	//�ܽ���
	foreach (get_page_name() as $page_name){
		if(!preg_match("/^".$WikiName_ORG."$/",$page_name)){ //WikiName�ʳ�
			//$page_name =  preg_quote ($page_name, "/");
			$page_name = addslashes($page_name);
			$msg = preg_replace("/(^|\x1d)([^\x1c\x1d]*?)(\x1c|$)/e","auto_br_replace('$1','$2','$3','$page_name')","$msg");
		}
	}

	//���������ʸ�������ִ�
	$msg = str_replace(chr(28),"[[",$msg);
	$msg = str_replace(chr(29),"]]",$msg);

	// ����饤��ץ饰���� ���󥨥�������
	$msg = preg_replace("/\[\[(&.*?;)\]\]/","$1",$msg);
	// #�ǻϤޤ�ץ饰����ԡ�//�ǻϤޤ��å������Ԥ�������ʤ��٤θ����
	//$msg = preg_replace("/(^|[\n])( |#|\/\/)\[\[(.*)\]\](\n|$)/","\\1\\2\\3\\4",$msg);
	$msg = preg_replace("/(^|[\n])( |#|\/\/)\[\[(.*)\]\]/","\\1\\2\\3",$msg);
	// ���������פ򸵤��᤹
	if ($tgt_name == "InterWikiName"){
		$msg = preg_replace("/\[\[(\[[^[\]]+\])\]\]/","$1",$msg);
	} else {
		// InterWikiName�ڡ����ʳ�
		$msg = ereg_replace("\[\[".$http_URL_regex."\]\]","\\1",$msg);
		$msg = eregi_replace("\[\[".$mail_ADR_regex."\]\]", "\\1", $msg);
	}
	
	return $msg;
}
// �����ȥ֥饱�å��ѥ��ִؿ�
function auto_br_replace($front,$tgt,$back,$page_name){
	$front = stripslashes($front);
	$tgt = stripslashes($tgt);
	$back = stripslashes($back);
	$page_name = stripslashes($page_name);
	return $front.str_replace($page_name,chr(28).$page_name.chr(29),$tgt).$back;
}
//////////////////////////////////////////////////////
//
// XOOPS�ѡ��ؿ�
//
//////////////////////////////////////////////////////

// �桼��������°���륰�롼��ID������
function X_get_groups(){
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $X_uid,$xoopsDB;
		$X_M = new XoopsMemberHandler($xoopsDB);
		return $X_M->getGroupsByUser($X_uid);
	} else {
		// XOOPS 1
		global $xoopsUser;
		return XoopsGroup::getByUser($xoopsUser);
	}
}
// ���롼�װ���������
function X_get_group_list(){
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $xoopsDB;
		$X_M = new XoopsMemberHandler($xoopsDB);
		return $X_M->getGroupList();
	} else {
		// XOOPS 1
		return XoopsGroup::getAllGroupsList();
	}
}

// ��Ͽ�桼��������������
function X_get_users(){
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $xoopsDB;
		$X_M = new XoopsMemberHandler($xoopsDB);
		return $X_M->getUserList();
	} else {
		// XOOPS 1
		return XoopsUser::getAllUsersList();
	}
}

?>
