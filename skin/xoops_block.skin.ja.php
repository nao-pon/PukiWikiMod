<?php // $Id: xoops_block.skin.ja.php,v 1.5 2004/08/23 13:32:38 nao-pon Exp $

if (!defined('DATA_DIR')) { exit; }

global $xoopsModule, $xoopsUser, $modifier, $hide_navi, $anon_writable, $wiki_writable, $_freeze, $wiki_allow_newpage, $X_admin,$noattach,$noheader,$trackback;

$_freeze = is_freeze($vars[page]);
if($_freeze){
	// 凍結ページなら
	$hide_navi = ($X_admin)? 0 : $hide_navi;
}

$pukiwiki_url = XOOPS_URL."/modules/".$xoopsModule->dirname()."/";
// イメージタグのURLを訂正
$body = preg_replace("/(<img[^>]+src=('|\")?)\.\//","$1".$pukiwiki_url,$body);
?>
	<?php if (WIKI_THEME_CSS){ ?>
		<link rel="stylesheet" href="<?php echo WIKI_THEME_CSS ?>" type="text/css" media="screen" charset="shift_jis">
	<?php } else { ?>
		<link rel="stylesheet" href="<?php echo $pukiwiki_url ?>skin/default.ja.css" type="text/css" media="screen" charset="shift_jis">
	<?php } ?>
	<?php if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css")){ ?>
		<link rel="stylesheet" href="<?php echo $pukiwiki_url ?>cache/css.css" type="text/css" media="screen" charset="shift_jis">
	<?php } ?>
	<script language=javascript src="<?php echo $pukiwiki_url ?>skin/default.ja.js"></script>
	<div class="wiki_content" onmouseup="pukiwiki_pos();" onkeyup="pukiwiki_pos();">
	<?php echo $body ?>
	</div>
