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
	$notes = "[[インフォシークファイナンス:$url]]より引用。万一、この情報に基づいて被ったいかなる損害についても、当サイトでは一切の責任を負いかねますのでご了承ください。";
	
	if ($money == "notes") return make_link("(($notes))");

	if (!$money) $money = "usd";

	

  if (file_exists(CACHE_DIR) === false or is_writable(CACHE_DIR) === false) {
    $nocachable = 1;		          // キャッシュ不可の場合
  }
  if ($body = plugin_exrate_cache_fetch(CACHE_DIR)) {
		
  } else {
    $nocache = 1;			  // キャッシュ見つからず

		$body = implode('', file($url));
		//$body = mb_convert_encoding($body,SOURCE_ENCODING,"AUTO");
		//$body = preg_replace("/^.*<!-- START exchange rate -->(.*)<!-- END exchange rate -->.*$/s","$1",$body);
		$body = preg_replace("/^.*<!----- 外国為替レート一覧 ----->(.*)<!-- End FooterWord -->.*$/s","$1",$body);
		$body = strip_tags($body);
		
		if ($nocache == 1 and $nocachable != 1) {
			//キャッシュに保存
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
			return "<a href=\"$url\" target=\"_blank\" title=\"更新時間 ".$rate[8]."\">".$rate[1]."</a>";
		}
	}
	return "<a href=\"$url\" target=\"_blank\" title=\"取得できませんでした。\">？</a>";
}
// キャッシュがあるか調べる
function plugin_exrate_cache_fetch($dir) {
	$filename = $dir . "exrate_data.tmp";
	if (!is_readable($filename)) {
		return false;
	}
	//キャッシュの有効時間
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

// キャッシュを保存
function plugin_exrate_cache_save($data, $dir) {
  $filename = $dir . "exrate_data.tmp";
  $fp = fopen($filename, "w");
  fwrite($fp, $data);
  fclose($fp);
  return $filename;
}

?>