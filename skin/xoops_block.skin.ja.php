<?php // $Id: xoops_block.skin.ja.php,v 1.1 2004/01/12 13:12:06 nao-pon Exp $

if (!defined('DATA_DIR')) { exit; }

global $xoopsModule, $xoopsUser, $modifier, $hide_navi, $anon_writable, $wiki_writable, $_freeze, $wiki_allow_newpage, $X_admin,$noattach,$noheader,$trackback;

$_freeze = is_freeze($vars[page]);
if($_freeze){
	// 凍結ページなら
	$hide_navi = ($X_admin)? 0 : $hide_navi;
}

$pukiwiki_url = XOOPS_URL."/modules/".$xoopsModule->dirname()."/";
?>

	<link rel="stylesheet" href="<?php echo $pukiwiki_url ?>skin/default.ja.css" type="text/css" media="screen" charset="shift_jis">
	<?php if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css")){ ?>
		<link rel="stylesheet" href="<?php echo $pukiwiki_url ?>cache/css.css" type="text/css" media="screen" charset="shift_jis">
	<?php } ?>
	<script language=javascript src="<?php echo $pukiwiki_url ?>skin/default.js"></script>
<table border=0 cellspacing="5" style="width:100%;"><tr><td class="pukiwiki_body">
	<?php if($is_page) { ?>
		<table cellspacing="1" cellpadding="0" border="0" style="width:100%;">
			<tr>
			<td valign="top" style="word-break:break-all;">
	<?php } ?>
	<?php echo $body ?>
	<?php if($is_page) { ?>
			</td>
			</tr>
		</table>
	<?php } ?>
</td></tr></table>
