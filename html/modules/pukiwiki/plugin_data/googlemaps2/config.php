<?php
//
// Created on 2006/09/01 by nao-pon http://hypweb.net/
// $Id: config.php,v 1.1 2006/09/01 00:26:36 nao-pon Exp $
//
// Goodle Map ID
define ('PLUGIN_GOOGLEMAPS2_DEF_KEY', '');

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

?>