<?php
// $Id: index.php,v 1.11 2003/07/08 04:08:38 nao-pon Exp $
define("UTIME",time());
include("admin_header.php");
include_once(XOOPS_ROOT_PATH."/class/module.errorhandler.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/pukiwiki.ini.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/config.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/html.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/file.php");
include(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/func.php");
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
	redirect_header("./index.php",1,_AM_DBUPDATED);
	exit();
}

function writeConfig(){
	global $xoopsConfig, $HTTP_POST_VARS;

	foreach($HTTP_POST_VARS as $k => $v){
		$$k = $v;
	}

	$filename = _AM_WIKI_CONFIG_FILE;
	$file = fopen($filename, "w");
	$gids = implode(",",$gids);
	$aids = implode(",",$aids);
	$freeze = ($freeze)? 1 : 0 ;
	$content = "";
	$content .= "<?php";
	$content .= "
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
	\n";

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

	redirect_header("./index.php",1,_AM_DBUPDATED);
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
	global $xoopsConfig, $xoopsModule, $xoopsUser, $X_admin, $X_uid;
	global $defaultpage, $modifier, $modifierlink, $function_freeze, $adminpass, $wiki_writable, $hide_navi, $wiki_mail_sw, $_btn_freeze_enable ,$defvalue_freeze,$defvalue_gids,$defvalue_aids, $wiki_allow_new;
	xoops_cp_header();
	OpenTable();
	checkPermit();
	
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
		$_ff_enable = "";
		$_ff_disable = " checked";
	} else {
		$_ff_disable = " checked";
		$_ff_enable = "";
	}
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
	$wiki_admin_mode = (isset($HTTP_POST_VARS['wiki_admin_mode']))? $HTTP_POST_VARS['wiki_admin_mode'] : "";
	$wiki_permit_change_dir = (isset($HTTP_POST_VARS['wiki_permit_change_dir']))? $HTTP_POST_VARS['wiki_permit_change_dir'] : "";
	
	if($wiki_admin_mode == "change_config"){
		writeConfig();
	} else if($wiki_admin_mode == "change_permit"){
		changePermit(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/".$wiki_permit_change_dir."/");
	}
}

?>

