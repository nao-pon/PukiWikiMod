<?php
// $Id: index.php,v 1.1 2003/06/28 06:01:56 nao-pon Exp $

include("admin_header.php");
include_once(XOOPS_ROOT_PATH."/class/module.errorhandler.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/pukiwiki.ini.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/config.php");
define("_AM_WIKI_CONFIG_FILE", "../cache/config.php");
define("_AM_WIKI_ADMIN_PASS", "../cache/adminpass.php");
define("_AM_WIKI_CSS_FILE", XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css");
define("_WIKI_AM_TEXT_SIZE", "50");

function changePermit($_target_dir){
	global $xoopsModule;
	if ($dir = @opendir($_target_dir)) {
		while($file = readdir($dir)) {
			if($file == ".." || $file == ".") continue;
			chmod(trim($_target_dir."$file"), 0666);
		}
		closedir($dir);
	}
	redirect_header(XOOPS_ADMIN_URL,1,_AM_DBUPDATED);
	exit();
}

function writeConfig(){
	global $xoopsConfig, $HTTP_POST_VARS;
	global $wiki_defaultpage, $wiki_modifier, $wiki_modifierlink, $wiki_function_freeze, $wiki_adminpass, $wiki_css, $wiki_anon_writable, $wiki_hide_navi,$_wiki_mail_sw;

	$filename = _AM_WIKI_CONFIG_FILE;
	$file = fopen($filename, "w");

	$content = "";
	$content .= "<?php";
	$content .= "
	\$defaultpage = '$wiki_defaultpage';
	\$modifier = '$wiki_modifier';
	\$modifierlink = '$wiki_modifierlink';
	\$wiki_writable = $wiki_anon_writable;
	\$hide_navi = $wiki_hide_navi;
	\$wiki_mail_sw = $_wiki_mail_sw;
	\$function_freeze = $wiki_function_freeze;\n";

	$content .= "\n?>";

	fwrite($file, $content);
	fclose($file);

	if($wiki_adminpass != ""){
		$wiki_adminpass = md5($wiki_adminpass);
		$filename = _AM_WIKI_ADMIN_PASS;
		$file = fopen($filename, "w");
		$content = "<?php\n\$adminpass = '$wiki_adminpass';\n?>";
		fwrite($file, $content);
		fclose($file);
	}

	$filename = _AM_WIKI_CSS_FILE;
	$file = fopen($filename, "w");
	fwrite($file, $wiki_css);
	fclose($file);

	redirect_header(XOOPS_ADMIN_URL,1,_AM_DBUPDATED);
	exit();
}

function checkPermit(){
	global $xoopsModule;
	$wiki_error = array();
	$_check_list = array(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/attach/",
		XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/backup/",
		XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/",
		XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/counter/",
		XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/diff/",
		XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/wiki/");

	if ($dir = @opendir(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/wiki/")) {
		while($file = readdir($dir)) {
			if($file == ".." || $file == ".") continue;
			array_push($_check_list, XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/wiki/".$file);
		}
		closedir($dir);
	}

	foreach($_check_list as $dir){
		if(!is_writable($dir)){
			$wiki_error[] = _AM_WIKI_ERROR01."=> ".$dir;
		}
	}

	$_alert_icon = "<img src='../image/alert.gif'>&nbsp;";
//	$_alert_icon = "<img src='../caution.gif' height='15' width='50'>&nbsp;";
	foreach($wiki_error as $er_msg){
//		echo "<img src='$_alert_icon' height='15' width='50'>&nbsp;$er_msg<br />";
		echo "$_alert_icon$er_msg<br />";
	}
}

function displayForm(){
	global $xoopsConfig, $xoopsModule;
	global $defaultpage, $modifier, $modifierlink, $function_freeze, $adminpass, $anon_writable, $hide_navi, $wiki_mail_sw;
	xoops_cp_header();
	OpenTable();
	checkPermit();
	if($hide_navi){
		$_hide_navi_1 = " checked";
	} else {
		$_hide_navi_0 = " checked";
	}
	$_mail_sw_ = array();
	if(isset($wiki_mail_sw)){
		$_mail_sw_[$wiki_mail_sw] = " checked";
	}
	if($anon_writable === 0){
		$_anon_writable_all = " checked";
	} elseif($anon_writable === 1) {
		$_anon_writable_regist = " checked";
	} else {
		$_anon_writable_admin = " checked";
	}
	if($function_freeze){
		$_ff_enable = " checked";
	} else {
		$_ff_disable = " checked";
	}
	$_wiki_css_file = @file(_AM_WIKI_CSS_FILE);
	foreach($_wiki_css_file as $__wiki){
		$wiki_css .= $__wiki;
	}
	$wiki_css = htmlspecialchars($wiki_css);

	echo "
	<h2>"._AM_WIKI_TITLE1."</h2>
	<form method='post' action='index.php'>
	<table border=1>
	<tr><td>
		"._AM_WIKI_DEFAULTPAGE."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_defaultpage' value='".$defaultpage."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_MODIFIER."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_modifier' value='".$modifier."'>
	</td></tr>
	<tr><td>
		"._AM_WIKI_MODIFIERLINK."
	</td><td>
		<input type='text' size='"._WIKI_AM_TEXT_SIZE."' name='wiki_modifierlink' value='".$modifierlink."'>
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
	<tr><td valign='top'>
		"._AM_WIKI_CSS."
	</td><td>
		<textarea name='wiki_css' cols='70' rows='10'>".$wiki_css."</textarea>
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_ANONWRITABLE."
	</td><td>
		<input type='radio' name='wiki_anon_writable' value='0'".$_anon_writable_all.">"._AM_WIKI_ALL."
		<input type='radio' name='wiki_anon_writable' value='1'".$_anon_writable_regist.">"._AM_WIKI_REGIST."
		<input type='radio' name='wiki_anon_writable' value='2'".$_anon_writable_admin.">"._AM_WIKI_ADMIN."
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_HIDE_NAVI."
	</td><td>
		<input type='radio' name='wiki_hide_navi' value='1'".$_hide_navi_1.">"._AM_WIKI_NONAVI."
		<input type='radio' name='wiki_hide_navi' value='0'".$_hide_navi_0.">"._AM_WIKI_NAVI."
	</td></tr>
	<tr><td valign='top'>
		"._AM_WIKI_MAIL_SW."
	</td><td>
		<input type='radio' name='_wiki_mail_sw' value='2'".$_mail_sw_[2].">"._AM_WIKI_MAIL_ALL."
		<input type='radio' name='_wiki_mail_sw' value='1'".$_mail_sw_[1].">"._AM_WIKI_MAIL_NOADMIN."
		<input type='radio' name='_wiki_mail_sw' value='0'".$_mail_sw_[0].">"._AM_WIKI_MAIL_NONE."
	</td></tr>
	</table><p>
	<input type='hidden' name='wiki_admin_mode' value='change_config'>
	<input type='submit' value='"._AM_WIKI_SUBMIT."'>
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
			<option value='wiki'>wiki/*</option>
		</select>
	</td></tr>
	</table><p>
	<input type='hidden' name='wiki_admin_mode' value='change_permit'>
	<input type='submit' value='"._AM_WIKI_SUBMIT."'>
	</form>";

	CloseTable();
	xoops_cp_footer();
}

clearstatcache();
if($_SERVER["REQUEST_METHOD"] == "GET"){
	displayForm();
} else {
	if($wiki_admin_mode == "change_config"){
		writeConfig();
	} else if($wiki_admin_mode == "change_permit"){
		changePermit(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/".$wiki_permit_change_dir."/");
	}
}

?>

