<?php
// $Id: age.inc.php,v 1.4 2005/01/14 05:12:42 nao-pon Exp $

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
		return day($y,$m,$d);
	}
	else
	{
		return age($y,$m,$d,$format);
	}
}

// 年齢算出
function age($y,$m,$d,$format=false)
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

// 日齢算出
function day($y,$m,$d) {
	$today = GregorianToJD (date("m"),date("d"),date("Y"));
	$date = GregorianToJD ($m,$d,$y);
	return ($today-$date > 0)? $today-$date : 0;
}

?>