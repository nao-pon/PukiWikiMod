<?php
// $Id: yetlist.inc.php,v 1.1 2003/06/28 06:01:53 nao-pon Exp $

// modified by PANDA <panda@arino.jp> http://home.arino.jp/
// Last-Update:2002-09-12 rev.1

function plugin_yetlist_init() {
	if (LANG == "ja"){
		define("WIKI_PLUGIN_YETLIST_MSG","未入力(作成されていない)ページの一覧");
	} else {
		define("WIKI_PLUGIN_YETLIST_MSG","List of pages,are not made yet");
	}
}

function plugin_yetlist_action() {
	global $script,$LinkPattern;

	$ret['msg'] = WIKI_PLUGIN_YETLIST_MSG;
	
	if (!$dir = @opendir(DATA_DIR)) { return $ret; }

	while($file = readdir($dir)) {
		if ($file == '..' || $file == '.') continue;
		$page = decode(str_replace('.txt','',$file));
		if ($page == "RenameLog") continue;
		$line = join("\n",preg_replace('/^(\s|\/\/|#).*$/','',file(DATA_DIR.$file)));
		$obj = new link_wrapper($page);
		foreach ($obj->get_link($line) as $obj) {
			if ($obj->name != '' and ($obj->type == 'WikiName' or $obj->type == 'BracketName') and !is_page($obj->name)) {
				$refer[$obj->name][] = $page;
			}
		}
	}
	closedir($dir);

	if (count($refer) == 0)
		return $ret;

	ksort($refer);

	foreach($refer as $page=>$refs) {
		$page_raw  = rawurlencode($page);
		$page_disp = strip_bracket($page);
		
		$link_refs = array();
		foreach(array_unique($refs) as $ref) {
			$ref_raw  = rawurlencode($ref);
			$ref_disp = strip_bracket($ref);
			
			$link_refs[] = "<a href=\"$script?$ref_raw\">$ref_disp</a>";
		}
		$link_ref = join(' ',$link_refs);
		// 参照元ページが複数あった場合、referは最後のページを指す(いいのかな)
		$ret['body'] .= "<li><a href=\"$script?cmd=edit&amp;page=$page_raw&amp;refer=$ref_raw\">$page_disp</a> ($link_ref)</li>\n";
	}

	if ($ret['body'] != '')
		$ret['body'] = "<ul>\n{$ret['body']}</ul>\n";

	return $ret;
}
?>
