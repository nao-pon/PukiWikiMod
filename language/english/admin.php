<?php
// $Id: admin.php,v 1.4 2003/10/13 12:23:28 nao-pon Exp $
// FIXME: not good at English. :P

define("_AM_WIKI_TITLE1", "Preferences");
define("_AM_WIKI_TITLE2", "Change Permission");
define("_AM_WIKI_SUBMIT", "Submit");
define("_AM_WIKI_ENABLE", "enable");
define("_AM_WIKI_DISABLE", "disable");
define("_AM_WIKI_NOLEAVE", "reject");
define("_AM_WIKI_LEAVE", "leave");
define("_AM_WIKI_NONAVI", "hide");
define("_AM_WIKI_NAVI", "show");

define("_AM_DBUPDATED", "The writing to a file was completed.");

define("_AM_WIKI_ERROR01", "not writable");

define("_AM_WIKI_DEFAULTPAGE", "Default Page");
define("_AM_WIKI_MODIFIER", "Modifier");
define("_AM_WIKI_MODIFIERLINK", "Modifier's Homepage");
define("_AM_WIKI_FUNCTION_FREEZE", "Enable Freeze");
define("_AM_WIKI_ADMINPASS", "The password to unfreeze<br>¡Êtype a new password only when changing it.¡Ë");
define("_AM_WIKI_CSS", "Styles of page that override default style<br />(This can be useful to change style of PukiWiki pages to fit them your theme)");

define("_AM_WIKI_PERMIT_CHANGE", "A directory with the file which wants to change a permission<br>(Recommended to use this for uninstalling this module only<br />Basically chmod to 0666)");
define("_AM_WIKI_ANONWRITABLE", "Who is permitted to edit pages");
define("_AM_WIKI_HIDE_NAVI", "Hide the navigation menu at the top of pages hen frozen (Always shown if Webmasters logged in)");
define("_AM_WIKI_MAIL_SW", "Notify by email when edited by");
define("_AM_WIKI_ALL", "All users");
define("_AM_WIKI_REGIST", "All registered users");
define("_AM_WIKI_ADMIN", "Webmasters only");
define("_AM_WIKI_MAIL_ALL", "All users");
define("_AM_WIKI_MAIL_NOADMIN", "All users except Webmasters");
define("_AM_WIKI_MAIL_NONE", "Nobody");

define("_AM_WIKI_FUNCTION_UNVISIBLE", "Enable perusal authority for every page.");
define("_AM_WIKI_BACKUP_TIME", "The interval of fixed backup.(hour) [every updating at 0]");
define("_AM_WIKI_BACKUP_AGE", "Maximum generations of backup.");
define("_AM_WIKI_PCMT_PAGE", 'The default of the new creation page name in pcomment plug-in.(Installation page name is set to %s.)');
define("_AM_WIKI_USER_DIR", 'The format at the time of the name input in form.<br />(Name at the time of contribution goes into %1$s)<br />ex. <b>[[%1$s>user/%1$s]]</b><br />When not setting up here, a setup with each plug-in is applied.');
define("_AM_WIKI_FUNCTION_JPREADING", "Is kana reading acquisition of the page name by ChaSen and KAKASI confirmed?");
define("_AM_WIKI_KANJI2KANA_ENCODING", "The kanji code used for delivery with ChaSen/KAKASI (UNIX systems EUC-JP and Win systems S-JIS is foundations.)");
define("_AM_WIKI_PAGEREADING_CHASEN_PATH", "Execution file path of ChaSen (it sets up according to each one of environment);
define("_AM_WIKI_PAGEREADING_KAKASI_PATH", "Execution file path of KAKASI (it sets up according to each one of environment)");
define("_AM_WIKI_PAGEREADING_CONFIG_PAGE", "The name of the page which stored page name reading.");

?>
