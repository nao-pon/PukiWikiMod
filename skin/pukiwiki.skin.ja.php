<?php 
// $Id: pukiwiki.skin.ja.php,v 1.43 2006/06/16 06:07:41 nao-pon Exp $
if (!defined('DATA_DIR')) exit;
global $pwm_confg;
?>

<!-- pukiwikimod -->
	<script type="text/javascript">
	<!--
	var pukiwiki_root_url = "<?php echo XOOPS_WIKI_HOST.XOOPS_WIKI_URL ?>/";
	//-->
	</script>
	<script type="text/javascript" src="skin/default.ja.js"></script>
<div class="pukiwiki_body">
	<?php if((!$hide_navi && !$noheader) || !$is_read){ // header ?>
		<center><div class="wiki_page_title"><?php echo $page ?></div>
	<?php if($is_page) { ?>
		[ <a href="<?php echo $link_page ?>">�����</a> ]
		&nbsp;
	<?php
	$source_tag = "<a href=\"$link_source\">������</a>";
	if ($anon_writable){
		if ($wiki_allow_newpage){
			echo "[ <a href=\"$link_new\">����</a> | ";
		} else {
			echo "[ ";
		}
		if (!$_freeze) {
			echo "
			<a href=\"$link_edit\">�Խ�</a> | <a href=\"$link_diff\">��ʬ</a> | <a href=\"$link_attach\">ź��</a> ";
			if ($X_admin){
				echo "| <a href=\"$link_rename\">��͡���</a> ";
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
				echo "[ <a href='".XOOPS_URL."/user.php'>�����󤹤���Խ��Ǥ��ޤ�</a> | $source_tag ]&nbsp;";
			} else {
				echo "[ ".$source_tag." ]&nbsp;";
			}
		}
	}
	?>
	
	<?php } ?>
	[ <a href="<?php echo $link_top ?>">�ȥå�</a>
	| <a href="<?php echo $link_list ?>">����</a>
	| <a href="<?php echo $link_search ?>">ñ�측��</a>
	<?php if(arg_check("list")) { ?>
		| <a href="<?php echo $link_filelist ?>">ź�եե��������</a>
	<?php } ?>
	| <a href="<?php echo $link_whatsnew ?>">�ǿ�</a>
	<?php if ($wiki_allow_newpage){ ?>
	| <a href="<?php echo "$script?plugin=yetlist" ?>">̤����</a>
	<?php } ?>
	<?php if($do_backup) { ?>
		| <a href="<?php echo $link_backup ?>">�Хå����å�</a>
	<?php } ?>
	| <a href="<?php echo "$script?".rawurlencode("�إ��") ?>">�إ��</a>
	]<br /></center>
	<?php echo $hr ?>
	<?php } else { if (!$_freeze) { // header ?>
		<div style="float:right;width:65px;"><a href="<?php echo $link_attach ?>"><img src="./image/file.png" width="20" height="20" border="0" alt="�ե�����ź��" title="�ե�����ź��" /></a><a href="<?php echo $link_edit ?>"><img src="./image/edit_button.gif" width="45" height="22" border="0" alt="�Խ�" title="�Խ�" /></a></div>
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

	<?php
	if (isset($pwm_confg['ad_top']))
	{
		echo $pwm_confg['ad_top'];
	}
	?>
	
	<div class="wiki_header_img"></div>
	<div class="wiki_content" id="body" style="width:100%;">
	<?php echo $body ?>
	</div>
	<?php if ($is_page && $fusen_tag) { echo $fusen_tag; } ?>
	<?php if($attaches)
		{
			print $hr;
			print $attaches;
		}
	?>
	<?php if ($is_read && $trackback_body){ echo $trackback_body; } ?>
	
	<?php if($use_xoops_tpl){
		ob_start ();
	} ?>
	
	<div>
	<div style="text-align:right">
		<?php if($is_page) { ?>
		<a href="<?php echo $link_page ?>"><img src="./image/reload.png" width="20" height="20" border="0" alt="�����" title="�����" /></a>
		&nbsp;
		<?php if (!$_freeze){ ?>
		<?php if ($wiki_allow_newpage){ ?>
		<a href="<?php echo $link_new ?>"><img src="./image/new.png" width="20" height="20" border="0" alt="����" title="����" /></a>
		<a href="<?php echo $link_copy ?>"><img src="./image/copy.png" width="20" height="20" border="0" alt="���ԡ�" title="���ԡ�" /></a>
		<?php } // $wiki_allow_newpage ?>
		<a href="<?php echo $link_edit ?>"><img src="./image/edit.png" width="20" height="20" border="0" alt="�Խ�" title="�Խ�" /></a>
		<a href="<?php echo $link_rename ?>"><img src="./image/rename.png" width="20" height="20" border="0" alt="��͡���" title="��͡���" /></a>
		&nbsp;
		<?php } // !$_freeze ?>
		<a href="<?php echo $link_diff ?>"><img src="./image/diff.png" width="20" height="20" border="0" alt="��ʬ" title="��ʬ" /></a>
		<a href="<?php echo $link_source ?>"><img src="./image/source.png" width="20" height="20" border="0" alt="������" title="������" /></a>
		<a href="<?php echo $link_attach ?>"><img src="./image/file.png" width="20" height="20" border="0" alt="�ե�����ź��" title="�ե�����ź��" /></a>
		<a href="<?php echo $link_attachlist ?>"><img src="./image/attach.png" width="20" height="20" border="0" alt="ź�եե��������" title="ź�եե��������" /></a>
		&nbsp;
		<?php } // $is_page ?>
		<a href="<?php echo $link_top ?>"><img src="./image/top.png" width="20" height="20" border="0" alt="Wiki�ȥå�" title="Wiki�ȥå�" /></a>
		<a href="<?php echo $link_list ?>"><img src="./image/list.png" width="20" height="20" border="0" alt="����" title="����" /></a>
		<a href="<?php echo $link_search ?>"><img src="./image/search.png" width="20" height="20" border="0" alt="����" title="����" /></a>
		<a href="<?php echo $link_whatsnew ?>"><img src="./image/recentchanges.png" width="20" height="20" border="0" alt="�ǽ�����" title="�ǽ�����" /></a>
		<?php if($do_backup) { ?>
		<a href="<?php echo $link_backup ?>"><img src="./image/backup.png" width="20" height="20" border="0" alt="�Хå����å�" title="�Хå����å�" /></a>
		<?php } // $do_backup ?>
		&nbsp;
		<a href="<?php echo "$script?".rawurlencode("�إ��") ?>"><img src="./image/help.png" width="20" height="20" border="0" alt="�إ��" title="�إ��" /></a>
		&nbsp;
		<a href="<?php echo $script ?>?cmd=rss10"><img src="./image/rss.png" width="36" height="14" border="0" alt="�ǽ�������RSS" title="�ǽ�������RSS" /></a>
	</div>
	<?php
	if (isset($pwm_confg['ad_bottom']))
	{
		echo $pwm_confg['ad_bottom'];
	}
	
	if ($is_page)
	{
	?>
	<table style="width:auto;" class="small">
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">�ڡ���̾:</td><td style="margin:0px;padding:0px;" colspan="2"><?php echo strip_bracket($vars['page'])." ".$sended_ping_tag ?></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">�ڡ�������:</td><td style="margin:0px;padding:0px;white-space:nowrap;"><?php echo $pg_auther_name ?></td><td style="margin:0px;padding:0px;;width:100%;"> - <?php echo date("Y/m/d H:i:s T",$pginfo['buildtime']) . "<small>" . get_passage($pginfo['buildtime']); ?></small></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">�ǽ�����:</td><td style="margin:0px;padding:0px;white-space:nowrap;"><?php echo $last_editer ?></td><td style="margin:0px;padding:0px;width:100%;"> - <?php echo date("Y/m/d H:i:s T",$pginfo['editedtime']) . get_pg_passage($vars["page"]); ?></td>
	</tr>
	<tr>
	<td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">�Խ���:</td><td style="margin:0px;padding:0px;white-space:nowrap;" colspan="2"><?php echo $allow_edit_groups.$allow_editers ?></td>
	</tr>
	<?php if($related) { ?>
		<tr>
		 <td style="text-align:right;margin:0px;padding:0px;white-space:nowrap;">��󥯥ڡ���:<td style="margin:0px;padding:0px;" colspan="2"><?php echo $related ?></td>
		</tr>
	<?php } ?>
	</table>
	<?php } ?>
	</div>
	
	<?php if($use_xoops_tpl){
		$xoopsTpl->assign('page_info', ob_get_contents());
		ob_end_clean();
		ob_start ();
	} ?>
	
	<div>
		<address>
			Modified by <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a><br /><br />
			<?php echo _XOOPS_WIKI_COPYRIGHT ?><br />
			<?php echo S_COPYRIGHT ?><br />
			Powered by PHP <?php echo PHP_VERSION ?><br /><br />
			HTML convert time to <?php echo $taketime ?> sec.
		</address>
	</div>
	
	<?php if($use_xoops_tpl){
		$xoopsTpl->assign('pukiwiki_footer', ob_get_contents());
		ob_end_clean();
	} ?>
</div>
<!-- /pukiwikimod -->
