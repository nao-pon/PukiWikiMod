<?php
// exrate.inc.php
// by nao-pon 
// $Id: exrate.inc.php,v 1.1 2003/07/14 09:13:48 nao-pon Exp $
// License: GNU/GPL

function plugin_exrate_init() {

}

function plugin_exrate_inline() {
	$money = func_get_args();
	$money = implode('',$money);
	$url = "http://finance.www.infoseek.co.jp/Frx?pg=2201_forex.html&sv=FI";
	$notes = "[[����ե��������ե����ʥ�:$url]]�����ѡ�&br;���������졢���ξ���˴�Ť�����ä������ʤ�»���ˤĤ��Ƥ⡢�������ȤǤϰ��ڤ���Ǥ���餤���ͤޤ��ΤǤ�λ������������";
	
	if ($money == "notes") return inline("(($notes))");

	if (!$money) $money = "usd";

	

  if (file_exists(CACHE_DIR) === false or is_writable(CACHE_DIR) === false) {
    $nocachable = 1;		          // ����å����ԲĤξ��
  }
  if ($body = plugin_exrate_cache_fetch(CACHE_DIR)) {
		
  } else {
    $nocache = 1;			  // ����å��師�Ĥ��餺

		$body = implode('', file($url));
		//$body = mb_convert_encoding($body,SOURCE_ENCODING,"AUTO");
		$body = preg_replace("/^.*<!-- START exchange rate -->(.*)<!-- END exchange rate -->.*$/s","$1",$body);
		$body = strip_tags($body);
		
		if ($nocache == 1 and $nocachable != 1) {
			//����å������¸
      plugin_exrate_cache_save($body,CACHE_DIR);
    }

	}
	if (preg_match("/^.*$money\s([0-9.]*)[^\/]+(\d\d\:\d\d).*$/is",$body,$arg)){
		$rate[$money]['val'] = $arg[1];
		$rate[$money]['time'] = $arg[2];
		return "<a href=\"$url\" target=\"_blank\" title=\"�������� ".$rate[$money]['time']."\">".$rate[$money]['val']."</a>";
	} else {
		return "<a href=\"$url\" target=\"_blank\" title=\"�����Ǥ��ޤ���Ǥ�����\">��</a>";
	}
}
// ����å��夬���뤫Ĵ�٤�
function plugin_exrate_cache_fetch($dir) {
	$filename = $dir . "exrate_data.tmp";
	if (!is_readable($filename)) {
		return false;
	}
	//����å����ͭ������
	$time_limit = 600; // 10min

	$time = UTIME - filemtime($filename);
	if ($time > $time_limit){
		unlink();
		return false;
	}
	if (!($fp = @fopen($filename, "r"))) return false;
	$cache_data = fread($fp, 4096);
	fclose($fp);
	if (strlen($cache_data) > 0) {
		return $cache_data;
	}
	return false;
}

// ����å������¸
function plugin_exrate_cache_save($data, $dir) {
  $filename = $dir . "exrate_data.tmp";
  $fp = fopen($filename, "w");
  fwrite($fp, $data);
  fclose($fp);
  return $filename;
}

?>