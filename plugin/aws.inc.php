<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: aws.inc.php,v 1.9 2005/02/23 00:16:41 nao-pon Exp $
/////////////////////////////////////////////////

// #aws([Format Filename],[Mode],[Key Word],[Node Number],[Sort Mode])

function plugin_aws_init()
{
	$msg = array('plugin_aws_dataset'=>array(
		'msg_notfound'  => '※ Amazonで検索しましたがヒットしませんでした。',
		'err_setconfig'  => '※ 設定エラー: [ '.PLUGIN_DATA_DIR.'aws/config.php ] に設定情報を記入してください。',
		'err_nonwritable'  => '※ 設定エラー: ディレクトリ [ '.PLUGIN_DATA_DIR.'aws/ ] に書き込み権限がありません。',
		'writable_check'  => 1,
	));
	
	// config 読み込み
	$config_file = PLUGIN_DATA_DIR."aws/config.php";
	if (file_exists($config_file))
	{
		include($config_file);
	}
	else
	{
		if(!is_writable(PLUGIN_DATA_DIR."aws/"))
		{
			$msg['plugin_aws_dataset']['writable_check'] = 0;
			$data['plugin_aws_dataset'] = array();
		}
		else
		{
			// 以下は設定個所ではありません。
			// 設定個所は、plugin_data/aws/config.php です。
			$data = <<<EOT
<?php
\$data = array('plugin_aws_dataset'=>array(
	//////// Config ///////
	'xls_url'      => "", // XSLTファイルが置いてあるディレクトリURL
	'amazon_dev_t' => "", // デベロッパートークン
	'amazon_t'     => "", // アソシエイツID
	'amazon_xml'   => "http://xml-jp.amznxslt.com",
	'cache_time'   => 360, // Cache time (min) 360m = 6h
	//////// Config ///////
));
?>
EOT;
			$fp = fopen($config_file,"wb");
			fputs($fp,$data);
			fclose($fp);
			include($config_file);
		}
	}
	
	$data['plugin_aws_dataset'] = $data['plugin_aws_dataset'] + $msg['plugin_aws_dataset'];
	
	set_plugin_messages($data);
}

function plugin_aws_action()
{
	global $get,$plugin_aws_dataset;
		
	if ($get['pmode'] == "refresh")
	{
		$f = (isset($get['f']))? $get['f'] : "";
		$m = (isset($get['m']))? $get['m'] : "";
		$k = (isset($get['k']))? $get['k'] : "";
		$b = (isset($get['b']))? $get['b'] : "";
		$s = (isset($get['s']))? $get['s'] : "";
		$page = (isset($get['ref']))? $get['ref'] : "";
		
		$filename = P_CACHE_DIR.md5($f.$m.$k.$b.$s).".aws";
		
		$old_time = filemtime($filename);

		if (!is_readable($filename) || time() - filemtime($filename) > $plugin_aws_dataset['cache_time'] * 60 )
		{
			// 処理中に別スレッドが走らないように
			touch($filename);
			
			@list($ret,$refresh) = plugin_aws_get($f,$m,$k,$b,$s,TRUE);
			
			if ($ret)
			{
				// plane_text DB を更新
				need_update_plaindb($page);
				// ページHTMLキャッシュを削除
				delete_page_html($page,"html");
			}
			else
			{
				// 失敗したのでタイムスタンプを戻す
				touch($filename,$old_time);
			}
		}
		
		header("Content-Type: image/gif");
		readfile('image/transparent.gif');
		exit;
	}
	
	return false;
}

function plugin_aws_convert()
{
	global $script,$vars,$plugin_aws_dataset;
	
	//$start = getmicrotime();
	
	@list($f,$m,$k,$b,$s) = func_get_args();

	@list($ret,$refresh) = plugin_aws_get($f,$m,$k,$b,$s);
	
	$style = " style='word-break:break-all;'";
	$clear = "";
	$more = "";
	if ($k)
	{
		$more_link = "http://www.amazon.co.jp/exec/obidos/external-search?mode=blended&amp;tag={$plugin_aws_dataset['amazon_t']}&amp;keyword=".rawurlencode($k)."&amp;encoding-string-jp=".rawurlencode("日本語");
		$more = "<h4><a href=\"{$more_link}\" target=\"_blank\">「".htmlspecialchars($k)."」をAmazonで探す...</a></h4>";
		
	}
	// リフレッシュが必要
	if ($refresh)
	{
		$vars['mc_refresh'][] = "?plugin=aws&pmode=refresh&ref=".rawurlencode(strip_bracket($vars["page"]))."&f=".rawurlencode($f)."&m=".rawurlencode($m)."&k=".rawurlencode($k)."&b=".rawurlencode($b)."&s=".rawurlencode($s);
	}
	
	//$taketime = "<div style=\"text-align:right;\">".sprintf("%01.03f",getmicrotime() - $start)."</div>";
	return "{$more}<div{$style}>{$ret}</div>{$clear}";

}
function plugin_aws_add_imgsize_tag($tag)
{
	$tag = stripslashes($tag);
	if (preg_match('/<(img src=")([^"]+)("[^>]*)>/',$tag,$arg))
	{
		$img_size = @getimagesize($arg[2]);
		if (!empty($img_size[3]))
			$img_size = " ".$img_size[3];
		else
			$img_size = "";
		$tag = "<".$arg[1].$arg[2].$arg[3].$img_size.">";
	}
	return $tag;
}

function plugin_aws_get($f,$m,$k,$b,$s,$do_refresh=FALSE)
{
	global $plugin_aws_dataset;
	
	$xls_url = $plugin_aws_dataset['xls_url']; // XSLTファイルが置いてあるディレクトリURL
	$amazon_dev_t = $plugin_aws_dataset['amazon_dev_t']; // デベロッパートークン
	$amazon_t = $plugin_aws_dataset['amazon_t']; // アソシエイツID
	$amazon_xml = $plugin_aws_dataset['amazon_xml'];
	$cache_time = $plugin_aws_dataset['cache_time']; // Cache time (min)
	
	if (!$plugin_aws_dataset['writable_check'])
		return array($plugin_aws_dataset['err_nonwritable'],false);
	
	if (!$xls_url || !$amazon_dev_t || !$amazon_t)
		return array($plugin_aws_dataset['err_setconfig'],false);
	
	$refresh = 0;
	$ret = "";

	$cache_file = P_CACHE_DIR.md5($f.$m.$k.$b.$s).".aws";
	
	if (!$do_refresh && is_readable($cache_file))
	{
		$ret = join('',@file($cache_file));
		if (time() - filemtime($cache_file) >  $cache_time * 60)
		{
			$refresh = 1;
		}
		if ((trim($ret) == $plugin_aws_dataset['msg_notfound']) && (time() - filemtime($cache_file) > $cache_time * 6))
		{
			$refresh = 1;
			$ret = "";
		}
	}
	
	if (!$ret)
	{
		$url = "";
		if ($k) $k = rawurlencode(mb_convert_encoding($k, "UTF-8", "EUC-JP"));
		
		if (!$m && $k)
			$url = $amazon_xml."/onca/xml3?t={$amazon_t}&dev-t={$amazon_dev_t}&type=lite&page=1&locale=jp&f={$xls_url}$f&BlendedSearch=$k";
		elseif ($b)
			$url = $amazon_xml."/onca/xml3?t={$amazon_t}&dev-t={$amazon_dev_t}&type=lite&page=1&locale=jp&f={$xls_url}$f&mode=$m&BrowseNodeSearch=$b";
		elseif ($k)
			$url = $amazon_xml."/onca/xml3?t={$amazon_t}&dev-t={$amazon_dev_t}&type=lite&page=1&locale=jp&f={$xls_url}$f&mode=$m&KeywordSearch=$k";
			
		if ($s && preg_match("/(\+(titlerank|daterank))/",$s,$s_val))
		{
			$url .= "&sort=".$s_val[1];
		}
		
		$ret = join('',@file($url));
		$ret = mb_convert_encoding($ret, "EUC-JP", "UTF-8");
		
		if (strpos(strtolower($ret),"<errormsg>") === FALSE)
		{
			$ret = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>","",$ret);
			
			$ret = str_replace('/hypweb-22?','/ref=nosim/hypweb-22?',$ret);
			
			$ret = preg_replace('/(<img[^>]+>)/ie',"plugin_aws_add_imgsize_tag('$1')",$ret);
			
			if (!strip_tags($ret)) $ret = $plugin_aws_dataset['msg_notfound'];
			
			if ($fp = @fopen($cache_file,"wb"))
			{
				fputs($fp,$ret);
				fclose($fp);
			}
		}
	}
	
	return array($ret,$refresh);

}
?>