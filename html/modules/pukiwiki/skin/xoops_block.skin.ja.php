<?php // $Id: xoops_block.skin.ja.php,v 1.12 2005/02/23 00:16:41 nao-pon Exp $

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
<table border="0" cellspacing="0" style="width:100%;" onmouseup=pukiwiki_pos() onkeyup=pukiwiki_pos()>
 <tr>
  <td class="pukiwiki_body">
	<div class="wiki_content">
	<?php echo $body ?>
	</div>
  </td>
 </tr>
</table>
<!-- /PukiWiki Block -->
