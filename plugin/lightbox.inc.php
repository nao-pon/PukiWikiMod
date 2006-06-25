<?php
// $Id: lightbox.inc.php,v 1.1 2006/06/25 06:43:27 nao-pon Exp $

function plugin_lightbox_init()
{
	if (LANG == "ja")
	{
		$mes = array(
			'_lightbox_mes' => array(
			)
		);
	}
	else
	{
		$mes = array(
			'_lightbox_mes' => array(
			)
		);
	}
	set_plugin_messages($mes);
}

function plugin_lightbox_convert()
{
	global $stack,$_lightbox_mes,$script;
	
	if (isset($stack['addheaders']['lightbox'])) return '';
	
	$stack['addheaders']['lightbox'] = <<< EOD
<script type="text/javascript" src="./plugin_data/lightbox/js/prototype.js"></script>
<script type="text/javascript" src="./plugin_data/lightbox/js/scriptaculous.js?load=effects"></script>
<script type="text/javascript" src="./plugin_data/lightbox/js/lightbox.js"></script>
<link rel="stylesheet" href="./plugin_data/lightbox/css/lightbox.css" type="text/css" media="screen" />
EOD;
	
	return '';
}
?>