<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: pukiwiki.ini.php,v 1.41 2005/05/20 00:02:19 nao-pon Exp $
//
// PukiWiki setting file

/////////////////////////////////////////////////
// �ǥ��쥯�ȥ���� �Ǹ�� / ��ɬ�� °���� 777
/////////////////////////////////////////////////
// �ǡ����γ�Ǽ�ǥ��쥯�ȥ�
define("DATA_DIR",XOOPS_WIKI_PATH."/wiki/");
/////////////////////////////////////////////////
// ��ʬ�ե�����γ�Ǽ�ǥ��쥯�ȥ�
define("DIFF_DIR",XOOPS_WIKI_PATH."/diff/");
/////////////////////////////////////////////////
// �Хå����åץե������Ǽ��ǥ��쥯�ȥ�
define("BACKUP_DIR",XOOPS_WIKI_PATH."/backup/");
/////////////////////////////////////////////////
// �ץ饰����ե������Ǽ��ǥ��쥯�ȥ�
define("PLUGIN_DIR",XOOPS_WIKI_PATH."/plugin/");
/////////////////////////////////////////////////
// �ץ饰�����ѥǡ����ե������Ǽ��ǥ��쥯�ȥ�
define("PLUGIN_DATA_DIR",XOOPS_WIKI_PATH."/plugin_data/");
/////////////////////////////////////////////////
// counter file
define("COUNTER_DIR",XOOPS_WIKI_PATH."/counter/");
/////////////////////////////////////////////////
// �ڡ���HTML����å���ǥ��쥯�ȥ�
define("PAGE_CACHE_DIR",XOOPS_WIKI_PATH."/pagehtml/");
/////////////////////////////////////////////////
// ����å���ǥ��쥯�ȥ�
define("CACHE_DIR",XOOPS_WIKI_PATH."/cache/");
/////////////////////////////////////////////////
// �ץ饰�����ѥ���å���ǥ��쥯�ȥ�
define("P_CACHE_DIR",CACHE_DIR."p/");


/////////////////////////////////////////////////
// ���󥯥롼�ɤ���Ĥ����ʿ�
define("PLUGIN_INCLUDE_MAX",4);


/////////////////////////////////////////////////
// Language
if($xoopsConfig['language'] == "japanese"){
	define("LANG","ja");
} else {
	define("LANG","en");
}

/////////////////////////////////////////////////
// ������ե�����ξ�ꡣ
if (empty($_GET['xoops_block'])) 
	define("SKIN_FILE","./skin/pukiwiki.skin.".LANG.".php");
else
	define("SKIN_FILE","./skin/xoops_block.skin.".LANG.".php");
	
/////////////////////////////////////////////////
// ����ե�������ɤ߹���(�Խ����ʤ��Ǥ�������)
if(
	!file_exists(XOOPS_WIKI_PATH."/".LANG.".lng")
	||
	!is_readable(XOOPS_WIKI_PATH."/".LANG.".lng")
	)
	die_message(LANG.".lng(language file) is not found.");
require(XOOPS_WIKI_PATH."/".LANG.".lng");

/////////////////////////////////////////////////
// �ե����륢�åץ��ɴ�Ϣ
// set PHP value to enable file upload
ini_set("file_uploads","1");

// upload dir(must set end of /)
define("UPLOAD_DIR","./attach/");

// max file size for upload on PHP(PHP default 2MB)
ini_set("upload_max_filesize","10M");

// max file size for upload on script of PukiWiki(default 1MB)
define("MAX_FILESIZE",10000000);


/////////////////////////////////////////////////
// index.php �ʤɤ��ѹ��������Υ�����ץ�̾������
// �Ȥ������ꤷ�ʤ��Ƥ�����ʤ�
$script = XOOPS_WIKI_URL.'/index.php';

/////////////////////////////////////////////////
// ��������ڡ�����̾��
$whatsnew = "RecentChanges";

///////////////////////////////////////////////// 
// �������ڡ�����̾�� 
$whatsdeleted = ':RecentDeleted'; 

/////////////////////////////////////////////////
// InterWikiName�ڡ�����̾��
$interwiki = "InterWikiName";

/////////////////////////////////////////////////
// ���������ɽ������Ȥ��κ�����
$maxshow = 80;

///////////////////////////////////////////////// 
// �������κ�����(0�ǵ�Ͽ���ʤ�) 
$maxshow_deleted = 80;
///////////////////////////////////////////////// 
// ������������԰ʳ��ϱ����ػߤˤ���(Yes:1, No:0)
$unvisible_deleted = 0;

/////////////////////////////////////////////////
// �Խ����뤳�ȤΤǤ��ʤ��ڡ�����̾�� , �Ƕ��ڤ�
$cantedit = array( $whatsnew, );

/////////////////////////////////////////////////
// WikiName��*̵����*����
$nowikiname = 0;

/////////////////////////////////////////////////
// AutoLink��ͭ���ˤ�����ϡ�AutoLink�оݤȤʤ�
// �ڡ���̾�κ�û�Х��ȿ������
// AutoLink��̵���ˤ������0
$autolink = 3;

/////////////////////////////////////////////////
// �ڡ�����󥯤�ѥ󤯤��ꥹ�Ȥˤ���
$breadcrumbs = 1;

/////////////////////////////////////////////////
// �ڡ������ɽ�����˥ڡ���̾��[ ����-_ ]�򸫽Ф��Ԥ��Ѵ�����
$convert_d2s = 1;

/////////////////////////////////////////////////
// ñ�측�����˥�����ɤ�ϥ��饤�Ȥ��뤫
$search_word_color = 0;
/////////////////////////////////////////////////
// �ץ�ӥ塼��ɽ������Ȥ��Υơ��֥���طʿ�
$preview_color = "#F5F8FF";
/////////////////////////////////////////////////
// [[�ڡ���]] �ؤΥ�󥯻�[[]]�򳰤���
$strip_link_wall = 1;
/////////////////////////////////////////////////
// �����ڡ�����Ƭʸ������ǥå�����Ĥ��뤫
$list_index = 1;
/////////////////////////////////////////////////
// http:// ��󥯤Υ�����ɥ�̾����(_top,_blank,etc)
$link_target = "_blank";
/////////////////////////////////////////////////
// InterWikiName�Υ�����ɥ�̾����(_top,_blank,etc)
$interwiki_target = "_blank";
/////////////////////////////////////////////////
// URL��󥯻�����˥��ơ������С���
// URL��ɽ���������о�ʸ�����ɽ������
$alias_set_status = 0;

/////////////////////////////////////////////////
// �ꥹ�ȹ�¤�κ��ޡ�����
$_list_left_margin = 10; // �ꥹ�ȤȲ��̺�ü�Ȥδֳ�(px)
$_list_margin = 8;      // �ꥹ�Ȥγ��ش֤δֳ�(px)
$_list_pad_str = ' class="list%d" style="padding-left:%dpx;margin-left:%dpx"';

/////////////////////////////////////////////////
// �ơ��֥�Υޡ�����
$_table_left_margin = 10;		// �ơ��֥뺸�󤻤ξ��β��̺�ü�Ȥδֳ�(px)
$_table_right_margin = 10;	// �ơ��֥뱦�󤻤ξ��β��̱�ü�Ȥδֳ�(px)

/////////////////////////////////////////////////
// �ƥ����ȥ��ꥢ�Υ�����
$cols = "80";
/////////////////////////////////////////////////
// �ƥ����ȥ��ꥢ�ιԿ�
$rows = 20;

/////////////////////////////////////////////////
// �硦�����Ф������ܼ�������󥯤�ʸ��
$top = $_msg_content_back_to_top;
/////////////////////////////////////////////////
// ��Ϣ�ڡ���ɽ���Υڡ���̾�ζ��ڤ�ʸ��
$related_str = " ";
/////////////////////////////////////////////////
// �����롼��Ǥδ�Ϣ�ڡ���ɽ���Υڡ���̾�ζ��ڤ�ʸ��
$rule_related_str = "\n<li>";
/////////////////////////////////////////////////
// ��ʿ���Υ���
$hr = '<hr class="full_hr">';
/////////////////////////////////////////////////
// ʸ��������ľ����ɽ�����륿��
$note_hr = '<hr class="note_hr">';
/////////////////////////////////////////////////
// ��Ϣ�����󥯤���ɽ������(��ô��������ޤ�)
$related_link = 1;
/////////////////////////////////////////////////
// WikiName,BracketName�˷в���֤��ղä���
$show_passage = 1;

/////////////////////////////////////////////////
// Last-Modified �إå�����Ϥ���
$lastmod = 0;

/////////////////////////////////////////////////
// ���եե����ޥå�
$date_format = "Y-m-d";
/////////////////////////////////////////////////
// ����ե����ޥå�
$time_format = "H:i:s";
/////////////////////////////////////////////////
// ��������
$weeklabels = $_msg_week;

/////////////////////////////////////////////////
// RSS �˽��Ϥ���ڡ�����
$rss_max = 15;

/////////////////////////////////////////////////
// �Хå����åפ�Ԥ������ꤷ�ޤ� 0 or 1
$do_backup = 1;
/////////////////////////////////////////////////
// �ڡ������������ݤ˥Хå����åפ⤹�٤ƺ������
$del_backup = 0;
/////////////////////////////////////////////////
// �Хå����åפ��������ڤ�ʸ�������ꤷ�ޤ�
// (�̾�Ϥ��Τޤޤ��ɤ�����ʸ����ǻȤ����ǽ��
// ������С��Ȥ�줽���ˤʤ�ʸ�������ꤹ��)
$splitter = ">>>>>>>>>>";
/////////////////////////////////////////////////
// �ڡ����ι������˥Хå������ɤǼ¹Ԥ���륳�ޥ��(mknmz�ʤ�)
//$update_exec = '/usr/local/bin/mknmz -O /vhosts/www.factage.com/sng/pukiwiki/nmz -L ja -k -K /vhosts/www.factage.com/sng/pukiwiki/wiki';

/////////////////////////////////////////////////
// ���������������˴ޤ�ʤ��ڡ���̾(����ɽ����)
$non_list = "(^(\[\[)?\:|RenameLog|.*\/template)";

/////////////////////////////////////////////////
// �����Ȥ���ڡ������ɤ߹��ߤ�ɽ�������뤫
$load_template_func = 1;

/////////////////////////////////////////////////
// �ڡ���̾�˽��äƼ�ư�ǡ������Ȥ���ڡ������ɤ߹���
$auto_template_func = 1;
$auto_template_name = "template";
$auto_template_rules = array();
$auto_template_rules[] = array('\[\[((.+)\/([^\/]+))\]\]' => '[[\2/'.$auto_template_name.']]');
$auto_template_rules[] = array('\[\[((.+)\/([^\/]+))\]\]' => '[[:'.$auto_template_name.'/\2]]');

/////////////////////////////////////////////////
// ̵���ˤ���Action�ץ饰����ץ饰����
// ����޶��ڤ�ǡ�#��Ĥ����˵���
// (map�ץ饰����ϥڡ�������������ȶ�ü����٤��⤯�ʤ�)
$disabled_plugin = "map";

/////////////////////////////////////////////////
// TrackBack�Ǥ�Ping��URL��л��˽�������ץ饰����
// ����޶��ڤ�ǡ�#��Ĥ����˵���
$notb_plugin = "include,calendar2,showrss,calendar_viewer,bugtrack_list,tracker_list,aws,blogs,google,gimage,newsclip,xoopsblock";

/////////////////////////////////////////////////
// ������Plain�������������˽�������ץ饰����
// ����޶��ڤ�ǡ�#��Ĥ����˵���
$noplain_plugin = "include,calendar2,calendar_viewer,bugtrack_list,tracker_list,ls2,ls,recent,popular,pcomment,contents,tenki,ref,exrate,xoopsblock,attachref,related,whatday,fortune";

/////////////// ParaEdit //////////////////
// ParaEdit ���Ԥ�����ʸ����
//   <input type=hidden value=XXXXX> �ǲ���(CR,LF�ʤ�)���Ѥ��˻��Ѥ���ʸ����
define("_PARAEDIT_SEPARATE_STR", '_PaRaeDiT_');
//if (!defined("_PARAEDIT_SEPARATE_STR")) define("_PARAEDIT_SEPARATE_STR", '_PaRaeDiT_');

// �Խ���󥯤�ʸ���󡦥�����������
//   %s �� URL ������
define("_EDIT_LINK", '<a href="%s"><img style="float:right" src="image/edit.png" alt="Edit" title="Edit" /></a>');

// �Խ���󥯤������ս�����
//   <h2>header</h2> �λ���$1:<h2>, $2:header, $3:</h2> �Ȥʤ�Τ� $link �򹥤��ʾ��˰�ư
// (��)
//  define("_PARAEDIT_LINK_POS", '$1$2$para_link$3'); // </h2>����
    define("_PARAEDIT_LINK_POS", '$para_link$1$2$3'); // <h2>����
//  define("_PARAEDIT_LINK_POS", '$1$2$3$para_link'); // </h2>�θ��
/////////////// ParaEdit //////////////////

///////////////////////////////////////////////// 
// HTTP�ꥯ�����Ȥ˥ץ��������Ф���Ѥ��� 
$use_proxy = 0; 
// proxy �ۥ��� 
$proxy_host = 'proxy.xxx.yyy.zzz'; 
// proxy �ݡ����ֹ� 
$proxy_port = 8080; 
// �ץ��������Ф���Ѥ��ʤ��ۥ��ȤΥꥹ�� 
$no_proxy = array( 
'127.0.0.1', 
'localhost', 
//'192.168.1.0/24', 
//'no-proxy.com', 
); 

/////////////////////////////////////////////////
// �桼������롼��
//
//  ����ɽ���ǵ��Ҥ��Ƥ���������?(){}-*./+\$^|�ʤ�
//  �� \? �Τ褦�˥������Ȥ��Ƥ���������
//  �����ɬ�� / ��ޤ�Ƥ�����������Ƭ����� ^ ��Ƭ�ˡ�
//  ��������� $ ����ˡ�
//
/////////////////////////////////////////////////
// �桼������롼��(ľ�ܥ��������ִ�)
$str_rules = array(
"now\?" => date($date_format,UTIME)." (".$weeklabels[date("w",UTIME)].") ".date($time_format,UTIME),
"date\?" => date($date_format,UTIME),
"time\?" => date($time_format,UTIME),
);

/////////////////////////////////////////////////
// �桼������롼��(����С��Ȼ����ִ���ľ�ܤ��ʤ�)
$line_rules = array(
//"/!([A-Z][a-z]+(?:[A-Z][a-z]+)+)/" => "$1",
"((��|��)(��|��)|����|�Ĥ��|����|�ޤ�|����|�ĥ��|(?<!��)����|�ޥ�)" => "<span style=\"color:white;background-color:white;\">$0</span>", //�ػߥ��
);

/////////////////////////////////////////////////
// �ե������ޡ�������롼��
// $usefacemark = 1�ʤ�ե������ޡ������ִ�����ޤ�
// ʸ�����XD�ʤɤ����ä�����facemark���ִ�����Ƥ��ޤ��Τ�
// ɬ�פΤʤ����� $usefacemark��0�ˤ��Ƥ���������
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

////////�ʲ��������XOOPS�δ������̤Ǥ�����Ǿ�񤭤���ޤ�///////
/////////////////////////////////////////////////
// �ۡ���ڡ����Υ����ȥ�(��ͳ���Ѥ��Ƥ�������)
// RSS �˽��Ϥ�������ͥ�̾
$page_title = "PukiWiki";

/////////////////////////////////////////////////
// �ȥåץڡ�����̾��
$defaultpage = "FrontPage";

/////////////////////////////////////////////////
// �Խ��Ԥ�̾��(��ͳ���Ѥ��Ƥ�������)
$modifier = 'me';

/////////////////////////////////////////////////
// �Խ��ԤΥۡ���ڡ���(��ͳ���Ѥ��Ƥ�������)
$modifierlink = 'http://change me!/';

/////////////////////////////////////////////////
// ��뵡ǽ��ͭ���ˤ��뤫
$function_freeze = 1;

/////////////////////////////////////////////////
// ������Ѥδ����ԥѥ����(MD5)
// pukiwiki.php?md5=pass �Τ褦��URL�����Ϥ�
// MD5�ˤ��Ƥ���ɤ��������ݤʤ�ʲ��Τ褦�ˡ�
// $adminpass = md5("pass");
// �ʲ��� pass ��MD5�ѥ���ɤˤʤäƤޤ���
$adminpass = "";

///////////////////////////////////////////////// 
// �ڡ������Ȥα������¤���Ѥ��뤫
// 0:���Ѥ��ʤ� 
// 1:���Ѥ���
$read_auth = 1; 

/////////////////////////////////////////////////
// ����Хå����åפδֳ֤����(hour)�ǻ��ꤷ�ޤ�(0�ǹ�����)
$cycle = 6;

/////////////////////////////////////////////////
// �Хå����åפκ������������ꤷ�ޤ�
$maxage = 20;

/////////////////////////////////////////////////
// ChaSen, KAKASI �ˤ�롢�ڡ���̾���ɤߤμ��� (0:̵��,1:ͭ��)
$pagereading_enable = 0;
// ChaSen or KAKASI
//$pagereading_kanji2kana_converter = 'chasen';
$pagereading_kanji2kana_converter = 'kakasi';
// ChaSen/KAKASI �Ȥμ����Ϥ��˻Ȥ����������� (UNIX�Ϥ� EUC��Win�Ϥ� SJIS ������)
$pagereading_kanji2kana_encoding = 'EUC';
//$pagereading_kanji2kana_encoding = 'SJIS';
// ChaSen/KAKASI �μ¹ԥե����� (�Ƽ��δĶ��˹�碌������)
$pagereading_chasen_path = '/usr/local/bin/chasen';
//$pagereading_chasen_path = 'c:\Program Files\chasen21\chasen.exe';
$pagereading_kakasi_path = '/usr/local/bin/kakasi';
//$pagereading_kakasi_path = 'c:\kakasi\bin\kakasi.exe';
// �ڡ���̾�ɤߤ��Ǽ�����ڡ�����̾��
$pagereading_config_page = ':config/PageReading';

//////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////
// �ڡ���HTML����å�����¡�ʬ��0 �ǥ���å��夷�ʤ�
// �����ȥ桼�����Τߥ���å��嵡ǽ��ͭ���ˤʤ�ޤ���
$page_cache_min = 0;

/////////////////////////////////////////////////
// �ڡ���ID.html �Ȥ����褦����Ū�ڡ����Τ褦��URL�ˤ���
$use_static_url = 0;

/////////////////////////////////////////////////
// ��󥯤ʤ��ȥ�å��Хå��ϼ����դ��ʤ���
$tb_check_link_to_me = 1;

/////////////////////////////////////////////////
// ���ڡ�������䵵�ǽ��ͭ���ˤ���
$fusen_enable_allpage = 1;

////////�ʾ�������XOOPS�δ������̤Ǥ�����Ǿ�񤭤���ޤ�///////

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