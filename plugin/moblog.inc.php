<?php
// $Id: moblog.inc.php,v 1.3 2004/01/24 14:50:27 nao-pon Exp $
// Author: nao-pon http://hypweb.net/
// Bace script is pop.php of mailbbs by Let's PHP!
// Let's PHP! Web: http://php.s3.to/

function plugin_moblog_action()
{
	global $sock,$vars;
	
	//error_reporting(E_ALL);
	//設定ファイル読み込み
	require('moblog.ini.php');

	$chk_file = CACHE_DIR."moblog.chk";
	if (!file_exists($chk_file))
	{
		$fp = fopen($chk_file,wb);
		fclose ($fp);
	}
	elseif ($refresh_min * 60 > time() - filemtime($chk_file) && empty($vars['now']))
		plugin_moblog_output ();
	else
		touch ($chk_file);


	// 接続開始
	$sock = fsockopen($host, 110, $err, $errno, 10) or plugin_moblog_error_output("サーバーに接続できません。");
	$buf = fgets($sock, 512);
	if(substr($buf, 0, 3) != '+OK')
	{
		plugin_moblog_error_output($buf);
	}
	$buf = plugin_moblog_sendcmd("USER $user");
	$buf = plugin_moblog_sendcmd("PASS $pass");
	$data = plugin_moblog_sendcmd("STAT");//STAT -件数とサイズ取得 +OK 8 1234
	sscanf($data, '+OK %d %d', $num, $size);

	if ($num == "0") {
		$buf = plugin_moblog_sendcmd("QUIT"); //バイバイ
		fclose($sock);
		plugin_moblog_output ();
	}
	// 件数分
	for($i=1;$i<=$num;$i++) {
		$line = plugin_moblog_sendcmd("RETR $i");//RETR n -n番目のメッセージ取得（ヘッダ含
		$dat[$i] = "";
		while (!ereg("^\.\r\n",$line)) {//EOFの.まで読む
			$line = fgets($sock,512);
			$dat[$i].= $line;
		}
		$data = plugin_moblog_sendcmd("DELE $i");//DELE n n番目のメッセージ削除
	}
	$buf = plugin_moblog_sendcmd("QUIT"); //バイバイ
	fclose($sock);

	for($j=1;$j<=$num;$j++) {
		$write = true;
		$subject = $from = $text = $atta = $part = $attach = $filename = "";
		list($head, $body) = plugin_moblog_mime_split($dat[$j]);
		// To:ヘッダ確認
		if (preg_match("/(?:^|\n|\r)To:[ \t]*([^\r\n]+)/i", $head, $treg)){
			$toreg = "/".quotemeta($mail)."/";
			if (!preg_match($toreg,$treg[1])) $write = false; //投稿アドレス以外
		} else {
			// To: ヘッダがない
			$write = false;
		}
		// メーラーのチェック
		if ($write && (eregi("(X-Mailer|X-Mail-Agent):[ \t]*([^\r\n]+)", $head, $mreg))) {
			if ($deny_mailer){
				if (preg_match($deny_mailer,$mreg[2])) $write = false;
			}
		}
		// キャラクターセットのチェック
		if ($write && (eregi("charset[\s]*=[\s]*([^\r\n]+)", $head, $mreg))) {
			if ($deny_lang){
				if (preg_match($deny_lang,$mreg[1])) $write = false;
			}
		}
		// 日付の抽出
		eregi("Date:[ \t]*([^\r\n]+)", $head, $datereg);
		$now = strtotime($datereg[1]);
		if ($now == -1) $now = time();
		// サブジェクトの抽出
		if (preg_match("/\nSubject:[ \t]*(.+?)(\n[\w-_]+:|$)/is", $head, $subreg)) {
			// 改行文字削除
			$subject = str_replace(array("\r","\n"),"",$subreg[1]);
			// エンコード文字間の空白を削除
			$subject = preg_replace("/\?=[\s]+?=\?/","?==?",$subject);
			
			while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME B
				$subject = $regs[1].base64_decode($regs[2]).$regs[3];
			}
			while (eregi("(.*)=\?iso-[^\?]+\?Q\?([^\?]+)\?=(.*)",$subject,$regs)) {//MIME Q
				$subject = $regs[1].quoted_printable_decode($regs[2]).$regs[3];
			}
			$subject = htmlspecialchars(mb_convert_encoding($subject,"EUC-JP","AUTO"));
			// 未承諾広告カット
			if ($write && $deny_title){
				if (preg_match($deny_title,$subject)) $write = false;
			}
		}
		// 送信者アドレスの抽出
		if (eregi("From:[ \t]*([^\r\n]+)", $head, $freg)) {
			$from = plugin_moblog_addr_search($freg[1]);
		} elseif (eregi("Reply-To:[ \t]*([^\r\n]+)", $head, $freg)) {
			$from = plugin_moblog_addr_search($freg[1]);
		} elseif (eregi("Return-Path:[ \t]*([^\r\n]+)", $head, $freg)) {
			$from = plugin_moblog_addr_search($freg[1]);
		}
		
		$today = getdate();
		$date = sprintf("/%04d-%02d-%02d-0",$today['year'],$today['mon'],$today['mday']);
		
		$page = "";
		if (!empty($adr2page[$from])) $page = "[[".$adr2page[$from].$date."]]";
		if (!$page && $adr2page['other']) $page = "[[".$adr2page['other'].$date."]]";
		if (!$page) $write = false;
		
		// 拒否アドレス
		if ($write){
			for ($f=0; $f<count($deny); $f++)
				if (eregi($deny[$f], $from)) $write = false;
		}

		// マルチパートならばバウンダリに分割
		if (eregi("\nContent-type:.*multipart/",$head)) {
			eregi('boundary="([^"]+)"', $head, $boureg);
			$body = str_replace($boureg[1], urlencode($boureg[1]), $body);
			$part = split("\r\n--".urlencode($boureg[1])."-?-?",$body);
			if (eregi('boundary="([^"]+)"', $body, $boureg2)) {//multipart/altanative
				$body = str_replace($boureg2[1], urlencode($boureg2[1]), $body);
				$body = eregi_replace("\r\n--".urlencode($boureg[1])."-?-?\r\n","",$body);
				$part = split("\r\n--".urlencode($boureg2[1])."-?-?",$body);
			}
		} else {
			$part[0] = $dat[$j];// 普通のテキストメール
		}
	//print_r($part);

		foreach ($part as $multi) {
			list($m_head, $m_body) = plugin_moblog_mime_split($multi);
			$m_body = ereg_replace("\r\n\.\r\n$", "", $m_body);
			// キャラクターセットのチェック
			if ($write && (eregi("charset[\s]*=[\s]*([^\r\n]+)", $m_head, $mreg))) {
				if ($deny_lang){
					if (preg_match($deny_lang,$mreg[1])) $write = false;
				}
			}
			if (!eregi("Content-type: *([^;\n]+)", $m_head, $type)) continue;
			list($main, $sub) = explode("/", $type[1]);
			// 本文をデコード
			if (strtolower($main) == "text") {
				if (eregi("Content-Transfer-Encoding:.*base64", $m_head)) 
					$m_body = base64_decode($m_body);
				if (eregi("Content-Transfer-Encoding:.*quoted-printable", $m_head)) 
					$m_body = quoted_printable_decode($m_body);
				$text = trim(mb_convert_encoding($m_body,"EUC-JP","AUTO"));
				if ($sub == "html") $text = strip_tags($text);
				$text = str_replace(">","&gt;",$text);
				$text = str_replace("<","&lt;",$text);
				$text = str_replace("\r\n", "\r",$text);
				$text = str_replace("\r", "\n",$text);
				$text = preg_replace("/\n{2,}/", "\n\n", $text);
				//$text = str_replace("\n", "~\n", $text);
				if ($write) {
					// 電話番号削除
					$text = eregi_replace("([[:digit:]]{11})|([[:digit:]\-]{13})", "", $text);
					// 下線削除
					$text = eregi_replace($del_ereg, "", $text);
					// mac削除
					$text = ereg_replace("Content-type: multipart/appledouble;[[:space:]]boundary=(.*)","",$text);
					// 広告等削除
					if (is_array($word)) {
						foreach ($word as $delstr)
							$text = str_replace($delstr, "", $text);
					}
					if (strlen($text) > $body_limit) $text = substr($text, 0, $body_limit)."...";
				}
			}
			if ($write) {
				// ファイル名を抽出
				if (eregi("name=\"?([^\"\n]+)\"?",$m_head, $filereg)) {
					$filename = trim($filereg[1]);
					// エンコード文字間の空白を削除
					$filename = preg_replace("/\?=[\s]+?=\?/","?==?",$filename);
					while (eregi("(.*)=\?iso-[^\?]+\?B\?([^\?]+)\?=(.*)",$filename,$regs)) {//MIME B
						$filename = $regs[1].base64_decode($regs[2]).$regs[3];
					}
					$filename = mb_convert_encoding($filename,"EUC-JP","AUTO");
				}
				// 添付データをデコードして保存
				if (eregi("Content-Transfer-Encoding:.*base64", $m_head) && eregi($subtype, $sub)) {
					$tmp = base64_decode($m_body);
					if (!$filename) $filename = time().".$sub";
					//$save_file = UPLOAD_DIR.encode($page).'_'.encode($filename);
					$_filename = $filename;
					$attachname = preg_replace('/\..+$/','', $_filename,1);
					//すでに存在した場合、 ファイル名に'_0','_1',...を付けて回避(姑息)
					$count = '_0';
					while (file_exists(UPLOAD_DIR.encode($page).'_'.encode($_filename)))
					{
						$_filename = preg_replace('/^[^\.]+/',$attachname.$count++,$filename);
					}
					$filename = $_filename;
					$save_file = UPLOAD_DIR.encode($page).'_'.encode($filename);
					
					if (strlen($tmp) < $maxbyte && !eregi($viri, $filename) && $write) {
						$fp = fopen($save_file, "wb");
						fputs($fp, $tmp);
						fclose($fp);
					} else {
						$write = false;
					}
				}
			}
		}
		if ($imgonly && $attach=="") $write = false;
		
		$subject = trim($subject);
		
		// wikiページ書き込み
		if ($write) plugin_moblog_page_write($page,$subject,$text,$filename,$ref_option,$now);

/*
		if ($write) {
			array_unshift($lines, $line);
			
			// ヘッダ情報を記録
			if ($mailbbs_head_save)
				mailbbs_head_save($id,$head);
		} else {
			// 拒否メールログ保存
			if ($mailbbs_denylog_save)
				mailbbs_deny_log($head,$subject,str_replace("~\n","\n",$text));
		}
*/
	}

	//echo $debg;

	// imgタグ呼び出し
	plugin_moblog_output ();
}
function plugin_moblog_convert()
{
	//POPサーバーにアクセスするためのイメージタグを生成
	return "<div style=\"float:left\"><img src=\"$script?plugin=moblog\" width=1 height=1 /></div>\n";
}

function plugin_moblog_page_write($page,$subject,$text,$filename,$ref_option,$now)
{
	global $X_uid,$auto_template_name;
	$aids = $gids = $freeze = "";
	$date = "at ".date("g:i a", $now);
	$set_data = "\n\n";
	$set_data .= ($subject)?  "**$subject\n" : "----\n";
	if ($filename) $set_data .= "#ref(".$filename.$ref_option.")\n";
	$set_data .= $text."\n\n".$date."\n#clear\n\n";
	
	// 改行有効
	$set_data = auto_br($set_data);
	// オートブラケット
	$set_data = auto_braket($set_data,$page);
	// 念のためページ情報を削除
	delete_page_info($set_data);
	
	if (is_page($page))
	{
		//ページ更新
		$page_data = rtrim(join('',get_source($page)))."\n";
		if (preg_match("/\/\/ Moblog Body\n/",$page_data))
		{
			$page_data = preg_split("/\/\/ Moblog Body\n/",$page_data,2);
			$save_data = $page_data[0].$set_data."// Moblog Body\n".$page_data[1];
		}
		else
		{
			$save_data = $page_data.$set_data;
		}
	}
	else
	{
		//ページ新規作成
		//編集権限継承がセットされている上位ページ凍結情報を得る
		$up_freezed = get_freezed_uppage($page);
		$page_info = "";
		//ページ情報のセット
		if ($up_freezed[0])
		{
			//編集権限継承あり
			$freeze = 1;
			$uid = $up_freezed[1];
			$aids = preg_replace("/(($|,)$uid,|,$)/","",join(",",$up_freezed[2]));
			$gids = preg_replace("/,$/","",join(",",$up_freezed[3]));
			$page_info = "#freeze\tuid:{$uid}\taid:{$aids}\tgid:{$gids}\n// author:{$uid}\n";
		}
		$template = preg_replace("/(.*)\/[^\/]+/","$1",strip_bracket($page))."/".$auto_template_name."_m";
		
		if (is_page($template))
		{
			$page_data = rtrim(join('',get_source($template)))."\n";
			delete_page_info($page_data);
		}
		else
			$page_data = "";

		if (preg_match("/\/\/ Moblog Body\n/",$page_data))
		{
			$page_data = preg_split("/\/\/ Moblog Body\n/",$page_data,2);
			$save_data = $page_data[0].$set_data."// Moblog Body\n".$page_data[1];
		}
		else
		{
			$save_data = $page_data.$set_data;
		}
		$save_data = $page_info.$save_data;
	}
	
	// ページ更新
	page_write($page,$save_data,NULL,$aids,$gids,"","",$freeze,"");
	
	//Ping送信
	$tmp = @file(XOOPS_URL."/modules/pukiwiki/ping.php?$page");
}
/* コマンドー送信！！*/
function plugin_moblog_sendcmd($cmd) {
	global $sock;
	fputs($sock, $cmd."\r\n");
	$buf = fgets($sock, 512);
	if(substr($buf, 0, 3) == '+OK') {
		return $buf;
	} else {
		plugin_moblog_error_output($buf);
	}
	return false;
}
/* ヘッダと本文を分割する */
function plugin_moblog_mime_split($data) {
	$part = split("\r\n\r\n", $data, 2);
	$part[0] = ereg_replace("\r\n[\t ]+", " ", $part[0]);
	return $part;
}
/* メールアドレスを抽出する */
function plugin_moblog_addr_search($addr) {
	if (eregi("[-!#$%&\'*+\\./0-9A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+", $addr, $fromreg)) {
		return $fromreg[0];
	} else {
		return false;
	}
}
// エラー出力
function plugin_moblog_error_output ($str)
{
	echo $str;
	header("Content-Type: image/gif");
	readfile('poperror.gif');
}
// イメージ出力
function plugin_moblog_output ()
{
	// imgタグ呼び出し用
	header("Content-Type: image/gif");
	readfile('./image/spacer.gif');
	exit;
}
?>