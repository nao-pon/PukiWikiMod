<?php
// $Id: xoops_version.php,v 1.1 2003/06/28 11:33:01 nao-pon Exp $
 
$modversion['name'] = "PukiWiki";
$modversion['version'] = 0.05;
$modversion['description'] = "PukiWiki";
$modversion['credits'] = "";
$modversion['author'] = "";
$modversion['help'] = "top.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "pukiwiki.gif";
$modversion['dirname'] = "pukiwiki";

//Admin things
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

// Menu
$modversion['hasMain'] = 1;
$modversion['sub'][1]['name'] = _MI_PUKIWIKI_SITEMAP;
$modversion['sub'][1]['url'] = "index.php?plugin=map";
$modversion['sub'][2]['name'] = _MI_PUKIWIKI_RECENT;
$modversion['sub'][2]['url'] = "index.php?MenuBar";
$modversion['sub'][3]['name'] = _MI_PUKIWIKI_LIST;
$modversion['sub'][3]['url'] = "index.php?cmd=list";

// Search
$modversion['hasSearch'] = 1;
$modversion['search']['file'] = "xoops_search.inc.php";
$modversion['search']['func'] = "wiki_search";

// Blocks
$modversion['blocks'][1]['file'] = "pukiwiki_new.php";
$modversion['blocks'][1]['name'] = _MI_PUKIWIKI_BNAME1;
$modversion['blocks'][1]['description'] = "Shows recently contents";
$modversion['blocks'][1]['show_func'] = "b_pukiwiki_new_show";
?>
