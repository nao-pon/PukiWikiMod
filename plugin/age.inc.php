<?php
// $Id: age.inc.php,v 1.3 2004/09/07 12:07:50 nao-pon Exp $

/*
 * age.inc.php
 * License: GPL
 * Author: Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
 * Last-Update: 2003-06-08
 *
 * 年齢算出プラグイン
 */

// インラインプラグインとしての挙動
function plugin_age_inline() {
  list($y,$m,$d,$prm) = func_get_args();
  if (!$y || !$m || !$d) return FALSE;
  
  if ($prm == "day"){
		return day($y,$m,$d);
	} else {
		return age($y,$m,$d);
	}
}

// 年齢算出
function age($y,$m,$d) {
  // 現在年月日
  $ny = date("Y",UTIME);
  $nm = date("m",UTIME);
  $nd = date("d",UTIME);

  $md  = $m*100 +$d;
  $nmd = $nm*100+$nd;
  $age = $ny - $y;

  if ($nmd < $md) $age = $age-1; // まだ誕生日を迎えていない
	return ($age > 0)? $age : 0;
}

// 日齢算出
function day($y,$m,$d) {
	$today = GregorianToJD (date("m"),date("d"),date("Y"));
	$date = GregorianToJD ($m,$d,$y);
	return ($today-$date > 0)? $today-$date : 0;
}

?>