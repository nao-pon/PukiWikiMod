<?php 
// $Id: pukiwiki.skin.en.php,v 1.8 2004/11/11 23:34:00 nao-pon Exp $
if (!defined('DATA_DIR')) exit;
?>

<!-- pukiwikimod -->

	<link rel="stylesheet" href="skin/trackback.css" type="text/css" media="screen" charset="shift_jis">
<?php if (WIKI_THEME_CSS){ ?>
	<link rel="stylesheet" href="<?php echo WIKI_THEME_CSS ?>" type="text/css" media="screen" charset="shift_jis">
<?php } else { ?>
	<link rel="stylesheet" href="skin/default.ja.css" type="text/css" media="screen" charset="shift_jis">
<?php } ?>
<?php if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css")){ ?>
	<link rel="stylesheet" href="cache/css.css" type="text/css" media="screen" charset="shift_jis">
<?php } ?>
	<script type="text/javascript">
	<!--
	var pukiwiki_root_url = "";
	//-->
	</script>
	<script type="text/javascript" src="skin/default.js"></script>
<table border=0 cellspacing="5" style="width:100%;" onmouseup=pukiwiki_pos() onkeyup=pukiwiki_pos()>
 <tr>
  <td class="pukiwiki_body">

	<?php if((!$hide_navi && !$noheader) || !$is_read){ // header ?>
		<center><div class="wiki_page_title"><?php echo $page ?></div>
	<?php if($is_page) { ?>
		[ <a href="<?php echo $link_page ?>">Reload</a> ]
		&nbsp;
	<?php
	$source_tag = "<a href=\"$link_source\">Source</a>";
	if ($anon_writable){
		if ($wiki_allow_newpage){
			echo "[ <a href=\"$link_new\">New</a> | ";
		} else {
			echo "[ ";
		}
		if (!$_freeze) {
			echo "
			<a href=\"$link_edit\">Edit</a> | <a href=\"$link_diff\">Diff</a> | <a href=\"$link_attach\">Attach</a> ";
			if ($X_admin){
				echo "| <a href=\"$link_rename\">Rename</a> ";
			}
		} else {
			echo $source_tag." ";
		}
		echo "]&nbsp;";
	} else {
		if ($_freeze){
			echo "[ ".$source_tag." ]&nbsp;";
		} else {
			if ($wiki_writable < 2) {
				echo "[ <a href='".XOOPS_URL."/user.php'>Login for Edit</a> | $source_tag ]&nbsp;";
			} else {
				echo "[ ".$source_tag." ]&nbsp;";
			}
		}
	}
	?>
	
	<?php } ?>
	[ <a href="<?php echo $link_top ?>">Top</a>
	| <a href="<?php echo $link_list ?>">List</a>
	| <a href="<?php echo $link_search ?>">Search</a>
	<?php if(arg_check("list")) { ?>
		| <a href="<?php echo $link_filelist ?>">Attach List</a>
	<?php } ?>
	| <a href="<?php echo $link_whatsnew ?>">Recent</a>
	<?php if ($wiki_allow_newpage){ ?>
	| <a href="<?php echo "$script?plugin=yetlist" ?>">Yet List</a>
	<?php } ?>
	<?php if($do_backup) { ?>
		| <a href="<?php echo $link_backup ?>">Backup</a>
	<?php } ?>
	| <a href="<?php echo "$script?".rawurlencode("Help") ?>">Help</a>
	]<br /></center>
	<?php echo $hr ?>
	<?php } else { if (!$_freeze) { // header ?>
		<div style="float:right;width:65px;"><a href="<?php echo $link_attach ?>"><img src="./image/file.png" width="20" height="20" border="0" alt="Attach" /></a><a href="<?php echo $link_edit ?>"><img src="./image/edit_button.gif" width="45" height="22" border="0" alt="Edit" /></a></div>
	<?php } } ?>

	<?php if($is_read) { ?>
	
	<div style="text-align:left;">
	<?php echo $where.$tb_tag; ?>
	</div>
	
	<div style="text-align:right;clear:both;">
	<?php echo $counter ?>
	</div>
	
	<div class="wiki_page_navi"><?php echo get_prevpage_link_by_name($vars['page']) ?> <img src="./image/prev.png" width="6" height="12" alt="Prev"> <img src="./image/next.png" width="6" height="12" alt="Next"> <?php echo get_nextpage_link_by_name($vars['page']) ?></div>
	
	<?php } // is_read ?>
	
	<div class="wiki_content" id="body" style="width:100%;">
	<?php echo $body ?>
	</div>
	<?php echo $hr ?>
	<?php if($attaches)
		{
			print $attaches;
			print $hr;
		}
	?>
	<?php if ($is_read && $trackback_body){ echo $trackback_body; } ?>
	
	<div style="text-align:right">
		<?php if($is_page) { ?>
		<a href="<?php echo $link_page ?>"><img src="./image/reload.png" width="20" height="20" border="0" alt="Reload" /></a>
		&nbsp;
		<?php if (!$_freeze){ ?>
		<?php if ($wiki_allow_newpage){ ?>
		<a href="<?php echo $link_new ?>"><img src="./image/new.png" width="20" height="20" border="0" alt="New" /></a>
		<a href="<?php echo $link_copy ?>"><img src="./image/copy.png" width="20" height="20" border="0" alt="Copy" /></a>
		<?php } // $wiki_allow_newpage ?>
		<a href="<?php echo $link_edit ?>"><img src="./image/edit.png" width="20" height="20" border="0" alt="Edit" /></a>
		<a href="<?php echo $link_attach ?>"><img src="./image/file.png" width="20" height="20" border="0" alt="Attach" /></a>
		<a href="<?php echo $link_rename ?>"><img src="./image/rename.png" width="20" height="20" border="0" alt="Rename" /></a>
		&nbsp;
		<?php } // !$_freeze ?>
		<a href="<?php echo $link_diff ?>"><img src="./image/diff.png" width="20" height="20" border="0" alt="Diff" /></a>
		<a href="<?php echo $link_source ?>"><img src="./image/source.png" width="20" height="20" border="0" alt="Source" /></a>
		<a href="<?php echo $link_attachlist ?>"><img src="./image/attach.png" width="20" height="20" border="0" alt="Attach List" /></a>
		&nbsp;
		<?php } // $is_page ?>
		<a href="<?php echo $link_top ?>"><img src="./image/top.png" width="20" height="20" border="0" alt="Wiki TOP" /></a>
		<a href="<?php echo $link_list ?>"><img src="./image/list.png" width="20" height="20" border="0" alt="List" /></a>
		<a href="<?php echo $link_search ?>"><img src="./image/search.png" width="20" height="20" border="0" alt="Serach" /></a>
		<a href="<?php echo $link_whatsnew ?>"><img src="./image/recentchanges.png" width="20" height="20" border="0" alt="Recent" /></a>
		<?php if($do_backup) { ?>
		<a href="<?php echo $link_backup ?>"><img src="./image/backup.png" width="20" height="20" border="0" alt="Backup" /></a>
		<?php } // $do_backup ?>
		&nbsp;
		<a href="<?php echo "$script?".rawurlencode("Help") ?>"><img src="./image/help.png" width="20" height="20" border="0" alt="Help" /></a>
		&nbsp;
		<a href="<?php echo $script ?>?cmd=rss10"><img src="./image/rss.png" width="36" height="14" border="0" alt="RSS feeds" /></a>
	</div>
	<?php
	if ($is_page)
	{
	?>
	<table style="width:auto;" class="small">
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Pagename:</td><td style="margin:0px;padding:0px;" colspan="2"><?php echo strip_bracket($vars['page'])." ".$sended_ping_tag ?></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Created:</td><td style="margin:0px;padding:0px;white-space:nowrap;"><a href="<?php echo XOOPS_URL ?>/userinfo.php?uid=<?php echo get_pg_auther($vars["page"]) ?>"><?php echo $pg_auther_name ?></a></td><td style="margin:0px;padding:0px;;width:100%;"> - <?php echo date("Y/m/d H:i:s T",$pginfo['buildtime']) . "<small>" . get_passage($pginfo['buildtime']); ?></small></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Changed:</td><td style="margin:0px;padding:0px;white-space:nowrap;"><a href="<?php echo XOOPS_URL ?>/userinfo.php?uid=<?php echo $pginfo['lastediter'] ?>"><?php echo $last_editer ?></a></td><td style="margin:0px;padding:0px;width:100%;"> - <?php echo date("Y/m/d H:i:s T",$pginfo['editedtime']) . get_pg_passage($vars["page"]); ?></td>
	<?php if($related) { ?>
		<tr>
		 <td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Link pages:<td style="margin:0px;padding:0px;" colspan="2"><?php echo $related ?></td>
		</tr>
	<?php } ?>
	</tr>
	</table>
	<?php } ?>
	<br />
	<address>
		Modified by <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a><br /><br />
		<?php echo _XOOPS_WIKI_COPYRIGHT ?><br />
		<?php echo S_COPYRIGHT ?><br />
		Powered by PHP <?php echo PHP_VERSION ?><br /><br />
		HTML convert time to <?php echo $taketime ?> sec.
	</address>

  </td>
 </tr>
</table>
<script type="text/javascript">
<!--
pukiwiki_initTexts();
//-->
</script>
<!-- /pukiwikimod -->
