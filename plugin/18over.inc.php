<?php
// $Id: 18over.inc.php,v 1.1 2006/06/23 14:35:54 nao-pon Exp $

function plugin_18over_init()
{
	if (LANG == "ja")
	{
		$mes = array(
			'_18over_mes' => array(
				'confirm' => 'このページには性的な表現を扱ったコンテンツが一部ございます。\n18歳未満およびそういった表現を好まれない方は閲覧をご遠慮下さい。\n\n当サイト内のそのようなページを閲覧しますか？',
			)
		);
	}
	else
	{
		$mes = array(
			'_18over_mes' => array(
				'confirm' => 'There are contents that treat a sexual expression in this page. Is this page displayed?',
			)
		);
	}
	set_plugin_messages($mes);
}

function plugin_18over_convert()
{
	global $stack,$_18over_mes,$script;
	
	if (isset($stack['javascripts']['18over'])) return '';
	
	$stack['javascripts']['18over'] = <<< EOD
<script type="text/javascript">
<!--
if (!pukiwiki_load_cookie("pwm18"))
{
	if (confirm("{$_18over_mes['confirm']}"))
		pukiwiki_save_cookie("pwm18",1,0,"/");
	else
		location.href = "{$script}";
}
//-->
</script>
EOD;
	
	return '';
}
?>