<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: yahoo.inc.php,v 1.8 2010/04/30 00:39:51 nao-pon Exp $
/////////////////////////////////////////////////

// #yahoo([Format Filename],[Mode],[Key Word],[Node Number],[Sort Mode])

function plugin_yahoo_init()
{
	$msg = array('plugin_yahoo_dataset'=>array(
		'msg_notfound'  => '�� Yahoo!�ǡ�%1$s�פ�%2$s�򸡺����ޤ��������Ĥ���ޤ���Ǥ�����',
		'msg_more'  => '��%1$s�פ�%2$s���ä�õ��',
		'msg_web'  => 'Web������',
		'msg_img'  => '����',
		'msg_mov'  => 'ư��',
		'err_badres'  => 'Yahoo!�Υ����С�����³�Ǥ��ޤ���Ǥ�����',
		'err_option'  => '#yahoo(mode,query) ���顼:���ץ�������������ꤵ��Ƥ��ޤ���',
		'err_setconfig'  => '�� ���ꥨ�顼: [ '.PLUGIN_DATA_DIR.'yahoo/config.php ] ���������������Ƥ���������',
		'err_nonwritable'  => '�� ���ꥨ�顼: �ǥ��쥯�ȥ� [ '.PLUGIN_DATA_DIR.'yahoo/ ] �˽񤭹��߸��¤�����ޤ���',
		'writable_check'  => 1,
		'YouTubeNAVI' => 1,
	));

	// config �ɤ߹���
	$config_file = PLUGIN_DATA_DIR."yahoo/config.php";
	if (file_exists($config_file))
	{
		include($config_file);
	}
	else
	{
		if(!is_writable(PLUGIN_DATA_DIR."yahoo/"))
		{
			$msg['plugin_yahoo_dataset']['writable_check'] = 0;
			$data['plugin_yahoo_dataset'] = array();
		}
		else
		{
			// �ʲ�������Ľ�ǤϤ���ޤ���
			// ����Ľ�ϡ�plugin_data/yahoo/config.php �Ǥ���
			$data = <<<EOT
<?php
\$data = array('plugin_yahoo_dataset'=>array(
	//////// Config ///////
	'adult_ok'   => 1, // [1|0]������ȥ���ƥ�Ĥθ�����̤�ޤ�뤫�ɤ�������ꤷ�ޤ���1�ξ��ϥ�����ȥ���ƥ�Ĥ�ޤߤޤ���
	'similar_ok' => 1, // [1|0]Ʊ������ƥ�Ĥ��̤θ�����̤Ȥ��뤫�ɤ�������ꤷ�ޤ���1�ξ���Ʊ������ƥ�Ĥ�ޤߤޤ�
	'ng_site'    => "", // ����������([SPACE]���ڤ�Ǻ���30������)
	'coloration' => "any", // ���������оݤο�����[any|color|bw]
	'format_web' => "any", // �����о�[any|html|msword|pdf|ppt|rss|txt|xls](Web)
	'format_img' => "any", // �����о�[any|bmp|gif|jpeg|png](Image)
	'format_mov' => "any", // �����о�[any|avi|flash|mpeg|msmedia|quicktime|realmedia](Movie)
	'max_web'    => 10, // ��������ε�����(Web)
	'max_img'    => 5, // ��������ε�����(Image)
	'max_mov'    => 4, // ��������ε�����(Movie)
	'col_web'    => 1, // ɽ������ε�����(Web)
	'col_img'    => 5, // ɽ������ε�����(Image)
	'col_mov'    => 4, // ɽ������ε�����(Movie)
	'cache_time' => 360, // Cache time (min) 360m = 6h
	'YouTubeNAVI'=> 1, // ư�踡���� YouTube NAVI �ؤΥ�󥯤��ղä���
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

	$data['plugin_yahoo_dataset'] = array_merge($msg['plugin_yahoo_dataset'], $data['plugin_yahoo_dataset']);

	set_plugin_messages($data);
}
?>
<?php
function plugin_yahoo_action()
{
	global $get,$plugin_yahoo_dataset;

	if ($get['pmode'] == "refresh")
	{
		foreach(array("m","q","t","ma","ta","c","ref") as $key)
		{
			$$key = (isset($get[$key]))? $get[$key] : "";
		}
		$page = $ref;

		$filename = P_CACHE_DIR.md5($m.$q.$t.$ma.$ta.$c).".yah";

		$old_time = filemtime($filename);

		if (!is_readable($filename) || time() - filemtime($filename) > $plugin_yahoo_dataset['cache_time'] * 60 )
		{
			// ��������̥���åɤ�����ʤ��褦��
			touch($filename);

			list($ret,$refresh) = plugin_yahoo_get($m,$q,$t,$ma,$ta,$c,TRUE);

			if ($ret)
			{
				// �ڡ���HTML����å������
				delete_page_html($page,"html");
			}
			else
			{
				// ���Ԥ����Τǥ����ॹ����פ��᤹
				touch($filename,$old_time);
			}
		}
		exit;
	}

	return false;
}

function plugin_yahoo_convert()
{
	global $script, $vars, $plugin_yahoo_dataset, $link_target;

	$args = func_get_args();
	if (count($args) < 2)
	{
		return "<p>{$plugin_yahoo_dataset['err_option']}</p>";
	}
	$mode = array_shift($args);
	$query = array_shift($args);
	$youtube = "";

	// mode Ƚ��
	$mode = trim(strtolower($mode));
	switch($mode)
	{
		case "web":
			$mode = "web";
			$more = "http://search.yahoo.co.jp/search?p=".rawurlencode($query)."&amp;ei=EUC-JP&amp;b=";
			$more_add = 1;
			break;
		case "image":
		case "img":
			$mode = "img";
			$more = "http://image-search.yahoo.co.jp/search?p=".rawurlencode($query)."&amp;ei=EUC-JP";
			$more_add = FALSE;
			break;
		case "movie":
		case "mov":
			$mode = "mov";
			$more = "http://video.search.yahoo.co.jp/search/video?p=".rawurlencode($query)."&amp;ei=EUC-JP";
			$more_add = FALSE;
			if (!empty($plugin_yahoo_dataset['YouTubeNAVI']))
			{
				$youtube = ' [ <a href="http://youtube.navi-gate.org/tag/'.plugin_yahoo_youtube_urlencode(mb_convert_encoding($query,"UTF-8",SOURCE_ENCODING)).'/" target="'.$link_target.'">YouTube NAVI: '.htmlspecialchars($query).'</a> ]';
			}
			break;
		//case "related":
		//case "rel":
		//	$mode = "rel";
		//	break;
		default:
			// web
			$mode = "web";
	}

	$prms = array("target"=>$link_target,"type"=>"and","max"=>$plugin_yahoo_dataset['max_'.$mode],"col"=>$plugin_yahoo_dataset['col_'.$mode]);
	pwm_check_arg($args, &$prms);
	$max = (int)$prms['max'];
	$more = "<a href='".$more.(($more_add !== FALSE)? ($max + $more_add) : '')."' target='".htmlspecialchars($prms['target'])."'>".sprintf($plugin_yahoo_dataset['msg_more'],htmlspecialchars($query),$plugin_yahoo_dataset['msg_'.$mode])."</a>";

	list($ret,$refresh) = plugin_yahoo_get($mode,$query,$prms['type'],$max,$prms['target'],$prms['col']);

	// ��ե�å��夬ɬ��
	if ($refresh)
	{
		$vars['mc_refresh'][] = "?plugin=yahoo&pmode=refresh&ref=".rawurlencode(strip_bracket($vars["page"]))."&m=".rawurlencode($mode)."&q=".rawurlencode($query)."&t=".rawurlencode($prms['type'])."&ma=".rawurlencode($prms['max'])."&ta=".rawurlencode($prms['target'])."&c=".rawurlencode($prms['col']);
	}


	$cr = '<!-- Begin Yahoo! JAPAN Web Services Attribution Snippet -->
<a href="http://developer.yahoo.co.jp/about" target="'.$link_target.'"><img src="http://i.yimg.jp/images/yjdn/yjdn_attbtn2_105_17.gif" width="105" height="17" title="Web�����ӥ� by Yahoo! JAPAN" alt="Web�����ӥ� by Yahoo! JAPAN" border="0" style="margin:15px 15px 15px 15px"></a>
<!-- End Yahoo! JAPAN Web Services Attribution Snippet -->';

	return "<p><div class='pwm_yahoo'>{$ret}</div>{$cr}{$more}{$youtube}</p>";

}

function plugin_yahoo_get($mode,$query,$type,$max,$target,$col,$do_refresh=FALSE)
{
	global $plugin_yahoo_dataset;

	$xml_cache = $plugin_yahoo_dataset['cache_time'];
	$cache_file = P_CACHE_DIR.md5($mode.$query.$type.$max.$target.$col).".yah";

	// ����å���Ƚ��
	if (!$do_refresh && file_exists($cache_file))
	{
		$html = join("",file($cache_file));
		$refresh = (filemtime($cache_file) > time() - $xml_cache * 60)? FALSE : TRUE;
		return array($html,$refresh);
	}

	if (!$plugin_yahoo_dataset['writable_check'])
		return array($plugin_yahoo_dataset['err_nonwritable'],false);

	$html = plugin_yahoo_gethtml($mode,$query,$type,$max,$target,$col);

	// ����å�����¸
	if ($html && $fp = @fopen($cache_file,"wb"))
	{
		fputs($fp,$html);
		fclose($fp);
	}

	return array($html,0);

}

function plugin_yahoo_gethtml($mode,$query,$type,$max,$target,$col)
{
	global $plugin_yahoo_dataset;
	include_once("./include/hyp_common/hyp_simplexml.php");

	$qs = htmlspecialchars($query);
	// REST�ꥯ�����Ȥι���
	$query = rawurlencode(mb_convert_encoding(trim($query),"UTF-8",SOURCE_ENCODING));
	$max = (int)$max;
	$type = trim(strtolower($type));
	switch($type)
	{
		case "and":
		case "all":
			$type = "all";
			break;
		case "or":
		case "any":
			$type = "any";
			break;
		case "word":
		case "phrase":
			$type = "phrase";
			break;
		default:
			$type = "any";
	}
	$mode = trim(strtolower($mode));
	switch($mode)
	{
		case "web":
			$mode = "web";
			$url = "http://search.yahooapis.jp/WebSearchService/V1/webSearch?appid=PukiWikiMod&query={$query}&results={$max}&type={$type}";
			break;
		case "image":
		case "img":
			$mode = "img";
			$url = "http://search.yahooapis.jp/ImageSearchService/V1/imageSearch?appid=PukiWikiMod&query={$query}&results={$max}&type={$type}";
			break;
		case "movie":
		case "mov":
			$mode = "mov";
			$url = "http://search.yahooapis.jp/VideoSearchService/V1/videoSearch?appid=PukiWikiMod&query={$query}&results={$max}&type={$type}";
			break;
		case "related":
		case "rel":
			$mode = "rel";
			$url = "http://search.yahooapis.jp/AssistSearchService/V1/webunitSearch?appid=PukiWikiMod&query={$query}&results={$max}";
			break;
		default:
			// web
			$mode = "web";
			$url = "http://search.yahooapis.jp/WebSearchService/V1/webSearch?appid=PukiWikiMod&query={$query}&results={$max}&type={$type}";
	}

	// �ǡ�������
	$xml = http_request($url);
	if ($xml['rc'] == 200 && $xml['data'])
	{
		$xml = $xml['data'];
		$xm = new HypSimpleXML();
		$xml = $xm->XMLstr_in($xml);
		// �����ǡ����ʤ�
		if (!$xml['totalResultsReturned'])
		{
			return sprintf($plugin_yahoo_dataset['msg_notfound'],$qs,$plugin_yahoo_dataset['msg_'.$mode]);
		}
	}
	else
	{
		// �ǡ����������顼
		return $plugin_yahoo_dataset['err_badres'];

	}

	// �����ǡ����ʤ�
	if (!$xml['totalResultsReturned'])
	{
		return sprintf($plugin_yahoo_dataset['msg_notfound'],$qs,$plugin_yahoo_dataset['msg_'.$mode]);
	}

	$func = "plugin_yahoo_build_".$mode;
	$html = $func($xml,$target,$col);
	return $html;
}

function plugin_yahoo_build_web($xml,$target,$col)
{
	//$xml['totalResultsAvailable'];
    //$xml['totalResultsReturned'];
    //$xml['firstResultPosition'];

	$dats = array();
	if (isset($xml['Result'][0]))
	{
		$dats = $xml['Result'];
	}
	else
	{
		$dats[0] = (empty($xml['Result']))? array() : $xml['Result'];
	}

	$html = "";
	if ($dats)
	{
		$html = $sdiv = $ediv = "";
		if ($col > 1)
		{
			$sdiv = "<div style='float:left;width:".(intval(99/$col*10)/10)."%'>";
			$ediv = "</div><div style='clear:left;'></div>";
		}
		$cnt = 0;
		$limit = ceil(count($dats)/$col);
		$html .= $sdiv."<ul>";
		mb_convert_variables(SOURCE_ENCODING,"UTF-8",$dats);
		foreach ($dats as $dat)
		{
			if (plugin_yahoo_check_ngsite($dat['ClickUrl'])) {continue;}
			if ($cnt++ % $limit === 0 && $cnt !== 1) $html .= "</ul></div>".$sdiv."<ul>";
			$html .= "<li>";
			$html .= "<a href='".$dat['ClickUrl']."' target='{$target}'>".htmlspecialchars($dat['Title'])."</a>";
			$html .= "<div class='quotation'>".make_link($dat['Summary'])."</div>";
			$html .= "</li>";
		}
		$html .= "</ul>".$ediv;
	}

	return $html;
}

function plugin_yahoo_build_img($xml,$target,$col)
{
	$dats = array();
	if (isset($xml['Result'][0]))
	{
		$dats = $xml['Result'];
	}
	else
	{
		$dats[0] = (empty($xml['Result']))? array() : $xml['Result'];
	}

	$html = "";
	if ($dats)
	{
		$cnt = 0;
		$html = "<table><tr>";
		mb_convert_variables(SOURCE_ENCODING,"UTF-8",$dats);
		foreach ($dats as $dat)
		{
			if (plugin_yahoo_check_ngsite($dat['ClickUrl'])) {continue;}
			$title = "[".htmlspecialchars($dat['Title'])."]".htmlspecialchars($dat['Summary']);
			$size = $dat['Width']." x ".$dat['Height']." ".$dat['FileSize'];
			$site = "[ <a href=\"".htmlspecialchars($dat['RefererUrl'])."\" target='{$target}'>Site</a> ]";

			if ($cnt++ % $col === 0 && $cnt !== 1) $html .= "</tr><tr>";
			$html .= "<td style='text-align:center;vertical-align:middle;'>";
			$html .= "<a href=\"".$dat['ClickUrl']."\" target=\"{$target}\" title=\"{$title}\" type=\"img\"><img src=\"{$dat['Thumbnail']['Url']}\" width=\"{$dat['Thumbnail']['Width']}\" height=\"{$dat['Thumbnail']['Height']}\" alt=\"{$title}\" title=\"{$title}\" /></a>";
			$html .= "<br /><small>".$size."<br />".$site."</small>";
			$html .= "</td>";
		}
		$html .= "</tr></table>";
	}

	return $html;
}

function plugin_yahoo_build_mov($xml,$target,$col)
{
	$dats = array();
	if (isset($xml['Result'][0]))
	{
		$dats = $xml['Result'];
	}
	else
	{
		$dats[0] = (empty($xml['Result']))? array() : $xml['Result'];
	}

	$html = "";
	if ($dats)
	{
		$cnt = 0;
		$html = "<table><tr>";
		mb_convert_variables(SOURCE_ENCODING,"UTF-8",$dats);
		foreach ($dats as $dat)
		{
			if (plugin_yahoo_check_ngsite($dat['ClickUrl'])) {continue;}
			$title = "[".htmlspecialchars($dat['Title'])."]".htmlspecialchars($dat['Summary']);
			$size = $dat['Width']." x ".$dat['Height'];
			$site = "[ <a href=\"".htmlspecialchars($dat['RefererUrl'])."\" target='{$target}'>Site</a> ]";
			$min = (int)($dat['Duration'] / 60);
			$sec = sprintf("%02d",($dat['Duration'] % 60));
			$length = $min.":".$sec;

			if ($cnt++ % $col === 0 && $cnt !== 1) $html .= "</tr><tr>";
			$html .= "<td style='text-align:center;vertical-align:middle;'>";
			$html .= "<a href='".$dat['ClickUrl']."' target='{$target}'><img src='{$dat['Thumbnail']['Url']}' width='{$dat['Thumbnail']['Width']}' height='{$dat['Thumbnail']['Height']}' alt=\"{$title}\" title=\"{$title}\" /></a>";
			$html .= "<br />".$size." ".$length."<br />".$site;
			$html .= "</td>";
		}
		$html .= "</tr></table>";
	}

	return $html;
}

function plugin_yahoo_build_rel($xml,$target,$col)
{

	return $html;
}

function plugin_yahoo_check_ngsite($url)
{
	global $plugin_yahoo_dataset;
	static $ngsites = null;
	if (is_null($ngsites))
	{
		$ngsites = explode(" ",$plugin_yahoo_dataset['ng_site']);
	}
	foreach($ngsites as $ngsite)
	{
		if ($ngsite && preg_match("#".preg_quote($ngsite,"#")."#i",$url))
		{
			return true;
		}
	}
	return false;
}

function plugin_yahoo_youtube_urlencode($tag)
{
	return (preg_match('/^[0-9a-z\-\. ]([0-9a-z\-\._ ]+)?$/i', $tag))? urlencode($tag) : "_".encode($tag);
}
?>