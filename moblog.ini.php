<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: moblog.ini.php,v 1.1 2004/01/12 13:17:32 nao-pon Exp $
/////////////////////////////////////////////////

	/////// Moblog設定 ////////
	///////////////////////////
	////// 必須設定項目 ///////
	
	// 受信用メールアドレス
	$mail = "";
	// POPサーバー
	$host = "";
	// POPサーバーアカウント
	$user = "";
	// POPサーバーパスワード
	$pass = "";

	// 送信アドレスによって振り分けるページの指定(ブラケットはつけない)
	// 'メールアドレス' => 'Wikiページ名',
	$adr2page = array(
	'other' => '',	// 登録メアド以外 空白で登録しない
	'hoge@hoge.com' => 'Moblog', // hoge@hoge.com からのメールは、Moblog/yyyy-mm-dd-0 のページへ登録
	);
	
	////// 必須設定項目終了 //////
	//////////////////////////////
	///// 以下はお好みで設定 /////
	
	//refプラグインの追加オプション
	$ref_option = ',left,around,mw:200';

	// 最大添付量（バイト・1ファイルにつき）※超えるものは保存しない
	$maxbyte = "1000000";//1MB

	// 本文文字制限（半角で
	$body_limit = 1000;
	
	// 最小自動更新間隔（分）
	$refresh_min = 5;

	// 件名がないときの題名
	$nosubject = "";

	// 投稿非許可アドレス（ログに記録しない）
	$deny = array('163.com','bigfoot.com','boss.com','yahoo-delivers@mail.yahoo.co.jp');

	// 投稿非許可メーラー(perl互換正規表現)（ログに記録しない）
	$deny_mailer = '/(Mail\s*Magic|Easy\s*DM|Friend\s*Mailer|Extra\s*Japan|The\s*Bat)/i';

	// 投稿非許可タイトル(perl互換正規表現)（ログに記録しない）
	$deny_title = '/((未|末)\s?承\s?(諾|認)\s?広\s?告)|相互リンク/i';

	// 投稿非許可キャラクターセット(perl互換正規表現)（ログに記録しない）
	$deny_lang = '/us-ascii|big5|euc-kr|gb2312|iso-2022-kr|ks_c_5601-1987/i';

	// 対応MIMEタイプ（正規表現）Content-Type: image/jpegの後ろの部分。octet-streamは危険かも
	$subtype = "gif|jpe?g|png|bmp|octet-stream|x-pmd|x-mld|x-mid|x-smd|x-smaf|x-mpeg";

	// 保存しないファイル(正規表現)
	$viri = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";

	// 25字以上の下線は削除（広告区切り）
	$del_ereg = "[_]{25,}";

	// 本文から削除する文字列
	$word[] = "会員登録は無料  充実した出品アイテムなら MSN オークション";
	$word[] = "http://auction.msn.co.jp/";
	$word[] = "Do You Yahoo!?";
	$word[] = "Yahoo! BB is Broadband by Yahoo!";
	$word[] = "http://bb.yahoo.co.jp/";

	// 添付メールのみ記録する？Yes=1 No=0（本文のみはログに載せない）
	$imgonly = 0;

	////////////////// 設定ここまで ///////////////////
?>