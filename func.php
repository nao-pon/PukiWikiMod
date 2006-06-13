<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: func.php,v 1.78 2006/06/13 13:39:19 nao-pon Exp $
/////////////////////////////////////////////////
if (!defined("PLUGIN_INCLUDE_MAX")) define("PLUGIN_INCLUDE_MAX",4);

// class HypCommonFunc
if(!class_exists('HypCommonFunc')){include("./include/hyp_common/hyp_common_func.php");}

// 文字列がページ名かどうか
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

// 検索語を展開する
function get_search_words($words,$special=FALSE)
{
	$retval = array();
	// Perlメモ - 正しくパターンマッチさせる
	// http://www.din.or.jp/~ohzaki/perl.htm#JP_Match
	$eucpre = $eucpost = '';
	if (SOURCE_ENCODING == 'EUC-JP')
	{
		$eucpre = '(?<!\x8F)';
		// # JIS X 0208 が 0文字以上続いて # ASCII, SS2, SS3 または終端
		$eucpost = '(?=(?:[\xA1-\xFE][\xA1-\xFE])*(?:[\x00-\x7F\x8E\x8F]|\z))';
	}
	// $special : htmlspecialchars()を通すか
	$quote_func = create_function('$str',$special ?
		'return preg_quote($str,"/");' :
		'return preg_quote(htmlspecialchars($str),"/");'
	);
	// LANG=='ja'で、mb_convert_kanaが使える場合はmb_convert_kanaを使用
	$convert_kana = create_function('$str,$option',
		(LANG == 'ja' and function_exists('mb_convert_kana')) ?
			'return mb_convert_kana($str,$option);' : 'return $str;'
	);
	
	foreach ($words as $word)
	{
		// 英数字は半角,カタカナは全角,ひらがなはカタカナに
		$word_zk = $convert_kana($word,'aKCV');
		$chars = array();
		for ($pos = 0; $pos < mb_strlen($word_zk);$pos++)
		{
			$char = mb_substr($word_zk,$pos,1);
			$arr = array($quote_func($char));
			if (strlen($char) == 1) // 英数字
			{
				$_char = strtoupper($char); // 大文字
				$arr[] = $quote_func($_char);
				$arr[] = $quote_func($convert_kana($_char,"A")); // 全角
				$_char = strtolower($char); // 小文字
				$arr[] = $quote_func($_char);
				$arr[] = $quote_func($convert_kana($_char,"A")); // 全角
			}
			else // マルチバイト文字
			{
				$arr[] = $quote_func($convert_kana($char,"c")); // ひらがな
				$arr[] = $quote_func($convert_kana($char,"k")); // 半角カタカナ
			}
			$chars[] = '(?:'.join('|',array_unique($arr)).')';
		}
		$retval[$word] = $eucpre.join('',$chars).$eucpost;
	}
	return $retval;
}
// 検索 1.4
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
	$search_func = "wiki".PUKIWIKI_DIR_NUM."_search";
	$pages = $search_func($keys, $type);

	//$non_format=TRUEでページ名(ブラケットなし)を配列で返す
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

// プログラムへの引数のチェック
function arg_check($str, $method="vars")
{
	global $get,$post,$vars;

	return preg_match("/^".$str."/",${$method}["cmd"]);
}

// ページリストのソート
function list_sort($values)
{
	if(!is_array($values)) return array();
	
	// ksortのみだと、[[日本語]]、[[英文字]]、英文字のみ、に順に並べ替えられる
	ksort($values);

	$vals1 = array();
	$vals2 = array();
	$vals3 = array();

	// 英文字のみ、[[英文字]]、[[日本語]]、の順に並べ替える
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

// ページ名のエンコード
function encode($key)
{
	return ($key == '') ? '' : strtoupper(bin2hex($key));
	// Equal to strtoupper(join('', unpack('H*0', $key)));
	// But PHP 4.3.10 says 'Warning: unpack(): Type H: outside of string in ...'
}

// ファイル名のデコード
function decode($key)
{
	// preg_match()       = Warning: pack(): Type H: illegal hex digit ...
	// (string)   : Always treat as string (not int etc).
	return preg_match('/^[0-9a-f]+$/i', $key) ?
		pack('H*', (string)$key) : $key;
}

// InterWikiName List の解釈(返値:２次元配列)
function open_interwikiname_list()
{
	global $interwiki;
	
	$retval = array();
	$aryinterwikiname = get_source($interwiki);

	$cnt = 0;
	foreach($aryinterwikiname as $line)
	{
		$match = array();
		if(preg_match("/\[(((?:https?|ftp|news):\/\/|\.\.?\/)([[:alnum:]\+\$\;\?\.%,!#~\*\/\:@&=_\-]+))\s([^\]]+)\]\s?([^\s]*)/",$line,$match))
		{
			$retval[$match[4]]["url"] = $match[1];
			$retval[$match[4]]["opt"] = $match[5];
		}
	}

	return $retval;
}

// [[ ]] を取り除く
function strip_bracket($str)
{
	global $strip_link_wall;
	static $ret = array();
	
	if ($str === "") return "";
	
	if (isset($ret[$str])) return $ret[$str];
	
	$ret[$str] = $str;
	if($strip_link_wall)
	{
		$match = array();
		if(preg_match("/^\[\[(.*)\]\]$/",$str,$match)) {
			$ret[$str] = $match[1];
		}
	}
	return $ret[$str];
}

// [[ ]] を付加する
function add_bracket($str)
{
	global $WikiName;
	static $ret = array();
	
	if ($str === "") return "";
	
	if (isset($ret[$str])) return $ret[$str];
	
	$ret[$str] = $str;
	if (!preg_match("/^".$WikiName."$/",$str)){
		if (!preg_match("/\[\[.*\]\]/",$str)) $ret[$str] = "[[".$str."]]";
	}
	
	return $ret[$str];
}

// ページ一覧の作成
function page_list($pages, $cmd = 'read', $withfilename=FALSE, $prefix="", $lword="")
{
	global $script,$list_index,$top;
	global $_msg_symbol,$_msg_other;
	global $pagereading_enable;
	
	// ソートキーを決定する。 ' ' < '[a-zA-Z]' < 'zz'という前提。
	$symbol = ' ';
	$other = 'zz';
	
	// ページ数のカウント
	$page_cnt = count($pages);
	
	$single_mode = 0;
	if ($page_cnt > 500)
	{
		// ページ数が多い場合(500ページ超)は各語モード
		$single_mode = 1;
		if (!$lword) $lword = " "; // 記号
	}
	
	$retval = '';
	if ($prefix){
		$retval .= "<p>[ <a href='$script?plugin=list&prefix=".rawurlencode(preg_replace("/\/?[^\/]+$/","",$prefix))."'>../</a> ] ";
		$retval .= make_pagelink($prefix)."</p>";
	}

	if($pagereading_enable) {
		$readings = get_readings($pages);
	}

	$list = $matches = array();
	foreach($pages as $file=>$page)
	{
		$page = strip_bracket($page);
		
		// WARNING: Japanese code hard-wired
		if($pagereading_enable)
		{
			$reading = (empty($readings[$page]))? mb_convert_kana($page, 'a') : $readings[$page];
			if ($prefix)
			{
				$c_count =count_chars($reading);
				$reading = ltrim_pagename($reading,$c_count[47]);
			}
			if(mb_ereg('^([0-9A-Za-zァ-ヶ])',$reading,$matches)) {
				$head = $matches[1];
			}
			elseif (mb_ereg('^[ -~]|[^ぁ-ん亜-熙]',$page)) {
				$head = $symbol;
			}
			else {
				$head = $other;
			}
		}
		else
		{
			$head = (preg_match('/^([0-9A-Za-z])/', $page, $matches)) ? $matches[1] :
				(preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);
		}
		
		if (!$single_mode || $lword == $head)
		{
			$passage = get_pg_passage($page);
			
			if ($cmd == 'read')
			{
				$chiled = get_child_counts($page);
				$chiled = ($chiled)? " [<a href='$script?plugin=list&prefix=".rawurlencode(strip_bracket($page))."'>+$chiled</a>]":"";
				$str = "   <li>".make_pagelink($page,"#compact#")."$passage".$chiled;
			}
			else
			{
				$r_page = rawurlencode($page);
				$s_page = htmlspecialchars($page, ENT_QUOTES);
				$str = "   <li><a href=\"$script?cmd=$cmd&amp;page=$r_page\">$s_page</a>$passage";
			}
			
			if ($withfilename)
			{
				$s_file = htmlspecialchars($file);
				$str .= "\n    <ul><li>$s_file</li></ul>\n   ";
			}
			
			$str .= "</li>";
		}
		else
		{
			$str = htmlspecialchars($page) . ", ";
		}
		
		$list[$head][$page] = $str;
	}
	ksort($list);
	
	$cnt = 0;
	$arr_index = array();
	//$retval .= "<ul>\n";
	
	$add_link = "";
	if ($single_mode)
	{
		$add_link = $script."?cmd=".(($cmd == 'read')? "list" : $cmd);
		if ($prefix) $add_link .= "&amp;prefix=".$prefix;
		$add_link .= "&amp;lw=";
	}

	foreach ($list as $head=>$pages)
	{
		$_head = $head;
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
			$e_head = encode($_head);
			$_link = "";
			if ($single_mode && $lword != $_head) $_link = $add_link.rawurlencode($_head);
			$arr_index[] = "<a id=\"top_$e_head\" href=\"$_link#head_$e_head\"><strong>$head</strong></a>";
			$retval .= " <div class=\"page_list_word\"><a id=\"head_$e_head\" href=\"$_link#".(($_link)? "head" : "top")."_$e_head\"><strong class=\"page_list_word\">$head</strong></a>\n </div>\n";
		}
		ksort($pages);
		$retval .= " <ul>\n";
		$retval .= join("\n",$pages);
		$retval .= "\n </ul>\n";
	}
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

// テキスト整形ルールを表示する
function catrule()
{
	global $rule_body;
	return $rule_body;
}

// エラーメッセージを表示する
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

// 現在時刻をマイクロ秒で取得
if (!function_exists('getmicrotime'))
{
function getmicrotime()
{
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$sec + (float)$usec);
}
}

// 日時を得る
function get_date($format,$timestamp = NULL)
{
	$time = ($timestamp === NULL) ? UTIME : $timestamp;
	$time += ZONETIME;
	
	$format = preg_replace('/(?<!\\\)T/',preg_replace('/(.)/','\\\$1',ZONE),$format);
	
	return date($format,$time);
}

// 日時文字列を作る
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

// 経過時刻文字列を作る
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

// 差分の作成
function do_diff($strlines1,$strlines2)
{
	//改行コード統一
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


// 差分の作成
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
変数内のnull(\0)バイトを削除する
PHPはfopen("hoge.php\0.txt")で"hoge.php"を開いてしまうなどの問題あり

http://ns1.php.gr.jp/pipermail/php-users/2003-January/012742.html
[PHP-users 12736] null byte attack

2003-05-16: magic quotes gpcの復元処理を統合
2003-05-21: 連想配列のキーはbinary safe
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

// URLかどうか
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
		<p>ブラウザで JavaScript が利用できません。</p>
		<hr />
		<p>指定のページへジャンプするには、以下のリンクをクリックしてください。<br />
		<a href=\"$location\">$location</a></p>
		<p>[ <a href=\"$script\">PukiWiki トップへ戻る</a> ]</p>
		<hr />
		";
}
//整形済みブロックの | を &#x7c; に置換
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

//整形済みブロックの行頭にスペースを追加
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

//整形済みブロックの行頭の1文字を削除
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

//改行を有効にする by nao-pon
function auto_br($msg){
		//*表中改行指定文字列の挿入
		//表と表の間は空行が2行必要
		$msg = str_replace("|\n\n|","|\n\n\n|",$msg);
		//まずは、表内の -> を取り除く
		$msg = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('->\n','\n','$2')).'$3'",$msg);
		//とりあえず表内の改行はすべて置換
		$msg = preg_replace("/(^|\n)(\|[^\r]+?\|)(\n[^|]|$)/e","'$1'.stripslashes(str_replace('\n','->\n','$2')).'$3'",$msg);
		//そして行区切の箇所は、取り除く
		$msg = preg_replace("/\|(c|h)?(->\n)+\|/e","str_replace('->','','$0')",$msg);		
		//表と表の間は空行2行を1行に戻す
		$msg = str_replace("|\n\n\n|","|\n\n|",$msg);

		//行の配列に変換して1行毎に処理
		$post_lines = array();
		$post_lines = split("\n",$msg);
		//テーブル対応のため変更⇒処理が複雑になってしまった。
		$msg = "";
		$_pre = 0;
		foreach($post_lines as $line)
		{
			if (preg_match("/(^|\|)<<<($|->)/",$line)) $_pre ++;
			if (substr($line,0,1) != ' ' && !$_pre)
			{
				//取り合えず改行前の~を削除
				$line = preg_replace("/~(->)?$/","\\1",$line);
				$reg = array();
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

		//余分な~を削除
		$msg = preg_replace("/~(->)?\n((?:[#\-+*|:>\n]|\/\/|RIGHT:|CENTER:|LEFT:|<<<))/","\\1\n\\2",$msg);
		$msg = preg_replace("/((?:^|\n)[^ ][^\n~]*)~((:?->)?\n )/","\\1\\2",$msg);
		$msg = preg_replace("/((?:^|\n)(?:----|\/\/)(?:.*)?)~((:?->)?\n)/","\\1\\2",$msg);
		$msg = preg_replace("/(^|\n)->~(\n|$)/","\\1->\\2",$msg);
		//$msg = str_replace("<<<~->\n","<<<->\n",$msg);
		$msg = preg_replace("/~$/","",$msg);

		//前処理制御文字へ置換
		$msg = str_replace("{|}",chr(29).chr(28),$msg);
		$msg = str_replace("|}",chr(28),$msg);
		$msg = str_replace("{|",chr(29),$msg);

		//入れ子内は改行にすべて->を付加する。
		$msg = preg_replace("/\x1c[^\x1d]*(\x1c[^\x1c\x1d]*?\x1d)?[^\x1c]*\x1d/e","str_replace('->\n','\n','$0')",$msg);//ok
		$msg = preg_replace("/\x1c[^\x1d]*(\x1c[^\x1c\x1d]*?\x1d)?[^\x1c]*\x1d/e","str_replace('\n','->\n','$0')",$msg);//ok
		//後処理制御文字から元の文字へ戻す。
		$msg = str_replace(chr(29).chr(28),"{|}",$msg);
		$msg = str_replace(chr(28),"|}",$msg);
		$msg = str_replace(chr(29),"{|",$msg);
		
		return $msg;
}
// オートブラケット by nao-pon
function auto_braket($msg,$tgt_name){
	
	$msg = inssp_for_pre($msg);
	
	// \をエスケープ
	$msg = str_replace('\\','_yEn_',$msg);
	
	$WikiName_ORG = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
	$http_URL_regex = '(s?https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)';
	$mail_ADR_regex = "((mailto:)?([0-9A-Za-z._-]+@[0-9A-Za-z.-]+))";
	
	//InterWikiNameは [ ] で囲むのでエスケープ
	if ($tgt_name == "InterWikiName"){
		$msg = preg_replace("/(\[[^[\]]+\])/","[[$1]]",$msg);
	} else {
		// InterWikiNameページ以外
		// URL E-mail はエスケープ
		$msg = ereg_replace($http_URL_regex,"[[\\1]]",$msg);
		$msg = eregi_replace($mail_ADR_regex, "[[\\1]]", $msg);
	}
	// //で始まるメッセージ行、整形済み行を処理しない為の前処理
	$msg = preg_replace("/(^|\n)( |\/\/)(.*)/","\\1\\2[[\\3]]",$msg);
	// プラグイン エスケープ
	//$msg = preg_replace("/(^|\n|\|[^#\n|]*)#\w+(\([^\n|]*\))?/","[[$0]]",$msg);
	$msg = preg_replace("/(^|\n|\|[^#\n|]*)(#\w+(\([^\n|]*\))?)/","$1[[$2]]",$msg);
	// インラインプラグイン エスケープ
	$msg = preg_replace("/(&.*?;)/","[[$1]]",$msg);
	
	//前処理制御文字へ置換
	$msg = str_replace("[[",chr(28),$msg);
	$msg = str_replace("]]",chr(29),$msg);

	//本処理
	foreach (get_page_name() as $page_name){
		if(!preg_match("/^".$WikiName_ORG."$/",$page_name)){ //WikiName以外
			//$page_name =  preg_quote ($page_name, "/");
			$page_name = addslashes($page_name);
			$msg = preg_replace("/(^|\x1d)([^\x1c\x1d]*?)(\x1c|$)/e","auto_br_replace('$1','$2','$3','$page_name')","$msg");
			//$msg = preg_replace("/(^|\x1d)([^\x1c\x1d]*?)(\x1c|$)/e","'$1'.str_replace($page_name,chr(28).$page_name.chr(29),'$2').'$3'","$msg");
		}
	}

	//後処理制御文字から置換
	$msg = str_replace(chr(28),"[[",$msg);
	$msg = str_replace(chr(29),"]]",$msg);

	// インラインプラグイン アンエスケープ
	$msg = preg_replace("/\[\[(&.*?;)\]\]/","$1",$msg);
	// プラグイン アンエスケープ
	//$msg = preg_replace("/\[\[((^|\n|\|[^#\n|]*)#\w+(\([^\n|]*\))?)\]\]/","$1",$msg);
	$msg = preg_replace("/(^|\n|\|[^#\n|]*)\[\[(#\w+(\([^\n|]*\))?)\]\]/","$1$2",$msg);
	// //で始まるメッセージ行、整形済み行を処理しない為の後処理
	$msg = preg_replace("/(^|[\n])( |\/\/)\[\[(.*)\]\]/","\\1\\2\\3",$msg);
	// エスケープを元に戻す
	if ($tgt_name == "InterWikiName"){
		$msg = preg_replace("/\[\[(\[[^[\]]+\])\]\]/","$1",$msg);
	} else {
		// InterWikiNameページ以外
		$msg = ereg_replace("\[\[".$http_URL_regex."\]\]","\\1",$msg);
		$msg = eregi_replace("\[\[".$mail_ADR_regex."\]\]", "\\1", $msg);
	}
	// \をアンエスケープ
	$msg = str_replace('_yEn_','\\',$msg);
	
	$msg = delsp_for_pre($msg);
	
	return $msg;
}
// オートブラケット用サブ関数
function auto_br_replace($front,$tgt,$back,$page_name){
	$front = stripslashes($front);
	$tgt = stripslashes($tgt);
	$back = stripslashes($back);
	$page_name = stripslashes($page_name);
	return $front.str_replace($page_name,chr(28).$page_name.chr(29),$tgt).$back;
}

// テーブルセルのフォーマート指定子を削除する
function cell_format_tag_del ($td) {
	// カラーネームの正規表現
	$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";
	// 文字色指定削除
	$td = preg_replace("/FC:(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
	// 背景色指定削除
	$td = preg_replace("/(SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)(\(([^),]*)(,no|,one|,1)?\))/i","SC:$2",$td);
	$td = preg_replace("/(SC|BC):(#?[0-9abcdef]{6}?|$colors_reg|0)/i","",$td);
	// 背景画指定削除
	$td = preg_replace("/(SC|BC):\(([^),]*)(,once|,1)?\)/i","",$td);
	// 文字揃え指定削除
	$tmp = array();
	if (preg_match("/^(LEFT|CENTER|RIGHT)?(:)(TOP|MIDDLE|BOTTOM)?/i",$td,$tmp)) {
		$td = (!$tmp[1] && !$tmp[3])? $tmp[2] : "";
	}
	return $td;
}

// ユーザーページへのリンクを作成する
function make_user_link (&$name,$format="",$convert=false)
{
	global $X_uname,$no_name,$pwm_config;
	global $no_name;
	
	if (!$name) $name = $no_name;
	
	if (!$convert && $name != $no_name)
	{
		// 名前をクッキーに保存
		setcookie("pukiwiki_un", $name, time()+86400*365,str_replace("modules/".PUKIWIKI_DIR_NAME,"",XOOPS_WIKI_URL));//1年間
	}
	
	$trip = $b_name = "";
	$_name = $name;

	if (!$convert)
	{
		if ($pwm_config['use_trip'])
		{
			// Trip
			list($_name,$trip) = convert_trip($name);
			$trip = ($trip)? "&trip(\"$trip\");" : "";
		}
		
		if ($pwm_config['show_oldname'])
		{
			// 名前を変えた場合
			$c_name = preg_replace("/([^#]+).*/","$1",$name);
			$b_name = ($X_uname == $no_name)? $c_name : preg_replace("/([^#]+).*/","$1",$X_uname);
			$b_name = ($c_name == $b_name)? "" : "&font(80%){(&#171;{$b_name})};";
		}
	}
	
	// 実ページ名の取り出し
	$pure_name = strip_tags(make_link($_name));
	// ページ名に使用できない文字を置換
	$pure_name = preg_replace('/[\s\]#&<>":]+/',"_",$pure_name);
	
	if (!WIKI_USER_DIR)
	{
		if ($format)
		{
			$_name = sprintf($format, $pure_name, $_name);
		}
		else
		{
			$_name = "[[{$_name}>{$pure_name}]]";
		}
	}
	else
	{
		$_name = sprintf(WIKI_USER_DIR,$pure_name,$_name);
	}
	$name = add_bracket(strip_bracket($_name)).$trip.$b_name;
	return $name;
}

// ページ名から作成日付を返す(カレンダー用)
function get_makedate_byname($page,$sep="-")
{
	$page = strip_bracket($page);
	$info = get_pg_info_db($page);
	$page_date = array();
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

// PukiWiki 1.4 互換用
//<input type="(submit|button|image)"...>を隠す
function drop_submit($str)
{
	return preg_replace(
		'/<input([^>]+)type="(submit|button|image)"/i',
		'<input$1type="hidden"',
		$str
	);
}

// AutoLinkのパターンを生成する
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

	foreach ($pages as $page)
	{
		//if (preg_match("/^".$WikiName."$/", $page) ?
		//	$nowikiname : strlen($page) >= $autolink)
		if (strlen($page) >= $autolink)
			$auto_pages[] = $page;
	}

	if (count($auto_pages) == 0)
	{
		//$result = ($nowikiname)? '(?!)' : $WikiName;
		$result = '(?!)';
	}
	else
	{
		$auto_pages = array_unique($auto_pages);
		sort($auto_pages, SORT_STRING);

		$result = get_autolink_pattern_sub($auto_pages, 0, count($auto_pages), 0);
	}
	
	return array($result, '(?!)', $forceignorepages);
}

function get_autolink_pattern_sub(& $pages, $start, $end, $pos)
{
	static $lev = 0;
	
	if ($end == 0) return '(?!)';
	
	$lev ++;
	
	$result = '';
	$count = $i = $j = 0;
	$x = (mb_strlen($pages[$start]) <= $pos);
	if ($x) { ++$start; }
	
	for ($i = $start; $i < $end; $i = $j)
	{
		$char = mb_substr($pages[$i], $pos, 1);
		for ($j = $i; $j < $end; $j++)
		{
			if (mb_substr($pages[$j], $pos, 1) != $char) { break; }
		}
		if ($i != $start)
		{
			if ($lev === 1)
			{
				$result .= "\t";
			}
			else
			{
				$result .= '|';
			}
			
		}
		if ($i >= ($j - 1))
		{
			$result .= str_replace(' ', '\\ ', preg_quote(mb_substr($pages[$i], $pos), '/'));
		}
		else
		{
			$result .= str_replace(' ', '\\ ', preg_quote($char, '/')) .
				get_autolink_pattern_sub($pages, $i, $j, $pos + 1);
		}
		
		++$count;
	}
	if ($lev === 1)
	{
		$limit = 1024 * 30; //マージンを持たせて 30kb で分割
		$_result = "";
		$size = 0;
		foreach(explode("\t",$result) as $key)
		{
			if (strlen($_result.$key) - $size > $limit)
			{
				$_result .= ")\t(?:".$key;
				$size = strlen($_result);
			}
			else
			{
				$_result .= ($_result ? "|" : "").$key;
			}
		}
		$result = '(?:' . $_result . ')';
	}
	else
	{
		if ($x or $count > 1) { $result = '(?:' . $result . ')'; }
		if ($x) { $result .= '?'; }
	}
	$lev --;
	return $result;
}

// ページ名を指定階層数切り詰める
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

// 指定ページをインクルードする
function include_page($page,$ret_array=false)
{
	global $vars,$post,$get,$comment_no,$article_no,$h_excerpt,$digest,$pgid,$related;
	global $_msg_read_more;
	global $now_inculde_convert;
	global $nocache_plugin_on_include;
	
	static $included = array();
	static $parents = array();
	static $count = 1;
	
	$now_inculde_convert = true;
	
	$page = strip_bracket($page);
	
	// 自己ページ
	$root = isset($vars['page']) ? strip_bracket($vars['page']) : '';
	$included[$root] = TRUE;
	
	// 読み出し元
	$parents[$root] = TRUE;
	
	$ret = "";
	// チェック
	if (isset($included[$page])) {
		$ret = '#include(): Included already: ' . make_pagelink($page) . '<br />' . "\n";
	} if (! is_page($page)) {
		$ret = '#include(): No such page: ' . htmlspecialchars($page) . '<br />' . "\n";
	} if ($count > PLUGIN_INCLUDE_MAX) {
		$ret = '#include(): Limit exceeded: ' . make_pagelink($page) . '<br />' . "\n";
	} else {
		if (isset($parents[$page])) ++$count;
	}
	
	if ($ret) return ($ret_array)? array($ret,"") : $ret;
	
	// 読み込み済みにする
	$included[$page] = TRUE;
	
	$body = join("",get_source($page));
	
	$pcon = new pukiwiki_converter();
	$pcon->safe = TRUE;
	$pcon->page = add_bracket($page);
	if (preg_match("/^#more/m",$body))
	{
		$body = preg_replace("/\n#more\(\s*off\s*\).*?(\n#more\(\s*on\s*\)\n|$)/s","\n",$body);
		$match = array();
		if (preg_match("/(.*?)\n#more(\([^)]*\))?\n/s",$body,$match))
			$body = $match[1];
		$body .= "\n\nRIGHT:[[$_msg_read_more>$page]]";
		
		$pcon->string = $body;
		$pcon->ret_array = $ret_array;
		$ret = $pcon->convert();
	}
	else if ($nocache_plugin_on_include && preg_match("/^{$nocache_plugin_on_include}/m",$body))
	{
		$pcon->string = $body;
		$pcon->ret_array = $ret_array;
		$ret = $pcon->convert();
	}
	else
	{
		$pcon->string = $page;
		$pcon->page_cvt = TRUE;
		$pcon->ret_array = $ret_array;
		$ret = $pcon->convert();
	}
	unset($pcon);
	
	$now_inculde_convert = false;
	
	return $ret;
}

// ページ専用CSSタグを得る
function get_page_css_tag($page)
{
	global $xoopsModule;
	
	$page = strip_bracket($page);
	$ret = '';
	$_page = '';
	$dir = XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/";
	foreach(explode('/',$page) as $val)
	{
		$_page = ($_page)? $_page."/".$val : $val;
		$_pagecss_file = encode($_page).".css";
		if(file_exists($dir.$_pagecss_file))
		{
			$ret .= '<link rel="stylesheet" href="cache/'.$_pagecss_file.'" type="text/css" media="screen" charset="shift_jis">'."\n";
		}
	}
	return $ret;
}

//ページIDからURLを求める
function get_url_by_id($id=0)
{
	global $use_static_url;
	
	static $ret = array();
	static $func;
	
	if (!$id) return XOOPS_WIKI_URL."/";

	if (isset($ret[$id])) return $ret[$id];
	
	if (!$func)
	{
		$dir = str_replace(XOOPS_WIKI_HOST,"",XOOPS_URL);
		if ($use_static_url == 3)
			$func = create_function('$id','return "'.$dir.'/".PUKIWIKI_DIR_NAME."+._".$id.".htm";');
		else if ($use_static_url == 2)
			$func = create_function('$id','return "'.$dir.'/".PUKIWIKI_DIR_NAME."+index.pgid+_".$id.".htm";');
		else if ($use_static_url)
			$func = create_function('$id','return "'.XOOPS_WIKI_URL.'/".$id.".html";');
		else
			$func = create_function('$id','return "'.XOOPS_WIKI_URL.'/?".rawurlencode(strip_bracket(get_pgname_by_id($id)));');
	}
	
	return $ret[$id] = $func($id);
}

//ページ名からURLを求める
function get_url_by_name($name="")
{
	global $use_static_url;
	
	static $ret = array();
	static $func;
	
	if(isset($ret[$name])) return $ret[$name];
	
	if (!$name || !is_page($name)) return $ret[$name] = XOOPS_WIKI_URL."/";
	
	if (!$func)
	{
		$dir = str_replace(XOOPS_WIKI_HOST,"",XOOPS_URL);
		if ($use_static_url == 3)
			$func = create_function('$name','return "'.$dir.'/pukiwiki+._".get_pgid_by_name($name).".htm";');
		else if ($use_static_url == 2)
			$func = create_function('$name','return "'.$dir.'/pukiwiki+index.pgid+_".get_pgid_by_name($name).".htm";');
		else if ($use_static_url)
			$func = create_function('$name','return "'.XOOPS_WIKI_URL.'/".get_pgid_by_name($name).".html";');
		else
			$func = create_function('$name','return "'.XOOPS_WIKI_URL.'/?".rawurlencode(strip_bracket($name));');
	}
	return $ret[$name] = $func($name);
}

// $content から指定レベル以上のリストを取り出す。
function select_contents_by_level($str,$lev=1,$tag="ul")
{
	global $link_compact;
	
	$lev = (int)$lev;
	if (!$lev || !$str) return $str;

	$str = str_replace("<{$tag} class=\"list","\x08",$str);
	
	$reg = "/\x08(([\d]+)[^\x08]+?<\/$tag>)/";
	
	$arg = array();
	while(preg_match($reg,$str,$arg))
	{
		if ($arg[2] > $lev)
			$str = preg_replace($reg,"",$str,1);
		else
			$str = preg_replace($reg,"<{$tag} class=\"list$1",$str,1);
	}
	$str = preg_replace("#<a name=\"[^\"]+\"></a>#","",$str);
	
	if (!$link_compact)
	{
		$str = preg_replace("/(<a href=\"#ct([^_]+).*?\")>/e",'select_contents_by_level_sub("$1","$2")',$str);
	}
	
	return $str;
}
// titleタグを付加するサブ関数
function select_contents_by_level_sub($link,$pid)
{
	$link = str_replace('\"','"',$link);
	$page = get_pgname_by_id($pid);
	$passage = get_pg_passage(add_bracket($page),FALSE);
	$s_page = htmlspecialchars($page);
	$title = " title=\"$s_page$passage\"";
	return $link.$title.">";
}

// <a>タグ内の長すぎる英単語をワードラップ
function wordwrap4tolong(&$str)
{

	$str = str_replace('\"','\\\"',$str);
	//$str = preg_replace("/(<[^>]+>)?([!~*\"'();\/?:\@&=+\$,%#\w.-]*)/e","'$1'.wordwrap('$2',36,'&#173;',1)",$str);
	$str = preg_replace("/(<\s*a[^>]+>)?([!~*\"'();\/?:\@&=+\$,%#\w.-]+?)(<\/a>)/e","'$1'.wordwrap4tolong_sub('$2').'$3'",$str);
	$str = str_replace(array('\&#173;','\"'),array('&#173;','"'),$str);
	return $str;

}
function wordwrap4tolong_sub($str)
{
	global $entity_pattern;
	$str = str_replace('\"','"',$str);
	// 実体参照文字列が含まれる場合はパス
	if (preg_match('/&(#[0-9]+|#x[0-9a-f]+|'.$entity_pattern.');/',$str)) return $str;
	// 26文字ごとに &#173; を挿入
	$str = wordwrap($str,36,'&#173;',1);
	return $str;
}

// リファラチェック $blank = 1 で未設定も不許可(デフォルトで未設定は許可)
function pukiwiki_refcheck($blank = 0)
{
	$ref = xoops_getenv('HTTP_REFERER');
	if (!$blank && !$ref) return true;
	if (strpos($ref, XOOPS_URL) !== 0 ) return false;
	
	return true;
}

// アクセス制限
function check_access_ctl()
{
	global $post,$pwm_config;
	
	$config = &new Config('access');
	$config->read();
	$deny_ips = $config->get('Deny_IP');
	$pwm_config['deny_ucds'] = $config->get('Deny_UCD');
	$deny_names = $config->get('Deny_POST_NAME');
	$deny_exit = $config->get('Deny_POST_EXIT');
	unset($config);
	
	if (!empty($deny_ips) && !empty($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $deny_ips)) exit;
	if (!empty($pwm_config['deny_ucds']) && in_array(PUKIWIKI_UCD, $pwm_config['deny_ucds'])) exit;
	if (isset($post['name']) && !empty($deny_names))
	{
		function check_access_ctl_quote($str)
		{
			return preg_quote($str,"/");
		}
		$deny_names = array_map('check_access_ctl_quote',$deny_names);
		
		if (preg_match("/(".join('|',$deny_names).")/",$post['name']))
		{
			if ($deny_exit)
			{
				header("Location: ".$deny_exit[0]);
			}
			exit;
		}
	}
}

//整数値のみ許可されるパラメーターをチェック
function check_int_param(&$arg)
{
	foreach(array('v_gids','v_aids','gids','aids','f_author_uid') as $_tmp)
	{
		if (isset($arg[$_tmp]))
		{
			if (is_array($arg[$_tmp]))
			{
				$arg[$_tmp] = array_map('intval',$arg[$_tmp]);
			}
			else
			{
				$arg[$_tmp] = intval($arg[$_tmp]);
			}
		}
	}
	return $arg;
}

//XOOPS Protector モジュール で 挿入された末尾の */ を取り除く
function remove_protector_chr(&$arg)
{
	if (is_array($arg))
	{
		$_tmp = array();
		foreach($arg as $_arg)
		{
			remove_protector_chr($_arg);
			$_tmp[] = $_arg;
		}
		$arg = $_tmp;
	}
	else
	{
		$match = array();
		if (preg_match("#^(.+)\*/$#s",$arg,$match))
		{
			$_tmp = preg_replace("#/\*.*?\*/#s","",$match[1]);
			if (strpos($_tmp,"/*") !== false)
			{
				$arg = $match[1];
			}
		}
	}
	return $arg;
}

// XoopsToken Class の利用
// チケット取得
function get_token_html()
{
	if (!class_exists('XoopsTokenHandler')) {return "";}
	static $handler;
	if (!is_object($handler))
	{
		$handler = new XoopsMultiTokenHandler();
	}
	// 有効期限無期限(セッションが切れるまで)
	$ticket = &$handler->create('pukiwikimod'.PUKIWIKI_DIR_NUM,0);
	return $ticket->getHtml();
}
// チケットの検査
function check_token_ticket($onetime=true)
{
	if (!class_exists('XoopsTokenHandler')) {return true;}
	$handler = new XoopsMultiTokenHandler();
	return $handler->autoValidate('pukiwikimod'.PUKIWIKI_DIR_NUM,$onetime);
}

// 与えられた文字列をページ名に矯正する
function reform2pagename($str)
{
	global $pwm_confg;
	if (empty($pwm_confg['reform_to'])) return $str;
	$bef = array(" ","#","&","<",">",'"',":");
	$str = str_replace($bef,$pwm_confg['reform_to'],$str);
	return $str;
}

//プラグインオプションを解析する汎用関数
function pwm_check_arg($args, &$params, $separator=":")
{
	if (empty($args)) { $params['_done'] = TRUE; return; }
	$keys = array_keys($params);
	foreach($args as $val)
	{
		list($_key,$_val) = array_pad(explode($separator,$val,2),2,TRUE);
		$_key = trim($_key);
		$_val = trim($_val);
		if (in_array($_key,$keys))
		{
			$params[$_key] = $_val;
		}
		else
		{
			$params['_args'][] = $val;
		}
	}
	$params['_done'] = TRUE;
	return;
}

//////////////////////////////////////////////////////
//
// XOOPS用　関数
//
//////////////////////////////////////////////////////
// ユーザーが所属するグループIDを得る
function X_get_groups(){
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		global $X_uid;
		$X_M =& xoops_gethandler('member');
		return $X_M->getGroupsByUser($X_uid);
	} else {
		// XOOPS 1
		global $xoopsUser;
		return XoopsGroup::getByUser($xoopsUser);
	}
}
// グループ一覧を得る
function X_get_group_list()
{
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		$X_M =& xoops_gethandler('member');
		return $X_M->getGroupList();
	} else {
		// XOOPS 1
		return XoopsGroup::getAllGroupsList();
	}
}

// 登録ユーザー一覧を得る
function X_get_users($sort=true)
{
	$sort = $sort? 1 : 0;
	static $users = array();
	if (isset($users[$sort])) return $users[$sort];
	
	if (file_exists(XOOPS_ROOT_PATH.'/kernel/member.php')) {
		// XOOPS 2
		$X_M = xoops_gethandler('member');
		$ret = $X_M->getUserList();
	} else {
		// XOOPS 1
		$ret =  XoopsUser::getAllUsersList();
	}
	if ($sort) asort($ret);
	$users[$sort] = $ret;
	return $ret;
}

// #以降をトリップに変換して # で分割した配列で返す
function convert_trip($val)
{
		$match = array();
	if (preg_match('/([^#]+)#(.+)/', $val, $match))
	{
		$name = $match[1];
		$key = $match[2];
		$salt = substr(substr($key,1,2).'H.',0,2);
		$salt = strtr($salt,':;<=>?@[\]^_`','ABCDEFGabcdef');
		$salt = preg_replace('/[^\dA-Za-z]/', '.', $salt);
		return array($name,substr(crypt($key,$salt), -10));
	}
	else
	{
		return array($val,'');
	}
}

//// Compat ////

// is_a --  Returns TRUE if the object is of this class or has this class as one of its parents
// (PHP 4 >= 4.2.0)
if (! function_exists('is_a'))
{
	function is_a($class, $match)
	{
		if (empty($class)) return false;

		$class = is_object($class) ? get_class($class) : $class;
		if (strtolower($class) == strtolower($match)) {
			return true;
		} else {
			return is_a(get_parent_class($class), $match);	// Recurse
		}
	}
}

// array_fill -- Fill an array with values
// (PHP 4 >= 4.2.0)
if (! function_exists('array_fill'))
{
	function array_fill($start_index, $num, $value)
	{
		$ret = array();
		while ($num-- > 0) $ret[$start_index++] = $value;
		return $ret;
	}
}

// md5_file -- Calculates the md5 hash of a given filename
// (PHP 4 >= 4.2.0)
if (! function_exists('md5_file'))
{
	function md5_file($filename)
	{
		if (! file_exists($filename)) return FALSE;

		$fd = fopen($filename, 'rb');
		$data = fread($fd, filesize($filename));
		fclose($fd);
		return md5($data);
	}
}
?>