<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: func.php,v 1.30 2004/09/12 14:05:29 nao-pon Exp $
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
	//$keys = get_search_words(preg_split('/\s+/',$word,-1,PREG_SPLIT_NO_EMPTY));
	$keys = preg_split('/\s+/',$word,-1,PREG_SPLIT_NO_EMPTY);

	
	include_once("./xoops_search.inc.php");
	$pages = wiki_search($keys, $type);

	//$non_format=TRUE�ǥڡ���̾(�֥饱�åȤʤ�)��������֤�
	if ($non_format)
	{
		$retval = array();
		if (count($pages))
		{
			foreach ($pages as $page)
			{
				$retval[] = $page['page'];
			}
		}
		return $retval;
	}

	$_pages = get_existpages();

	$r_word = rawurlencode($word);
	$s_word = htmlspecialchars($word);
	if (count($pages) == 0)
	{
		return str_replace('$1',$s_word,$_msg_notfoundresult);
	}

	$retval = "<ul>\n";
	foreach ($pages as $page)
	{
		$page2 = $page['page'];
		$r_page = rawurlencode($page2);
		$s_page = htmlspecialchars($page2);
		$passage = get_pg_passage(add_bracket($page2));
		$retval .= " <li><a href=\"$script?cmd=read&amp;page=$r_page&amp;word=$r_word\">$s_page</a>$passage";
		$retval .= str_replace($page['page'],"",$page['title'])."</li>\n";
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

// �ڡ��������κ���
function page_list($pages, $cmd = 'read', $withfilename=FALSE, $prefix="")
{
	global $script,$list_index,$top;
	global $_msg_symbol,$_msg_other;
	global $pagereading_enable;
	
	// �����ȥ�������ꤹ�롣 ' ' < '[a-zA-Z]' < 'zz'�Ȥ�������
	$symbol = ' ';
	$other = 'zz';
	
	$retval = '';
	if ($prefix){
		$retval .= "<p>[ <a href='$script?plugin=list&prefix=".rawurlencode(preg_replace("/\/?[^\/]+$/","",$prefix))."'>../</a> ] ";
		$retval .= make_pagelink($prefix)."</p>";
	}

	if($pagereading_enable) {
		$readings = get_readings($pages);
	}

	$list = array();
	foreach($pages as $file=>$page)
	{
		$passage = get_pg_passage($page);
		$_page = $page = strip_bracket($page);
		//�ڡ���̾���ֿ�����-�פ����ξ��ϡ�*(**)�Ԥ�������Ƥߤ�
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$page,$f_page))
			$_page = $f_page[1].get_heading($page);
		
		$alias = ($prefix)? substr($_page,strlen($prefix)+1):"";
		
		$chiled = get_child_counts($page);
		$chiled = ($chiled)? " [<a href='$script?plugin=list&prefix=".rawurlencode(strip_bracket($page))."'>+$chiled</a>]":"";
		if (!$alias)
			$str = "   <li>".make_pagelink($page)."$passage".$chiled;
		else
			$str = "   <li>".make_pagelink($page,$alias)."$passage".$chiled;
		
		if ($withfilename)
		{
			$s_file = htmlspecialchars($file);
			$str .= "\n    <ul><li>$s_file</li></ul>\n   ";
		}
		else
		{
			//$page_info = get_pg_info_db($page);
			//$str .= "\n<br />[ ".$page_info['title']." ]\n";
		}
		$str .= "</li>";
		
		if($pagereading_enable) {
			$reading = $readings[$page];
			if ($alias)
			{
				$c_count =count_chars($reading);
				$reading = ltrim_pagename($reading,$c_count[47]);
			}
			//if(mb_ereg('^([A-Za-z��-��])',$readings[$page],$matches)) {
			if(mb_ereg('^([A-Za-z��-��])',$reading,$matches)) {
				$head = $matches[1];
			}
			elseif (mb_ereg('^[ -~]|[^��-��-��]',$page)) {
			//elseif (mb_ereg('^[ -~]|[^��-��-��]',$alias)) {
				$head = $symbol;
			}
			else {
				$head = $other;
			}
		}
		else {
			$head = (preg_match('/^([A-Za-z])/',$page,$matches)) ? $matches[1] :
				(preg_match('/^([ -~])/',$page,$matches) ? $symbol : $other);
		}
		
		$list[$head][$page] = $str;
	}
	ksort($list);
	
	$cnt = 0;
	$arr_index = array();
	//$retval .= "<ul>\n";
	foreach ($list as $head=>$pages)
	{
		if ($head === $symbol)
		{
			$head = $_msg_symbol;
		}
		else if ($head === $other)
		{
			$head = $_msg_other;
		}
		
		if ($list_index)
		{
			$cnt++;
			$arr_index[] = "<a id=\"top_$cnt\" href=\"#head_$cnt\"><strong>$head</strong></a>";
			$retval .= " <div class=\"page_list_word\"><a id=\"head_$cnt\" href=\"#top_$cnt\"><strong class=\"page_list_word\">$head</strong></a>\n </div>\n";
		}
		ksort($pages);
		$retval .= " <ul>\n";
		$retval .= join("\n",$pages);
		$retval .= "\n </ul>\n";
//		if ($list_index)
//		{
//			$retval .= "\n  </ul>\n </li>\n";
//		}
	}
	//$retval .= "</ul>\n";
	if ($list_index and $cnt > 0)
	{
		$top = array();
		while (count($arr_index) > 0)
		{
			$top[] = join(" | \n",array_splice($arr_index,0,16))."\n";
		}
		$retval = "<div id=\"top\" style=\"text-align:center\">\n".
			join("<br />",$top)."</div>\n".$retval;
	}
	return $retval;
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

2003-05-16: magic quotes gpc����������������
2003-05-21: Ϣ������Υ�����binary safe
*/
function input_filter($param)
{
	static $magic_quotes_gpc = NULL;
	if ($magic_quotes_gpc === NULL)
	    $magic_quotes_gpc = get_magic_quotes_gpc();

	if (is_array($param)) {
		return array_map('input_filter', $param);
	} else {
		$result = str_replace("\0", '', $param);
		if ($magic_quotes_gpc) $result = stripslashes($result);
		return $result;
	}
}

// Compat for 3rd party plugins. Remove this later
function sanitize_null_character($param) {
	return input_filter($param);
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
//�����Ѥߥ֥�å��� | �� &#x7c; ���ִ�
function rep_for_pre($msg)
{
	$lines = split("\n",$msg);
	$msg = "";
	$_pre = 0;
	foreach($lines as $line)
	{
		if (preg_match("/(^|\|)<<<($|->)/",$line)) $_pre ++;
		if ($_pre)
		{
			if (preg_match("/^>>>($|\||->)/",$line)) $_pre --;
			if ($_pre) $line = str_replace("|","&#x7c;",$line);
		}
		else
			if (preg_match("/^>>>($|\||->)/",$line)) $_pre --;
		$msg .= $line."\n";
	}
	return $msg;
}

//�����Ѥߥ֥�å��ι�Ƭ�˥��ڡ������ɲ�
function inssp_for_pre($msg)
{
	$lines = split("\n",$msg);
	$msg = "";
	$_pre = 0;
	foreach($lines as $line)
	{
		if (preg_match("/(^|\|)<<<($|->)/",$line)) $_pre ++;
		if ($_pre)
		{
			if (preg_match("/^>>>($|\||->)/",$line)) $_pre --;
			if ($_pre && !preg_match("/(^|\|)(<<<|>>>)($|->)/",$line)) $line = " " . $line;
		}
		else
			if (preg_match("/^>>>($|\||->)/",$line)) $_pre --;
		$msg .= $line."\n";
	}
	return $msg;
}

//�����Ѥߥ֥�å��ι�Ƭ��1ʸ������
function delsp_for_pre($msg)
{
	$lines = split("\n",$msg);
	$msg = "";
	$_pre = 0;
	foreach($lines as $line)
	{
		if (preg_match("/(^|\|)<<<($|->)/",$line)) $_pre ++;
		if ($_pre)
		{
			if (preg_match("/^>>>($|\||->)/",$line)) $_pre --;
			if ($_pre && !preg_match("/(^|\|)(<<<|>>>)($|->)/",$line)) $line = substr($line, 1);
		}
		else
			if (preg_match("/^>>>($|\||->)/",$line)) $_pre --;
		$msg .= $line."\n";
	}
	return $msg;
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
		$_pre = 0;
		foreach($post_lines as $line)
		{
			if (preg_match("/(^|\|)<<<($|->)/",$line)) $_pre ++;
			if (substr($line,0,1) != ' ' && !$_pre)
			{
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
			if (preg_match("/^>>>($|\|)/",$line)) $_pre --;
		}
		$msg = rtrim($msg);

		//;ʬ��~����
		$msg = preg_replace("/~(->)?\n((?:[#\-+*|:>\n]|\/\/|<<<))/","\\1\n\\2",$msg);
		$msg = preg_replace("/((?:^|\n)[^ ][^\n~]*)~((:?->)?\n )/","\\1\\2",$msg);
		$msg = preg_replace("/((?:^|\n)(?:----|\/\/)(?:.*)?)~((:?->)?\n)/","\\1\\2",$msg);
		$msg = preg_replace("/(^|\n)->~(\n|$)/","\\1->\\2",$msg);
		//$msg = str_replace("<<<~->\n","<<<->\n",$msg);
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
	
	$msg = inssp_for_pre($msg);
	
	// \�򥨥�������
	$msg = str_replace('\\','_yEn_',$msg);
	
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
	// //�ǻϤޤ��å������ԡ������Ѥ߹Ԥ�������ʤ��٤�������
	$msg = preg_replace("/(^|\n)( |\/\/)(.*)/","\\1\\2[[\\3]]",$msg);
	// �ץ饰���� ����������
	//$msg = preg_replace("/(^|\n|\|[^#\n|]*)#\w+(\([^\n|]*\))?/","[[$0]]",$msg);
	$msg = preg_replace("/(^|\n|\|[^#\n|]*)(#\w+(\([^\n|]*\))?)/","$1[[$2]]",$msg);
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
			//$msg = preg_replace("/(^|\x1d)([^\x1c\x1d]*?)(\x1c|$)/e","'$1'.str_replace($page_name,chr(28).$page_name.chr(29),'$2').'$3'","$msg");
		}
	}

	//���������ʸ�������ִ�
	$msg = str_replace(chr(28),"[[",$msg);
	$msg = str_replace(chr(29),"]]",$msg);

	// ����饤��ץ饰���� ���󥨥�������
	$msg = preg_replace("/\[\[(&.*?;)\]\]/","$1",$msg);
	// �ץ饰���� ���󥨥�������
	//$msg = preg_replace("/\[\[((^|\n|\|[^#\n|]*)#\w+(\([^\n|]*\))?)\]\]/","$1",$msg);
	$msg = preg_replace("/(^|\n|\|[^#\n|]*)\[\[(#\w+(\([^\n|]*\))?)\]\]/","$1$2",$msg);
	// //�ǻϤޤ��å������ԡ������Ѥ߹Ԥ�������ʤ��٤θ����
	$msg = preg_replace("/(^|[\n])( |\/\/)\[\[(.*)\]\]/","\\1\\2\\3",$msg);
	// ���������פ򸵤��᤹
	if ($tgt_name == "InterWikiName"){
		$msg = preg_replace("/\[\[(\[[^[\]]+\])\]\]/","$1",$msg);
	} else {
		// InterWikiName�ڡ����ʳ�
		$msg = ereg_replace("\[\[".$http_URL_regex."\]\]","\\1",$msg);
		$msg = eregi_replace("\[\[".$mail_ADR_regex."\]\]", "\\1", $msg);
	}
	// \�򥢥󥨥�������
	$msg = str_replace('_yEn_','\\',$msg);
	
	$msg = delsp_for_pre($msg);
	
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

// �ơ��֥륻��Υե����ޡ��Ȼ���Ҥ�������
function cell_format_tag_del ($td) {
	// ���顼�͡��������ɽ��
	$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";
	// ʸ����������
	$td = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
	// �طʿ�������
	$td = preg_replace("/(SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","SC:$2",$td);
	$td = preg_replace("/(SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
	// �طʲ������
	$td = preg_replace("/(SC|BC):\(([^),]*)(,once|,1)?\)/i","",$td);
	// ʸ��·��������
	if (preg_match("/^(LEFT|CENTER|RIGHT)?(:)(TOP|MIDDLE|BOTTOM)?/i",$td,$tmp)) {
		$td = (!$tmp[1] && !$tmp[3])? $tmp[2] : "";
	}
	return $td;
}

// �桼�����ڡ����ؤΥ�󥯤��������
function make_user_link (&$name)
{
	if (!WIKI_USER_DIR) 
		$name = "[[$name]]";
	else
	{
		$name = sprintf(WIKI_USER_DIR,str_replace(" ","",strip_tags(make_link($name))),$name);
		$name = add_bracket(strip_bracket($name));
	}
	return;
}

// �ڡ���̾����������դ��֤�(����������)
function get_makedate_byname($page,$sep="-")
{
	$page = strip_bracket($page);
	$info = get_pg_info_db($page);
	preg_match("/.*\/([0-9]+)$sep([0-9]+)$sep([0-9]+)/",$page,$page_date);
	$make_date[1] = date("Y",$info['buildtime']);
	$make_date[2] = date("m",$info['buildtime']);
	$make_date[3] = date("d",$info['buildtime']);
	$make_date[4] = date("g:i a",$info['buildtime']);
	if ($make_date[1] != $page_date[1])
		$ret = $make_date[1]."/".$make_date[2]."/".$make_date[3]." ".$make_date[4];
	elseif ($make_date[2] != $page_date[2])
		$ret = $make_date[2]."/".$make_date[3]." ".$make_date[4];
	elseif ($make_date[3] != $page_date[3])
		$ret = $make_date[3]." ".$make_date[4];
	else
		$ret = $make_date[4];
	return $ret;
}

// PukiWiki 1.4 �ߴ���
//<input type="(submit|button|image)"...>�򱣤�
function drop_submit($str)
{
	return preg_replace(
		'/<input([^>]+)type="(submit|button|image)"/i',
		'<input$1type="hidden"',
		$str
	);
}

// AutoLink�Υѥ��������������
// thx for hirofummy
function get_autolink_pattern(& $pages)
{
	global $WikiName, $autolink, $nowikiname;

	$config = &new Config('AutoLink');
	$config->read();
	$ignorepages = $config->get('IgnoreList');
	$forceignorepages = $config->get('ForceIgnoreList');
	unset($config);
	$auto_pages = array_merge($ignorepages, $forceignorepages);

	foreach ($pages as $page) {
		if (preg_match("/^$WikiName$/", $page) ?
		    $nowikiname : strlen($page) >= $autolink)
			$auto_pages[] = strip_bracket($page);
	}

	if (count($auto_pages) == 0) {
		return $nowikiname ? '(?!)' : $WikiName;
	}

	$auto_pages = array_unique($auto_pages);
	sort($auto_pages, SORT_STRING);

	$auto_pages_a = array_values(preg_grep('/^[A-Z]+$/i', $auto_pages));
	$auto_pages   = array_values(array_diff($auto_pages, $auto_pages_a));

	$result   = get_autolink_pattern_sub($auto_pages,   0, count($auto_pages),   0);
	$result_a = get_autolink_pattern_sub($auto_pages_a, 0, count($auto_pages_a), 0);

	return array($result, $result_a, $forceignorepages);
}

function get_autolink_pattern_sub(& $pages, $start, $end, $pos)
{
	if ($end == 0) return '(?!)';

	$result = '';
	$count = 0;
	$x = (mb_strlen($pages[$start]) <= $pos);

	if ($x) {
		++$start;
	}

	for ($i = $start; $i < $end; $i = $j) // What is the initial state of $j?
	{
		$char = mb_substr($pages[$i], $pos, 1);
		for ($j = $i; $j < $end; $j++) {
			if (mb_substr($pages[$j], $pos, 1) != $char)
				break;
		}
		if ($i != $start) {
			$result .= '|';
		}
		if ($i >= ($j - 1)) {
			$result .= str_replace(' ', '\\ ', preg_quote(mb_substr($pages[$i], $pos), '/'));
		} else {
			$result .= str_replace(' ', '\\ ', preg_quote($char, '/')) .
				get_autolink_pattern_sub($pages, $i, $j, $pos + 1);
		}
		++$count;
	}
	if ($x or $count > 1) {
		$result = '(?:' . $result . ')';
	}
	if ($x) {
		$result .= '?';
	}
	return $result;
}

// �ڡ���̾����곬�ؿ��ڤ�ͤ��
function ltrim_pagename($page,$num)
{
	//$c_count =count_chars($page);
	while ($num)
	{
		if (strpos($page,"/") !== false)
		{
			$page = substr($page,strpos($page,"/")+1);
			$num --;
		}
		else
			break;
	}
	return $page;
}

// ����ڡ����򥤥󥯥롼�ɤ���
function include_page($page)
{
	global $vars,$post,$get,$comment_no,$h_excerpt,$digest;
	global $_msg_read_more;
	
	//�ѿ�������
	$tmppage = $vars["page"];
	$_comment_no = $comment_no;
	$_h_excerpt = $h_excerpt;
	$_digest = $digest;
	
	//comment_no �����
	$comment_no = 0;
	
	//���ڡ���̾�񤭴���
	$vars["page"] = $post["page"] = $get["page"] = add_bracket($page);
	
	$body = join("",get_source($page));
	if (preg_match("/\n#more(\([^)]*\))?\n/",$body))
	{
		$body = preg_replace("/\n#more\(\s*off\s*\).*?(\n#more\(\s*on\s*\)\n|$)/s","\n",$body);
		if (preg_match("/(.*?)\n#more(\([^)]*\))?\n/s",$body,$match))
			$body = $match[1];
		$body .= "\n\nRIGHT:[[$_msg_read_more>$page]]";
		$body = convert_html($body);
	}
	else
	{
		$body = convert_html($page,false,true);
	}
	
	//�����ѿ����ᤷ
	$vars["page"] = $post["page"] = $get["page"] = $tmppage;
	$comment_no = $_comment_no;
	$h_excerpt = $_h_excerpt;
	$digest = $_digest;
	
	return $body;
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
function X_get_group_list()
{
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
function X_get_users($sort=true)
{
	$sort = $sort? 1 : 0;
	static $users = array();
	if (isset($users[$sort])) return $users[$sort];
	
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $xoopsDB;
		$X_M = new XoopsMemberHandler($xoopsDB);
		$ret = $X_M->getUserList();
	} else {
		// XOOPS 1
		$ret =  XoopsUser::getAllUsersList();
	}
	if ($sort) asort($ret);
	$users[$sort] = $ret;
	return $ret;
}

?>
