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
	$notes = "[[インフォシークファイナンス:$url]]より引用。&br;　　・万一、この情報に基づいて被ったいかなる損害についても、当サイトでは一切の責任を負いかねますのでご了承ください。";
	
	if ($money == "notes") return inline("(($notes))");

	if (!$money) $money = "usd";

	

  if (file_exists(CACHE_DIR) === false or is_writable(CACHE_DIR) === false) {
    $nocachable = 1;		          // キャッシュ不可の場合
  }
  if ($body = plugin_exrate_cache_fetch(CACHE_DIR)) {
		
  } else {
    $nocache = 1;			  // キャッシュ見つからず

		$body = implode('', file($url));
		//$body = mb_convert_encoding($body,SOURCE_ENCODING,"AUTO");
		$body = preg_replace("/^.*<!-- START exchange rate -->(.*)<!-- END exchange rate -->.*$/s","$1",$body);
		$body = strip_tags($body);
		
		if ($nocache == 1 and $nocachable != 1) {
			//キャッシュに保存
      plugin_exrate_cache_save($body,CACHE_DIR);
    }

	}
	if (preg_match("/^.*$money\s([0-9.]*)[^\/]+(\d\d\:\d\d).*$/is",$body,$arg)){
		$rate[$money]['val'] = $arg[1];
		$rate[$money]['time'] = $arg[2];
		return "<a href=\"$url\" target=\"_blank\" title=\"更新時間 ".$rate[$money]['time']."\">".$rate[$money]['val']."</a>";
	} else {
		return "<a href=\"$url\" target=\"_blank\" title=\"取得できませんでした。\">？</a>";
	}
}
// キャッシュがあるか調べる
function plugin_exrate_cache_fetch($dir) {
	$filename = $dir . "exrate_data.tmp";
	if (!is_readable($filename)) {
		return false;
	}
	//キャッシュの有効時間
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

// キャッシュを保存
function plugin_exrate_cache_save($data, $dir) {
  $filename = $dir . "exrate_data.tmp";
  $fp = fopen($filename, "w");
  fwrite($fp, $data);
  fclose($fp);
  return $filename;
}

?>