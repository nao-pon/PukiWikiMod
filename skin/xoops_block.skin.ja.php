<?php // $Id: xoops_block.skin.ja.php,v 1.11 2004/12/04 16:04:40 nao-pon Exp $

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

<!-- PukiWiki Block -->
<script type="text/javascript">
<!--
var pukiwiki_root_url = "<?php echo $pukiwiki_url ?>";
-->
</script>
<table border="0" cellspacing="0" style="width:100%;" onmouseup=pukiwiki_pos() onkeyup=pukiwiki_pos()>
 <tr>
  <td class="pukiwiki_body">
	<script language=javascript src="<?php echo $pukiwiki_url ?>skin/default.ja.js"></script>
	<div class="wiki_content">
	<?php echo $body ?>
	</div>
  </td>
 </tr>
</table>
<script type="text/javascript">
<!--
pukiwiki_initTexts();
//-->
</script>
<!-- /PukiWiki Block -->

