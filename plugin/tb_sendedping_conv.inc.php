<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: tb_sendedping_conv.inc.php,v 1.2 2006/04/06 13:32:16 nao-pon Exp $
//

function plugin_tb_sendedping_conv_action()
{
	global $xoopsDB,$X_admin;
	
	if (!$X_admin) return false;
	
	//exit (TRACKBACK_DIR);
	$cache = array();
	foreach (get_existpages_db(1) as $page)
	{
		$_t = md5(strip_bracket($page));
		$_tb = $_t.".ping";
		$cache[$_tb] = get_pgid_by_name($page);
		// データベースを変換
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_tb")." SET `tb_id` = ".$cache[$_tb]." WHERE `tb_id` = \"".$_t."\"";
		
		//echo $query."<br>";
		$xoopsDB->queryF($query);
	}
	$dp = opendir(TRACKBACK_DIR);
	$body = "Converted file<hr />";
	while ($file = readdir($dp))
	{
		if ($file == '.' || $file == '..') continue;
		
		if(isset($cache[$file]))
		{
			$pid = $cache[$file].".ping";
			if (rename(TRACKBACK_DIR.$file,TRACKBACK_DIR.$pid))
			{
				$body .= "<p>".strip_bracket($cache[$file])."<br />* $file > {$pid}</p>";
			}
			//echo $file."<br>";
		}
		else
		{
			//必要ないファイルを削除
			if (preg_match("/[a-f0-9]{32}\.(ping|txt)/",$file))
			{
				//echo "DELETE: $file<br />";
				unlink(TRACKBACK_DIR.$file);
			}
		}
	}
	closedir($dp);
	//exit;
	
	$msg = "TB Sended Pings converter";
	return array('msg'=>$msg,'body'=>$body);
}

?>