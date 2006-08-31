<?php
/* Pukiwiki GoogleMaps plugin 2.0
 * http://reddog.s35.xrea.com
 * -------------------------------------------------------------------
 * Copyright (c) 2005, 2006 OHTSUKA, Yoshio
 * This program is free to use, modify, extend at will. The author(s)
 * provides no warrantees, guarantees or any responsibility for usage.
 * Redistributions in any form must retain this copyright notice.
 * ohtsuka dot yoshio at gmail dot com
 * -------------------------------------------------------------------
 * 2005-09-25 1.1 Release
 * 2006-04-20 2.0 GoogleMaps API ver2
 */

define ('PLUGIN_GOOGLEMAPS2_MK_DEF_TITLE', '名称未設定'); //デフォルトのマーカーの名前
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_CAPTION', '');         //デフォルトのマーカーのキャプション
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_NOLIST', false);       //マーカーのリストを出力しない
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_NOINFOWINDOW', false); //マーカーのinfoWindowを表示しない
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_ZOOM', null);          //マーカーの初期zoom値。nullは初期値無し。
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_ICON', '');        //アイコン。空の時はデフォルト
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_NOICON', false);   //アイコンを表示しない。
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_AJUMP', '[説明]'); //infoWindowから本文中へのリンク文字

//FORMATLISTはhtmlに出力されるマーカーのリストの雛型
//FMTINFOはマップ上のマーカーをクリックして表示されるフキダシの（中の）雛型
//今のとこ%title%と%caption%の置換えのみ。
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATLIST' , '<b>%title%</b> - %caption%');
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATINFO' , '<b>%title%</b><br><div style=\'width:200px;\'><span style=\'float:left;\'>%image%</span>%caption%</div>');

//リストをクリックするとマップにフォーカスさせる。(0 or 1)
define ('PLUGIN_GOOGLEMAPS2_MK_DEF_ALINK' , 1);

function plugin_googlemaps2_mark_get_default () {
	global $vars;
	return array (
		'title'        => PLUGIN_GOOGLEMAPS2_MK_DEF_TITLE,
		'caption'      => PLUGIN_GOOGLEMAPS2_MK_DEF_CAPTION,
		'image'        => '',
		'icon'         => PLUGIN_GOOGLEMAPS2_MK_DEF_ICON,
		'nolist'       => PLUGIN_GOOGLEMAPS2_MK_DEF_NOLIST,
		'noinfowindow' => PLUGIN_GOOGLEMAPS2_MK_DEF_NOINFOWINDOW,
		'noicon'       => PLUGIN_GOOGLEMAPS2_MK_DEF_NOICON,
		//'zoom'         => PLUGIN_GOOGLEMAPS2_MK_DEF_ZOOM,
		'zoom'         => NULL,
		'type'         => NULL,
		'map'          => $vars['googlemaps2_defmapname'],
		'formatlist'   => PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATLIST,
		'formatinfo'   => PLUGIN_GOOGLEMAPS2_MK_DEF_FORMATINFO,
		'alink'        => PLUGIN_GOOGLEMAPS2_MK_DEF_ALINK
	);
}

function plugin_googlemaps2_mark_inline() {
	if (!exist_plugin('googlemaps2')) return false;
	$args = func_get_args();
	$body = array_pop($args);
	if (sizeof($args)<2) {
		return "error: plugin googlemaps2_mark wrong args\n";
	}
	return plugin_googlemaps2_mark_output($args[0], $args[1], array_slice($args, 2), $body);
}

function plugin_googlemaps2_mark_output($lat, $lng, $params, $body='') {
	global $vars, $pgid;

	$defoptions = plugin_googlemaps2_mark_get_default();

	$inoptions = array();
	foreach ($params as $param) {
		list($index, $value) = split('=', $param);
		$index = trim($index);
		$value = htmlspecialchars(trim($value));
		if ($value) $inoptions[$index] = $value;
		if ($index == 'zoom') {$isSetZoom = true;}//for old api
	}
	// map名指定は基本をベースに文字列を追加とする by nao-pon
	if (isset($inoptions['map'])) 
	{
		$inoptions['map'] = $defoptions['map']."_".$pgid."_".$inoptions['map'];
	}
	else
	{
		$inoptions['map'] = $defoptions['map']."_".$pgid."_".$vars['googlemaps2_info']['count'][$pgid];
	}
	// inline body部
	if ($body)
	{
		if (isset($inoptions['caption']))
		{
			$inoptions['caption'] .= "<br />".$body;
		}
		else
		{
			$inoptions['caption'] = $body;
		}
	}

	if (array_key_exists('define', $inoptions)) {
		$vars['googlemaps2_mark'][$inoptions['define']] = $inoptions;
		return "";
	}
	
	$coptions = array();
	if (array_key_exists('class', $inoptions)) {
		$class = $inoptions['class'];
		if (array_key_exists($class, $vars['googlemaps2_mark'])) {
			$coptions = $vars['googlemaps2_mark'][$class];
		}
	}
	$options = array_merge($defoptions, $coptions, $inoptions);
	$lat = trim($lat);
	$lng = trim($lng);
	$title        = $options['title'];
	$caption      = $options['caption'];
	$image        = $options['image'];
	$icon         = $options['icon'];
	$nolist       = $options['nolist'];
	$noinfowindow = $options['noinfowindow'];
	$noicon       = $options['noicon'];
	$zoom         = $options['zoom'];
	$type         = $options['type'];
	$map          = $options['map'];
	$formatlist   = $options['formatlist'];
	$formatinfo   = $options['formatinfo'];
	$alink        = $options['alink'];
	$api = $vars['googlemaps2_info'][$map]['api'];

	if ($api < 2 && $isSetZoom) $zoom = 17 - $zoom;
	if (is_null($zoom)) $zoom = 'null';
	
	// Mapタイプの名前を正規化
	if ($type) $type = plugin_googlemaps2_get_maptype($type);
	if (is_null($type)) $type = 'null';
	
	if ($noicon == true) {
		$noinfowindow = true;
	}

	if ($noinfowindow == false) {
		$infohtml = plugin_googlemaps_mark_format_infohtml(
			$map, $formatinfo, $alink,
			$title, $caption, $image);
		$infohtml = "'$infohtml'";
	} else {
		$infohtml = 'null';
	}

	$key = "$map,$lat,$lng";

	if ($nolist == false) {
		$listhtml = plugin_googlemaps_mark_format_listhtml(
			$map, $formatlist, $alink,
			$key, $infohtml, 
			$title, $caption, $image,
			$zoom);
	}


	// Create Marker
	$output = <<<EOD
<script type="text/javascript">
//<![CDATA[
onloadfunc.push(function() {
	if (document.getElementById("$map") == null) {
		alert("googlemaps error: mapname \\"$map\\" is not defined");
	} else {
		var center = PGTool.getLatLng($lat , $lng, "$api");\n
EOD;
	if ($noicon == false) {
	$output .= "		var m = new PGMarker(center, \"$icon\", \"$map\", false, true);\n";
	} else {
	$output .= "		var m = new PGMarker(center, \"$icon\", \"$map\", true, true);\n";
	}
	$output .= <<<EOD
		m.setHtml($infohtml);
		m.setZoom($zoom);
		m.setMapType($type);
		googlemaps_markers["$key"] = m;
	}
});
//]]>
</script>\n
EOD;

	//Show Markers List
	if ($nolist == false) {
		$output .= $listhtml;
	}

	return $output;
}

function plugin_googlemaps_mark_format_listhtml($map, $format, $alink, 
	$key, $infohtml, $title, $caption, $image, $zoomstr) {

	if ($alink == true) {
		$atag = "<a name=\"googlemaps_".$map."_%title%\"></a>";
		$atag .= "<a href=\"#$map\"";
	}
	
	$atag .= " onClick=\"googlemaps_markers['$key'].onclick();\">%title%</a>";

	$html = $format;
	if ($alink == true) {
		$html = str_replace('%title%', $atag , $html);
	}
	$html = str_replace('%title%', $title, $html);
	$html = str_replace('%caption%', $caption, $html);
	$html = str_replace('%image%', '<img src="'.$image.'" border=0/>', $html);
	return $html;
}

function plugin_googlemaps_mark_format_infohtml($map, $format, $alink, $title, $caption, $image) {

	$html = str_replace('\'', '\\\'', $format);
	if ($alink == true) {
		$atag = "%title% <a href=\\'#googlemaps_".$map."_%title%\\'>"
			.PLUGIN_GOOGLEMAPS2_MK_DEF_AJUMP.'</a>';
		$html = str_replace('%title%', $atag , $html);
	}
	$html = str_replace('%title%',$title , $html);
	$html = str_replace('%caption%', $caption, $html);

	if ($image != '')
	{
		if (exist_plugin_inline('ref'))
		{
			$image = do_plugin_inline("ref","{$image},mw:100,mh:100");
		}
		else
		{
			$image = '<img src=\\\''.$image.'\\\' border=0/>';
		}
	}
	$html = str_replace('%image%', $image, $html);

	return $html;
}

?>
