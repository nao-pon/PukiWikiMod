<?php
// exrate.inc.php
// by nao-pon 
// $Id: exrate.inc.php,v 1.3 2003/10/13 12:23:28 nao-pon Exp $
// License: GNU/GPL

function plugin_exrate_init() {

}

function plugin_exrate_inline() {
	$money = func_get_args();
	$money = implode('',$money);
	//$url = "http://finance.www.infoseek.co.jp/Frx?pg=2201_forex.html&sv=FI";
	$url = "http://money.www.infoseek.co.jp/MnForex?sv=MN&pg=mn_fxrate.html";
	$notes = "[[����ե��������ե����ʥ�:$url]]�����ѡ����졢���ξ���˴�Ť�����ä������ʤ�»���ˤĤ��Ƥ⡢�������ȤǤϰ��ڤ���Ǥ���餤���ͤޤ��ΤǤ�λ������������";
	
	if ($money == "notes") return make_link("(($notes))");

	if (!$money) $money = "usd";

	

  if (file_exists(CACHE_DIR) === false or is_writable(CACHE_DIR) === false) {
    $nocachable = 1;		          // ����å����ԲĤξ��
  }
  if ($body = plugin_exrate_cache_fetch(CACHE_DIR)) {
		
  } else {
    $nocache = 1;			  // ����å��師�Ĥ��餺

		$body = implode('', file($url));
		//$body = mb_convert_encoding($body,SOURCE_ENCODING,"AUTO");
		//$body = preg_replace("/^.*<!-- START exchange rate -->(.*)<!-- END exchange rate -->.*$/s","$1",$body);
		$body = preg_replace("/^.*<!----- ������إ졼�Ȱ��� ----->(.*)<!-- End FooterWord -->.*$/s","$1",$body);
		$body = strip_tags($body);
		
		if ($nocache == 1 and $nocachable != 1) {
			//����å������¸
      plugin_exrate_cache_save($body,CACHE_DIR);
    }

	}
	$body = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$body);
	$body = explode("\n\n\n",$body);
	$body = str_replace("\n","",$body);
	$body = preg_replace("/\s+/"," ",$body);
	foreach($body as $line){
		if (preg_match("/$money\/jpy/i",$line)){
			$rate = explode(" ",$line);
			return "<a href=\"$url\" target=\"_blank\" title=\"�������� ".$rate[8]."\">".$rate[1]."</a>";
		}
	}
	return "<a href=\"$url\" target=\"_blank\" title=\"�����Ǥ��ޤ���Ǥ�����\">��</a>";
}
// ����å��夬���뤫Ĵ�٤�
function plugin_exrate_cache_fetch($dir) {
	$filename = $dir . "exrate_data.tmp";
	if (!is_readable($filename)) {
		return false;
	}
	//����å����ͭ������
	$time_limit = 300; // 5min

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