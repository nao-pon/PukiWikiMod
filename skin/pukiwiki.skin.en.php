<?php // $Id: pukiwiki.skin.en.php,v 1.3 2003/07/15 13:19:02 nao-pon Exp $

if (!defined('DATA_DIR')) { exit; }

global $xoopsModule, $xoopsUser, $modifier, $hide_navi, $anon_writable, $wiki_writable, $_freeze, $wiki_allow_newpage, $X_admin;

$_freeze = is_freeze($vars[page]);
if($_freeze){
	// if it is frozen
	$hide_navi = ($X_admin)? 0 : $hide_navi;
}

?>
<meta http-equiv="content-style-type" content="text/css">
	<meta http-equiv="content-script-type" content="text/javascript">
<?php if (! ( ($vars['cmd']==''||$vars['cmd']=='read') && $is_page) ) { ?>
	<meta name="robots" content="noindex,nofollow" />
<?php } ?>
	<link rel="stylesheet" href="skin/default.en.css" type="text/css" media="screen" charset="shift_jis">
	<?php if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css")){ ?>
		<link rel="stylesheet" href="cache/css.css" type="text/css" media="screen" charset="shift_jis">
	<?php } ?>
	<script language=javascript src="skin/default.js"></script>
<table border=0 width="100%" cellspacing="5"><tr><td>
	<?php if(!$hide_navi){ ?>
		<center><div style="width:80%;text-align:center;font-size:14px;font-weight:bold;border: #6699FF thick ridge 2px;background-color:#FFFFEE;padding:2px;"><?php echo $page ?></div>
	<?php if($is_page) { ?>
		[ <a href="<?php echo "$script?".rawurlencode($vars['page']) ?>">Reload</a> ]
		&nbsp;
	<?php
	$source_tag = "<a href=\"$script?plugin=source&amp;page=".rawurlencode($vars['page'])."\">Source</a>";
	if ($anon_writable){
		if ($wiki_allow_newpage){
			echo "[ <a href=\"$script?plugin=newpage\">New</a> | ";
		} else {
			echo "[ ";
		}
		if (!$_freeze) {
			echo "
			<a href=\"$link_edit\">Edit</a> | <a href=\"$link_diff\">Diff</a> | <a href=\"$script?plugin=attach&amp;pcmd=upload&amp;page=".rawurlencode($vars['page'])."\">Attach</a> ";
			if ($X_admin){
				echo "| <a href=\"$script?plugin=rename\">Rename</a> ";
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
				echo "[ <a href='".XOOPS_URL."/user.php'>Login to edit</a> | $source_tag ]&nbsp;";
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
		| <a href="<?php echo $link_filelist ?>">List</a>
	<?php } ?>
	| <a href="<?php echo $link_whatsnew ?>">Recent</a>
	| <a href="<?php echo "$script?plugin=yetlist" ?>">Yet List</a>
	<?php if($do_backup) { ?>
		| <a href="<?php echo $link_backup ?>">Backup</a>
	<?php } ?>
	| <a href="<?php echo "$script?".rawurlencode("[[Help]]") ?>">Help</a>
	]<br /></center>

	<?php echo $hr ?>

	<?php
	if ($is_page) {
		if (strip_bracket($vars['page']) != $defaultpage) {
			require_once(PLUGIN_DIR.'where.inc.php');
			echo do_plugin_convert("where");
		}
		require_once(PLUGIN_DIR.'counter.inc.php');
		echo "<div style=\"float:right\">".do_plugin_convert("counter")."</div>";
		echo $hr;
	}
	?>
	
	<?php } ?>
	<?php if($is_page) { ?>
		<table cellspacing="1" cellpadding="0" border="0" width="100%">
			<tr>
			<td valign="top" style="word-break:normal;">
	<?php } ?>
	<?php echo $body ?>
	<?php if($is_page) { ?>
			</td>
			</tr>
		</table>
	<?php } ?>
	<?php echo $hr ?>
	<?php
		if(file_exists(PLUGIN_DIR."attach.inc.php") && $is_read)
		{
			require_once(PLUGIN_DIR."attach.inc.php");
			$attaches = attach_filelist();
			if($attaches)
			{
				print $attaches;
				print $hr;
			}
		}
	?>
	<div style="text-align:right">
		<?php if($is_page) { ?>
			<a href="<?php echo "$script?".rawurlencode($vars[page]) ?>"><img src="./image/reload.gif" width="20" height="20" border="0" alt="Reload" /></a>
			&nbsp;
		  <?php if (!$_freeze){ ?>
		  <?php if ($wiki_allow_newpage){ ?>
			<a href="<?php echo $script ?>?plugin=newpage"><img src="./image/new.gif" width="20" height="20" border="0" alt="New" /></a>
			<?php } ?>
			<a href="<?php echo $link_edit ?>"><img src="./image/edit.gif" width="20" height="20" border="0" alt="Edit" /></a>
			<a href="<?php echo $link_diff ?>"><img src="./image/diff.gif" width="20" height="20" border="0" alt="Diff" /></a>
			&nbsp;
		  <?php } ?>
		<?php } ?>
		<a href="<?php echo $link_top ?>"><img src="./image/top.gif" width="20" height="20" border="0" alt="Top" /></a>
		<a href="<?php echo $link_list ?>"><img src="./image/list.gif" width="20" height="20" border="0" alt="List" /></a>
		<a href="<?php echo $link_search ?>"><img src="./image/search.gif" width="20" height="20" border="0" alt="Search" /></a>
		<a href="<?php echo $link_whatsnew ?>"><img src="./image/recentchanges.gif" width="20" height="20" border="0" alt="Recent" /></a>
		<?php if($do_backup) { ?>
			<a href="<?php echo $link_backup ?>"><img src="./image/backup.gif" width="20" height="20" border="0" alt="Backup" /></a>
		<?php } ?>
		&nbsp;
		<a href="<?php echo "$script?".rawurlencode("[[Help]]") ?>"><img src="./image/help.gif" width="20" height="20" border="0" alt="Help" /></a>
		&nbsp;
		<a href="pukiwiki.php?cmd=rss"><img src="./image/rss.gif" width="36" height="14" border="0" alt="RSS feeds" /></a>
	</div>
	<?php $pg_auther_name=get_pg_auther_name($vars["page"]);
	if ($pg_auther_name){ ?>
	<span class="small">Created by:<a href="<?=XOOPS_URL?>/userinfo.php?uid=<?=get_pg_auther($vars["page"])?>"><?=$pg_auther_name?></a> - </span>
	<?php } ?>
	<?php if($fmt) { ?>
		 <span class="small">Last Modified: <?php echo date("Y/m/d H:i:s T",$fmt) ?></span> <?php echo get_pg_passage($vars["page"]) ?><br />
	<?php } ?>
	<?php if($related) { ?>
		 <span class="small">Links: <?php echo $related ?></span><br />
	<?php } ?>
	<br />
	<address>
		Modified by <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a><br /><br />
		<?php echo _XOOPS_WIKI_COPYRIGHT ?><br />
		<?php echo S_COPYRIGHT ?><br />
		Powered by PHP <?php echo PHP_VERSION ?><br /><br />
		HTML convert time to <?php echo $taketime ?> sec.
	</address>
</td></tr></table>
