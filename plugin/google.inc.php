<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: google.inc.php,v 1.1 2004/06/07 15:13:12 nao-pon Exp $
//
//	 GNU/GPL にしたがって配布する。
//

function plugin_google_init()
{
	$data = array('plugin_google_dataset'=>array(
	'license_key'   => '8eJ5g29QFHIOFDogGDNwHdkjoRw5M002', // GoogleAPIsライセンスキー
	'cache_time'    => 1,                                  // キャッシュ有効時間(h)
	'def_max'       => 10,                                 // デフォルト表示数
	'max_limit'     => 50,                                 // 最大表示数
	'head_msg'      => '<h4>検索結果: %s <span class="small">by Google</span></h4><p class="empty"></p>',
	'research'      => 'さらに Google で探す',
	'err_nolicense'  => 'Googleライセンスキーが未設定です。<br />plugin/google.inc.php にて設定してください。',
	'err_noresult'  => 'Google では見つかりませんでした。',
	'err_noconnect' => 'Google に接続できませんでした。',
	));
	set_plugin_messages($data);
}

function plugin_google_convert()
{
	global $plugin_google_dataset;
	
	if (!$plugin_google_dataset['license_key']) return $plugin_google_dataset['err_nolicense'];
	$array = func_get_args();
	
	$query = "";
	$def_max = $max = $plugin_google_dataset['def_max'];
	$max_limit = $plugin_google_dataset['max_limit'];
	
	switch (func_num_args())
	{
		case 2:
			$max = min($array[1],$max_limit);
		case 1:
			$query = trim($array[0]);
	}
	if ($max < 1) $max = $def_max;
	
	return "<div>".sprintf($plugin_google_dataset['head_msg'],htmlspecialchars($query)).plugin_google_search_google_result($query)."</div>";
}

function plugin_google_search_google_result($query,$max=10)
{
	global $plugin_google_dataset;

	$ret = plugin_google_get_result_by_google($query,$max);
	if ($ret !== false)
	{
		foreach($ret as $line)
		{
			$result .= "<li><a href=\"".$line['url']."\" target=\"_blank\">".$line['title']."</a>\n";
			$result .= "<div class=\"small\" style=\"text-align:right;\"><a href=\"".$line['url']."\" target=\"_blank\">".$line['url']."</a></div>\n";
			$result .= "<p class=\"quotation\" style=\"margin-top:1px;\">".strip_tags($line['snippet'])."</p>";
			$result .= "</li>\n";
		}
		if ($result)
		{
			$result = "\n<ul class=\"recent_list\">\n".$result."</ul>\n";
			$result .= "<p><a href='http://www.google.co.jp/search?num=".$max."&lr=lang_ja&ie=euc_jp&q=".rawurlencode($query)."'>".$plugin_google_dataset['research']."</a></p>\n";
			return $result;
		}
		else
			return $plugin_google_dataset['err_noresult'];
	}
	else
	{
		return $plugin_google_dataset['err_noconnect'];
	}
}

function plugin_google_get_result_by_google($word,$max=10,$start=0)
{
	$res = plugin_google_result_google_api($word);
	if ($res['rc'] != 200) return false;
	$data = str_replace(array("\r","\n"),"",$res['data']);
	$data = str_replace(array("&lt;","&gt;","&amp;"),array("<",">","&"),$data);
	$data = str_replace("<br> ","",$data);
	//echo $data;
	$ret = array();
	if (preg_match_all("/<item[^>]*>(.+?)<\/item>/i",$data,$arg))
	{
		$i = 0;
		foreach($arg[1] as $item)
		{
			if (preg_match("/<title[^>]*>(.*)<\/title>/",$item,$match)) $ret[$i]['title'] = $match[1];
			if (preg_match("/<snippet[^>]*>(.*)<\/snippet>/",$item,$match)) $ret[$i]['snippet'] = $match[1];
			if (preg_match("/<URL[^>]*>(.*)<\/URL>/",$item,$match)) $ret[$i]['url'] = $match[1];
			$i++;
		}
	}
	return $ret;
}

function plugin_google_result_google_api($word,$max=10,$start=0)
{
	global $plugin_google_dataset;

	////////   Googleライセンスキーを設定   ////////
	$google_license_key = $plugin_google_dataset['license_key'];
	
	// キャッシュ有効時間(h)
	$cache_time = $plugin_google_dataset['cache_time'];
	
	// キャッシュファイル名
	$c_file = CACHE_DIR.md5($word.$max.$start).".ggl";
	
	if (file_exists($c_file) && $cache_time * 3600 < time() - filemtime($filename))
	{
		return array(
			'query'  => $query, // Query String
			'rc'     => 200, // エラー番号
			'header' => '',     // Header
			'data'   => join('',file($c_file))
		);
	}
	
	$word = mb_convert_encoding($word, "UTF-8", "EUC-JP");
	
	$data = "<?xml version='1.0' encoding='UTF-8'?>

<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/1999/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/1999/XMLSchema\">
  <SOAP-ENV:Body>
    <ns1:doGoogleSearch xmlns:ns1=\"urn:GoogleSearch\" 
         SOAP-ENV:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\">
      <key xsi:type=\"xsd:string\">$google_license_key</key>
      <q xsi:type=\"xsd:string\">$word</q>
      <start xsi:type=\"xsd:int\">$start</start>
      <maxResults xsi:type=\"xsd:int\">$max</maxResults>
      <filter xsi:type=\"xsd:boolean\">true</filter>
      <restrict xsi:type=\"xsd:string\"></restrict>
      <safeSearch xsi:type=\"xsd:boolean\">false</safeSearch>
      <lr xsi:type=\"xsd:string\">lang_ja</lr>
      <ie xsi:type=\"xsd:string\">utf8</ie>
      <oe xsi:type=\"xsd:string\">utf8</oe>
    </ns1:doGoogleSearch>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>";

	$query = 'POST '."/search/beta2"." HTTP/1.0\r\n";
	$query .= "Host: "."api.google.com"."\r\n";

	$query .= "Content-Type: text/xml; charset=utf-8\r\n";
	$query .= 'Content-Length: '.strlen($data)."\r\n";
	$query .= "\r\n";
	$query .= $data;

	$fp = fsockopen("api.google.com",80,$errno,$errstr,5);
	if (!$fp)
	{
		return array(
			'query'  => $query, // Query String
			'rc'     => $errno, // エラー番号
			'header' => '',     // Header
			'data'   => $errstr // エラーメッセージ
		);
	}
	
	fputs($fp, $query);
	
	$response = '';
	while (!feof($fp))
	{
		$response .= fgets($fp,4096);
	}
	fclose($fp);
	
	$response = mb_convert_encoding($response, "EUC-JP", "UTF-8");
	//echo $response;
	$resp = explode("\r\n\r\n",$response,2);
	$rccd = explode(' ',$resp[0],3); // array('HTTP/1.1','200','OK\r\n...')
	
	// キャッシュ保存
	$fp = fopen($c_file, "wb");
	fwrite($fp, $resp[1]);
	fclose($fp);

	
	return array(
		'query'  => $query,             // Query String
		'rc'     => (integer)$rccd[1], // Response Code
		'header' => $resp[0],           // Header
		'data'   => $resp[1]            // Data
	);
}

?>