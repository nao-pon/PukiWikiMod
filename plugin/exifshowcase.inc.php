<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
//
// ref.inc.php,v 1.20をベースに作成
// $Id: exifshowcase.inc.php,v 1.5 2004/10/05 12:46:47 nao-pon Exp $
// ORG: exifshowcase.inc.php,v 1.20 2004/01/17 12:52:01 m-arai Exp $
//

/*
*プラグイン exifshowcase
そのページに添付されたExif画像ファイルのサムネイルやExif情報
を一覧表示する

*Usage
 #exifshowcase([pattern,[option parameter]])

*パラメータ
-パラメータ
--pattern
eregで比較して、ファイル名がマッチするものだけを対象とする。
指定無き場合、全ての*.jpgが対象になる。
--left|center|right~
横の位置合わせ
--around
回り込み許可
--nofilename~
ファイル名を表示しない
--nomapi~
マピオンへのリンクを生成しない
--nokash~
カシミールLMLサーバへのリンクを生成しない
--noexif~
Exif情報表示を行なわない
--sort~
ファイル名でソートする
--reverse~
表示順を逆にする
--col:整数値~
複数列指定。この場合、Exif情報は表示されない。
--row:整数値
行数指定。画像をランダムに表示する。
--mw:整数値
表示画像最大幅
--mh:整数値
表示画像最大高
*/

// file icon image
if (!defined('FILE_ICON'))
{
	define('FILE_ICON','<img src="./image/file.png" width="20" height="20" alt="file" style="border-width:0px" />');
}

// default alignment
define('EXIFSHOWCASE_DEFAULT_ALIGN','center'); // 'left','center','right'

// default Max width
define('EXIFSHOWCASE_DEFAULT_MW',160); // px

// default Max height
define('EXIFSHOWCASE_DEFAULT_MH',160); // px

// カシミールアイコン
define('EXIFSHOWCASE_KASH_ICON', 'image/kash3d.png');

// マピオンアイコン
define('EXIFSHOWCASE_MAPI_ICON', 'http://www.mapion.co.jp/QA/user/img/mapion_a.gif');

/*
function plugin_exifshowcase_inline()
{
	global $vars;
	
	$params = plugin_exifshowcase_body(func_get_args(),$vars['page']);
	
	return ($params['_error'] != '') ? $params['_error'] : $params['_body'];
}
*/
function plugin_exifshowcase_convert()
{
	global $vars;

	$params = plugin_exifshowcase_body(func_get_args(),$vars['page']);
	
	if ($params['_error'] != '')
	{
		return "<p>{$params['_error']}</p>";
	}
	
	// divで包む
	if ($params['around'])
	{
		$style = ($params['_align'] == 'right') ? 'float:right' : 'float:left';
	}
	else
	{
		$style = "text-align:{$params['_align']}";
	}
	return "<div style=\"$style\">{$params['_body']}</div>\n";
}

function plugin_exifshowcase_body($args,$page)
{
	global $script,$WikiName,$BracketName,$vars,$xoopsDB;
	
	// 戻り値
	$params = array();
	
	//パラメータ
	$params = array(
		'left'      => FALSE, // 左寄せ
		'center'    => FALSE, // 中央寄せ
		'right'     => FALSE, // 右寄せ
		'around'    => FALSE, // 回り込み許可
		'col'       => 1,     // 表示列数
		'row'       => 0,     // 表示行数
		'mw'        => FALSE, // 画像表示幅(最大)
		'mh'        => FALSE, // 画像表示高(最大)
		'nofilename'=> FALSE, // ファイル名を表示しない
		'nomapi'    => FALSE, // マピオンへのリンクを張らない
		'nokash'    => FALSE, // カシミールLMLサーバへのリンクを張らない
		'noexif'    => FALSE, // Exif情報を表示しない
		'reverse'   => FALSE, // 表示順を逆に
		'sort'      => FALSE, // ファイル名でソート
		'page'      => $page, // ページ名
		'_args'     => array(),
		'_done'     => FALSE,
		'_error'    => ''
	);

	$colmn = 1; // 表示列数

	$pattern = trim(array_shift($args));
	array_walk($args, 'exifshowcase_check_arg', &$params);
	
	$colmn = $params['col']; // 表示列数
	$row = $params['row']; // 表示行数（指定時ランダム表示
	
	$page = add_bracket($params['page']);
	
	//全体の配置
	if ($params['left'])
		$params['_align'] = "left";
	elseif ($params['right'])
		$params['_align'] = "right";
	elseif ($params['center'])
		$params['_align'] = "center";
	else
		$params['_align'] = EXIFSHOWCASE_DEFAULT_ALIGN;
		
	//画像の表示サイズ
	if (!$params['mw'])
		$params['mw'] = EXIFSHOWCASE_DEFAULT_MW;
	if (!$params['mh'])
		$params['mh'] = EXIFSHOWCASE_DEFAULT_MH;
	
	$exif_extension = ($colmn == 1) && (!$row) && (!$params['noexif']) && extension_loaded('exif');
	
	if (!is_dir(UPLOAD_DIR))
	{
		$params['_error'] = 'no UPLOAD_DIR.';
		return $params;
	}
	
	$where = $files = $aname = array();
	
	$where[] = "`pgid` = ".get_pgid_by_name($page);
	$where[] = "`type` LIKE 'image%'";
	$where[] = "`age` = 0";
	if ($pattern)
		$where[] = "`name` REGEXP '{$pattern}'";
	
	$where = join(' AND ',$where);
	
	// 並べ替え
	if ($row)
	{
		// ランダム表示？
		$show_count = $row * $colmn;
		$order = " ORDER BY RAND() LIMIT {$show_count}";
	}
	else if ($params['sort'])
	{
		// ファイル名順
		$order = " ORDER BY `name` ASC";
	}
	else
	{
		// タイムスタンプ順
		$order = " ORDER BY `mtime` ASC";
	}
	
	$query = "SELECT name FROM `".$xoopsDB->prefix(pukiwikimod_attach)."` WHERE {$where}{$order};";
	
	$result = $xoopsDB->query($query);
	while($_row = mysql_fetch_row($result))
	{
		$aname[] = $_row[0];
		$files[] = UPLOAD_DIR.encode($page).'_'.encode($_row[0]);
	}
	
	if( !$files) {

		$params['_body'] .= "対象画像 (".($pattern?$pattern:"*.jp(e)g").") がありません。";
		return $params;
	}

	if ( $params['reverse']) {
		$files = array_reverse( $files);
		$aname = array_reverse( $aname);
	}

	$params['_body'] = 
		'<table class="style_table" style="margin:0px;">'.
		( $exif_extension ? '<tr class="style_th" style="text-align:center;"><th>ファイル</th><th>撮影情報</th><th>コメント</th>': '').'</tr>';
EOD;

	for ( $cnt=0; $fname = $files[$cnt]; $cnt++) {

		$url = "?plugin=attach&openfile={$aname[$cnt]}&refer=".rawurlencode($page);

		if ( $exif_extension ) {
			$exif  = exif_read_data($fname, 0, true);
		}

		$info = "";

		$szstr = (($eh = $exif["COMPUTED"]["Height"]) > ( $ew = $exif["COMPUTED"]["Width"])) ?
				"height=128": "width=128";

		if (!$exif_extension) {
			$sz = filesize($fname);
			list($ew,$eh) = getimagesize($fname);
		} else {
			if (!( $edate = $exif["EXIF"]["DateTimeOriginal"])) {
				if (!( $edate = $exif["EXIF"]["DateTimeDigitized"])) {
					$edate = $exif["IFD0"]["DateTime"];
				}
			}
			$edate = htmlentities(trim($edate), ENT_QUOTES, "EUC-JP");

			if ( $edate) {
				$info .= "<tr><td nowrap>撮影時刻</td><td>:</td><td>{$edate}</td></tr>";
			}

			if ( $edesc = trim($exif["IFD0"]["ImageDescription"])) {
				$edesc = mb_convert_encoding($edesc,"EUC-JP", "auto");
				$edesc = htmlentities($edesc, ENT_QUOTES, "EUC-JP");
				$info .= "<tr style=\"vertical-align:top;\"><td>タイトル</td><td>:</td><td>{$edesc}</td></tr>";
			}

			$cright = rtrim( $exif["COMPUTED"]["Copyright"]);
			$cphoto = rtrim( $exif["COMPUTED"]["Copyright.Photographer"]);
			$cedit  = rtrim( $exif["COMPUTED"]["Copyright.Editor"]);

			if ( $cphoto ){
				$cphoto = mb_convert_encoding($cphoto,"EUC-JP", "auto");
				$cphoto = htmlentities($cphoto, ENT_QUOTES, "EUC-JP");
				$info .= "<tr style=\"vertical-align:top;\"><td nowrap>撮影著作権者</td><td>:</td><td>{$cphoto}</td></tr>";
			}

			if ( $cedit ){
				$cedit  = mb_convert_encoding($cedit,"EUC-JP", "auto");
				$cedit  = htmlentities($cedit, ENT_QUOTES, "EUC-JP");
				$info .= "<tr style=\"vertical-align:top;\"><td nowrap>編集著作権者</td><td>:</td><td>{$cedit}</td></tr>";
			}

			if ( ($cright) && !( $cphoto || $cedit ) ){ 
				$cright = mb_convert_encoding($cright,"EUC-JP", "auto");
				$cright = htmlentities($cright, ENT_QUOTES, "EUC-JP");
				$info .= "<tr style=\"vertical-align:top;\"><td>著作権者</td><td>:</td><td>{$cright}</td></tr>";
			}

			$model = trim( $exif["IFD0"]["Model"]);
			$make  = trim( $exif["IFD0"]["Make"]);
			if ( $model ) {
				$model = htmlentities($model, ENT_QUOTES, "EUC-JP");
				$make  = htmlentities( $make, ENT_QUOTES, "EUC-JP");
				$info .= "<tr style=\"vertical-align:top;\"><td>撮影機材</td><td>:</td><td>{$model}". ( $make ? " ({$make})": "") . "</td></tr>";
			}

			if ( $exif["GPS"] ) {
				$lar = $exif["GPS"]["GPSLatitudeRef"];
				$lad = ratstr2num($exif["GPS"]["GPSLatitude"][0]);
				$lam = ratstr2num($exif["GPS"]["GPSLatitude"][1]);
				$las = ratstr2num($exif["GPS"]["GPSLatitude"][2]);
				list ($lad,$lam,$las) = dms2dms($lad,$lam,$las);
				$lasm = round($las,2);

				$lor = $exif["GPS"]["GPSLongitudeRef"];
				$lod = ratstr2num($exif["GPS"]["GPSLongitude"][0]);
				$lom = ratstr2num($exif["GPS"]["GPSLongitude"][1]);
				$los = ratstr2num($exif["GPS"]["GPSLongitude"][2]);
				list ($lod,$lom,$los) = dms2dms($lod,$lom,$los);
				$losm = round($los,2);

				if ( $datum = $exif["GPS"]["GPSMapDatum"] ) {
					$datum  = htmlentities($datum, ENT_QUOTES, "EUC-JP");
					$edatum = "({$datum})";
				}

				if ( !$params['nokash'] ) {
					$lml = "<a href=\"http://lml.kashmir3d.com/getlml?".
						MakeLMLURL($lar,$lad,$lam,$las,$lor,$lod,$lom,$los,$datum).
						"&icon=915001&name={$aname[$cnt]}&url=http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}".rawurlencode($url)."\"><img src=\"".EXIFSHOWCASE_KASH_ICON."\"></a>";
				}

				if (!$params['nomapi']) {
					$mpi = MakeMapionURL($lar,$lad,$lam,$las,$lor,$lod,$lom,$los,$datum);
					if ( $mpi ) {
						$mpi = "<a href=\"http://www.mapion.co.jp/c/f?scl=250000&pnf=1&uc=1&grp=all&size=500,500&{$mpi}\" target=_blank><img width=15 height=15 src=\"".EXIFSHOWCASE_MAPI_ICON."\" alt=\"mapion\"></a>";
					}
				}

				$info .= <<<EOD
<tr style="vertical-align:top;"><td nowrap>撮影位置</td><td>:</td><td>{$lar}{$lad}'{$lam}'{$lasm}"-{$lor}{$lod}'{$lom}'{$losm}"{$edatum}</td></tr><tr><td></td><td></td><td>$lml $mpi</td><tr>
EOD;
			}

			if ( $ucom = trim($exif["COMPUTED"]["UserComment"])) {
				$ucom = mb_convert_encoding($ucom,"EUC-JP", "auto");
				$ucom = htmlentities("$ucom", ENT_QUOTES, "EUC-JP");
				$ucom = str_replace( "\r\n", "<br>","$ucom");
				$ucom = str_replace( "\r",   "<br>","$ucom");
				$ucom = str_replace( "\n",   "<br>","$ucom");
				$ucom = str_replace( "<br><br>",   "<p>","$ucom");
			}

			$sz = $exif['FILE']['FileSize'];
		}

		if ( $sz > 1024*10) {
			$sz = (int)($sz/1024)."KBytes";
		} else {
			$sz = $sz."Bytes";
		}

		$img = make_link("&ref({$page}/{$aname[$cnt]},mw:".$params['mw'].",mh:".$params['mh'].");");

		$params['_body'] .= 
			(( $cnt % $colmn) == 0 ?"<tr class=\"style_td\">":'').
			"<td align=center>{$img}".
			($params['nofilename']?'':"<br>{$aname[$cnt]}").
			"<br>{$ew}x{$eh}<br>({$sz})</td>".
			($exif_extension ? "<td style=\"padding:5px;text-align:left;\"><table style=\"border-spacing:0px 0px;\">{$info}</table></td><td style=\"padding:5px;text-align:left;\">{$ucom}</td>":'').
			(( $colmn-($cnt%$colmn)) == 1 ? '</tr>':'');
	}
	$params['_body'] .= (($cnt%$colmn)?'</tr>':'').'</table>';

	return $params;
}

//-----------------------------------------------------------------------------
//オプションを解析する
function exifshowcase_check_arg($val, $key, &$params)
{
	if ($val == '') { $params['_done'] = TRUE; return; }

	if (!$params['_done']) {
		foreach (array_keys($params) as $key)
		{
			if (strpos($val,':')) // PHP4.3.4＋Apache2 環境で何故かApacheが落ちるとの報告があったので
				list($_val,$thisval) = explode(":",$val);
			else
			{
				$_val = $val;
				$thisval = null;
			}
			if (strtolower($_val) == $key)
			{
				if (!empty($thisval))
					$params[$key] = $thisval;
				else
					$params[$key] = TRUE;
				return;
			}
		}
		$params['_done'] = TRUE;
	}
	$params['_args'][] = $val;
}

function ratstr2num( $str)
{
	list( $ch, $mot) = explode( "/", $str);

	return $mot == 0 ? 0: ($ch/$mot);
}


function dms2dms($d,$m,$s)
{
	$do = $d*600 + $m*10.0 +$s/6.0;

	$td = (int)($do/600);
	$tm = (int)(($do - $td*600)/10);
	$ts = ( $do - $td*600 - $tm*10)*6.0;

	return array( $td,$tm,$ts);
}

//function dms2dms($d,$m,$s)
//{
//	$do = $d + $m/60.0 +$s/3600.0;
//
//	$td = ceil($do)-1;
//	$td = $td < 0 ? 0: $td;
//	$tm = ceil(( $do - $td )*60)-1;
//	$tm = $tm < 0 ? 0: $tm;
//	$ts = ( $do - $td - $tm/60.0)*3600.0;
//
//	return array( $td,$tm,$ts);
//}


function MakeLMLURL($latr,$latd,$latm,$lats,$lotr,$lotd,$lotm,$lots,$datum)
{
    if ( stristr( $datum, "WGS") && stristr( $datum, "84")) {
	$datum = "WGS84";
    } else {
	$datum = "Tokyo";
    }

    if ( !strcmp("$latr","N")) { $latr=""; } else { $latr="-"; }
    if ( !strcmp("$lotr","E")) { $lotr=""; } else { $lotr="-"; }

    $lats = ceil($lats*10)-1+1000;
    $lots = ceil($lots*10)-1+1000;

    $lats = substr("$lats",1);
    $lots = substr("$lots",1);

    $latm = $latm+100;
    $latm = substr("$latm",1,2);
    $lotm = $lotm+100;
    $lotm = substr("$lotm",1,2);

    return ( "lat=$latr$latd.$latm$lats&lon=$lotr$lotd.$lotm$lots&datum=$datum");
}

function MakeMapionURL($latr,$latd,$latm,$lats,$lotr,$lotd,$lotm,$lots,$datum)
{
    if ( !strcmp($latr,"S") || !strcmp($lotr,"W") || $latd > 50 || $latd < 20 ||
	$lotd > 150 || $lotd < 120 ) {
	return "";
    }

    if ( stristr( $datum, "WGS") && stristr( $datum, "84")) {
	list ($latd,$latm,$lats,$lotd,$lotm,$lots) = WGS84toTOKYO($latd,$latm,$lats,
							     $lotd,$lotm,$lots);
    }

    if ( !strcmp("$latr","N")) { $latr="nl"; } else { $latr="sl"; }
    if ( !strcmp("$lotr","E")) { $lotr="el"; } else { $lotr="wl"; }

    $lats = round($lats,2);
    $lots = round($lots,2);

    return ( "$latr=$latd/$latm/$lats&$lotr=$lotd/$lotm/$lots" );
}

function WGS84toTOKYO($latd,$latm,$lats,$lotd,$lotm,$lots)
{
    $b = $latd + $latm/60.0 + $lats/3600.0;
    $l = $lotd + $lotm/60.0 + $lots/3600.0;

    // Mr. Toshiaki UMEMURA's simple trans. method
    // See  http://member.nifty.ne.jp/Nowral/

    $tb = $b + 0.000106960*$b - 0.000017467*$l - 0.0046020;
    $tl = $l + 0.000046047*$b + 0.000083049*$l - 0.0100410;

    list ($latd,$latm,$lats) = dms2dms($tb,0,0);
    list ($lotd,$lotm,$lots) = dms2dms($tl,0,0);

    return array($latd,$latm,$lats,$lotd,$lotm,$lots);
}
function  plugin_exifshowcase_glob ($pattern)
{
	$path_parts = pathinfo ($pattern);
	$pattern = '^' . str_replace (array ('*',  '?'), array ('(.+)', '(.)'), $path_parts['basename'] . '$');
	$dir = opendir ("{$path_parts['dirname']}/");
	while ($file = readdir ($dir))
	{
		if (ereg ($pattern, $file))
		{
			$result[$path_parts['dirname']."/".$file] = filemtime($path_parts['dirname']."/".$file);
		}
	}
	closedir ($dir);
	asort($result);
	return array_keys($result);
} 
?>