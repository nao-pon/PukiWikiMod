<?php
// $Id: index.php,v 1.40 2006/03/08 06:55:05 nao-pon Exp $

include('../../../include/cp_header.php');

if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname("pukiwiki");
	if ( !$xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		redirect_header(XOOPS_URL."/",3,_NOPERM);
		exit();
	}
} else {
	redirect_header(XOOPS_URL."/",3,_NOPERM);
	exit();
}

define("UTIME",time());

// PukiWikiMod ディレクトリ名
define("PUKIWIKI_DIR_NAME", $xoopsModule->dirname());

// PukiWikiMod ルートDir
define("XOOPS_WIKI_PATH",XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME);

// PukiWikiMod ルートURL
define("XOOPS_WIKI_URL",XOOPS_URL.'/modules/'.PUKIWIKI_DIR_NAME);

// class HypCommonFunc
if(!class_exists('HypCommonFunc')){include(XOOPS_WIKI_PATH."/include/hyp_common_func.php");}

// config.php がない場合(初期導入時)
if (file_exists(XOOPS_WIKI_PATH."/cache/config.php")) { @touch(XOOPS_WIKI_PATH."/cache/config.php"); }

include(XOOPS_WIKI_PATH."/pukiwiki.ini.php");
@include(XOOPS_WIKI_PATH."/cache/config.php");
include(XOOPS_WIKI_PATH."/html.php");
include(XOOPS_WIKI_PATH."/file.php");
include(XOOPS_WIKI_PATH."/func.php");
define("_AM_WIKI_CONFIG_FILE", "../cache/config.php");
define("_AM_WIKI_ADMIN_PASS", "../cache/adminpass.php");
define("_AM_WIKI_CSS_FILE", XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/cache/css.css");
define("_WIKI_AM_TEXT_SIZE", "50");

function wiki_synchronize_allusers($id="",$type="all users")
{
	global $xoopsUser,$xoopsModule;
	//include_once XOOPS_ROOT_PATH."/modules/system/admin/users/users.php";
	global $xoopsDB;
	switch($type) {
	case 'user':
		// Array of tables from which to count 'posts'
		$tables = array();
		// Count comments (approved only: com_status == XOOPS_COMMENT_ACTIVE)
		include_once XOOPS_ROOT_PATH . '/include/comment_constants.php';
		$tables[] = array ('table_name' => 'xoopscomments', 'uid_column' => 'com_uid', 'criteria' => new Criteria('com_status', XOOPS_COMMENT_ACTIVE));
		// Count forum posts
		$tables[] = array ('table_name' => 'bb_posts', 'uid_column' => 'uid');
		// PukiWikiMod
		$tables[] = array ('table_name' => 'pukiwikimod_pginfo', 'uid_column' => 'uid');

		$total_posts = 0;
		foreach ($tables as $table) {
			$criteria = new CriteriaCompo();
			$criteria->add (new Criteria($table['uid_column'], $id));
			if (!empty($table['criteria'])) {
				$criteria->add ($table['criteria']);
			}
			$sql = "SELECT COUNT(*) AS total FROM ".$xoopsDB->prefix($table['table_name']) . ' ' . $criteria->renderWhere();
			if ( $result = $xoopsDB->query($sql) ) {
				if ($row = $xoopsDB->fetchArray($result)) {
					$total_posts = $total_posts + $row['total'];
				}
			}
		}
		$sql = "UPDATE ".$xoopsDB->prefix("users")." SET posts = $total_posts WHERE uid = $id";
		if ( !$result = $xoopsDB->query($sql) )
		{
			exit(sprintf(_AM_CNUUSER %s ,$id));
		}
		//echo $sql."<br>";
		
		break;
	case 'all users':
		$sql = "SELECT uid FROM ".$xoopsDB->prefix("users")."";
		if ( !$result = $xoopsDB->query($sql) ) {
			exit(_AM_CNGUSERID);
		}
		while ($row = $xoopsDB->fetchArray($result)) {
			$id = $row['uid'];
			wiki_synchronize_allusers($id, "user");
			//echo 'synchronize($id, "user")<br>';
		}
		break;
	default:
		break;
	}
	
	//redirect_header("./index.php",1,_AM_DBUPDATED);
	//exit();
}

function changePermit($_target_dir){
	global $xoopsModule;
	if ($dir = @opendir($_target_dir)) {
		while($file = readdir($dir)) {
			if($file == ".." || $file == ".") continue;
			chmod(trim($_target_dir."$file"), 0666);
		}
		closedir($dir);
	}
	redirect_header("./index.php",1,_AM_DBUPDATED);
	exit();
}

function writeConfig(){
	global $xoopsConfig, $_POST, $xoopsModule;
	
	$badkeys = array('GLOBALS', '_SESSION', 'HTTP_SESSION_VARS', '_GET', 'HTTP_GET_VARS', '_POST', 'HTTP_POST_VARS', '_COOKIE', 'HTTP_COOKIE_VARS', '_REQUEST', '_SERVER', 'HTTP_SERVER_VARS', '_ENV', 'HTTP_ENV_VARS', '_FILES', 'HTTP_POST_FILES', 'xoopsDB', 'xoopsUser', 'xoopsUserId', 'xoopsUserGroups', 'xoopsUserIsAdmin', 'xoopsConfig', 'xoopsOption', 'xoopsModule', 'xoopsModuleConfig');
	foreach($_POST as $k => $v)
	{
		if (in_array($k, $badkeys)) continue;
		$$k = str_replace("'","\'",$v);
	}

	$file = fopen(_AM_WIKI_CONFIG_FILE, "wb");
	
	$gids = implode(",",$gids);
	$aids = implode(",",$aids);
	$freeze = (isset($freeze))? 1 : 0;
	$f_trackback = (int)$f_trackback;
	$f_cycle = (int)$f_cycle;
	$f_maxage = (int)$f_maxage;
	$f_page_cache_min = (int)$f_page_cache_min;
	$f_use_static_url = (int)$f_use_static_url;
	$f_jp_pagereading = (int)$f_jp_pagereading;
	$fixed_heading_anchor = (int)$fixed_heading_anchor;
	$f_countup_xoops = (int)$f_countup_xoops;
	$f_tb_check_link_to_me = (int)$f_tb_check_link_to_me;
	$f_fusen_enable_allpage = (int)$f_fusen_enable_allpage;
	$f_kanji2kana_encoding = (int)$f_kanji2kana_encoding;
	
	if ($f_jp_pagereading == 2)
	{
		$f_pagereading_kanji2kana_converter = 'kakasi';
		$f_jp_pagereading = 1;
	}
	elseif ($f_jp_pagereading == 1)
	{
		$f_pagereading_kanji2kana_converter = 'chasen';
		$f_jp_pagereading = 1;
	}
	else
	{
		$f_pagereading_kanji2kana_converter = 'kakasi';
		$f_jp_pagereading = 0;
	}
	if ($f_kanji2kana_encoding == 0)
		$f_kanji2kana_encoding = 'EUC';
	else
		$f_kanji2kana_encoding = 'SJIS';
	$f_trackback_encoding = (int)$f_trackback_encoding;
	if (!$f_trackback_encoding)
		$trackback_encoding = "EUC-JP";
	else if ($f_trackback_encoding === 1)
		$trackback_encoding = "UTF-8";
	else
		$trackback_encoding = "SJIS";
	
	$content = "";
	$content .= "<?php";
	$content .= "
	\$pukiwiki_dir_name = '".PUKIWIKI_DIR_NAME."';
	\$page_title = '$wiki_site_name';
	\$defaultpage = '$wiki_defaultpage';
	\$modifier = '$wiki_modifier';
	\$modifierlink = '$wiki_modifierlink';
	\$wiki_writable = $wiki_anon_writable;
	\$hide_navi = $wiki_hide_navi;
	\$wiki_mail_sw = $_wiki_mail_sw;
	\$function_freeze = $wiki_function_freeze;
	\$defvalue_freeze = $freeze;
	\$defvalue_gids = \"$gids\";
	\$defvalue_aids = \"$aids\";
	\$wiki_allow_new = $wiki_allow_new;
	\$read_auth = $wiki_function_unvisible;
	\$cycle = $f_cycle;
	\$maxage = $f_maxage;
	\$wiki_user_dir = '$f_wiki_user_dir';
	\$pcmt_page_name = '$f_pcmt_page_name';
	\$pagereading_enable = $f_jp_pagereading;
	\$pagereading_kanji2kana_converter = '$f_pagereading_kanji2kana_converter';
	\$pagereading_kanji2kana_encoding = '$f_kanji2kana_encoding';
	\$pagereading_chasen_path = '$f_pagereading_chasen_path';
	\$pagereading_kakasi_path = '$f_pagereading_kakasi_path';
	\$pagereading_config_page = '$f_pagereading_config_page';
	\$trackback = $f_trackback;
	\$page_cache_min = $f_page_cache_min;
	\$use_static_url = $f_use_static_url;
	\$update_ping_to = '$update_ping_to';
	\$wiki_common_dirs = '$wiki_common_dirs';
	\$fixed_heading_anchor = $fixed_heading_anchor;
	\$trackback_encoding = '$trackback_encoding';
	\$countup_xoops = $f_countup_xoops;
	\$tb_check_link_to_me = $f_tb_check_link_to_me;
	\$fusen_enable_allpage = $f_fusen_enable_allpage;
	\$pukiwiki_dirs = array(
		'wiki' => '".DATA_DIR."',
	);
	";
	$content .= "\n?>";

	fwrite($file, str_replace("\r","",$content));
	fclose($file);

	if(!empty($wiki_adminpass)){
		$wiki_adminpass = md5($wiki_adminpass);
		$file = fopen(_AM_WIKI_ADMIN_PASS, "wb");
		$content = "<?php\n\$adminpass = '$wiki_adminpass';\n?>";
		fwrite($file, $content);
		fclose($file);
	}
	
	$wiki_css = trim($wiki_css);
	if ($wiki_css)
	{
		$file = fopen(_AM_WIKI_CSS_FILE, "wb");
		fwrite($file, $wiki_css);
		fclose($file);
	}
	else
	{
		@unlink(_AM_WIKI_CSS_FILE);
	}

	redirect_header("./index.php",1,_AM_DBUPDATED);
	exit();
}

function checkPermit(){
	global $xoopsModule;
	$wiki_error = array();
	$_check_list = array(XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/attach/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/attach/s/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/backup/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/cache/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/cache/p/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/counter/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/diff/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/pagehtml/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/plugin_data/painter/tmp/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/trackback/",
		XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/wiki/");
	
	/*
	if ($dir = @opendir(XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/wiki/")) {
		while($file = readdir($dir)) {
			if($file == ".." || $file == ".") continue;
			array_push($_check_list, XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/wiki/".$file);
		}
		closedir($dir);
	}
	*/

	foreach($_check_list as $dir){
		if(!is_writable($dir)){
			$wiki_error[] = _AM_WIKI_ERROR01."=> ".$dir;
		}
	}

	$_alert_icon = "<img src='../image/alert.gif'>&nbsp;";
	foreach($wiki_error as $er_msg){
		echo "$_alert_icon$er_msg<br />";
	}
	
	// need trackBack data convert
	$need_trackBack_data_convert = 0;
	if ($dir = @opendir(XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/trackback/")) {
		while($file = readdir($dir)) {
			if($file == ".." || $file == ".") continue;
			if (preg_match("/[a-f0-9]{32}\.(ping|txt)/",$file))
			{
				$need_trackBack_data_convert = 1;
				break;
			}
		}
		closedir($dir);
	}
	if ($need_trackBack_data_convert)
		echo "<p>$_alert_icon<a href='".XOOPS_URL."/modules/".PUKIWIKI_DIR_NAME."/?plugin=tb_sendedping_conv'><span style='color:red;font-weight:bold;font-size:160%;'>"._AM_WIKI_ERROR02."</span><a></p>";
}

function displayForm(){
	global $xoopsConfig, $xoopsModule, $xoopsUser, $X_admin, $X_uid;
	global $defaultpage, $modifier, $modifierlink, $function_freeze, $adminpass, $wiki_writable, $hide_navi, $wiki_mail_sw, $_btn_freeze_enable ,$defvalue_freeze,$defvalue_gids,$defvalue_aids, $wiki_allow_new, $read_auth, $cycle, $maxage, $pcmt_page_name,$wiki_user_dir,$pagereading_enable,$pagereading_kanji2kana_converter,$pagereading_kanji2kana_encoding,$pagereading_chasen_path,$pagereading_kakasi_path,$pagereading_config_page,$page_title,$trackback,$page_cache_min,$use_static_url,$update_ping_to,$wiki_common_dirs,$fixed_heading_anchor,$trackback_encoding,$countup_xoops,$use_xoops_comments,$tb_check_link_to_me,$fusen_enable_allpage;
	
	// 認証チケット発行
	$tiket = md5(chr(mt_rand(ord('a'), ord('z'))).UTIME.chr(mt_rand(ord('a'), ord('z'))));
	$filename = "../cache/t_config.php";
	$file = fopen($filename, "wb");
	fwrite($file, "<?php\n".'$tiket="'.$tiket."\";\n?>");
	fclose($file);
	
	xoops_cp_header();
	OpenTable();
	checkPermit();

//	global $xoopsConfig;
//	echo (nl2br(htmlspecialchars(print_r($xoopsModule,true))));

	$X_admin =0;
	$X_uid =0;
	if ( $xoopsUser ) {
		$xoopsModule = XoopsModule::getByDirname("pukiwiki");
		if ( $xoopsUser->isAdmin($xoopsModule->mid()) ) { 
			$X_admin = 1;
		}
		$X_uid = $xoopsUser->uid();
	}
	
	if($hide_navi){
		$_hide_navi_1 = " checked";
		$_hide_navi_0 = "";
	} else {
		$_hide_navi_0 = " checked";
		$_hide_navi_1 = "";
	}
	$_mail_sw_[0] = $_mail_sw_[1] = $_mail_sw_[2] = "";
	if(isset($wiki_mail_sw)){
		$_mail_sw_[$wiki_mail_sw] = " checked";
	} else {
		$_mail_sw_[1] = " checked";
	}
	$_fixed_heading_anchor=array("","");
	if(isset($fixed_heading_anchor)){
		$_fixed_heading_anchor[$fixed_heading_anchor] = " checked";
	} else {
		$_fixed_heading_anchor[0] = " checked";
	}
	if(!isset($wiki_writable)) $wiki_writable = 0;
	if($wiki_writable === 0){
		$_anon_writable_all = " checked";
		$_anon_writable_regist = "";
		$_anon_writable_admin = "";
	} elseif($wiki_writable === 1) {
		$_anon_writable_regist = " checked";
		$_anon_writable_all = "";
		$_anon_writable_admin = "";
	} else {
		$_anon_writable_admin = " checked";
		$_anon_writable_all = "";
		$_anon_writable_regist = "";
	}
	if($function_freeze){
		$_ff_enable = " checked";
		$_ff_disable = "";
	} else {
		$_ff_disable = " checked";
		$_ff_enable = "";
	}
	
	$trackback = (int)$trackback;

	$page_cache_min = (int)$page_cache_min;

	$use_static_url = (int)$use_static_url;
	$use_static_url_sw = array("","");
	$use_static_url_sw[$use_static_url] = " checked";

	$pcmt_page_name = strip_bracket($pcmt_page_name);
	$_jp_pagereading = array("","","");
	if (!$pagereading_enable)
		$_jp_pagereading[0] = " checked";
	elseif ($pagereading_kanji2kana_converter == "chasen")
		$_jp_pagereading[1] = " checked";
	else
		$_jp_pagereading[2] = " checked";
	$_kanji2kana_encoding = array("","");
	if ($pagereading_kanji2kana_encoding == "EUC")
		$_kanji2kana_encoding[0] = " checked";
	else
		$_kanji2kana_encoding[1] = " checked";
	$_unvisible_sw_ = array("","");
	$_unvisible_sw_[$read_auth] = " checked";

	$_wiki_css_file = @file(_AM_WIKI_CSS_FILE);
	$wiki_css = "";
	foreach($_wiki_css_file as $__wiki){
		$wiki_css .= $__wiki;
	}
	$wiki_css = htmlspecialchars($wiki_css);
	$freeze_check = ($defvalue_freeze)? " checked" : "";
	if (!isset($defvalue_gids)) $defvalue_gids = "0";
	if (!isset($defvalue_aids)) $defvalue_aids = "0";
	$def_gids = explode(",",$defvalue_gids.",");
	$def_aids = explode(",",$defvalue_aids.",");
	$allow_edit_form = allow_edit_form($def_gids,$def_aids);

	$_allow_new_sw_[0] = $_allow_new_sw_[1] = $_allow_new_sw_[2] = "";
	if(isset($wiki_allow_new)){
		$_allow_new_sw_[$wiki_allow_new] = " checked";
	} else {
		$_allow_new_sw_[$wiki_writable] = " checked";
	}
	
	$_trackback_encoding_sw = array("","","");
	if (!$trackback_encoding || $trackback_encoding == "EUC-JP")
		$_trackback_encoding_sw[0] = " checked";
	else if ($trackback_encoding == "UTF-8")
		$_trackback_encoding_sw[1] = " checked";
	else
		$_trackback_encoding_sw[2] = " checked";
		
	
	$_countup_xoops = array("","");
	if ($countup_xoops)
		$_countup_xoops[1] = " checked";
	else
		$_countup_xoops[0] = " checked";
	
	$_use_xoops_comments = array("","");
	if ($use_xoops_comments)
		$_use_xoops_comments[1] = " checked";
	else
		$_use_xoops_comments[0] = " checked";
	
	$f_tb_check_link_to_me = array("","");
	if ($tb_check_link_to_me)
		$f_tb_check_link_to_me[1] = " checked";
	else
		$f_tb_check_link_to_me[0] = " checked";

	$f_fusen_enable_allpage = array("","");
	if ($fusen_enable_allpage)
		$f_fusen_enable_allpage[1] = " checked";
	else
		$f_fusen_enable_allpage[0] = " checked";
	
	global $xoopsModule;
	echo "
	| "._AM_WIKI_TITLE1." | <a href='./myblocksadmin.php'>"._MI_SYSTEM_ADMENU2."</a> | <a href='".XOOPS_URL."/modules/system/admin.php?fct=preferences&op=showmod&mod=".$xoopsModule->mid()."'>"._MI_SYSTEM_ADMENU6."</a> |
	<hr />
	<h2>"._AM_WIKI_TITLE0."</h2>
	<span style='color:red;font-weight:bold;'>"._AM_WIKI_INFO0."</span>
	<ul>
		<li><a href='".XOOPS_URL."/modules/pukiwiki/?plugin=pginfo' target='setting'>"._AM_WIKI_DB_INIT."</a></li>
	</ul>
	<hr />
	<h2>"._AM_WIKI_TITLE1."</h2>
	<form method='post' action='index.php'>
	<table border=1>
	<tr><td>
		"._AM_WIKI_SITE_NAME."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_site_name' value='".htmlspecialchars($page_title)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_DEFAULTPAGE."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_defaultpage' value='".htmlspecialchars($defaultpage)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_MODIFIER."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_modifier' value='".htmlspecialchars($modifier)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_MODIFIERLINK."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_modifierlink' value='".htmlspecialchars($modifierlink)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_FUNCTION_FREEZE."
	</td><td>
		<input type='radio' name='wiki_function_freeze' value='1'".$_ff_enable.">"._AM_WIKI_ENABLE."
		<input type='radio' name='wiki_function_freeze' value='0'".$_ff_disable.">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td>
		"._AM_WIKI_ADMINPASS."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_adminpass' value=''>
	</td></tr>
	<tr><td>
		"._AM_WIKI_FUNCTION_UNVISIBLE."
	</td><td>
		<input type='radio' name='wiki_function_unvisible' value='1'".$_unvisible_sw_[1].">"._AM_WIKI_ENABLE."
		<input type='radio' name='wiki_function_unvisible' value='0'".$_unvisible_sw_[0].">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td>
		"._AM_WIKI_COUNTUP_XOOPS."
	</td><td>
		<input type='radio' name='f_countup_xoops' value='1'".$_countup_xoops[1].">"._AM_WIKI_ENABLE."
		<input type='radio' name='f_countup_xoops' value='0'".$_countup_xoops[0].">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td>
		"._AM_WIKI_FUSEN_ENABLE_ALLPAGE."
	</td><td>
		<input type='radio' name='f_fusen_enable_allpage' value='1'".$f_fusen_enable_allpage[1].">"._AM_WIKI_ENABLE."
		<input type='radio' name='f_fusen_enable_allpage' value='0'".$f_fusen_enable_allpage[0].">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td>
		"._AM_WIKI_TB_CHECK_LINK_TO_ME."
	</td><td>
		<input type='radio' name='f_tb_check_link_to_me' value='1'".$f_tb_check_link_to_me[1].">"._AM_WIKI_ENABLE."
		<input type='radio' name='f_tb_check_link_to_me' value='0'".$f_tb_check_link_to_me[0].">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td>
		"._AM_WIKI_FUNCTION_TRACKBACK."
	</td><td>
		<input type='text' size='2' name='f_trackback' value='".$trackback."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_TRACKBACK_ENCODING."
	</td><td>
		<input type='radio' name='f_trackback_encoding' value='0'".$_trackback_encoding_sw[0].">EUC-JP 
		<input type='radio' name='f_trackback_encoding' value='1'".$_trackback_encoding_sw[1].">UTF-8 
		<input type='radio' name='f_trackback_encoding' value='2'".$_trackback_encoding_sw[2].">S-JIS 
	</td></tr>
	<tr><td>
		"._AM_WIKI_UPDATE_PING_TO."
	</td><td>
		<textarea name='update_ping_to' cols='70' rows='5'>".htmlspecialchars($update_ping_to)."</textarea>
	</td></tr>
	<tr><td>
		"._AM_WIKI_PAGE_CACHE_MIN."
	</td><td>
		<input type='text' size='4' name='f_page_cache_min' value='".$page_cache_min."'> (min)
	</td></tr>
	<tr><td>
		"._AM_WIKI_USE_STATIC_URL."
	</td><td>
		<input type='radio' name='f_use_static_url' value='1'".$use_static_url_sw[1].">"._AM_WIKI_ENABLE."
		<input type='radio' name='f_use_static_url' value='0'".$use_static_url_sw[0].">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_COMMON_DIRS."
	</td><td>
		<textarea name='wiki_common_dirs' cols='70' rows='10'>".htmlspecialchars($wiki_common_dirs)."</textarea>
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_CSS."
	</td><td>
		<textarea name='wiki_css' cols='70' rows='10'>".$wiki_css."</textarea>
	</td></tr>
	<tr><td>
		"._AM_WIKI_BACKUP_TIME."
	</td><td>
		<input type='text' size='2' name='f_cycle' value='".$cycle."'> h
	</td></tr>
	<tr><td>
		"._AM_WIKI_BACKUP_AGE."
	</td><td>
		<input type='text' size='2' name='f_maxage' value='".$maxage."'> ages
	</td></tr>
	<tr><td>
		"._AM_WIKI_USER_DIR."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='f_wiki_user_dir' value='".htmlspecialchars($wiki_user_dir)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_PCMT_PAGE."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='f_pcmt_page_name' value='".htmlspecialchars($pcmt_page_name)."'>
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_HIDE_NAVI."
	</td><td>
		<input type='radio' name='wiki_hide_navi' value='1'".$_hide_navi_1.">"._AM_WIKI_NONAVI."
		<input type='radio' name='wiki_hide_navi' value='0'".$_hide_navi_0.">"._AM_WIKI_NAVI."
	</td></tr>
	<tr><td>
		"._AM_WIKI_ANCHOR_VISIBLE."
	</td><td>
		<input type='radio' name='fixed_heading_anchor' value='1'".$_fixed_heading_anchor[1].">"._AM_WIKI_ENABLE."
		<input type='radio' name='fixed_heading_anchor' value='0'".$_fixed_heading_anchor[0].">"._AM_WIKI_DISABLE."
	</td></tr>
	<tr><td colspan=2><hr /></td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_FUNCTION_JPREADING."
	</td><td>
		<input type='radio' name='f_jp_pagereading' value='0'".$_jp_pagereading[0].">"._AM_WIKI_DISABLE."
		<input type='radio' name='f_jp_pagereading' value='1'".$_jp_pagereading[1].">ChaSen
		<input type='radio' name='f_jp_pagereading' value='2'".$_jp_pagereading[2].">KAKASI
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_KANJI2KANA_ENCODING."
	</td><td>
		<input type='radio' name='f_kanji2kana_encoding' value='0'".$_kanji2kana_encoding[0].">EUC-JP
		<input type='radio' name='f_kanji2kana_encoding' value='1'".$_kanji2kana_encoding[1].">S-JIS
	</td></tr>
	<tr><td>
		"._AM_WIKI_PAGEREADING_CHASEN_PATH."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='f_pagereading_chasen_path' value='".htmlspecialchars($pagereading_chasen_path)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_PAGEREADING_KAKASI_PATH."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='f_pagereading_kakasi_path' value='".htmlspecialchars($pagereading_kakasi_path)."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_PAGEREADING_CONFIG_PAGE."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='f_pagereading_config_page' value='".htmlspecialchars($pagereading_config_page)."'>
	</td></tr>
	<tr><td colspan=2><hr /></td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_MAIL_SW."
	</td><td>
		<input type='radio' name='_wiki_mail_sw' value='2'".$_mail_sw_[2].">"._AM_WIKI_MAIL_ALL."
		<input type='radio' name='_wiki_mail_sw' value='1'".$_mail_sw_[1].">"._AM_WIKI_MAIL_NOADMIN."
		<input type='radio' name='_wiki_mail_sw' value='0'".$_mail_sw_[0].">"._AM_WIKI_MAIL_NONE."
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_ALLOW_NEW."
	</td><td>
		<input type='radio' name='wiki_allow_new' value='0'".$_allow_new_sw_[0].">"._AM_WIKI_ALL."
		<input type='radio' name='wiki_allow_new' value='1'".$_allow_new_sw_[1].">"._AM_WIKI_REGIST."
		<input type='radio' name='wiki_allow_new' value='2'".$_allow_new_sw_[2].">"._AM_WIKI_ADMIN."
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_ANONWRITABLE."
	</td><td>
		<input type='radio' name='wiki_anon_writable' value='0'".$_anon_writable_all.">"._AM_WIKI_ALL."
		<input type='radio' name='wiki_anon_writable' value='1'".$_anon_writable_regist.">"._AM_WIKI_REGIST."
		<input type='radio' name='wiki_anon_writable' value='2'".$_anon_writable_admin.">"._AM_WIKI_ADMIN."
	</td></tr>
	<tr><td colspan=2>"._AM_WIKI_ANONWRITABLE_MSG."</td></tr>
	<tr><th colspan=2>"._AM_ALLOW_EDIT_VALDEF."</th></tr>
	<tr><td colspan=2><input type='checkbox' name='freeze' value='1'".$freeze_check." /><span class='small'>".sprintf($_btn_freeze_enable,_AM_WIKI_WRITABLE)."</span></td></tr>
	<tr><td colspan=2>".$allow_edit_form."</td></tr>
	</table><p>
	<input type='hidden' name='wiki_admin_mode' value='change_config'>
	<input type='hidden' name='wiki_admin_tiket' value='$tiket'>
	<input type='submit' value='"._AM_WIKI_CONFIG_SUBMIT."'>
	</form>";

	echo "
	<p /><hr>
	<h2>"._AM_WIKI_TITLE2."</h2>
	<form method='post' action='index.php'>
	<table border=1>
	<tr><td>"._AM_WIKI_PERMIT_CHANGE."
	</td><td>
		<select name='wiki_permit_change_dir'>
			<option value='attach'>attach/*</option>
			<option value='backup'>backup/*</option>
			<option value='cache'>cache/*</option>
			<option value='counter'>counter/*</option>
			<option value='diff'>diff/*</option>
			<option value='diff'>pagehtml/*</option>
			<option value='diff'>trackback/*</option>
			<option value='wiki'>wiki/*</option>
		</select>
	</td></tr>
	</table><p>
	<input type='hidden' name='wiki_admin_mode' value='change_permit'>
	<input type='hidden' name='wiki_admin_tiket' value='$tiket'>
	<input type='submit' value='"._AM_WIKI_PERM_SUBMIT."'>
	</form>";

	echo "
	<p /><hr>
	<h2>"._AM_WIKI_TITLE3."</h2>
	<p>"._AM_WIKI_SYNC_MSG."</p>
	<form method='post' action='index.php'>
	<input type='hidden' name='wiki_admin_mode' value='synchronize'>
	<input type='hidden' name='wiki_admin_tiket' value='$tiket'>
	<input type='submit' value='"._AM_WIKI_SYNC_SUBMIT."'>
	</form>";

	CloseTable();
	xoops_cp_footer();
}

function db_check()
{
	global $xoopsDB;
	// DB Check
	$query = "select * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." LIMIT 1;";
	if(!$result=$xoopsDB->query($query))
	{
		$query="CREATE TABLE `".$xoopsDB->prefix("pukiwikimod_pginfo")."` (
  `id` int(10) NOT NULL auto_increment,
  `name` varchar(255) binary NOT NULL default '',
  `buildtime` int(10) NOT NULL default '0',
  `editedtime` int(10) NOT NULL default '0',
  `aids` text NOT NULL,
  `gids` varchar(255) NOT NULL default '',
  `vaids` text NOT NULL,
  `vgids` varchar(255) NOT NULL default '',
  `lastediter` mediumint(8) NOT NULL default '0',
  `uid` mediumint(8) NOT NULL default '0',
  `freeze` tinyint(1) NOT NULL default '0',
  `unvisible` tinyint(1) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `update` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;";
		if(!$result=$xoopsDB->queryF($query)){
			echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
			echo $query;
		}
	}
	else
	{
		// titleを追加した
		$query = "select `title` FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." LIMIT 1;";
		if(!$result=$xoopsDB->query($query))
		{
			$query = "ALTER TABLE `".$xoopsDB->prefix("pukiwikimod_pginfo")."` ADD `title` VARCHAR(255) NOT NULL default '';";
			if(!$result=$xoopsDB->queryF($query)){
				echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
				echo $query;
			}
		}
		// updateを追加した 04/1/27
		$query = "select `update` FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." LIMIT 1;";
		if(!$result=$xoopsDB->query($query))
		{
			$query = "ALTER TABLE `".$xoopsDB->prefix("pukiwikimod_pginfo")."` ADD `update` tinyint(1) NOT NULL default '0';";
			if(!$result=$xoopsDB->queryF($query)){
				echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
				echo $query;
			}
		}
	}

	$query = "select * FROM ".$xoopsDB->prefix("pukiwikimod_count")." LIMIT 1;";
	if(!$result=$xoopsDB->query($query))
	{
		$query="CREATE TABLE `".$xoopsDB->prefix("pukiwikimod_count")."` (
  `name` varchar(255) NOT NULL default '',
  `count` int(10) NOT NULL default '0',
  `today` varchar(10) NOT NULL default '',
  `today_count` int(10) NOT NULL default '0',
  `yesterday_count` int(10) NOT NULL default '0',
  `ip` varchar(15) NOT NULL default '',
  UNIQUE KEY `name` (`name`)
) TYPE=MyISAM;";
		if(!$result=$xoopsDB->queryF($query)){
			echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
			echo $query;
		}
	}

	$query = "select * FROM ".$xoopsDB->prefix("pukiwikimod_tb")." LIMIT 1;";
	if(!$result=$xoopsDB->query($query))
	{
		$query="CREATE TABLE `".$xoopsDB->prefix("pukiwikimod_tb")."` (
  `last_time` int(10) NOT NULL default '0',
  `url` text NOT NULL,
  `title` varchar(255) NOT NULL default '',
  `excerpt` text NOT NULL,
  `blog_name` varchar(255) NOT NULL default '',
  `tb_id` varchar(32) NOT NULL default '',
  `page_name` varchar(255) NOT NULL default '',
  `ip` varchar(15) NOT NULL default '',
  KEY `page_id` (`tb_id`),
  KEY `page_name` (`page_name`)
) TYPE=MyISAM;";
		if(!$result=$xoopsDB->queryF($query)){
			echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
			echo $query;
		}
	}
	$query = "select * FROM ".$xoopsDB->prefix("pukiwikimod_plain")." LIMIT 1;";
	if(!$result=$xoopsDB->query($query))
	{
		$query="CREATE TABLE `".$xoopsDB->prefix("pukiwikimod_plain")."` (
  `pgid` int(10) NOT NULL default '0',
  `plain` text NOT NULL,
  PRIMARY KEY  (`pgid`)
) TYPE=MyISAM;";
		if(!$result=$xoopsDB->queryF($query)){
			echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
			echo $query;
		}
	}
	
	$query = "select * FROM ".$xoopsDB->prefix("pukiwikimod_attach")." LIMIT 1;";
	if(!$result=$xoopsDB->query($query))
	{
		$query = "CREATE TABLE `".$xoopsDB->prefix("pukiwikimod_attach")."` (
 `id` int(11) NOT NULL auto_increment,
 `pgid` int(11) NOT NULL default '0',
 `name` varchar(255) binary NOT NULL default '',
 `type` varchar(255) NOT NULL default '',
 `mtime` int(11) NOT NULL default '0',
 `size` int(11) NOT NULL default '0',
 `mode` varchar(20) NOT NULL default '',
 `count` int(11) NOT NULL default '0',
 `age` tinyint(4) NOT NULL default '0',
 `pass` varchar(16) binary NOT NULL default '',
 `freeze` tinyint(1) NOT NULL default '0',
 `copyright` tinyint(1) NOT NULL default '0',
 `owner` int(11) NOT NULL default '0',
 UNIQUE KEY `id` (`id`)
) TYPE=MyISAM;";
		if(!$result=$xoopsDB->queryF($query)){
			echo "ERROR: 'pukiwikimod_attach' is already processing settled.<br/>";
			echo $query;
		}
	}
	
	// Ver 1.4.1 ページ間リンク情報DB追加
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_rel")." LIMIT 1;";
	if(!$result=$xoopsDB->query($query))
	{
		$query="CREATE TABLE `".$xoopsDB->prefix("pukiwikimod_rel")."` (
  `pgid` int(11) NOT NULL default '0',
  `relid` int(11) NOT NULL default '0',
  KEY `pgid` (`pgid`),
  KEY `relid` (`relid`)
) TYPE=MyISAM;";
		if(!$result=$xoopsDB->queryF($query)){
			echo "ERROR: 'pukiwikimod_pginfo' is already processing settled.<br/>";
			echo $query;
		}
	}
	
	// ページ名を BINARY に
	$query = "ALTER TABLE `".$xoopsDB->prefix("pukiwikimod_pginfo")."` CHANGE `name` `name` VARCHAR( 255 ) BINARY NOT NULL";
	$xoopsDB->queryF($query);
	$query = "ALTER TABLE `".$xoopsDB->prefix("pukiwikimod_count")."` CHANGE `name` `name` VARCHAR( 255 ) BINARY NOT NULL";
	$xoopsDB->queryF($query);
	
}
clearstatcache();
if($_SERVER["REQUEST_METHOD"] == "GET"){
	db_check();
	displayForm();
} else {
	$tiket = "";
	$tiket_file = "../cache/t_config.php";
	
	// チケット有効時間 10分
	if (file_exists($tiket_file) && time()-filemtime($tiket_file) < 600)
		include ($tiket_file);
	
	if (!$tiket || $tiket != $_POST['wiki_admin_tiket'])
	{
		redirect_header("./index.php",1,_AM_WIKI_DBDENIED);
		exit();
	}
	unlink($tiket_file);
	
	$wiki_admin_mode = (isset($_POST['wiki_admin_mode']))? $_POST['wiki_admin_mode'] : "";
	$wiki_permit_change_dir = (isset($_POST['wiki_permit_change_dir']))? $_POST['wiki_permit_change_dir'] : "";
	
	if($wiki_admin_mode == "change_config")
	{
		writeConfig();
	}
	else if($wiki_admin_mode == "change_permit")
	{
		changePermit(XOOPS_ROOT_PATH."/modules/".PUKIWIKI_DIR_NAME."/".$wiki_permit_change_dir."/");
	}
	else if($wiki_admin_mode == "synchronize")
	{
		wiki_synchronize_allusers();
		redirect_header("./index.php",1,_AM_DBUPDATED);
		exit();
	}
}
?>