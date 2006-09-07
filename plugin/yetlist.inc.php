<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: yetlist.inc.php,v 1.7 2006/09/07 11:57:39 nao-pon Exp $
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
	
	if (file_exists(CACHE_DIR."yetlist.dat"))
	{
		$yetlists = unserialize(join("",file(CACHE_DIR."yetlist.dat")));
	}
	foreach ($yetlists as $ref => $notyets)
	{
		foreach ($notyets as $notyet)
		{
			$refer[$notyet][] = $ref; 	
		}
	}
	
	ksort($refer,SORT_STRING);
	
	foreach($refer as $page=>$refs)
	{
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars(strip_bracket($page));
		
		$link_refs = array();
		foreach(array_unique($refs) as $_refer)
		{
			$r_refer = rawurlencode($_refer);
			//$_refer = strip_bracket($_refer);
			$s_refer = htmlspecialchars(strip_bracket($_refer));
			
			$link_refs[] = make_pagelink($_refer,$s_refer);
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