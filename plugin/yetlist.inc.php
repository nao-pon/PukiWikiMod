<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: yetlist.inc.php,v 1.6 2004/11/24 13:15:35 nao-pon Exp $
//
function plugin_yetlist_init() {
	if (LANG == "ja"){
		define("WIKI_PLUGIN_YETLIST_MSG","未入力(作成されていない)ページの一覧");
	} else {
		define("WIKI_PLUGIN_YETLIST_MSG","List of pages,are not made yet");
	}
}

function plugin_yetlist_action()
{
	global $script;
	//global $_title_yetlist,$_err_notexist;
	
	$retval = array(
		'msg' => WIKI_PLUGIN_YETLIST_MSG,
		'body' => ''
	);
	
	$refer = array();
	$pages = array_diff(get_existpages(CACHE_DIR,'.ref'),get_existpages());
	foreach ($pages as $page)
	{
		$page = add_bracket($page);
		foreach (file(CACHE_DIR.encode($page).'.ref') as $line)
		{
			list($_page) = explode("\t",$line);
			$refer[$page][] = $_page;
		}
	}
	
	if (count($refer) == 0)
	{
		$retval['body'] = "no page!";
		return $retval;
	}
	
	ksort($refer,SORT_STRING);
	
	foreach($refer as $page=>$refs)
	{
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		
		$link_refs = array();
		foreach(array_unique($refs) as $_refer)
		{
			$r_refer = rawurlencode($_refer);
			$s_refer = htmlspecialchars($_refer);
			
			$link_refs[] = "<a href=\"$script?$r_refer\">$s_refer</a>";
			//$link_refs[] = make_pagelink($_refer);
		}
		$link_ref = join(' ',$link_refs);
		// 参照元ページが複数あった場合、referは最後のページを指す(いいのかな)
		$retval['body'] .= "<li><a href=\"$script?cmd=edit&amp;page=$r_page&amp;refer=$r_refer\">$s_page</a> <em>($link_ref)</em></li>\n";
	}
	
	if ($retval['body'] != '')
	{
		$retval['body'] = "<ul>\n".$retval['body']."</ul>\n";
	}
	
	return $retval;
}
?>