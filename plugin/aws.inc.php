<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: aws.inc.php,v 1.1 2004/07/31 07:09:36 nao-pon Exp $
/////////////////////////////////////////////////

// #aws([Format Filename],[Mode],[Key Word],[Node Number],[Sort Mode])

function plugin_aws_convert()
{
	////// config //////
	$xls_url = "http://www5f.biglobe.ne.jp/~nao-pon/aws/xsl/"; // XSLTファイルが置いてあるディレクトリURL
	$amazon_dev_t = "D2OS08VR83Y4NP"; // デベロッパートークン
	$amazon_t = "hypweb-22"; // アソシエイツID
	$amazon_xml = "http://xml-jp.amznxslt.com";
	$cache_time = 1440; // Cache time (min)
	////////////////////
	
	list($f,$m,$k,$b,$s) = func_get_args();

	$cache_time = $cache_time * 60;
	$cache_file = P_CACHE_DIR.md5($f.$m.$k.$b.$s).".aws";
	
	if (file_exists($cache_file) && filemtime($cache_file) > time() - $cache_time)
	{
		$ret = join('',@file($cache_file));
	}
	else
	{
		$url = "";
		if ($k) $k = rawurlencode(mb_convert_encoding($k, "UTF-8", "EUC-JP"));
		
		if (!$m && $k)
			$url = $amazon_xml."/onca/xml3?t={$amazon_t}&dev-t={$amazon_dev_t}&type=lite&page=1&locale=jp&f={$xls_url}$f&BlendedSearch=$k";
		elseif ($b)
			$url = $amazon_xml."/onca/xml3?t={$amazon_t}&dev-t={$amazon_dev_t}&type=lite&page=1&locale=jp&f={$xls_url}$f&mode=$m&BrowseNodeSearch=$b";
		elseif ($k)
			$url = $amazon_xml."/onca/xml3?t={$amazon_t}&dev-t={$amazon_dev_t}&type=lite&page=1&locale=jp&f={$xls_url}$f&mode=$m&KeywordSearch=$k";
			
		if ($s && preg_match("/(\+titlerank)/",$s,$s_val))
		{
			$url .= "&sort=".$s_val[1];
		}
			
		//echo $url."<br>";

		$ret = join('',@file($url));
		$ret = mb_convert_encoding($ret, "EUC-JP", "UTF-8");
		
		if (strpos($ret,"<ErrorMsg>") === FALSE)
		{
			$ret = str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>","",$ret);
			
			$ret = str_replace('/hypweb-22?','/ref=nosim/hypweb-22?',$ret);
			
			$ret = preg_replace('/(<img[^>]+>)/ie',"plugin_aws_add_imgsize_tag('$1')",$ret);
			
			if ($ret)
			{
				if ($fp = @fopen($cache_file,"w"))
				{
					fputs($fp,$ret);
					fclose($fp);
				}
				// plane_text DB を更新を指示
				need_update_plaindb();
			}
		}
	}
	$style = " style='word-break:break-all;'";
	$clear = "";
	return "<div{$style}>{$ret}</div>{$clear}";

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
?>