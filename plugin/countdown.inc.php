<?php
// $Id: countdown.inc.php,v 1.4 2003/08/05 23:45:37 nao-pon Exp $

/*
 * countdown.inc.php
 * License: GPL
 * Author: nao-pon http://hypweb.net
 * Last-Update: 2003-06-27
 *
 * ������ȥ�����ץ饰����
 */
// ������ꡡ��ݲ��б����äƥ����Ȥ����ܸ졡(^^��
function plugin_countdown_init() {
	if (LANG == "ja") {
		$msg['_countdown_msg'] = "%1\$s�ޤǤ���%2\$d%3\$s��";
	} else {
		$msg['_countdown_msg'] = "%2\$d%3\$sday(s) to %1\$s";
	}
	set_plugin_messages($msg);
	
	// calendar��ĥ�⥸�塼�뤬ͭ���Ǥʤ��ʤ��Ȥ߹��ߤ��ߤ�
	if (!extension_loaded('calendar')) {
		if (strtoupper(substr(PHP_OS, 0,3) == 'WIN')) {
			dl('php_calendar.dll');
		} else {
			dl('calendar.so');
		}
	}

}
// ����饤��ץ饰����Ȥ��Ƥε�ư
function plugin_countdown_inline() {
	global $_msg_week;
	global $_countdown_msg;
	
	$just_day = "";
  list($y,$m,$d,$title) = func_get_args();
  //��[1-5]�������б�
  $my_lng_week = "|".implode("|",$_msg_week);
  if (preg_match("/(sun|mon|tue|wed|thu|fri|sat".$my_lng_week.")([1-5])?/",$d,$arg)) {
		$e_week = array("sun","mon","tue","wed","thu","fri","sat");
		if (LANG != "en") $arg[1] = str_replace($_msg_week,$e_week,$arg[1]);
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
	//���դ������������å�
	if (!checkdate($m,$d,$y)) return false;
	
	if ($title) {
		$title = htmlspecialchars($title);
		return sprintf($_countdown_msg,$title,plugin_countdown_day($y,$m,$d),$just_day);
	} else {
		return plugin_countdown_day($y,$m,$d).$just_day;
	}
}

// ������
function plugin_countdown_day($y,$m,$d) {
	$today = GregorianToJD (date("m"),date("d"),date("Y"));
	$date = GregorianToJD ($m,$d,$y);
	return ($date-$today > 0)? $date-$today : 0;
}

?>