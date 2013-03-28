<?php 
// $Id: pukiwiki.skin.en.php,v 1.13 2006/06/23 14:19:58 nao-pon Exp $
if (!defined('DATA_DIR')) exit;
?>

<!-- pukiwikimod -->
<div class="pukiwiki_body" onmouseup="pukiwiki_pos();return true;" onkeyup="pukiwiki_pos();return true;">
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
	| <a href="<?php echo $link_list ?>">Page List</a>
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
		<div style="float:right;width:65px;"><a href="<?php echo $link_attach ?>"><img src="./image/file.png" width="20" height="20" border="0" alt="Attach" title="Attach" /></a><a href="<?php echo $link_edit ?>"><img src="./image/edit_button.gif" width="45" height="22" border="0" alt="edit" title="edit" /></a></div>
	<?php } } ?>

	<?php if($is_read) { ?>
	
	<div class="wiki_page_where">
	<?php echo $where ?>
	</div>
	
	<div style="float:left;text-align:left;width:49%;">
	<?php echo "<small>".$comments_tag.$tb_tag."<span id='pukiwiki_fusenlist' name='pukiwiki_fusenlist'></span></small>" ?>
	</div>	
	
	<div style="float:right;text-align:right;width:50%;">
	<?php echo $counter ?>
	</div>
	
	<div style="clear:both;"></div>
	
	<div class="wiki_page_navi"><?php echo get_prevpage_link_by_name($vars['page']) ?> <img src="./image/prev.png" width="6" height="12" alt="Prev" /> <img src="./image/next.png" width="6" height="12" alt="Next" /> <?php echo get_nextpage_link_by_name($vars['page']) ?></div>
	
	<?php } // is_read ?>
	<div class="wiki_header_img"></div>
	<div class="wiki_content" id="body" style="width:100%;">
	<?php echo $body ?>
	</div>
	<?php if ($is_page && $fusen_tag) { echo $fusen_tag; } ?>
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
		<a href="<?php echo $link_page ?>"><img src="./image/reload.png" width="20" height="20" border="0" alt="Reload" title="Reload" title="Reload" /></a>
		&nbsp;
		<?php if (!$_freeze){ ?>
		<?php if ($wiki_allow_newpage){ ?>
		<a href="<?php echo $link_new ?>"><img src="./image/new.png" width="20" height="20" border="0" alt="New" title="New" title="New" /></a>
		<a href="<?php echo $link_copy ?>"><img src="./image/copy.png" width="20" height="20" border="0" alt="Page Copy" title="Page Copy" title="Page Copy" /></a>
		<?php } // $wiki_allow_newpage ?>
		<a href="<?php echo $link_edit ?>"><img src="./image/edit.png" width="20" height="20" border="0" alt="Edit" title="Edit" title="Edit" /></a>
		<a href="<?php echo $link_rename ?>"><img src="./image/rename.png" width="20" height="20" border="0" alt="Rename" title="Rename" title="Rename" /></a>
		&nbsp;
		<?php } // !$_freeze ?>
		<a href="<?php echo $link_diff ?>"><img src="./image/diff.png" width="20" height="20" border="0" alt="Diff" title="Diff" title="Diff" /></a>
		<a href="<?php echo $link_source ?>"><img src="./image/source.png" width="20" height="20" border="0" alt="Source" title="Source" title="Source" /></a>
		<a href="<?php echo $link_attach ?>"><img src="./image/file.png" width="20" height="20" border="0" alt="Attach" title="Attach" title="Attach" /></a>
		<a href="<?php echo $link_attachlist ?>"><img src="./image/attach.png" width="20" height="20" border="0" alt="Attach List" title="Attach List" title="Attach List" /></a>
		&nbsp;
		<?php } // $is_page ?>
		<a href="<?php echo $link_top ?>"><img src="./image/top.png" width="20" height="20" border="0" alt="Wiki Top" title="Wiki Top" title="Wiki Top" /></a>
		<a href="<?php echo $link_list ?>"><img src="./image/list.png" width="20" height="20" border="0" alt="Page List" title="Page List" title="Page List" /></a>
		<a href="<?php echo $link_search ?>"><img src="./image/search.png" width="20" height="20" border="0" alt="Search" title="Search" title="Search" /></a>
		<a href="<?php echo $link_whatsnew ?>"><img src="./image/recentchanges.png" width="20" height="20" border="0" alt="Resent Chenges" title="Resent Chenges" title="Resent Chenges" /></a>
		<?php if($do_backup) { ?>
		<a href="<?php echo $link_backup ?>"><img src="./image/backup.png" width="20" height="20" border="0" alt="Backup" title="Backup" title="Backup" /></a>
		<?php } // $do_backup ?>
		&nbsp;
		<a href="<?php echo "$script?".rawurlencode("Help") ?>"><img src="./image/help.png" width="20" height="20" border="0" alt="Help" title="Help" title="Help" /></a>
		&nbsp;
		<a href="<?php echo $script ?>?cmd=rss10"><img src="./image/rss.png" width="36" height="14" border="0" alt="RSS feed" title="RSS feed" title="RSS feed" /></a>
	</div>
	<?php
	if ($is_page)
	{
	?>
	<table style="width:auto;" class="small">
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Page name:</td><td style="margin:0px;padding:0px;" colspan="2"><?php echo strip_bracket($vars['page'])." ".$sended_ping_tag ?></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Author:</td><td style="margin:0px;padding:0px;white-space:nowrap;"><a href="<?php echo XOOPS_URL ?>/userinfo.php?uid=<?php echo get_pg_auther($vars["page"]) ?>"><?php echo $pg_auther_name ?></a></td><td style="margin:0px;padding:0px;;width:100%;"> - <?php echo date("Y/m/d H:i:s T",$pginfo['buildtime']) . "<small>" . get_passage($pginfo['buildtime']); ?></small></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Last edit:</td><td style="margin:0px;padding:0px;white-space:nowrap;"><a href="<?php echo XOOPS_URL ?>/userinfo.php?uid=<?php echo $pginfo['lastediter'] ?>"><?php echo $last_editer ?></a></td><td style="margin:0px;padding:0px;width:100%;"> - <?php echo date("Y/m/d H:i:s T",$pginfo['editedtime']) . get_pg_passage($vars["page"]); ?></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Editers:</td><td style="margin:0px;padding:0px;white-space:nowrap;" colspan="2"><?php echo $allow_edit_groups.$allow_editers ?></td>
	</tr>
	<?php if($related) { ?>
		<tr>
		 <td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">Back Link:<td style="margin:0px;padding:0px;" colspan="2"><?php echo $related ?></td>
		</tr>
	<?php } ?>
	</table>
	<?php } ?>
	<?php if(!$use_xoops_tpl){ ?>
	<br />
	<address>
		Modified by <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a><br /><br />
		<?php echo _XOOPS_WIKI_COPYRIGHT ?><br />
		<?php echo S_COPYRIGHT ?><br />
		Powered by PHP <?php echo PHP_VERSION ?><br /><br />
		HTML convert time to <?php echo $taketime ?> sec.
	</address>
	<?php } ?>

</div>
<!-- /pukiwikimod -->
