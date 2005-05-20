<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: pukiwiki.ini.php,v 1.41 2005/05/20 00:02:19 nao-pon Exp $
//
// PukiWiki setting file

/////////////////////////////////////////////////
// ディレクトリ指定 最後に / が必要 属性は 777
/////////////////////////////////////////////////
// データの格納ディレクトリ
define("DATA_DIR",XOOPS_WIKI_PATH."/wiki/");
/////////////////////////////////////////////////
// 差分ファイルの格納ディレクトリ
define("DIFF_DIR",XOOPS_WIKI_PATH."/diff/");
/////////////////////////////////////////////////
// バックアップファイル格納先ディレクトリ
define("BACKUP_DIR",XOOPS_WIKI_PATH."/backup/");
/////////////////////////////////////////////////
// プラグインファイル格納先ディレクトリ
define("PLUGIN_DIR",XOOPS_WIKI_PATH."/plugin/");
/////////////////////////////////////////////////
// プラグイン用データファイル格納先ディレクトリ
define("PLUGIN_DATA_DIR",XOOPS_WIKI_PATH."/plugin_data/");
/////////////////////////////////////////////////
// counter file
define("COUNTER_DIR",XOOPS_WIKI_PATH."/counter/");
/////////////////////////////////////////////////
// ページHTMLキャッシュディレクトリ
define("PAGE_CACHE_DIR",XOOPS_WIKI_PATH."/pagehtml/");
/////////////////////////////////////////////////
// キャッシュディレクトリ
define("CACHE_DIR",XOOPS_WIKI_PATH."/cache/");
/////////////////////////////////////////////////
// プラグイン用キャッシュディレクトリ
define("P_CACHE_DIR",CACHE_DIR."p/");


/////////////////////////////////////////////////
// インクルードを許可する段数
define("PLUGIN_INCLUDE_MAX",4);


/////////////////////////////////////////////////
// Language
if($xoopsConfig['language'] == "japanese"){
	define("LANG","ja");
} else {
	define("LANG","en");
}

/////////////////////////////////////////////////
// スキンファイルの場所。
if (empty($_GET['xoops_block'])) 
	define("SKIN_FILE","./skin/pukiwiki.skin.".LANG.".php");
else
	define("SKIN_FILE","./skin/xoops_block.skin.".LANG.".php");
	
/////////////////////////////////////////////////
// 言語ファイルの読み込み(編集しないでください)
if(
	!file_exists(XOOPS_WIKI_PATH."/".LANG.".lng")
	||
	!is_readable(XOOPS_WIKI_PATH."/".LANG.".lng")
	)
	die_message(LANG.".lng(language file) is not found.");
require(XOOPS_WIKI_PATH."/".LANG.".lng");

/////////////////////////////////////////////////
// ファイルアップロード関連
// set PHP value to enable file upload
ini_set("file_uploads","1");

// upload dir(must set end of /)
define("UPLOAD_DIR","./attach/");

// max file size for upload on PHP(PHP default 2MB)
ini_set("upload_max_filesize","10M");

// max file size for upload on script of PukiWiki(default 1MB)
define("MAX_FILESIZE",10000000);


/////////////////////////////////////////////////
// index.php などに変更した場合のスクリプト名の設定
// とくに設定しなくても問題なし
$script = XOOPS_WIKI_URL.'/index.php';

/////////////////////////////////////////////////
// 更新履歴ページの名前
$whatsnew = "RecentChanges";

///////////////////////////////////////////////// 
// 削除履歴ページの名前 
$whatsdeleted = ':RecentDeleted'; 

/////////////////////////////////////////////////
// InterWikiNameページの名前
$interwiki = "InterWikiName";

/////////////////////////////////////////////////
// 更新履歴を表示するときの最大件数
$maxshow = 80;

///////////////////////////////////////////////// 
// 削除履歴の最大件数(0で記録しない) 
$maxshow_deleted = 80;
///////////////////////////////////////////////// 
// 削除履歴を管理者以外は閲覧禁止にする(Yes:1, No:0)
$unvisible_deleted = 0;

/////////////////////////////////////////////////
// 編集することのできないページの名前 , で区切る
$cantedit = array( $whatsnew, );

/////////////////////////////////////////////////
// WikiNameを*無効に*する
$nowikiname = 0;

/////////////////////////////////////////////////
// AutoLinkを有効にする場合は、AutoLink対象となる
// ページ名の最短バイト数を指定
// AutoLinkを無効にする場合は0
$autolink = 3;

/////////////////////////////////////////////////
// ページリンクをパンくずリストにする
$breadcrumbs = 1;

/////////////////////////////////////////////////
// ページリンク表示時にページ名の[ 数字-_ ]を見出し行に変換する
$convert_d2s = 1;

/////////////////////////////////////////////////
// 単語検索時にキーワードをハイライトするか
$search_word_color = 0;
/////////////////////////////////////////////////
// プレビューを表示するときのテーブルの背景色
$preview_color = "#F5F8FF";
/////////////////////////////////////////////////
// [[ページ]] へのリンク時[[]]を外すか
$strip_link_wall = 1;
/////////////////////////////////////////////////
// 一覧ページに頭文字インデックスをつけるか
$list_index = 1;
/////////////////////////////////////////////////
// http:// リンクのウィンドウ名指定(_top,_blank,etc)
$link_target = "_blank";
/////////////////////////////////////////////////
// InterWikiNameのウィンドウ名指定(_top,_blank,etc)
$interwiki_target = "_blank";
/////////////////////////////////////////////////
// URLリンク指定時にステータスバーに
// URLを表示せずに対象文字列を表示する
$alias_set_status = 0;

/////////////////////////////////////////////////
// リスト構造の左マージン
$_list_left_margin = 10; // リストと画面左端との間隔(px)
$_list_margin = 8;      // リストの階層間の間隔(px)
$_list_pad_str = ' class="list%d" style="padding-left:%dpx;margin-left:%dpx"';

/////////////////////////////////////////////////
// テーブルのマージン
$_table_left_margin = 10;		// テーブル左寄せの場合の画面左端との間隔(px)
$_table_right_margin = 10;	// テーブル右寄せの場合の画面右端との間隔(px)

/////////////////////////////////////////////////
// テキストエリアのカラム数
$cols = "80";
/////////////////////////////////////////////////
// テキストエリアの行数
$rows = 20;

/////////////////////////////////////////////////
// 大・小見出しから目次へ戻るリンクの文字
$top = $_msg_content_back_to_top;
/////////////////////////////////////////////////
// 関連ページ表示のページ名の区切り文字
$related_str = " ";
/////////////////////////////////////////////////
// 整形ルールでの関連ページ表示のページ名の区切り文字
$rule_related_str = "\n<li>";
/////////////////////////////////////////////////
// 水平線のタグ
$hr = '<hr class="full_hr">';
/////////////////////////////////////////////////
// 文末の注釈の直前に表示するタグ
$note_hr = '<hr class="note_hr">';
/////////////////////////////////////////////////
// 関連するリンクを常に表示する(負担がかかります)
$related_link = 1;
/////////////////////////////////////////////////
// WikiName,BracketNameに経過時間を付加する
$show_passage = 1;

/////////////////////////////////////////////////
// Last-Modified ヘッダを出力する
$lastmod = 0;

/////////////////////////////////////////////////
// 日付フォーマット
$date_format = "Y-m-d";
/////////////////////////////////////////////////
// 時刻フォーマット
$time_format = "H:i:s";
/////////////////////////////////////////////////
// 曜日配列
$weeklabels = $_msg_week;

/////////////////////////////////////////////////
// RSS に出力するページ数
$rss_max = 15;

/////////////////////////////////////////////////
// バックアップを行うか指定します 0 or 1
$do_backup = 1;
/////////////////////////////////////////////////
// ページを削除した際にバックアップもすべて削除する
$del_backup = 0;
/////////////////////////////////////////////////
// バックアップの世代を区切る文字列を指定します
// (通常はこのままで良いが、文章中で使われる可能性
// があれば、使われそうにない文字を設定する)
$splitter = ">>>>>>>>>>";
/////////////////////////////////////////////////
// ページの更新時にバックグランドで実行されるコマンド(mknmzなど)
//$update_exec = '/usr/local/bin/mknmz -O /vhosts/www.factage.com/sng/pukiwiki/nmz -L ja -k -K /vhosts/www.factage.com/sng/pukiwiki/wiki';

/////////////////////////////////////////////////
// 一覧・更新一覧に含めないページ名(正規表現で)
$non_list = "(^(\[\[)?\:|RenameLog|.*\/template)";

/////////////////////////////////////////////////
// 雛形とするページの読み込みを表示させるか
$load_template_func = 1;

/////////////////////////////////////////////////
// ページ名に従って自動で、雛形とするページの読み込み
$auto_template_func = 1;
$auto_template_name = "template";
$auto_template_rules = array();
$auto_template_rules[] = array('\[\[((.+)\/([^\/]+))\]\]' => '[[\2/'.$auto_template_name.']]');
$auto_template_rules[] = array('\[\[((.+)\/([^\/]+))\]\]' => '[[:'.$auto_template_name.'/\2]]');

/////////////////////////////////////////////////
// 無効にするActionプラグインプラグイン
// カンマ区切りで、#をつけずに記述
// (mapプラグインはページ数が増えると極端に負荷が高くなる)
$disabled_plugin = "map";

/////////////////////////////////////////////////
// TrackBackでのPing先URL抽出時に除外するプラグイン
// カンマ区切りで、#をつけずに記述
$notb_plugin = "include,calendar2,showrss,calendar_viewer,bugtrack_list,tracker_list,aws,blogs,google,gimage,newsclip,xoopsblock";

/////////////////////////////////////////////////
// 検索用Plainソース作成時に除外するプラグイン
// カンマ区切りで、#をつけずに記述
$noplain_plugin = "include,calendar2,calendar_viewer,bugtrack_list,tracker_list,ls2,ls,recent,popular,pcomment,contents,tenki,ref,exrate,xoopsblock,attachref,related,whatday,fortune";

/////////////// ParaEdit //////////////////
// ParaEdit 改行の代替文字列
//   <input type=hidden value=XXXXX> で改行(CR,LFなど)の変わりに使用する文字列
define("_PARAEDIT_SEPARATE_STR", '_PaRaeDiT_');
//if (!defined("_PARAEDIT_SEPARATE_STR")) define("_PARAEDIT_SEPARATE_STR", '_PaRaeDiT_');

// 編集リンクの文字列・スタイルを指定
//   %s に URL が入る
define("_EDIT_LINK", '<a href="%s"><img style="float:right" src="image/edit.png" alt="Edit" title="Edit" /></a>');

// 編集リンクの挿入箇所を指定
//   <h2>header</h2> の時、$1:<h2>, $2:header, $3:</h2> となるので $link を好きな場所に移動
// (例)
//  define("_PARAEDIT_LINK_POS", '$1$2$para_link$3'); // </h2>の前
    define("_PARAEDIT_LINK_POS", '$para_link$1$2$3'); // <h2>の前
//  define("_PARAEDIT_LINK_POS", '$1$2$3$para_link'); // </h2>の後ろ
/////////////// ParaEdit //////////////////

///////////////////////////////////////////////// 
// HTTPリクエストにプロキシサーバを使用する 
$use_proxy = 0; 
// proxy ホスト 
$proxy_host = 'proxy.xxx.yyy.zzz'; 
// proxy ポート番号 
$proxy_port = 8080; 
// プロキシサーバを使用しないホストのリスト 
$no_proxy = array( 
'127.0.0.1', 
'localhost', 
//'192.168.1.0/24', 
//'no-proxy.com', 
); 

/////////////////////////////////////////////////
// ユーザ定義ルール
//
//  正規表現で記述してください。?(){}-*./+\$^|など
//  は \? のようにクォートしてください。
//  前後に必ず / を含めてください。行頭指定は ^ を頭に。
//  行末指定は $ を後ろに。
//
/////////////////////////////////////////////////
// ユーザ定義ルール(直接ソースを置換)
$str_rules = array(
"now\?" => date($date_format,UTIME)." (".$weeklabels[date("w",UTIME)].") ".date($time_format,UTIME),
"date\?" => date($date_format,UTIME),
"time\?" => date($time_format,UTIME),
);

/////////////////////////////////////////////////
// ユーザ定義ルール(コンバート時に置換、直接しない)
$line_rules = array(
//"/!([A-Z][a-z]+(?:[A-Z][a-z]+)+)/" => "$1",
"((氏|死)(ね|ネ)|うんこ|つんぼ|ちんこ|まんこ|ウンコ|ツンボ|(?<!パ)チンコ|マンコ)" => "<span style=\"color:white;background-color:white;\">$0</span>", //禁止ワード
);

/////////////////////////////////////////////////
// フェイスマーク定義ルール
// $usefacemark = 1ならフェイスマークが置換されます
// 文章内にXDなどが入った場合にfacemarkに置換されてしまうので
// 必要のない方は $usefacemarkを0にしてください。
$usefacemark = 1;
$facemark_rules = array(
"\s(\:(?:-)?\))" => " <img src=\"".XOOPS_WIKI_URL."/face/smile.gif\" alt=\"\\1\" />",
"\s(\:(?:-)?D)" => " <img src=\"".XOOPS_WIKI_URL."/face/bigsmile.gif\" alt=\"\\1\" />",
"\s(\:(?:-)?p)" => " <img src=\"".XOOPS_WIKI_URL."/face/huh.gif\" alt=\"\\1\" />",
"\s(\:(?:-)?d)" => " <img src=\"".XOOPS_WIKI_URL."/face/huh.gif\" alt=\"\\1\" />",
"\s(X(?:-)?D)" => " <img src=\"".XOOPS_WIKI_URL."/face/oh.gif\" alt=\"\\1\" />",
"\s(X(?:-)?\()" => " <img src=\"".XOOPS_WIKI_URL."/face/oh.gif\" alt=\"\\1\" />",
"\s(;(?:-)?\))" => " <img src=\"".XOOPS_WIKI_URL."/face/wink.gif\" alt=\"\\1\" />",
"\s(;(?:-)?\()" => " <img src=\"".XOOPS_WIKI_URL."/face/sad.gif\" alt=\"\\1\" />",
"\s(\:(?:-)?\()" => " <img src=\"".XOOPS_WIKI_URL."/face/sad.gif\" alt=\"\\1\" />",
"\s(\:(?:-)?\?)" => " <img src=\"".XOOPS_WIKI_URL."/face/confused.gif\" alt=\"\\1\" />",
'&amp;(smile;)' => ' <img src="'.XOOPS_WIKI_URL.'"/face/smile.gif" alt="&$1" />',
'&amp;(bigsmile;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/bigsmile.gif" alt="&$1" />',
'&amp;(huh;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/huh.gif" alt="&$1" />',
'&amp;(oh;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/oh.gif" alt="&$1" />',
'&amp;(wink;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/wink.gif" alt="&$1" />',
'&amp;(sad;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/sad.gif" alt="&$1" />',
'&amp;(heart;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/heart.gif" alt="&$1" />',
'&amp;(hammer;)' => ' <img src="'.XOOPS_WIKI_URL.'/face/hammer.gif" alt="&$1" />',
);

////////以下の設定はXOOPSの管理画面での設定で上書きされます///////
/////////////////////////////////////////////////
// ホームページのタイトル(自由に変えてください)
// RSS に出力するチャンネル名
$page_title = "PukiWiki";

/////////////////////////////////////////////////
// トップページの名前
$defaultpage = "FrontPage";

/////////////////////////////////////////////////
// 編集者の名前(自由に変えてください)
$modifier = 'me';

/////////////////////////////////////////////////
// 編集者のホームページ(自由に変えてください)
$modifierlink = 'http://change me!/';

/////////////////////////////////////////////////
// 凍結機能を有効にするか
$function_freeze = 1;

/////////////////////////////////////////////////
// 凍結解除用の管理者パスワード(MD5)
// pukiwiki.php?md5=pass のようにURLに入力し
// MD5にしてからどうぞ。面倒なら以下のように。
// $adminpass = md5("pass");
// 以下は pass のMD5パスワードになってます。
$adminpass = "";

///////////////////////////////////////////////// 
// ページごとの閲覧制限を使用するか
// 0:使用しない 
// 1:使用する
$read_auth = 1; 

/////////////////////////////////////////////////
// 定期バックアップの間隔を時間(hour)で指定します(0で更新毎)
$cycle = 6;

/////////////////////////////////////////////////
// バックアップの最大世代数を指定します
$maxage = 20;

/////////////////////////////////////////////////
// ChaSen, KAKASI による、ページ名の読みの取得 (0:無効,1:有効)
$pagereading_enable = 0;
// ChaSen or KAKASI
//$pagereading_kanji2kana_converter = 'chasen';
$pagereading_kanji2kana_converter = 'kakasi';
// ChaSen/KAKASI との受け渡しに使う漢字コード (UNIX系は EUC、Win系は SJIS が基本)
$pagereading_kanji2kana_encoding = 'EUC';
//$pagereading_kanji2kana_encoding = 'SJIS';
// ChaSen/KAKASI の実行ファイル (各自の環境に合わせて設定)
$pagereading_chasen_path = '/usr/local/bin/chasen';
//$pagereading_chasen_path = 'c:\Program Files\chasen21\chasen.exe';
$pagereading_kakasi_path = '/usr/local/bin/kakasi';
//$pagereading_kakasi_path = 'c:\kakasi\bin\kakasi.exe';
// ページ名読みを格納したページの名前
$pagereading_config_page = ':config/PageReading';

//////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////
// ページHTMLキャッシュ期限（分）0 でキャッシュしない
// ゲストユーザーのみキャッシュ機能が有効になります。
$page_cache_min = 0;

/////////////////////////////////////////////////
// ページID.html というような静的ページのようなURLにする
$use_static_url = 0;

/////////////////////////////////////////////////
// リンクなきトラックバックは受け付けない？
$tb_check_link_to_me = 1;

/////////////////////////////////////////////////
// 全ページで付箋機能を有効にする
$fusen_enable_allpage = 1;

////////以上の設定はXOOPSの管理画面での設定で上書きされます///////

$_cache_file = "cache/config.php";
clearstatcache();
if(file_exists($_cache_file) && is_readable($_cache_file)){
	require($_cache_file);
}
$_cache_file = "cache/adminpass.php";
if(file_exists($_cache_file) && is_readable($_cache_file)){
	require($_cache_file);
}
?>