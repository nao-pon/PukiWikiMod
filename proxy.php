<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: proxy.php,v 1.14 2005/11/06 05:35:00 nao-pon Exp $
//

/*
 * http_request($url)
 *   HTTPリクエストを発行し、データを取得する
 * $url     : http://から始まるURL(http://user:pass@host:port/path?query)
 * $method  : GET, POST, HEADのいずれか(デフォルトはGET)
 * $headers : 任意の追加ヘッダ
 * $post    : POSTの時に送信するデータを格納した配列('変数名'=>'値')
 * $redirect_max : HTTP redirectの回数制限

*/

// リダイレクト回数制限の初期値
define('HTTP_REQUEST_URL_REDIRECT_MAX',10);

function http_request
	(
		$url,
		$method='GET',
		$headers='',
		$post=array(),
		$redirect_max=HTTP_REQUEST_URL_REDIRECT_MAX,
		$blocking=TRUE,
		$retry=1,
		$c_timeout=30,
		$r_timeout=10
	)
{
	global $use_proxy,$proxy_host,$proxy_port;
	global $need_proxy_auth,$proxy_auth_user,$proxy_auth_pass,$no_proxy;
	
	$d = new Hyp_HTTP_Request();

	$d->url = $url;
	$d->method = $method;
	$d->headers = $headers;
	$d->post = $post;
	$d->redirect_max = $redirect_max;
	$d->blocking = $blocking;
	$d->connect_try = $retry;
	$d->connect_timeout = $c_timeout;
	$d->read_timeout = $r_timeou;
	
	$d->use_proxy = $use_proxy;
	$d->proxy_host = $proxy_host;
	$d->proxy_port = $proxy_port;
	
	$d->need_proxy_auth = $need_proxy_auth;
	$d->proxy_auth_user = $proxy_auth_user;
	$d->proxy_auth_pass = $proxy_auth_pass;
	
	$d->no_proxy = $no_proxy;
	
	$d->get();
	
	return array(
		'query'  => $d->query,      // Query String
		'rc'     => $d->rc,         // Result code
		'header' => $d->header,     // Header
		'data'   => $d->data        // Data or Error Msg
	);	
}
// プロキシを経由する必要があるかどうか判定
function via_proxy($host)
{
	global $use_proxy,$no_proxy;
	static $ip_pattern = '/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:\/(.+))?$/';
	
	if (!$use_proxy)
	{
		return FALSE;
	}
	$ip = gethostbyname($host);
	$l_ip = ip2long($ip);
	$valid = (is_long($l_ip) and long2ip($l_ip) == $ip); // valid ip address
	
	foreach ($no_proxy as $network)
	{
		if ($valid and preg_match($ip_pattern,$network,$matches))
		{
			$l_net = ip2long($matches[1]);
			$mask = array_key_exists(2,$matches) ? $matches[2] : 32;
			$mask = is_numeric($mask) ?
				pow(2,32) - pow(2,32 - $mask) : // "10.0.0.0/8"
				ip2long($mask);                 // "10.0.0.0/255.0.0.0"
			if (($l_ip & $mask) == $l_net)
			{
				return FALSE;
			}
		}
		else
		{
			if (preg_match('/'.preg_quote($network,'/').'/',$host))
			{
				return FALSE;
			}
		}
	}
	return TRUE;
}
?>