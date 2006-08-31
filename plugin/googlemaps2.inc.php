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

// Goodle Map ID
define ('PLUGIN_GOOGLEMAPS2_DEF_KEY', 'ABQIAAAAv2QINn0BFSDyNh38h-ot6RQ7maE_5AwHc1O-LHFbA5gzoHcPRRQnvRnshwL2nk8tkhRKIpZJ_P3gLA');

define ('PLUGIN_GOOGLEMAPS2_DEF_SERVER', 'us');//GoogleMap�����С��ۥ���(jp, us)
define ('PLUGIN_GOOGLEMAPS2_DEF_MAPNAME', 'googlemaps2');     //Map̾
define ('PLUGIN_GOOGLEMAPS2_DEF_WIDTH'  , '400px');           //����
define ('PLUGIN_GOOGLEMAPS2_DEF_HEIGHT' , '400px');           //����
define ('PLUGIN_GOOGLEMAPS2_DEF_LAT'    ,  35.036198);        //����
define ('PLUGIN_GOOGLEMAPS2_DEF_LNG'    ,  135.732103);       //����
define ('PLUGIN_GOOGLEMAPS2_DEF_ZOOM'   ,  13);       //�������٥�
define ('PLUGIN_GOOGLEMAPS2_DEF_TYPE'   ,  'normal'); //�ޥåפΥ�����(normal, satellite, hybrid)
define ('PLUGIN_GOOGLEMAPS2_DEF_MAPCTRL',  'normal'); //�ޥåץ���ȥ���(none,smallzoom,small,normal,large)
define ('PLUGIN_GOOGLEMAPS2_DEF_TYPECTRL'    ,'normal');  //maptype���إ���ȥ���(none, normal)
define ('PLUGIN_GOOGLEMAPS2_DEF_SCALECTRL'   ,'none');    //�������륳��ȥ���(none, normal)
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWCTRL','none');    //�����С��ӥ塼�ޥå�(none, hide, show)
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWTYPE', 'normal'); //�����С��ӥ塼�ޥåפΥ�����(normal, satellite, hybrid)
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH', '150');  //�����С��ӥ塼�ޥåפβ���
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT','150');  //�����С��ӥ塼�ޥåפν���
define ('PLUGIN_GOOGLEMAPS2_DEF_API', 2);                //API�θ����ߴ��ѥե饰(1=1��, 2=2��). �ѻ�ͽ�ꡣ
define ('PLUGIN_GOOGLEMAPS2_DEF_TOGGLEMARKER', true);     //�ޡ�������ɽ�����إ����å���ɽ��
define ('PLUGIN_GOOGLEMAPS2_DEF_NOICONNAME'  , 'ɸ��ޡ�����'); //��������̵���ޡ������Υ�٥�
define ('PLUGIN_GOOGLEMAPS2_DEF_USETOOL'   ,  2);         //���������ġ���(0:ɽ���ʤ�, 1:�ޡ������ѤΤ�, 2:�ޡ��������Ͽ���)

function plugin_googlemaps2_get_default () {
	global $vars;
	return array(
		'map'        => PLUGIN_GOOGLEMAPS2_DEF_MAPNAME,
		'key'            => PLUGIN_GOOGLEMAPS2_DEF_KEY,
		'width'          => PLUGIN_GOOGLEMAPS2_DEF_WIDTH,
		'height'         => PLUGIN_GOOGLEMAPS2_DEF_HEIGHT,
		'lat'            => PLUGIN_GOOGLEMAPS2_DEF_LAT,
		'lng'            => PLUGIN_GOOGLEMAPS2_DEF_LNG,
		'zoom'           => PLUGIN_GOOGLEMAPS2_DEF_ZOOM,
		'mapctrl'        => PLUGIN_GOOGLEMAPS2_DEF_MAPCTRL,
		'type'           => PLUGIN_GOOGLEMAPS2_DEF_TYPE,
		'typectrl'       => PLUGIN_GOOGLEMAPS2_DEF_TYPECTRL,
		'scalectrl'      => PLUGIN_GOOGLEMAPS2_DEF_SCALECTRL,
		'overviewctrl'   => PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWCTRL,
		'overviewtype'   => PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWTYPE,
		'overviewwidth'  => PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH,
		'overviewheight' => PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT,
		'api'            => PLUGIN_GOOGLEMAPS2_DEF_API,
		'togglemarker'   => PLUGIN_GOOGLEMAPS2_DEF_TOGGLEMARKER,
		'noiconname'     => PLUGIN_GOOGLEMAPS2_DEF_NOICONNAME,
		'usetool'        => PLUGIN_GOOGLEMAPS2_DEF_USETOOL,
		'host'           => PLUGIN_GOOGLEMAPS2_DEF_SERVER
	);
}

function plugin_googlemaps2_convert() {
	$args = func_get_args();
	return "<p>".plugin_googlemaps2_output($args)."</p>";
}

function plugin_googlemaps2_inline() {
	$args = func_get_args();
	$str = array_pop($args);
	return plugin_googlemaps2_output($args);
}

function plugin_googlemaps2_output($params) {
	global $vars, $pgid, $stack;
	
	// �ƤӽФ����
	if (isset($vars['googlemaps2_info']['count'][$pgid]))
	{
		++$vars['googlemaps2_info']['count'][$pgid];
	}
	else
	{
		$vars['googlemaps2_info']['count'][$pgid] = 0;
	}
	
	$defoptions = plugin_googlemaps2_get_default();
	
	$inoptions = array();
	$isSetZoom = false;
	foreach ($params as $param) {
		$pos = strpos($param, '=');
		if ($pos == false) continue;
		$index = trim(substr($param, 0, $pos));
		$value = htmlspecialchars(trim(substr($param, $pos+1)));
		if ($value) $inoptions[$index] = $value;
//        $$index = $value;
		if ($index == 'cx') {$cx = $value;}//for old api
		if ($index == 'cy') {$cy = $value;}//for old api
		if ($index == 'zoom') {$isSetZoom = true;}//for old api
	}
	// map̾����ϴ��ܤ�١�����ʸ������ɲäȤ��� by nao-pon
	if (isset($inoptions['map'])) 
	{
		$inoptions['map'] = $defoptions['map']."_".$pgid."_".$inoptions['map'];
		$usemapname = true;
	}
	else
	{
		$inoptions['map'] = $defoptions['map']."_".$pgid."_".$vars['googlemaps2_info']['count'][$pgid];
		$usemapname = false;
	}

	if (array_key_exists('define', $inoptions)) {
		$vars['googlemaps2'][$inoptions['define']] = $inoptions;
		return "";
	}
	
	$coptions = array();
	if (array_key_exists('class', $inoptions)) {
		$class = $inoptions['class'];
		if (array_key_exists($class, $vars['googlemaps2'])) {
			$coptions = $vars['googlemaps2'][$class];
		}
	}
	$options = array_merge($defoptions, $coptions, $inoptions);
	$mapname        = $options['map'];
	$key            = $options['key'];
	$width          = $options['width'];
	$height         = $options['height'];
	$lat            = $options['lat'];
	$lng            = $options['lng'];
	$zoom           = $options['zoom'];
	$mapctrl        = $options['mapctrl'];
	$type           = $options['type'];
	$typectrl       = $options['typectrl'];
	$scalectrl      = $options['scalectrl'];
	$overviewctrl   = $options['overviewctrl'];
	$overviewtype   = $options['overviewtype'];
	$overviewwidth  = $options['overviewwidth'];
	$overviewheight = $options['overviewheight'];
	$api            = $options['api'];
	$togglemarker   = $options['togglemarker'];
	$noiconname     = $options['noiconname'];
	$usetool        = $options['usetool'];
	$host           = strtolower($options['host']);
	
	//api�Υ����å�
	if ( ! (is_numeric($api) && $api >= 0 && $api <= 2) ) {
		$api = 2;
	}
	$vars['googlemaps2_info'][$mapname]['api'] = $api;

	//�Ť�1��API�Ȥθߴ����Τ���cx, cy���Ϥ��줿���lng, lat���������롣
	if ($api < 2) {
		if (isset($cx) && isset($cy)) {
			$lat = $cx;
			$lng = $cy;
		} else {
			$tmp = $lng;
			$lng = $lat;
			$lat = $tmp;
		}
	} else {
		if (isset($cx)) $lng = $cx;
		if (isset($cy)) $lat = $cy;
	}
	
	// zoom��٥�
	if ($api < 2 && $isSetZoom) {
		$zoom = 17 - $zoom;
	}
	
	// Map�����פ�̾����������
	$type = plugin_googlemaps2_get_maptype($type);
	$overviewtype = plugin_googlemaps2_get_maptype($overviewtype);

	// ¾�Υ��ޥ���Ѥ˾���򥰥��Х����¸
	if (!array_key_exists('loaded', $vars['googlemaps2_info'])) {
		$vars['googlemaps2_defmapname'] = PLUGIN_GOOGLEMAPS2_DEF_MAPNAME;
		$vars['googlemaps2_info']['loaded'] = true;
		$output = plugin_googlemaps2_init_output($key, $noiconname, $host);
	} else {
		$output = "";
	}
	
	$output .= <<<EOD
<!--a name="$mapname" ></a-->
<div id="$mapname" style="width: $width; height: $height;"></div>
EOD;
	
	$outjs = <<<EOD
<script type="text/javascript">
//<![CDATA[
onloadfunc.push( function () {
googlemaps_maps["$mapname"] = new GMap2(document.getElementById("$mapname"));\n
GEvent.addListener(googlemaps_maps["$mapname"], "dblclick", function() {
	// ���֥륯��å���fusen���Խ����̤�ɽ�������Τ��޻�
	if (fusenDblClick != undefined)
	{
		var _old = fusenDblClick;
		fusenDblClick = true;
		setTimeout(function(){fusenDblClick = _old;},10);
	}
	
	this.closeInfoWindow();

});
googlemaps_maps["$mapname"].setCenter(PGTool.getLatLng($lat, $lng, "$api"), $zoom, $type);
EOD;

	// Show Map Control/Zoom 
	switch($mapctrl) {
		case "small":
			$outjs .= "googlemaps_maps[\"$mapname\"].addControl(new GSmallMapControl());\n";
			break;
		case "smallzoom":
			$outjs .= "googlemaps_maps[\"$mapname\"].addControl(new GSmallZoomControl());\n";
			break;
		case "none":
			break;
		case "large":
		default:
			$outjs .= "googlemaps_maps[\"$mapname\"].addControl(new GLargeMapControl());\n";
			break;
	}
	
	// Scale
	if ($scalectrl != "none") {
		$outjs .= "googlemaps_maps[\"$mapname\"].addControl(new GScaleControl());\n";
	}
	
	// Show Map Type Control and Center
	if ($typectrl != "none") {
		$outjs .= "googlemaps_maps[\"$mapname\"].addControl(new GMapTypeControl());\n";
	}

	// OverviewMap
	if ($overviewctrl != "none") {
		$ovw = preg_replace("/(\d+).*/i", "\$1", $overviewwidth);
		$ovh = preg_replace("/(\d+).*/i", "\$1", $overviewheight);
		if ($ovw == "") $ovw = PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH;
		if ($ovh == "") $ovh = PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT;
		$outjs .= "var ovctrl = new GOverviewMapControl(new GSize($ovw, $ovh));\n";
		$outjs .= "googlemaps_maps['$mapname'].addControl(ovctrl);\n";
		$outjs .= "var ovdiv = document.getElementById(\"".$mapname."_overview\");\n";
		$outjs .= "ovdiv.style.position = \"relative\";\n";
		$outjs .= "ovdiv.style.right = \"0px\";\n";
		$outjs .= "ovdiv.style.bottom =\"8px\";\n";
		$outjs .= "document.getElementById('$mapname').appendChild(ovdiv);\n";
		$outjs .= "var ovmap = ovctrl.getOverviewMap();\n";
		$outjs .= "var pmap = googlemaps_maps['$mapname'];\n";
		$outjs .= "ovmap.setCenter(pmap.getCenter(), pmap.getZoom() - 3);";
		$outjs .= "ovmap.setMapType($overviewtype);\n";
		if ($overviewctrl == "hide") {
		$outjs .= "ovctrl.hide();\n";
		}
	}
	
	// �ޡ�������ɽ����ɽ�������å��ܥå���
	if ($togglemarker) {
		$outjs .= "onloadfunc.push( function(){p_googlemaps_togglemarker_checkbox('$mapname', '$noiconname');} );";
	}

	$outjs .= "});\n";
	$outjs .= "//]]>\n";
	$outjs .= "</script>\n";
	
	$stack['addheaders'][$mapname] = $outjs;
	
	// ��ʪ�ġ���
	if ($usetool > 0) {
		if ($usemapname) {
			$_map .= "+ ',&quot;map={$mapname}&quot;'";
		}
		$_onclick = <<< EOD
			var t=prompt('�����ȥ�����Ϥ��Ƥ���������','');
			if (t == null){return false;}
			var c=prompt('����ʸ�����Ϥ��Ƥ���������','');
			if (c == null){return false;}
			var op = '';
			if (confirm('ɽ���⡼�ɡ��̼ܤ���¸���ޤ�����'))
			{
				op = ', zoom=' + googlemaps_maps['$mapname'].getZoom()
					+ ', type=' + ((googlemaps_maps['$mapname'].getMapTypes()[1]==googlemaps_maps['$mapname'].getCurrentMapType())? 'satellite'
							    : ((googlemaps_maps['$mapname'].getMapTypes()[2]==googlemaps_maps['$mapname'].getCurrentMapType())?  'hybrid' : 'normal'));
			}
			var p = '&googlemaps2_mark('
			+ PGTool.fmtNum(googlemaps_maps['$mapname'].getCenter().lat())
			+ ', ' + PGTool.fmtNum(googlemaps_maps['$mapname'].getCenter().lng())
			{$_map}
			+ op
			+ ',&quot;title=' + t + '&quot;'
			+ '){' + c + '};';
			var m = (pukiwiki_elem != null)? 'OK�ǥ������������ž�����ޤ���' : '���ԡ����Ƥ��Ȥ�����������';
			var a = prompt('����ɽ�����Ƥ����濴�����Υޡ������ѥ������������ޤ�����\\n' + m, p);
			if (a != null && pukiwiki_elem != null)
			{
				pukiwiki_ins(a);
				pukiwiki_elem.focus();
			}
			return false;
EOD;
		$_onclick = str_replace(array("\r","\n","\t"),"",$_onclick);
		$output .= "<div>";
		$output .= "<b>[���������ġ���]</b> &nbsp; ";
		$output .= "<a onClick=\"{$_onclick}\" style=\"cursor:pointer;\">�濴�����Υޡ������ѥ���</a>";
	}
	if (($usetool == 1)) {$output .= "</div>\n";}
	if ($usetool > 1) {
		$output .= " &nbsp; <a onClick=\"prompt('����ɽ�����Ƥ���ޥå׺����ѥ���\\n���ԡ����Ƥ��Ȥ�����������', '#googlemaps2(lat=' + PGTool.fmtNum(googlemaps_maps['$mapname'].getCenter().lat()) + ', lng=' + PGTool.fmtNum(googlemaps_maps['$mapname'].getCenter().lng())";
		$output .= " + ', width=$width'";
		$output .= " + ', height=$height'";
		$output .= " + ', zoom=' + googlemaps_maps['$mapname'].getZoom()";
		$output .= " + ', type=' + ((googlemaps_maps['$mapname'].getMapTypes()[1]==googlemaps_maps['$mapname'].getCurrentMapType())? 'satellite'
							    : ((googlemaps_maps['$mapname'].getMapTypes()[2]==googlemaps_maps['$mapname'].getCurrentMapType())?  'hybrid' : 'normal'))";
		$output .= " + ', overviewctrl=$overviewctrl'";
		$output .= " + ', usetool=$usetool'";
		$output .= " + ')');\" style=\"cursor:pointer;\">���ξ��֤Υޥå׺����ѥ���</a>";
		$output .= "<br>";
		$output .= "</div>\n";
	}

	return $output;
}

function plugin_googlemaps2_get_maptype($type) {
	switch (strtolower(substr($type, 0, 1))) {
		case "n": $type = 'G_NORMAL_MAP'   ; break;
		case "s": $type = 'G_SATELLITE_MAP'; break;
		case "h": $type = 'G_HYBRID_MAP'   ; break;
		default:  $type = 'G_NORMAL_MAP'   ; break;
	}
	return $type;
}

function plugin_googlemaps2_init_output($key, $noiconname, $host) {
	global $stack;
	$host = ($host == 'jp')? "maps.google.co.jp" : "maps.google.com";
	$js = XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/plugin_data/googlemap2/js/init.js";
	$stack['addheaders']['googlemap2'] = <<<EOD
<script src="http://{$host}/maps?file=api&amp;v=2&amp;key=$key" type="text/javascript" charset="UTF-8"></script>
<script src="{$js}" type="text/javascript" charset="UTF-8"></script>
EOD;
}

?>
