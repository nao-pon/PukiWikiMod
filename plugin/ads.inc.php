<?php
//
// $Id: ads.inc.php,v 1.1 2004/04/03 14:18:56 nao-pon Exp $
//

function plugin_ads_convert() {

//É¬¤ºÀßÄê Google AdSense ID
$adsid = '';

//ÂåÂØ¹­¹ğ
$other_ad = 'google_alternate_ad_url = "http://hypweb.net/ads/dell_125_125.html";';

global $wiki_ads_shown;

if ($wiki_ads_shown) return "";

$wiki_ads_shown = 1;

list($type) = func_get_args();

if ($type == "h2")
{
	$type = 'google_ad_width = 120;
	google_ad_height = 240;
	google_ad_format = "120x240_as";';
	$width = "120";
}
else
{
	$type = 'google_ad_width = 125;
	google_ad_height = 125;
	google_ad_format = "125x125_as";';
	$width = "125";
}

$ret = '
	<script type="text/javascript"><!--
	google_ad_client = "'.$adsid.'";
	'.$other_ad.'
	'.$type.'
	google_ad_channel ="0557759778";
	google_color_border = "B4D0DC";
	google_color_bg = "ECF8FF";
	google_color_link = "0000CC";
	google_color_url = "008000";
	google_color_text = "6F6F6F";
	//--></script>
	<script type="text/javascript"
	  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
	</script>
';
	return "<div style='float:right;width:{$width}px;'>$ret</div>\n";
}
?>