<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: init.php,v 1.13 2003/10/13 14:20:24 nao-pon Exp $
/////////////////////////////////////////////////

// 設定ファイルの場所
define("INI_FILE","./pukiwiki.ini.php");

//** 初期設定 **

define("_XOOPS_WIKI_VERSION", "0.07");
define("_XOOPS_WIKI_COPYRIGHT", "<strong>\"PukiWikiMod\" "._XOOPS_WIKI_VERSION."</strong> Copyright &copy; 2003 <a href=\"http://ishii.s26.xrea.com/\">ishii</a> & <a href=\"http://hypweb.net/\">nao-pon</a>. License is <a href=\"http://www.gnu.org/\">GNU/GPL</a>.");
//文字エンコード
define('SOURCE_ENCODING','EUC-JP');

ini_set('error_reporting', 5);
define("S_VERSION","1.3.3");
define("S_COPYRIGHT","Based on \"PukiWiki\" by <a href=\"http://pukiwiki.org/\">PukiWiki Developers Team</a>");
define("UTIME",time());
define("HTTP_USER_AGENT",$HTTP_SERVER_VARS["HTTP_USER_AGENT"]);
define("PHP_SELF",$HTTP_SERVER_VARS["PHP_SELF"]);
define("SERVER_NAME",$HTTP_SERVER_VARS["SERVER_NAME"]);
define("MUTIME",getmicrotime());

define("CACHE_DIR","./cache/");

if($script == "") {
	$script = (getenv('SERVER_PORT')==443?'https://':('http://')).getenv('SERVER_NAME').(getenv('SERVER_PORT')==80?'':(':'.getenv('SERVER_PORT'))).getenv('SCRIPT_NAME');
}

//$WikiName = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
$WikiName = '(?<!(!|\w))[A-Z][a-z]+(?:[A-Z][a-z]+)+';
$BracketName = '\[\[(?!\/|\.\/|\.\.\/)(:?[^\s\]#&<>":]+:?)\]\](?<!\/\]\])';
$InterWikiName = "\[\[(\[*[^\s\]]+?\]*):(\[*[^>\]]+?\]*)\]\]";

/**************
$LinkPattern = "/( (?# <1>:all)
	(?# url )
	(?:\[\[([^\]]+):)?           (?#<2>:alias)
		(\[)?                      (?#<3>:open bracket)
			((?:(?:https?|ftp|news):\/\/|\.\.?\/)(?:[!~*'();\/?:\@&=+\$,%#\w.-]+)) (?#<4>:url)
		(?(3)\s((?:(?!\]\]).)+)\]) (?#<5>:alias, close bracket if <3>)
	(?(2)\]\])                   (?# close bracket if <2>)
	|
	(?# mailto)
	(?:\[\[((?:(?!\]\]).)+)      (?#<6>alias)
		(?:>|:|&gt;))?
		([\w.-]+@[\w-]+\.[\w.-]+)  (?#<7>:mailto>)
	(?(6)\]\])                   (?# close bracket if <6>)
	|
	(?# BracketName or InterWikiName)
	(\[\[                        (?#<8>:all)
		(?:
			(\[\[)?                  (?#<9>:open bracket)
			((?:(?!\]\]).)+)         (?#<10>:alias)
			(?:(?:&gt;)|>)           (?# '&gt;' or '>')
		)?
		(?:
			(\[\[)?                  (?#<11>:open bracket)
			(:?[^\s\[\]#&<>\":]*?:?) (?#<12>BracketName)
			((?(9)\]\]|(?(11)\]\])))?(?#<13>:close bracket if <9> or <11>)
			(\#(?:[a-zA-Z][\w-]*)?)? (?#<14>anchor)
			(?(13)|(?(9)\]\]|(?(11)\]\]))) (?#close bracket if <9> or <11> but !<13>)
			|
			(\[\[)?                  (?#<15>:open bracket)
			(\[*?[^\s\]]+?\]*?)      (?#<16>InterWiki)
			((?(9)\]\]|(?(15)\]\])))?(?#<17>:close bracket if <9> or <15>)
			(\:.*?)                  (?#<18>param)
			(?(17)|(?(9)\]\]|(?(15)\]\]))) (?#close bracket if <9> or <15> but !<17>)
		)?
	\]\])
	|
	(?# WikiNmae)
	($WikiName)                  (?#<19>:all)
	|
	(?# escape)
	(\x1c[^\x1c\x1d]*\x1d)       (?#<20>:all)
	)/x";
****************/

//** 入力値の整形 **

$cookie = $HTTP_COOKIE_VARS;

if(get_magic_quotes_gpc())
{
	foreach($HTTP_GET_VARS as $key => $value) {
		$get[$key] = stripslashes($HTTP_GET_VARS[$key]);
	}
	foreach($HTTP_POST_VARS as $key => $value) {
		if (is_array($HTTP_POST_VARS[$key])){
			foreach($HTTP_POST_VARS[$key] as $key2 => $value2){
				$post[$key][$key2] =stripslashes($HTTP_POST_VARS[$key][$key2]);
			}
		} else {
			$post[$key] = stripslashes($HTTP_POST_VARS[$key]);
		}
	}
	foreach($HTTP_COOKIE_VARS as $key => $value) {
		$cookie[$key] = stripslashes($HTTP_COOKIE_VARS[$key]);
	}
}
else {
	$post = $HTTP_POST_VARS;
	$get = $HTTP_GET_VARS;
}

// 外部からくる変数をサニタイズ
$get    = sanitize_null_character($get);
$post   = sanitize_null_character($post);
$cookie = sanitize_null_character($cookie);

if($post["msg"])
{
	$post["msg"] = preg_replace("/((\x0D\x0A)|(\x0D)|(\x0A))/","\n",$post["msg"]);
}
//if($get["page"]) $get["page"] = add_bracket(rawurldecode($get["page"]));
//if($post["page"]) $post["page"] = add_bracket(rawurldecode($post["page"]));
if(isset($get["page"])) $get["page"] = rawurldecode($get["page"]);
if(isset($post["page"])) $post["page"] = rawurldecode($post["page"]);
if(isset($post["word"])) $post["word"] = rawurldecode($post["word"]);
if(isset($get["word"])) $get["word"] = rawurldecode($get["word"]);

$vars = array_merge($post,$get);

if ($vars['cmd'] == "edit"){
	if(isset($get["page"])) $get["page"] = add_bracket($get["page"]);
	if(isset($post["page"])) $post["page"] = add_bracket($post["page"]);
	if(isset($vars["page"])) $vars["page"] = add_bracket($vars["page"]);
}

$arg = rawurldecode((getenv('QUERY_STRING') != '')?
		    getenv('QUERY_STRING') :
		    $HTTP_SERVER_VARS["argv"][0]);

$arg = sanitize_null_character($arg);

//** 初期処理 **
$update_exec = "";
$content_id = 0;
$noattach = 0;
$noheader = 0;


// 設定ファイルの読込
if(!file_exists(INI_FILE)||!is_readable(INI_FILE))
	die_message(INI_FILE." is not found.");
require(INI_FILE);

if(!file_exists(LANG.".lng")||!is_readable(LANG.".lng"))
	die_message(LANG.".lng(language file) is not found.");

/////////////////////////////////////////////////
// 文字実体参照　正規表現
$entity_pattern = '(?=[a-zA-Z0-9]{2,8})(?:A(?:Elig|acute|circ|grave|lpha|ring|tilde|uml)|Beta|C(?:cedil|hi)|D(?:agger|elta)|E(?:TH|acute|circ|grave|psilon|ta|uml)|Gamma|I(?:acute|circ|grave|ota|uml)|Kappa|Lambda|Mu|N(?:tilde|u)|O(?:Elig|acute|circ|grave|m(?:ega|icron)|slash|tilde|uml)|P(?:hi|i|rime|si)|Rho|S(?:caron|igma)|T(?:HORN|au|heta)|U(?:acute|circ|grave|psilon|uml)|Xi|Y(?:acute|uml)|Zeta|a(?:acute|c(?:irc|ute)|elig|grave|l(?:efsym|pha)|mp|n(?:d|g)|pos|ring|symp|tilde|uml)|b(?:dquo|eta|rvbar|ull)|c(?:ap|cedil|e(?:dil|nt)|hi|irc|lubs|o(?:ng|py)|rarr|u(?:p|rren))|d(?:Arr|a(?:gger|rr)|e(?:g|lta)|i(?:ams|vide))|e(?:acute|circ|grave|m(?:pty|sp)|nsp|psilon|quiv|t(?:a|h)|u(?:ml|ro)|xist)|f(?:nof|orall|ra(?:c(?:1(?:2|4)|34)|sl))|g(?:amma|e|t)|h(?:Arr|arr|e(?:arts|llip))|i(?:acute|circ|excl|grave|mage|n(?:fin|t)|ota|quest|sin|uml)|kappa|l(?:Arr|a(?:mbda|ng|quo|rr)|ceil|dquo|e|floor|o(?:wast|z)|rm|s(?:aquo|quo)|t)|m(?:acr|dash|i(?:cro|ddot|nus)|u)|n(?:abla|bsp|dash|e|i|ot(?:in)?|sub|tilde|u)|o(?:acute|circ|elig|grave|line|m(?:ega|icron)|plus|r(?:d(?:f|m))?|slash|ti(?:lde|mes)|uml)|p(?:ar(?:a|t)|er(?:mil|p)|hi|i(?:v)?|lusmn|ound|r(?:ime|o(?:d|p))|si)|quot|r(?:Arr|a(?:dic|ng|quo|rr)|ceil|dquo|e(?:al|g)|floor|ho|lm|s(?:aquo|quo))|s(?:bquo|caron|dot|ect|hy|i(?:gma(?:f)?|m)|pades|u(?:b(?:e)?|m|p(?:1|2|3|e)?)|zlig)|t(?:au|h(?:e(?:re4|ta(?:sym)?)|insp|orn)|i(?:lde|mes)|rade)|u(?:Arr|a(?:cute|rr)|circ|grave|ml|psi(?:h|lon)|uml)|weierp|xi|y(?:acute|en|uml)|z(?:eta|w(?:j|nj)))';

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
$user_rules = array_merge($str_rules,$line_rules);

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

// catch/config の設定を定数に
define('WIKI_USER_DIR',$wiki_user_dir);
define('PCMT_PAGE',add_bracket($pcmt_page_name));

?>
