<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: init.php,v 1.41 2005/03/16 12:49:47 nao-pon Exp $
/////////////////////////////////////////////////

// 設定ファイルの場所
define('INI_FILE','./pukiwiki.ini.php');

// PukiWikiMod Version
require('./version.php');

// Copyright.
define("_XOOPS_WIKI_COPYRIGHT", "<strong>\"PukiWikiMod\" "._XOOPS_WIKI_VERSION."</strong> Copyright &copy; 2003-2004 <a href=\"http://ishii.mydns.jp/\">ishii</a> & <a href=\"http://hypweb.net/\">nao-pon</a>. License is <a href=\"http://www.gnu.org/\">GNU/GPL</a>.");

//文字エンコード
define('SOURCE_ENCODING','EUC-JP');

// ini_set
ini_set('mbstring.substitute_character','');
ini_set('error_reporting', 5);

// PATH_INFO の 処理
if (!empty($_SERVER['PATH_INFO']))
{
	$_val = explode("/",$_SERVER['PATH_INFO']);
	if ($_val[1] == "rss" || $_val[1] == "rss10")
	{
		$_GET['cmd'] = $_val[1];
		$_GET['content'] = $_val[2];
		$_GET['p'] = $_val[3];
	}
	unset($_val);
}

define("S_VERSION","1.3.3");
define("S_COPYRIGHT","Based on \"PukiWiki\" by <a href=\"http://pukiwiki.org/\">PukiWiki Developers Team</a>");
define("UTIME",time());
define("HTTP_USER_AGENT",$HTTP_SERVER_VARS["HTTP_USER_AGENT"]);
define("PHP_SELF",$HTTP_SERVER_VARS["PHP_SELF"]);
define("SERVER_NAME",$HTTP_SERVER_VARS["SERVER_NAME"]);
define("MUTIME",getmicrotime());

// PukiWikiMod ディレクトリ名
define("PUKIWIKI_DIR_NAME", $xoopsModule->dirname());

// PukiWikiMod ルートDir
define("XOOPS_WIKI_PATH",XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME);

// PukiWikiMod ルートURL
define("XOOPS_WIKI_URL",XOOPS_URL.'/modules/pukiwiki');

/////////////////////////////////////////////////
// 初期設定 (サーバ変数)
foreach (array('SCRIPT_NAME', 'SERVER_ADMIN', 'SERVER_NAME',
	'SERVER_PORT', 'SERVER_SOFTWARE') as $key) {
	define($key, isset($_SERVER[$key]) ? $_SERVER[$key] : '');
	unset(${$key}, $_SERVER[$key], $HTTP_SERVER_VARS[$key]);
}

/////////////////////////////////////////////////
// 初期設定 (グローバル変数)

$foot_explain = array();	// 脚注
$related      = array();	// 関連するページ
$head_tags    = array();	// <head>内に追加するタグ
$h_excerpt    = "";	// <title>用
$update_exec = "";
$content_id = 0;
$noattach = 0;
$noheader = 0;
$vars['is_rsstop'] = 0;
$wiki_head_keywords = array();

// 設定ファイルの読込
if(!file_exists(INI_FILE)||!is_readable(INI_FILE))
	die_message(INI_FILE." is not found.");
require(INI_FILE);


// XOOPSデータ読み込み
// $anon_writable:編集可能(Yes:1 No:0)
// $X_uid:XOOPSユーザーID
// $X_admin:PukiWikiモジュール管理者(Yes:1 No:0)
// 
$X_admin =0;
$X_uid =0;
$wiki_ads_shown = 0;
// ゲストユーザーの名称
$no_name = $xoopsConfig['anonymous'];

if ( $xoopsUser && is_object($xoopsModule))
{
	//$xoopsModule = XoopsModule::getByDirname("pukiwiki");
	if ( $xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		$X_admin = 1;
	}
	$X_uid = $xoopsUser->uid();
	$X_uname = $xoopsUser->uname();
} else {
	$X_uname = (!empty($_COOKIE["pukiwiki_un"]))? $_COOKIE["pukiwiki_un"] : $no_name;
}

// UserCode with cookie
$X_ucd = (isset($_COOKIE["pukiwiki_uc"]))? $_COOKIE["pukiwiki_uc"] : "";
//user-codeの発行
// ↓この方式はやめた
//if(!$X_ucd){ $X_ucd = substr(crypt(md5(getenv("REMOTE_ADDR").$adminpass.gmdate("Ymd", time()+9*60*60)),'id'),-12); }
if(!$X_ucd || strlen($X_ucd) == 12){ $X_ucd = md5(getenv("REMOTE_ADDR").$adminpass.gmdate("Ymd", time()+9*60*60)); }
setcookie("pukiwiki_uc", $X_ucd, time()+86400*365);//1年間
$X_ucd = substr(crypt($X_ucd,($adminpass)? $adminpass : 'id'),-12);
define('PUKIWIKI_UCD',$X_ucd); //定数化
unset ($X_ucd);

/////////////////////////////////////////////////
// 編集権限セット
if ($X_admin || ($wiki_writable === 0) || (($X_uid && ($wiki_writable < 2)))) {
	$anon_writable = 1;
} else {
	$anon_writable = 0;
}	
// 新規作成権限セット
if ($X_admin || ($wiki_allow_new === 0) || (($X_uid && ($wiki_allow_new < 2)))) {
	define("WIKI_ALLOW_NEWPAGE",TRUE);
} else {
	define("WIKI_ALLOW_NEWPAGE",FALSE);
}	
$wiki_allow_newpage = WIKI_ALLOW_NEWPAGE; //Skin用に残す


if($script == "") {
	$script = (getenv('SERVER_PORT')==443?'https://':('http://')).getenv('SERVER_NAME').(getenv('SERVER_PORT')==80?'':(':'.getenv('SERVER_PORT'))).getenv('SCRIPT_NAME');
}

//$WikiName = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
//$WikiName = '(?<!(!|\w))[A-Z][a-z]+(?:[A-Z][a-z]+)+';
//$WikiName = '(?<!(!|\w))(?:[A-Z][a-z]+){2,}(?!\w)';
//$WikiName = '(?:[A-Z][a-z]+){2,}(?!\w)';
$WikiName = '(?:[A-Z][a-z]+){2,}(?![A-Za-z0-9_])';
$BracketName = '\[\[(?!\/|\.\/|\.\.\/)(:?[^\s\]#&<>":]+:?)\]\](?<!\/\]\])';
$InterWikiName = "\[\[(\[*[^\s\]]+?\]*):(\[*[^>\]]+?\]*)\]\]";

/////////////////////////////////////////////////
// 外部からくる変数のチェック

// Prohibit $_GET attack
foreach (array('msg', 'pass') as $key) {
	if (isset($_GET[$key])) die_message("Sorry, already reserved: $key=");
}

// Expire risk
unset($HTTP_GET_VARS, $HTTP_POST_VARS);	//, 'SERVER', 'ENV', 'SESSION', ...
unset($_REQUEST);	// Considered harmful

// Remove null character etc.
$_GET    = input_filter($_GET);
$_POST   = input_filter($_POST);
$_COOKIE = input_filter($_COOKIE);

// 文字コード変換 ($_POST)
// <form> で送信された文字 (ブラウザがエンコードしたデータ) のコードを変換
// POST method は常に form 経由なので、必ず変換する
//
if (isset($_POST['encode_hint']) && $_POST['encode_hint'] != '') {
	// html.php の中で、<form> に encode_hint を仕込んでいるので、
	// encode_hint を用いてコード検出する。
	// 全体を見てコード検出すると、機種依存文字や、妙なバイナリ
	// コードが混入した場合に、コード検出に失敗する恐れがある。
	$encode = mb_detect_encoding($_POST['encode_hint']);
	mb_convert_variables(SOURCE_ENCODING, $encode, $_POST);

} else if (isset($_POST['charset']) && $_POST['charset'] != '') {
	// TrackBack Ping で指定されていることがある
	// うまくいかない場合は自動検出に切り替え
	if (mb_convert_variables(SOURCE_ENCODING,
	    $_POST['charset'], $_POST) !== $_POST['charset']) {
		mb_convert_variables(SOURCE_ENCODING, 'auto', $_POST);
	}

} else if (! empty($_POST)) {
	// 全部まとめて、自動検出／変換
	mb_convert_variables(SOURCE_ENCODING, 'auto', $_POST);
}

// 文字コード変換 ($_GET)
// GET method は form からの場合と、<a href="http://script/?key=value> の場合がある
// <a href...> の場合は、サーバーが rawurlencode しているので、コード変換は不要
if (isset($_GET['encode_hint']) && $_GET['encode_hint'] != '')
{
	// form 経由の場合は、ブラウザがエンコードしているので、コード検出・変換が必要。
	// encode_hint が含まれているはずなので、それを見て、コード検出した後、変換する。
	// 理由は、post と同様
	$encode = mb_detect_encoding($_GET['encode_hint']);
	mb_convert_variables(SOURCE_ENCODING, $encode, $_GET);
}


/////////////////////////////////////////////////
// QUERY_STRINGを取得

// cmdもpluginも指定されていない場合は、QUERY_STRINGを
// ページ名かInterWikiNameであるとみなす
$arg = '';
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
	$arg = $_SERVER['QUERY_STRING'];
} else if (isset($_SERVER['argv']) && count($_SERVER['argv'])) {
	$arg = $_SERVER['argv'][0];
}
$arg = input_filter($arg); // \0 除去

// unset QUERY_STRINGs
foreach (array('QUERY_STRING', 'argv', 'argc') as $key) {
	unset(${$key}, $_SERVER[$key], $HTTP_SERVER_VARS[$key]);
}
// $_SERVER['REQUEST_URI'] is used at func.php NOW
unset($REQUEST_URI, $HTTP_SERVER_VARS['REQUEST_URI']);

// mb_convert_variablesのバグ(?)対策: 配列で渡さないと落ちる
$arg = array($arg);
mb_convert_variables(SOURCE_ENCODING, 'auto', $arg);
$arg = $arg[0];

/////////////////////////////////////////////////
// QUERY_STRINGを分解してコード変換し、$_GET に上書き

// URI を urlencode せずに入力した場合に対処する
$matches = array();
foreach (explode('&', $arg) as $key_and_value) {
	if (preg_match('/^([^=]+)=(.+)/', $key_and_value, $matches) &&
	    mb_detect_encoding($matches[2]) != 'ASCII') {
		$_GET[$matches[1]] = $matches[2];
	}
}
unset($matches);

/////////////////////////////////////////////////
// GET, POST, COOKIE

$get    = & $_GET;
$post   = & $_POST;
$cookie = & $_COOKIE;

// GET + POST = $vars
if (empty($_POST)) {
	$vars = & $_GET;  // Major pattern: Read-only access via GET
} else if (empty($_GET)) {
	$vars = & $_POST; // Minor pattern: Write access via POST etc.
} else {
	$vars = array_merge($_GET, $_POST); // Considered reliable than $_REQUEST
}

// 入力チェック: cmd, plugin の文字列は英数字以外ありえない
foreach(array('cmd', 'plugin') as $var) {
	if (array_key_exists($var, $vars) &&
	    ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $vars[$var])) {
		unset($get[$var], $post[$var], $vars[$var]);
	}
}

// 整形: page, add_bracket()
if (isset($vars['page'])) {
	$get['page'] = $post['page'] = $vars['page']  = add_bracket($vars['page']);
} else {
	$get['page'] = $post['page'] = $vars['page'] = '';
}

// 整形: msg, 改行を取り除く
if (isset($vars['msg'])) {
	$get['msg'] = $post['msg'] = $vars['msg'] = str_replace("\r", '', $vars['msg']);
}

// 後方互換性 (?md5=...)
if (isset($vars['md5']) && $vars['md5'] != '') {
	$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'md5';
}

// pwm_ping受信
if (isset($vars['pwm_ping']))
{
	$vars['plugin'] = "tb";
	$vars['tb_id'] = $vars['pwm_ping'];
}


// command 判定
if (arg_check("preview") || $post["preview"] || $post["template"]) $vars['cmd'] = "preview";
if (arg_check("write") || $post["write"]) $vars['cmd'] = "write";

// cmd,plugin,pgid が指定されていない場合は、QUERY_STRINGをページ名かInterWikiNameであるとみなす
if (! isset($vars['cmd']) && ! isset($vars['plugin']) && ! isset($vars['pgid'])) {

	$get['cmd']  = $post['cmd']  = $vars['cmd']  = 'read';
	
	// & 以降を除去(SID自動付加環境対策)
	$arg = preg_replace("/([^&]+)(&.*)?/","$1",$arg);
	
	$arg = rawurldecode($arg);
	$arg = input_filter($arg);
	
	// _PGID の場合
	if (preg_match("/^_([\d]+)$/",$arg,$key))
		$vars['pgid'] = $key[1];
	else
	{
		if ($arg == '') $arg = $defaultpage;
		$arg = add_bracket($arg);
		$get['page'] = $post['page'] = $vars['page'] = $arg;
	}
}

// idでのアクセス
$pgid = 0;
if (isset($vars['pgid']))
{
	$vars['pgid'] = (int)$vars['pgid'];
	$vars['page'] = get_pgname_by_id($vars['pgid']);
	if (is_page($vars['page']))
	{
		$vars['cmd'] = "read";
		$pgid = $vars['pgid'];
	}
	else
	{
		header("Location: ".XOOPS_WIKI_URL."/");
		exit;
	}
}

// XOOPS Mudule id
if (!$pgid) $pgid = get_pgid_by_name($vars["page"]);
$pukiwiki_mid = $xoopsModule->mid();

/////////////////////////////////////////////////
// 文字実体参照　正規表現
$entity_pattern = trim(join('',file(CACHE_DIR.'entities.dat')));

/////////////////////////////////////////////////
// ユーザ(システム)定義ルール(コンバート時に置換、直接しない)
$line_rules = array_merge(array(
"COLOR\(([^\(\)]*)\){([^}]*)}" => "<span style=\"color:\\1\">\\2</span>",
"SIZE\(([^\(\)]*)\){([^}]*)}" => "<span style=\"font-size:\\1px;display:inline-block;line-height:130%;text-indent:0px\">\\2</span>",
"COLOR\(([^\(\)]*)\):((?:(?!COLOR\([^\)]+\)\:).)*)" => "<span style=\"color:\\1\">\\2</span>",
"SIZE\(([^\(\)]*)\):((?:(?!SIZE\([^\)]+\)\:).)*)" => "<span class=\"size\\1\">\\2</span>",
"^LEFT:((?:(?!LEFT\:).)*)" => "<div style=\"text-align:left\">\\1</div>",
"^CENTER:((?:(?!CENTER\:).)*)" => "<div style=\"text-align:center\">\\1</div>",
"^RIGHT:((?:(?!RIGHT\:).)*)" => "<div style=\"text-align:right\">\\1</div>",
"%%((?:(?!%%).)*)%%" => "<del>\\1</del>",
"'''((?:(?!''').)*)'''" => "<em>\\1</em>",
"''((?:(?!'').)*)''" => "<strong>\\1</strong>",
"~((?:<\\/[a-zA-Z]+>)*)$" => "\\1<br />", /* 行末にチルダは改行 */
"&amp;aname\(([A-Za-z][\w\-]*)\);" => "<a id=\"\\1\" name=\"\\1\"></a>",
"^BR-ALL:" => "<br clear=all />",
"&amp;br-all;" => "<br clear=all />",
"&amp;br;" => "<br />",
'&amp;(#[0-9]+|#x[0-9a-f]+|'.$entity_pattern.');'=>'&$1;' /* 実体参照 */
),$line_rules);

if($usefacemark) {
  $line_rules = array_merge($line_rules,$facemark_rules);
}

$note_id = 1;
$foot_explain = array();

// 変数のチェック
if(php_sapi_name()=='cgi' && !preg_match("/^http:\/\/[-a-zA-Z0-9\@:;_.]+\//",$script))
	die_message("please set '\$script' in ".INI_FILE);


// 設定ファイルの変数チェック
$wrong_ini_file = "";
if(!isset($rss_max)) $wrong_ini_file .= '$rss_max ';
if(!isset($page_title)) $wrong_ini_file .= '$page_title ';
if(!isset($note_hr)) $wrong_ini_file .= '$note_hr ';
if(!isset($related_link)) $wrong_ini_file .= '$related_link ';
if(!isset($show_passage)) $wrong_ini_file .= '$show_passage ';
if(!isset($rule_related_str)) $wrong_ini_file .= '$rule_related_str ';
if(!isset($load_template_func)) $wrong_ini_file .= '$load_template_func ';
if(!defined("LANG")) $wrong_ini_file .= 'LANG ';
if(!defined("PLUGIN_DIR")) $wrong_ini_file .= 'PLUGIN_DIR ';

if(!is_writable(DATA_DIR))
	die_message("DATA_DIR is not found or not writable.");
if(!is_writable(DIFF_DIR))
	die_message("DIFF_DIR is not found or not writable.");
if($do_backup && !is_writable(BACKUP_DIR))
	die_message("BACKUP_DIR is not found or not writable.");
if($wrong_ini_file)
	die_message("The setting file runs short of information.<br>The version of a setting file may be old.<br><br>These option are not found : $wrong_ini_file");

//Defaultページ名(WikiName以外に対応)
$defaultpage = add_bracket($defaultpage);

if(!file_exists(get_filename(encode($defaultpage))))
	touch(get_filename(encode($defaultpage)));
if(!file_exists(get_filename(encode($whatsnew))))
	touch(get_filename(encode($whatsnew)));
if(!file_exists(get_filename(encode($interwiki))))
	touch(get_filename(encode($interwiki)));

$ins_date = date($date_format,UTIME);
$ins_time = date($time_format,UTIME);
$ins_week = "(".$weeklabels[date("w",UTIME)].")";

$now = "$ins_date $ins_week $ins_time";

// ページ名エイリアス取得
$pagename_aliases =get_pagename_aliases();

// 共通リンクディレクトリ展開
$wiki_common_dirs = preg_split("/\s+/",trim($wiki_common_dirs));
sort($wiki_common_dirs,SORT_STRING);

// catch/config の設定を定数に
define('WIKI_USER_DIR',$wiki_user_dir);
define('PCMT_PAGE',add_bracket($pcmt_page_name));
define('PAGE_CACHE_MIN',$page_cache_min);

// XOOPS THEME スタイルシート
define('WIKI_THEME_CSS',(file_exists(XOOPS_THEME_PATH.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'))?
	XOOPS_THEME_URL.'/'.$xoopsConfig['theme_set'].'/pukiwiki.css'
	:
	"");

// 名前欄の暫定値(コンバート時にユーザー名に置換される)
define('WIKI_NAME_DEF','_gEsTnAmE_');
?>