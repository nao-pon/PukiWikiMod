<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: func.php,v 1.14 2003/07/23 23:55:42 nao-pon Exp $
/////////////////////////////////////////////////
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
			array_unshift($source,$page); // ページ名も検索対象に
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

// プログラムへの引数のチェック
function arg_check($str)
{
	global $arg,$vars;

	return preg_match("/^".$str."/",$vars["cmd"]);
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
	$enkey = '';
	$arych = preg_split("//", $key, -1, PREG_SPLIT_NO_EMPTY);
	
	foreach($arych as $ch)
	{
		$enkey .= sprintf("%02X", ord($ch));
	}

	return $enkey;
}

// ファイル名のデコード
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

// InterWikiName List の解釈(返値:２次元配列)
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

// [[ ]] を取り除く
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

// [[ ]] を付加する
function add_bracket($str){
	global $WikiName;
	if (!preg_match("/^".$WikiName."$/",$str)){
		if (!preg_match("/\[\[.*\]\]/",$str)) $str = "[[".$str."]]";
	}
	return $str;
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
function getmicrotime()
{
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$sec + (float)$usec);
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
		foreach($post_lines as $line){
			if (substr($line,0,1) != ' ') {
				//取り合えず改行前の~を削除
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

		//余分な~を削除
		$msg = preg_replace("/~(->)?\n((?:[#\-+*|:>\n]|\/\/))/","\\1\n\\2",$msg);
		$msg = preg_replace("/((?:^|\n)[^ ][^\n~]*)~((:?->)?\n )/","\\1\\2",$msg);
		$msg = preg_replace("/((?:^|\n)(?:----|\/\/)(?:.*)?)~((:?->)?\n)/","\\1\\2",$msg);
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
	// #で始まるプラグイン行、//で始まるメッセージ行を処理しない為の前処理
	//$msg=preg_replace("/(^|\n)( |#|\/\/)(.*)(\n|$)/","\\1\\2[[\\3]]\\4",$msg);
	$msg=preg_replace("/(^|\n)( |#|\/\/)(.*)/","\\1\\2[[\\3]]",$msg);
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
		}
	}

	//後処理制御文字から置換
	$msg = str_replace(chr(28),"[[",$msg);
	$msg = str_replace(chr(29),"]]",$msg);

	// インラインプラグイン アンエスケープ
	$msg = preg_replace("/\[\[(&.*?;)\]\]/","$1",$msg);
	// #で始まるプラグイン行、//で始まるメッセージ行を処理しない為の後処理
	//$msg = preg_replace("/(^|[\n])( |#|\/\/)\[\[(.*)\]\](\n|$)/","\\1\\2\\3\\4",$msg);
	$msg = preg_replace("/(^|[\n])( |#|\/\/)\[\[(.*)\]\]/","\\1\\2\\3",$msg);
	// エスケープを元に戻す
	if ($tgt_name == "InterWikiName"){
		$msg = preg_replace("/\[\[(\[[^[\]]+\])\]\]/","$1",$msg);
	} else {
		// InterWikiNameページ以外
		$msg = ereg_replace("\[\[".$http_URL_regex."\]\]","\\1",$msg);
		$msg = eregi_replace("\[\[".$mail_ADR_regex."\]\]", "\\1", $msg);
	}
	
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
//////////////////////////////////////////////////////
//
// XOOPS用　関数
//
//////////////////////////////////////////////////////

// ユーザーが所属するグループIDを得る
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
// グループ一覧を得る
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

// 登録ユーザー一覧を得る
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
