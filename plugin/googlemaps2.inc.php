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

define ('PLUGIN_GOOGLEMAPS2_DEF_SERVER', 'us');//GoogleMapサーバーホスト(jp, us)
define ('PLUGIN_GOOGLEMAPS2_DEF_MAPNAME', 'googlemaps2');     //Map名
define ('PLUGIN_GOOGLEMAPS2_DEF_WIDTH'  , '400px');           //横幅
define ('PLUGIN_GOOGLEMAPS2_DEF_HEIGHT' , '400px');           //縦幅
define ('PLUGIN_GOOGLEMAPS2_DEF_LAT'    ,  35.036198);        //経度
define ('PLUGIN_GOOGLEMAPS2_DEF_LNG'    ,  135.732103);       //緯度
define ('PLUGIN_GOOGLEMAPS2_DEF_ZOOM'   ,  13);       //ズームレベル
define ('PLUGIN_GOOGLEMAPS2_DEF_TYPE'   ,  'normal'); //マップのタイプ(normal, satellite, hybrid)
define ('PLUGIN_GOOGLEMAPS2_DEF_MAPCTRL',  'normal'); //マップコントロール(none,smallzoom,small,normal,large)
define ('PLUGIN_GOOGLEMAPS2_DEF_TYPECTRL'    ,'normal');  //maptype切替コントロール(none, normal)
define ('PLUGIN_GOOGLEMAPS2_DEF_SCALECTRL'   ,'none');    //スケールコントロール(none, normal)
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWCTRL','none');    //オーバービューマップ(none, hide, show)
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWTYPE', 'normal'); //オーバービューマップのタイプ(normal, satellite, hybrid)
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWWIDTH', '150');  //オーバービューマップの横幅
define ('PLUGIN_GOOGLEMAPS2_DEF_OVERVIEWHEIGHT','150');  //オーバービューマップの縦幅
define ('PLUGIN_GOOGLEMAPS2_DEF_API', 2);                //APIの後方互換用フラグ(1=1系, 2=2系). 廃止予定。
define ('PLUGIN_GOOGLEMAPS2_DEF_TOGGLEMARKER', true);     //マーカーの表示切替チェックの表示
define ('PLUGIN_GOOGLEMAPS2_DEF_NOICONNAME'  , '標準マーカー'); //アイコン無しマーカーのラベル
define ('PLUGIN_GOOGLEMAPS2_DEF_USETOOL'   ,  2);         //タグ生成ツール(0:表示なし, 1:マーカー用のみ, 2:マーカーと地図用)

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
	
	// 呼び出し回数
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
	// map名指定は基本をベースに文字列を追加とする by nao-pon
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
	
	//apiのチェック
	if ( ! (is_numeric($api) && $api >= 0 && $api <= 2) ) {
		$api = 2;
	}
	$vars['googlemaps2_info'][$mapname]['api'] = $api;

	//古い1系APIとの互換性のためcx, cyが渡された場合lng, latに代入する。
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
	
	// zoomレベル
	if ($api < 2 && $isSetZoom) {
		$zoom = 17 - $zoom;
	}
	
	// Mapタイプの名前を正規化
	$type = plugin_googlemaps2_get_maptype($type);
	$overviewtype = plugin_googlemaps2_get_maptype($overviewtype);

	// 他のコマンド用に情報をグローバルに保存
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
	// ダブルクリックでfusenの編集画面が表示されるのを抑止
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
	
	// マーカーの表示非表示チェックボックス
	if ($togglemarker) {
		$outjs .= "onloadfunc.push( function(){p_googlemaps_togglemarker_checkbox('$mapname', '$noiconname');} );";
	}

	$outjs .= "});\n";
	$outjs .= "//]]>\n";
	$outjs .= "</script>\n";
	
	$stack['addheaders'][$mapname] = $outjs;
	
	// 小物ツール
	if ($usetool > 0) {
		if ($usemapname) {
			$_map .= "+ ',&quot;map={$mapname}&quot;'";
		}
		$_onclick = <<< EOD
			var t=prompt('タイトルを入力してください。','');
			if (t == null){return false;}
			var c=prompt('説明文を入力してください。','');
			if (c == null){return false;}
			var op = '';
			if (confirm('表示モード・縮尺も保存しますか？'))
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
			var m = (pukiwiki_elem != null)? 'OKでコメント入力欄に転送します。' : 'コピーしてお使いください。';
			var a = prompt('現在表示している中心地点のマーカー用タグを生成しました。\\n' + m, p);
			if (a != null && pukiwiki_elem != null)
			{
				pukiwiki_ins(a);
				pukiwiki_elem.focus();
			}
			return false;
EOD;
		$_onclick = str_replace(array("\r","\n","\t"),"",$_onclick);
		$output .= "<div>";
		$output .= "<b>[タグ生成ツール]</b> &nbsp; ";
		$output .= "<a onClick=\"{$_onclick}\" style=\"cursor:pointer;\">中心地点のマーカー用タグ</a>";
	}
	if (($usetool == 1)) {$output .= "</div>\n";}
	if ($usetool > 1) {
		$output .= " &nbsp; <a onClick=\"prompt('現在表示しているマップ作成用タグ\\nコピーしてお使いください。', '#googlemaps2(lat=' + PGTool.fmtNum(googlemaps_maps['$mapname'].getCenter().lat()) + ', lng=' + PGTool.fmtNum(googlemaps_maps['$mapname'].getCenter().lng())";
		$output .= " + ', width=$width'";
		$output .= " + ', height=$height'";
		$output .= " + ', zoom=' + googlemaps_maps['$mapname'].getZoom()";
		$output .= " + ', type=' + ((googlemaps_maps['$mapname'].getMapTypes()[1]==googlemaps_maps['$mapname'].getCurrentMapType())? 'satellite'
							    : ((googlemaps_maps['$mapname'].getMapTypes()[2]==googlemaps_maps['$mapname'].getCurrentMapType())?  'hybrid' : 'normal'))";
		$output .= " + ', overviewctrl=$overviewctrl'";
		$output .= " + ', usetool=$usetool'";
		$output .= " + ')');\" style=\"cursor:pointer;\">この状態のマップ作成用タグ</a>";
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
