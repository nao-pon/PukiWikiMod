<?php
// $Id: countdown.inc.php,v 1.2 2003/06/28 11:33:03 nao-pon Exp $

/*
 * age.inc.php
 * License: GPL
 * Author: nao-pon http://hypweb.net
 * Last-Update: 2003-06-11
 *
 * ������ȥ�����ץ饰����
 */

// ����饤��ץ饰����Ȥ��Ƥε�ư
function plugin_countdown_inline() {
	$just_day = "";
  list($y,$m,$d,$title) = func_get_args();
  //��[1-5]�������б�
  if (preg_match("/(sun|mon|tue|wed|thu|fri|sat|��|��|��|��|��|��|��)([1-5])?/",$d,$arg)) {
		$j_week = array("��","��","��","��","��","��","��");
		$e_week = array("sun","mon","tue","wed","thu","fri","sat");
		$arg[1] = str_replace($j_week,$e_week,$arg[1]);
		$y2=$y;
		if (!$y2) $y2=date("Y");
		$f_day = mktime(0,0,0,$m,1,$y2);
		$f_week = date("w",$f_day);
		$t_week = date("w",strtotime("last $arg[1]",$f_day));
		if ($f_week > $t_week){
			$d = 6-$f_week+$t_week;
		} else {
			$d = 1+$t_week-$f_week;
		}
		if ($arg[2]) $d += ($arg[2]-1)*7;
	}
  //��ǯ���б� every �ޤ��� ����
  if ($y == "" || $y == "every"){
		if ($m*100+$d == date("m")*100+date("d")) $just_day="(0)";
		$y = date("Y");
		if ($m*100+$d <= date("m")*100+date("d")){
			//��ǯ�ϲ᤮���Τ���ǯ
			$y ++;
		}
	}
	if ($title) return $title."�ޤǤ���".plugin_countdown_day($y,$m,$d).$just_day."��";
	return plugin_countdown_day($y,$m,$d).$just_day;
}

// ������
function plugin_countdown_day($y,$m,$d) {
	$today = GregorianToJD (date("m"),date("d"),date("Y"));
	$date = GregorianToJD ($m,$d,$y);
	return ($date-$today > 0)? $date-$today : 0;
}

?>