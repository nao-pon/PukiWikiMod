<?php
// $Id: pukiwiki_new.php,v 1.23 2006/04/06 13:32:15 nao-pon Exp $
if (! defined('PWM_BLOCK_NEW_INCLUDED')) {
define('PWM_BLOCK_NEW_INCLUDED', true);

if (! defined('PWM_BLOCK_FUNC_INCLUDED')) include_once("block_function.php");

function b_pukiwiki_new_show($option)
{
	//表示する件数
	$show_num = 10;

	global $xoopsUser,$xoopsDB;
	
	$dir_name = $option[0];
	$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);
	
	if ( $xoopsUser )
	{
		include_once XOOPS_ROOT_PATH.'/class/xoopsmodule.php';
		$xoopsModule = XoopsModule::getByDirname($dir_name);
		if ( $xoopsUser->isAdmin($xoopsModule->mid()) )
			$X_admin = 1;
		else
			$X_admin = 0;
	
		$X_uid = $xoopsUser->uid();
	}
	else
	{
		$X_admin = $X_uid = 0;
	}

	if ($X_admin)
		$where = "";
	else
	{
		$where = "";
		if ($X_uid) $where .= "  (uid = $X_uid) OR";
		$where .= " (vaids LIKE '%all%') OR (vgids LIKE '%&3&%')";
		if ($X_uid) $where .= " OR (vaids LIKE '%&{$X_uid}&%')";
		foreach(xb_X_get_groups($X_uid) as $gid)
		{
			$where .= " OR (vgids LIKE '%&{$gid}&%')";
		}
	}

	if ($where) $where = " AND ($where)";

	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod{$dir_num}_pginfo")." WHERE (name NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $show_num;";
	$res = $xoopsDB->query($query);

	if ($res)
	{
		$date = $items = "";
		$close = FALSE;
		while($data = mysql_fetch_row($res))
		{
			if(date("Y-n-j",$data[3]) != $date)
			{
				if ($close) $items .= "</ul>";
				$date = date("Y-n-j",$data[3]);
				$items .= "<strong>".$date."</strong>\n<ul>\n";
				$close = TRUE;
			}
			$items .="<li>".xb_make_link($data[1],"#/#",$dir_name)."</li>\n";
		}
		if ($close) $items .= "</ul>";
	}

	$block['title'] = _MI_PUKIWIKI_BTITLE;
	$block['content'] = "<div style=\"word-wrap:break-word; word-break:break-all;\">$items</div>";

	return $block;
}

function b_pukiwiki_newtb_show($option)
{
	global $xoopsDB;
	//表示する件数
	$show_num = 10;

	$dir_name = $option[0];
	$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);

	$query = 'SELECT * FROM `'.$xoopsDB->prefix("pukiwikimod{$dir_num}_tb").'` ORDER BY `last_time` DESC LIMIT '.$show_num;
	$res = $xoopsDB->query($query);

	if ($res)
	{
		$date = $items = "";
		$close = FALSE;
		while($data = mysql_fetch_row($res))
		{
			if(date("Y-n-j",$data[0]) != $date)
			{
				if ($close) $items .= "</ul>";
				$date = date("Y-n-j",$data[0]);
				$items .= "<strong>".$date."</strong>\n<ul>\n";
				$close = TRUE;
			}
			$items .="<li><a href=\"{$data[1]}\" target=\"_blank\">{$data[2]}</a>({$data[4]})<br />to: ".xb_make_link($data[6],$data[6],$dir_name)."</li>\n";
		}
		if ($close) $items .= "</ul>";
	}

	$block['title'] = _MI_PUKIWIKI_BTITLE2;
	$block['content'] = "<div style=\"word-wrap:break-word; word-break:break-all;\">$items</div>";

	return $block;
}

function b_pukiwiki_newattach_show($option)
{
	global $xoopsUser,$xoopsDB;
	
	$dir_name = $option[0];
	$dir_num = preg_replace( '/^(\D+)(\d*)$/', "$2",$dir_name);

	//表示する件数
	$show_num = 10;

	$query = 'SELECT a.mtime, a.name, i.name FROM `'.$xoopsDB->prefix("pukiwikimod{$dir_num}_attach").'` a LEFT JOIN `'.$xoopsDB->prefix("pukiwikimod{$dir_num}_pginfo").'` i ON a.pgid = i.id WHERE i.name != "" AND a.name != "fusen.dat" AND a.name NOT LIKE "ISBN%.jpg" AND a.name NOT LIKE "ISBN%.dat" ORDER BY `mtime` DESC LIMIT '.$show_num;
	$res = $xoopsDB->query($query);
	$item = $query;

	if ($res)
	{
		$date = $items = "";
		$close = FALSE;
		while($data = mysql_fetch_row($res))
		{
			if(date("Y-n-j",$data[0]) != $date)
			{
				if ($close) $items .= "</ul>";
				$date = date("Y-n-j",$data[0]);
				$items .= "<strong>".$date."</strong>\n<ul>\n";
				$close = TRUE;
			}
			$link = XOOPS_URL."/modules/".$dir_name."/index.php?plugin=attach&pcmd=info&file=".rawurlencode($data[1])."&refer=".rawurlencode(xb_add_bracket($data[2]));
			$items .="<li><a href=\"$link\">$data[1]</a><br />to: ".xb_make_link($data[2],$data[2],$dir_name)."</li>\n";
		}
		if ($close) $items .= "</ul>";
	}

	$block['title'] = _MI_PUKIWIKI_BTITLE3;
	$block['content'] = "<div style=\"word-wrap:break-word; word-break:break-all;\">$items</div>";

	return $block;
}

function b_pukiwiki_new_edit($options)
{
	$form = "";
	$form .= "<input type='hidden' name='options[]' value='".$options[0]."' />";
	return $form;
}

}
?>