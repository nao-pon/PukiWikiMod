<?php // $Id: pukiwiki.skin.ja.php,v 1.3 2003/06/28 15:52:58 nao-pon Exp $

if (!defined('DATA_DIR')) { exit; }

global $xoopsModule, $xoopsUser, $modifier, $hide_navi, $anon_writable, $wiki_writable;
if(is_freeze($vars['page'])){
	if($hide_navi){
		if($xoopsUser){
			foreach($xoopsUser->groups as $_gid){
				if($_gid == 1){
					$hide_navi = 0;
				}
			}
		}
	}
} else {
	$hide_navi = 0;
}
?>
<meta http-equiv="content-style-type" content="text/css">
	<meta http-equiv="content-script-type" content="text/javascript">
<?php if (! ( ($vars['cmd']==''||$vars['cmd']=='read') && $is_page) ) { ?>
	<meta name="robots" content="noindex,nofollow" />
<?php } ?>
	<link rel="stylesheet" href="skin/default.ja.css" type="text/css" media="screen" charset="shift_jis">
	<?php if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css")){ ?>
		<link rel="stylesheet" href="cache/css.css" type="text/css" media="screen" charset="shift_jis">
	<?php } ?>
	<script language=javascript src="skin/default.js"></script>
<table border=0 width="100%" cellspacing="5"><tr><td>
	<?php if(!$hide_navi){ ?>
		<center><div style="width:80%;text-align:center;font-size:14px;font-weight:bold;border: #6699FF thick ridge 2px;background-color:#FFFFEE;padding:2px;"><?php echo $page ?></div>
	<?php if($is_page) { ?>
		[ <a href="<?php echo "$script?".rawurlencode($vars['page']) ?>">リロード</a> ]
		&nbsp;
	<?php
	$isfreeze =is_freeze($vars[page]);
	
	if ($anon_writable){
		echo "
		[ <a href=\"$script?plugin=newpage\">新規</a>";
		if (!$isfreeze) echo "
		| <a href=\"$link_edit\">編集</a>
		| <a href=\"$link_diff\">差分</a>
		| <a href=\"$script?plugin=attach&amp;pcmd=upload&amp;page=".rawurlencode($vars['page'])."\">添付</a>
		| <a href=\"$script?plugin=rename\">リネーム</a>";
		echo "
		]
		&nbsp;";
	} else {
		$source_tag = "<a href=\"$script?plugin=source&amp;page=".rawurlencode($vars['page'])."\">ソース</a>";
		if (is_freeze($vars[page])){
			echo "[ ".$source_tag." ]&nbsp;";
		} else {
			if ($wiki_writable < 2) {
				echo "[ <a href='".XOOPS_URL."/user.php'>ログインすると編集できます</a> | $source_tag ]&nbsp;";
			} else {
				echo "[ ".$source_tag." ]&nbsp;";
			}
		}
	}
	?>
	
	<?php } ?>
	[ <a href="<?php echo $link_top ?>">トップ</a>
	| <a href="<?php echo $link_list ?>">一覧</a>
	| <a href="<?php echo $link_search ?>">単語検索</a>
	<?php if(arg_check("list")) { ?>
		| <a href="<?php echo $link_filelist ?>">ファイル名一覧</a>
	<?php } ?>
	| <a href="<?php echo $link_whatsnew ?>">最新</a>
	| <a href="<?php echo "$script?plugin=yetlist" ?>">未入力</a>
	<?php if($do_backup) { ?>
		| <a href="<?php echo $link_backup ?>">バックアップ</a>
	<?php } ?>
	| <a href="<?php echo "$script?".rawurlencode("[[ヘルプ]]") ?>">ヘルプ</a>
	]<br /></center>

	<?php echo $hr ?>

	<?php
	if ($is_page) {
		if (strip_bracket($vars['page']) != $defaultpage) {
			require_once(PLUGIN_DIR.'where.inc.php');
			echo plugin_where_convert();
		}
		require_once(PLUGIN_DIR.'counter.inc.php');
		echo "<div style=\"float:right\">".plugin_counter_convert()."</div>";
		//echo "<br clear=all />";
		echo $hr;
	}
	?>
	
	<?php } ?>
	<?php if($is_page) { ?>
		<table cellspacing="1" cellpadding="0" border="0" width="100%">
			<tr>
			<td valign="top" style="word-break:break-all;">
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
			<a href="<?php echo "$script?".rawurlencode($vars[page]) ?>"><img src="./image/reload.gif" width="20" height="20" border="0" alt="リロード" /></a>
			&nbsp;
		  <?php if ($anon_writable){ ?>
			<a href="<?php echo $script ?>?plugin=newpage"><img src="./image/new.gif" width="20" height="20" border="0" alt="新規" /></a>
			<a href="<?php echo $link_edit ?>"><img src="./image/edit.gif" width="20" height="20" border="0" alt="編集" /></a>
			<a href="<?php echo $link_diff ?>"><img src="./image/diff.gif" width="20" height="20" border="0" alt="差分" /></a>
			&nbsp;
		  <?php } ?>
		<?php } ?>
		<a href="<?php echo $link_top ?>"><img src="./image/top.gif" width="20" height="20" border="0" alt="トップ" /></a>
		<a href="<?php echo $link_list ?>"><img src="./image/list.gif" width="20" height="20" border="0" alt="一覧" /></a>
		<a href="<?php echo $link_search ?>"><img src="./image/search.gif" width="20" height="20" border="0" alt="検索" /></a>
		<a href="<?php echo $link_whatsnew ?>"><img src="./image/recentchanges.gif" width="20" height="20" border="0" alt="最終更新" /></a>
		<?php if($do_backup) { ?>
			<a href="<?php echo $link_backup ?>"><img src="./image/backup.gif" width="20" height="20" border="0" alt="バックアップ" /></a>
		<?php } ?>
		&nbsp;
		<a href="<?php echo "$script?".rawurlencode("[[ヘルプ]]") ?>"><img src="./image/help.gif" width="20" height="20" border="0" alt="ヘルプ" /></a>
		&nbsp;
		<a href="pukiwiki.php?cmd=rss"><img src="./image/rss.gif" width="36" height="14" border="0" alt="最終更新のRSS" /></a>
	</div>
	<?php $pg_auther_name=get_pg_auther_name($vars["page"]);
	if ($pg_auther_name){ ?>
	<span class="small">ページ作成:<a href="<?=XOOPS_URL?>/userinfo.php?uid=<?=get_pg_auther($vars["page"])?>"><?=$pg_auther_name?></a> - </span>
	<?php } ?>
	<?php if($fmt) { ?>
		 <span class="small">最終更新: <?php echo date("Y/m/d H:i:s T",$fmt) ?></span> <?php echo get_pg_passage($vars["page"]) ?><br />
	<?php } ?>
	<?php if($related) { ?>
		 <span class="small">リンクページ: <?php echo $related ?></span><br />
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
