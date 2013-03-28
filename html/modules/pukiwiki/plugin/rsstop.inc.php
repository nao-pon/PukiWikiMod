<?php
// $Id: rsstop.inc.php,v 1.2 2004/05/15 11:37:07 nao-pon Exp $

function plugin_rsstop_convert()
{
	global $vars, $content_id;
	// $content_id が 2以上になっている場合は
	// インクルードされているので処理しない。
	// テーブルセル中には #rsstop は書いても機能しない。
	if ($content_id === 1) $vars['is_rsstop'] = 1;
	return '';
}
?>