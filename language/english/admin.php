<?php
// $Id: admin.php,v 1.6 2004/11/01 09:03:55 nao-pon Exp $
// FIXME: not good at English. :P

define("_AM_WIKI_TITLE0", "PukiWikiMod initial setting.");
define("_AM_WIKI_INFO0", "In order to carry out initial setting, please access the following two link places and perform processing.");
define("_AM_WIKI_DB_INIT", "DataBace initialization.");
define("_AM_WIKI_PAGE_INIT", "Page relation initialization.");

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
define("_AM_WIKI_SITE_NAME", "Name of this site's wiki");
define("_AM_WIKI_FUNCTION_TRACKBACK", "Enable TrackBack");

// Ver 0.08 b5
define("_AM_WIKI_PAGE_CACHE_MIN", "The value(minutes) which carries out the cache of the conversion result to HTML. Only a guest user becomes effective. (with 0 is no cache)");
define("_AM_WIKI_USE_STATIC_URL", "Use static URL.(ex. [Page ID].html) A setup in '.htaccess ' is required.");

define("_AM_WIKI_UPDATE_PING_TO", "The place which always carries out Ping transmission at the time of edit of a page. It divides in a new-line or a space.");
define("_AM_WIKI_COMMON_DIRS", "A common link directory. An auto link becomes effective even if it omits this. Finally / (slash) is required. It divides in a new-line or a space.");
define("_AM_SYSTEM_ADMENU","Basic settings.");
define("_AM_SYSTEM_ADMENU2","Block management.");

// Ver 1.0.6
define("_AM_WIKI_ANCHOR_VISIBLE","A fixed link anchor is displayed.");

// Ver 1.0.8
define("_AM_WIKI_TRACKBACK_ENCODING","The character code at the time of track back transmission.");
?>
