<?php
if( ! class_exists( 'HypCommonFunc' ) )
{

class HypCommonFunc
{
	// 1バイト文字をエンティティ化
	function str_to_entity(&$str)
	{
		$e_mail = "";
		$i = 0;
		while($str[$i])
		{
			$e_mail .= "&#".ord((string)$str[$i]).";";
			$i++;
		}
		$str = $e_mail;
		return $str;
	}
	
	// ",' で括ったフレーズ対応スプリット
	function phrase_split($str)
	{
		$words = array();
		$str = preg_replace("/(\"|'|”|’)(.+?)(?:\\1)/e","str_replace(' ','\x08','$2')",$str);
		$words = preg_split('/\s+/',$str,-1,PREG_SPLIT_NO_EMPTY);
		$words = str_replace("\x08"," ",$words);
		return $words;
	}
	
	// 配列対応 & gpc 対応のstripslashes
	function stripslashes_gpc(&$v)
	{
		if(ini_get("magic_quotes_gpc"))
		{
			if (is_array($v))
			{
				$arr =array();
				foreach($v as $k=>$m)
				{
					$arr[$k] = HypCommonFunc::stripslashes_gpc($m);
				}
				$v = $arr;
			}
			else
			{
				$v = stripslashes($v);
			}
		}
		return $v;
	}
	
	// RSS関連のキャッシュを削除する
	function clear_rss_cache($files=array())
	{
		include_once XOOPS_ROOT_PATH.'/class/template.php';
		
		if (empty($files) || !is_array($files))
		{
			$files = array(
				'db:BopComments_rss.html',
				'db:whatsnew_rss.html',
				'db:whatsnew_atom.html',
				'db:whatsnew_rdf.html',
				'db:whatsnew_pda.html',
				'db:whatsnew_block_bop.html',
				'db:whatsnew_block_mod.html',
				'db:whatsnew_block_date.html',
			);
		}
		
		$tpl = new XoopsTpl();
		$tpl->xoops_setCaching(2);
		foreach($files as $tgt)
		{
			if ($tgt) {$tpl->clear_cache($tgt);}
		}
	}
	
	// RPC Update Ping を打つ
	function update_rpc_ping($default_update="http://bulkfeeds.net/rpc http://ping.myblog.jp http://ping.bloggers.jp/rpc/ http://blog.goo.ne.jp/XMLRPC http://ping.cocolog-nifty.com/xmlrpc http://rpc.technorati.jp/rpc/ping")
	{
		global $xoopsConfig;
		
		//RSSキャッシュファイルを削除
		HypCommonFunc::clear_rss_cache();
		
		$update_ping2 = $default_update;
		$update_ping = preg_split ( "/[\s,]+/" , $update_ping2 );

		$ping_blog_name = $xoopsConfig['sitename'];
		$ping_url		= XOOPS_URL."/";

		$ping_update = <<<EOF
	<?xml version="1.0"?>
	<methodCall>
		<methodName>weblogUpdates.ping</methodName>
		<params>
		<param><value>$ping_blog_name</value></param>
		<param><value>$ping_url</value></param>
		</params>
	</methodCall>
EOF;

		$ping_update = mb_convert_encoding
					   ( $ping_update , "UTF-8" , "EUC-JP" );

		$ping_update_leng = strlen($ping_update);

		foreach ( $update_ping as $up )
		{
			if ( $up != "" )
			{
				$uph = ereg_replace ( "http:\/\/", "", $up );
				list ( $host , $uri ) = split ( "/", $uph , 2 );
				list ( $host , $port ) = split ( ":", $host );

				if ( $port == "" )
				{
					$port = 80;
					$add_port = "";
				}
				else
				{
					$add_port = ":$port";
				}

				$files = @fsockopen($host, $port , $errNo , $errStr, 10);

				@fputs($files, "POST /$uri HTTP/1.0\r\n" );
				@fputs($files, "Host: $host$add_port\r\n" );
				@fputs($files, "Content-Length: $ping_update_leng\r\n" );
				@fputs($files, "User-Agent: XOOPS update pinger Ver 1.00\r\n" );
				@fputs($files, "Content-Type: text/xml\r\n" );
				@fputs($files, "\r\n" );
				@fputs($files, "$ping_update" );

				fclose ( $files );

			}
		}
		return ;
	}
	
	function make_context($text,$words=array(),$l=255)
	{
		static $strcut = "";
		if (!$strcut)
			$strcut = create_function ( '$a,$b,$c', (function_exists('mb_strcut'))?
				'return mb_strcut($a,$b,$c);':
				'return strcut($a,$b,$c);');
		
		$text = str_replace(array('&lt;','&gt;','&amp;','&quot;','&#039;'),array('<','>','&','"',"'"),$text);
		
		if (!is_array($words)) $words = array();
		
		$ret = "";
		$q_word = str_replace(" ","|",preg_quote(join(' ',$words),"/"));
		
		if (preg_match("/$q_word/i",$text,$match))
		{
			$ret = ltrim(preg_replace('/\s+/', ' ', $text));
			list($pre, $aft) = array_pad(preg_split("/$q_word/i", $ret, 2), 2, "");
			$m = intval($l/2);
			$ret = (strlen($pre) > $m)? "... " : "";
			$ret .= $strcut($pre, max(strlen($pre)-$m+1,0),$m).$match[0];
			$m = $l-strlen($ret);
			$ret .= $strcut($aft, 0, min(strlen($aft),$m));
			if (strlen($aft) > $m) $ret .= " ...";
		}
		
		if (!$ret)
			$ret = $strcut($text, 0, $l);
		
		return htmlspecialchars($ret, ENT_NOQUOTES);
	}
	
	function set_need_refresh($mode)
	{
		if ($mode)
		{
			setcookie ("HypNeedRefresh", "1");
		}
		else
		{
			setcookie ("HypNeedRefresh", "", time() - 3600);
		}
	}
}

/*
 *   HTTPリクエストを発行し、データを取得する
 * $url     : http://から始まるURL(http://user:pass@host:port/path?query)
 * $method  : GET, POST, HEADのいずれか(デフォルトはGET)
 * $headers : 任意の追加ヘッダ
 * $post    : POSTの時に送信するデータを格納した配列('変数名'=>'値')
 * $redirect_max : HTTP redirectの回数制限
*/

if( ! class_exists( 'Hyp_HTTP_Request' ) )
{
class Hyp_HTTP_Request
{
	var $url='';
	var $method='GET';
	var $headers='';
	var $post=array();
	
	// リダイレクト回数制限
	var $redirect_max=10;
	// 同期モード or 非同期モード
	var $blocking=TRUE;
	// 接続試行回数
	var $connect_try=1;
	// 接続時タイムアウト
	var $connect_timeout=30;
	// 通信時タイムアウト
	var $read_timeout=10;
	
	
	// プロキシ使用？
	var $use_proxy=0;
	
	// proxy ホスト
	var $proxy_host='proxy.xxx.yyy.zzz';
	
	// proxy ポート番号
	var $proxy_port='';
	
	// プロキシサーバを使用しないホストのリスト
	var $no_proxy=array( 
		'127.0.0.1', 
		'localhost', 
		//'192.168.1.0/24', 
		//'no-proxy.com', 
	);
	
	// プロキシ認証
	var $need_proxy_auth=0;
	var $proxy_auth_user='';
	var $proxy_auth_pass='';

	// result
	var $query = '';   // Query String
	var $rc = '';      // Response Code
	var $header = '';  // Header
	var $data = '';    // Data
	
	function get()
	{
		$max_execution_time = ini_get('max_execution_time');
		$max_execution_time = ($max_execution_time)? $max_execution_time : 30;
		
		$rc = array();
		$arr = parse_url($this->url);
		if (!$this->connect_try) $this->connect_try = 1;
		
		$via_proxy = $this->use_proxy and via_proxy($arr['host']);
		
		// query
		$arr['query'] = isset($arr['query']) ? '?'.$arr['query'] : '';
		// port
		$arr['port'] = isset($arr['port']) ? $arr['port'] : 80;
		
		$url_base = $arr['scheme'].'://'.$arr['host'].':'.$arr['port'];
		$url_path = isset($arr['path']) ? $arr['path'] : '/';
		$this->url = ($via_proxy ? $url_base : '').$url_path.$arr['query'];

		
		$query = $this->method.' '.$this->url." HTTP/1.0\r\n";
		$query .= "Host: ".$arr['host']."\r\n";
		$query .= "User-Agent: hyp_http_request/1.0\r\n";
		
		// proxyのBasic認証 
		if ($this->need_proxy_auth and isset($this->proxy_auth_user) and isset($this->proxy_auth_pass)) 
		{
			$query .= 'Proxy-Authorization: Basic '.
				base64_encode($this->proxy_auth_user.':'.$this->proxy_auth_pass)."\r\n";
		}

		// Basic 認証用
		if (isset($arr['user']) and isset($arr['pass']))
		{
			$query .= 'Authorization: Basic '.
				base64_encode($arr['user'].':'.$arr['pass'])."\r\n";
		}
		
		$query .= $this->headers;
		
		// POST 時は、urlencode したデータとする
		if (strtoupper($this->method) == 'POST')
		{
			if (is_array($this->post))
			{
				$_send = array();
				foreach ($this->post as $name=>$val)
				{
					$_send[] = $name.'='.urlencode($val);
				}
				$data = join('&',$_send);
				$query .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$query .= 'Content-Length: '.strlen($data)."\r\n";
				$query .= "\r\n";
				$query .= $data;
			}
			else
			{
				$query .= 'Content-Length: '.strlen($this->post)."\r\n";
				$query .= "\r\n";
				$query .= $this->post;
			}
		}
		else
		{
			$query .= "\r\n";
		}
		
		//set_time_limit($this->connect_timeout * $this->connect_try + 60);
		$fp = $connect_try_count = 0;
		while( !$fp && $connect_try_count < $this->connect_try )
		{
			@set_time_limit($this->connect_timeout + $max_execution_time);
			$fp = fsockopen(
				$via_proxy ? $this->proxy_host : $arr['host'],
				$via_proxy ? $this->proxy_port : $arr['port'],
				$errno,$errstr,$this->connect_timeout);
			if ($fp) break;
			$connect_try_count++;
			sleep(2); //2秒待つ
		}
		if (!$fp)
		{
			$this->query  = $query;  // Query String
			$this->rc     = $errno;  // エラー番号
			$this->header = '';      // Header
			$this->data   = $errstr; // エラーメッセージ
			return;
		}
		
		fputs($fp, $query);
		
		// 非同期モード
		if (!$this->blocking)
		{
			fclose($fp);
			$this->query  = $query;
			$this->rc     = 200;
			$this->header = '';
			$this->data   = 'Blocking mode is FALSE';
			return;
		}
		
		$response = '';
		while (!feof($fp))
		{
			if ($this->read_timeout)
			{
				@set_time_limit($this->read_timeout + $max_execution_time);
				socket_set_timeout($fp, $this->read_timeout);
			}
			$_response = fread($fp,4096);
			$_status = socket_get_status($fp);
			if ($_status['timed_out'] === false)
			{
				$response .= $_response;
			}
			else
			{
				fclose($fp);
				$this->query  = $query;
				$this->rc     = 408;
				$this->header = '';
				$this->data   = 'Request Time-out';
				return;
			}
		}
		fclose($fp);
		
		$resp = explode("\r\n\r\n",$response,2);
		$rccd = explode(' ',$resp[0],3); // array('HTTP/1.1','200','OK\r\n...')
		$rc = (integer)$rccd[1];
		
		// Redirect
		switch ($rc)
		{
			case 302: // Moved Temporarily
			case 301: // Moved Permanently
				if (preg_match('/^Location: (.+)$/m',$resp[0],$matches)
					and --$this->redirect_max > 0)
				{
					$this->url = trim($matches[1]);
					if (!preg_match('/^https?:\//',$this->url)) // no scheme
					{
						if ($this->url{0} != '/') // Relative path
						{
							// to Absolute path
							$this->url = substr($url_path,0,strrpos($url_path,'/')).'/'.$this->url;
						}
						// add sheme,host
						$this->url = $url_base.$this->url;
					} 
					return $this->get();
				}
		}
		
		$this->query = $query;    // Query String
		$this->rc = $rc;          // Response Code
		$this->header = $resp[0]; // Header
		$this->data = $resp[1];   // Data
		return;
	}
	// プロキシを経由する必要があるかどうか判定
	function via_proxy($host)
	{
		static $ip_pattern = '/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:\/(.+))?$/';
		
		if (!$this->use_proxy)
		{
			return FALSE;
		}
		$ip = gethostbyname($host);
		$l_ip = ip2long($ip);
		$valid = (is_long($l_ip) and long2ip($l_ip) == $ip); // valid ip address
		
		foreach ($this->no_proxy as $network)
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
}
}

// create a instance in global scope
//$GLOBALS['hypCommonFunc'] = new HypCommonFunc() ;

// Make context for search by nao-pon
if (!function_exists('xoops_make_context'))
{
function xoops_make_context($text,$words=array(),$l=255)
{
	return HypCommonFunc::make_context($text,$words,$l);
}
}

if (!function_exists('xoops_update_rpc_ping'))
{
function xoops_update_rpc_ping($default_update="http://bulkfeeds.net/rpc http://ping.myblog.jp http://ping.bloggers.jp/rpc/ http://blog.goo.ne.jp/XMLRPC http://ping.cocolog-nifty.com/xmlrpc http://rpc.technorati.jp/rpc/ping")
{
	return HypCommonFunc::update_rpc_ping($default_update);
}
}

}
?>