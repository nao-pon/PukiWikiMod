<?php
// $Id: age.inc.php,v 1.5 2005/02/24 23:30:39 nao-pon Exp $

/*
 * age.inc.php
 * License: GPL
 * Author: Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
 * Last-Update: 2003-06-08
 *
 * 年齢算出プラグイン
 */

function plugin_age_init() {
	$messages = array('_age_messages'=>array(
	'format' => '$y年$m月$d日 $age歳',
	));
	set_plugin_messages($messages);
}


// インラインプラグインとしての挙動
function plugin_age_inline() {
	list($y,$m,$d,$prm) = func_get_args();
	if (!$y || !$m || !$d) return FALSE;
	
	$format = (strtolower($prm) == "f" || strtolower($prm) == "format");
	
	if ($prm == "day")
	{
		return plugin_age_day($y,$m,$d);
	}
	else
	{
		return plugin_age_age($y,$m,$d,$format);
	}
}

// 年齢算出
function plugin_age_age($y,$m,$d,$format=false)
{
	global $_age_messages;
	// 現在年月日
	$ny = date("Y",UTIME);
	$nm = date("m",UTIME);
	$nd = date("d",UTIME);

	$md	= $m*100 +$d;
	$nmd = $nm*100+$nd;
	$age = $ny - $y;

	if ($nmd < $md) $age = $age-1; // まだ誕生日を迎えていない
	
	$age = ($age > 0)? $age : 0;
	
	if ($format)
	{
		$age = str_replace(array('$y','$m','$d','$age'),array($y,$m,$d,$age),$_age_messages['format']);
	}
	
	return $age;
}

// ユリウス日(JD)算出 (upkさん提供)
function plugin_age_date2jd($m,$d,$y,$h=0,$i=0,$s=0) {

  if( $m < 3.0 ){
    $y -= 1.0;
    $m += 12.0;
  }

  $jd  = (int)( 365.25 * $y );
  $jd += (int)( $y / 400.0 );
  $jd -= (int)( $y / 100.0 );
  $jd += (int)( 30.59 * ( $m-2.0 ) );
  $jd += 1721088;
  $jd += $d;

  $t  = $s / 3600.0;
  $t += $i /60.0;
  $t += $h;
  $t  = $t / 24.0;

  $jd += $t;
  return( $jd );
}

// 日齢算出
function plugin_age_day($y,$m,$d) {
	if (function_exists ("GregorianToJD")) {
	$today = GregorianToJD (date("m"),date("d"),date("Y"));
	$date = GregorianToJD ($m,$d,$y);
	} else {
		$today = plugin_age_date2jd(date("m"),date("d"),date("Y"));
		$date = plugin_age_date2jd($m,$d,$y);
	}
	return ($today-$date > 0)? $today-$date : 0;
}

?>