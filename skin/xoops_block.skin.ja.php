<?php // $Id: xoops_block.skin.ja.php,v 1.7 2004/11/01 13:43:21 nao-pon Exp $

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
	<!-- This tag will be replace CSS Link -->
	<?php if(is_readable(XOOPS_ROOT_PATH."/modules/".$xoopsModule->dirname()."/cache/css.css")){ ?>
		<link rel="stylesheet" href="<?php echo $pukiwiki_url ?>cache/css.css" type="text/css" media="screen" charset="shift_jis">
	<?php } ?>
	<script type="text/javascript">
	<!--
	var pukiwiki_root_url = "<?php echo $pukiwiki_url ?>";
	-->
	</script>
	<script language=javascript src="<?php echo $pukiwiki_url ?>skin/default.ja.js"></script>
	<div class="wiki_content" onmouseup="pukiwiki_pos();" onkeyup="pukiwiki_pos();">
	<?php echo $body ?>
	</div>
