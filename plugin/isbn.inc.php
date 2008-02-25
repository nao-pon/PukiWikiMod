<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: isbn.inc.php,v 1.24 2008/02/25 03:08:42 nao-pon Exp $
//
// *0.5: URL ��¸�ߤ��ʤ���硢������ɽ�����ʤ���
//			 Thanks to reimy.
//	 GNU/GPL �ˤ������ä����ۤ��롣
//
if (!defined('NOIMAGE')) define('NOIMAGE','./image/noimage.png'); // ���������뤤�� pukiwiki.ini.php �˻��äƤ�����
if (!defined('SOURCE_ENCODING')) define('SOURCE_ENCODING','EUC'); // ���������뤤�� pukiwiki.ini.php �˻��äƤ�����
// upload dir(must set end of /)
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR','./attach/');

/////////////////////////////////////////////////
// Amazon������������ID
if (!defined('AMAZON_ASE_ID')) define('AMAZON_ASE_ID','hypweb-22');
// amazon ���ʾ�����礻 URI(dev-t �ϥޥ˥奢��Υǥ��ե������)
if (!defined('ISBN_AMAZON_XML'))
	define('ISBN_AMAZON_XML','http://xml.amazon.co.jp/onca/xml3?t=webservices-20&dev-t=GTYDRES564THU&type=lite&page=1&f=xml&locale=jp&AsinSearch=');
// amazon shop URI (_ISBN_ �˾���ID�����åȤ����)
if (!defined('ISBN_AMAZON_SHOP'))
	define('ISBN_AMAZON_SHOP','http://www.amazon.co.jp/exec/obidos/ASIN/_ISBN_/ref=nosim/'.AMAZON_ASE_ID);
// amazon UsedShop URI (_ISBN_ �˾���ID�����åȤ����)
if (!defined('ISBN_AMAZON_USED'))
	define('ISBN_AMAZON_USED','http://www.amazon.co.jp/exec/obidos/tg/detail/offer-listing/-/_ISBN_/all/ref='.AMAZON_ASE_ID);

/////////////////////////////////////////////////
// expire ��������å�������Ǻ�����뤫
if (!defined('ISBN_AMAZON_EXPIRE_IMG')) define('ISBN_AMAZON_EXPIRE_IMG',10);
// expire �����ȥ륭��å�������Ǻ�����뤫
if (!defined('ISBN_AMAZON_EXPIRE_TIT')) define('ISBN_AMAZON_EXPIRE_TIT',1);


function plugin_isbn_convert() {
	if ($error = check_HypSimpleAmazon()) {
		return $error;
	}

	if (func_num_args() < 1 or func_num_args() > 3) {
		return false;
	}
	$aryargs = func_get_args();
	$isbn = htmlspecialchars($aryargs[0]);	// for XSS
	$isbn = str_replace("-","",$isbn);

	$align = "right"; //������
	$title = '';
	$header = '';
	switch (func_num_args())
	{
		case 3:
			if (strtolower($aryargs[2]) == 'left') $align = "left";
			elseif (strtolower($aryargs[2]) == 'clear') $align = "clear";
			elseif (strtolower($aryargs[2]) == 'header' || $aryargs[2] == 'h') $header = "header";
			elseif (strtolower($aryargs[2]) == 'info') $header = "info";
			elseif (strtolower($aryargs[2]) == 'img' || $aryargs[2] == 'image') $title = "image";
			else $title = htmlspecialchars($aryargs[2]);
		case 2:
			if (strtolower($aryargs[1]) == 'left') $align = "left";
			elseif (strtolower($aryargs[1]) == 'clear') $align = "clear";
			elseif (strtolower($aryargs[1]) == 'header' || $aryargs[1] == 'h') $header = "header";
			elseif (strtolower($aryargs[1]) == 'info') $header = "info";
			elseif (strtolower($aryargs[1]) == 'img' || $aryargs[1] == 'image') $title = "image";
			else $title = htmlspecialchars($aryargs[1]);
		case 1:
			if (strtolower($aryargs[0]) == 'clear') 
			{
				$align = "clear";
				$isbn = "";
			}
	}
	if ($isbn)
	{
		$tmpary = plugin_isbn_get_isbn_title($isbn);
		$alt = plugin_isbn_get_caption($tmpary);
		if ($tmpary[2]) $price = "<div style=\"text-align:right;\">����: $tmpary[2]��</div>";
		$off = 0;
		$_price = (int) trim(str_replace(",","",$tmpary[2]));
		$_listprice = (int) trim(str_replace(",","",$tmpary[8]));
		if ($_price && $_listprice && ($_price < $_listprice))
		{
			$off = 100 - (($_price/$_listprice) * 100);
			$price = "<div style=\"text-align:right;\">����: $tmpary[8]�� �� $tmpary[2]��<br />".(int)$off."% Off</div>";
			$listprice = '';
		} else {
			$listprice = ($tmpary[8] && $_price !== $_listprice)? "<div style=\"text-align:right;\">".str_replace('$1', $tmpary[8], $this->msg['price'])."</div>" : '';
		}
		$usedprice = ($tmpary[9])? "<div style=\"text-align:right;\">".str_replace('$1', $tmpary[9], $this->msg['used'])."</div>" : '';
		
		if ($title != '') {			// �����ȥ���꤫��ư������
			$h_title = $title;
		} else {					// �����ȥ뼫ư����
			$title = "[ $tmpary[1] ]<br />$tmpary[0]";
			$h_title = "$tmpary[0]";
		}
	}
	if ($header != "info")
		return plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header,$listprice,$usedprice);
	else
	{
		return plugin_isbn_get_info($tmpary,$isbn);
	}
}

function plugin_isbn_inline()
{
	if ($error = check_HypSimpleAmazon()) {
		return $error;
	}

	$prms = func_get_args();
	$body = array_pop($prms); // {}��
	$body = preg_replace('#</?(a|span)[^>]*>#i','',$body);
	$body = preg_replace('#(?:alt|title)=("|\').*\1#i','',$body);
	list($isbn,$option) = array_pad($prms,2,"");
	$option = htmlspecialchars($option); // for XSS
	$isbn = htmlspecialchars($isbn); // for XSS
	$isbn = str_replace("-","",$isbn);
	
	$tmpary = array();
	$tmpary = plugin_isbn_get_isbn_title($isbn);
	if ($tmpary[2]) $price = "<div style=\"text-align:right;\">$tmpary[2]��</div>";
	$title = $tmpary[0];
	//$text = htmlspecialchars(preg_replace('#</?(a|span)[^>]*>#i','',$option));
	$alt = plugin_isbn_get_caption($tmpary);
	$amazon_a = '<a href="'.str_replace('_ISBN_',$isbn,ISBN_AMAZON_SHOP).'" target="_blank" title="'.$alt.'">';
	$match = array();
	if (!preg_match("/(s|l|m)?ima?ge?/i",$option,$match))
	{
		if ($option || $body) $title = $option.$body;
		return $amazon_a . $title . '</a>';
	} else {
		$size = '';
		if (!empty($match[1])) {
			$size = strtoupper($match[1]);
			if ($size === 'M') {
				$size = '';
			} else {
				$size .= '-';
			}
		}
		$url = plugin_isbn_cache_image_fetch($size.$isbn, UPLOAD_DIR);
		return $amazon_a.'<img src="'.$url.'" alt="'.$alt.'" /></a>';
	}
}

function plugin_isbn_get_caption($data)
{
	$off = "";
	$_price = (int) trim(str_replace(",","",$data[2]));
	$_listprice = (int) trim(str_replace(",","",$data[8]));
	if ($_price && $_listprice && ($_price != $_listprice))
	{
		$off = (int)(100 - (($_price/$_listprice) * 100));
		$off = " ({$off}% Off)";
	}

	//����ʸ�����å� IE �� "&#13;&#10;"
	$br = (strstr($_SERVER["HTTP_USER_AGENT"], "MSIE"))? "&#13;&#10;" : " ";

	$alt = "[ $data[1] ]{$br}$data[0]";
	if ($data[8]) $alt .= "{$br}����: $data[8]��";
	if ($data[2]) $alt .= "{$br}Amazon: $data[2]��$off";
	if ($data[9]) $alt .= "{$br}USED: $data[9]�ߏ��";
	//if ($data[3]) $alt .= "{$br}����: $data[3]";
	//if ($data[4]) $alt .= "{$br}�����ƥ�����: $data[4]";
	if ($data[5]) $alt .= "{$br}ȯ����: $data[5]";
	if ($data[6]) $alt .= "{$br}ȯ�丵: ". strip_tags($data[6]);
	if ($data[7]) $alt .= "{$br}ȯ������: $data[7]";
	return $alt;
}

function plugin_isbn_get_info($data,$isbn)
{
	$alt = plugin_isbn_get_caption($data);
	$amazon_a = '<a href="'.str_replace('_ISBN_',$isbn,ISBN_AMAZON_SHOP).'" target="_blank" title="'.$alt.'">';
	$amazon_s1 = "<a href=\"http://www.amazon.co.jp/exec/obidos/external-search/?mode=blended&amp;keyword=";
	$amazon_s2 = "&amp;tag=".AMAZON_ASE_ID."&amp;encoding-string-jp=%93%FA%96%7B%8C%EA&amp;Go.x=14&amp;Go.y=5\" target=\"_blank\" alt=\"Amazon Serach\" title=\"Amazon Serach\">";
/*
	if ($data[3])
	{
		$artists = array();
		foreach(split(", ",$data[3]) as $tmp)
		{
			$artists[] = $amazon_s1 . plugin_isbn_jp_enc($tmp,"sjis") . $amazon_s2 . $tmp . "</a>";
		}
		$data[3] = join(", ",$artists);
	}
	if ($data[4])
	{
		$artists = array();
		foreach(split(", ",$data[4]) as $tmp)
		{
			$artists[] = $amazon_s1 . plugin_isbn_jp_enc($tmp,"sjis") . $amazon_s2 . $tmp . "</a>";
		}
		$data[4] = join(", ",$artists);
	}
	if ($data[6])
		$data[6] = $amazon_s1 . plugin_isbn_jp_enc($data[6],"sjis") . $amazon_s2 . $data[6] . "</a>";
*/
		
	$off = "";

	$_price = (int) trim(str_replace(",","",$data[2]));
	$_listprice = (int) trim(str_replace(",","",$data[8]));
	if ($_price && $_listprice && ($_price != $_listprice))
	{
		$off = (int)(100 - (($_price/$_listprice) * 100));
		$off = " ({$off}% Off)";
	}
	if ($data[9])
		$data[9] = '<a href="'.str_replace('_ISBN_',$isbn,ISBN_AMAZON_USED).'" target="_blank" alt="Amazon Used Serach" title="Amazon Used Serach">'.$data[9].'�ߡ�</a>';

	$td_title_style = " style=\"text-align:right;\" nowrap=\"true\"";
		
	$addrow = '';
	if (@ $data[3]) {
		foreach(explode('<br />', $data[3]) as $tmp){
			list($cap, $val) = explode(':', $tmp, 2);
			$cap = trim($cap);
			$val = trim($val);
			$addrow .= "<tr><td$td_title_style>{$cap}:</td><td style=\"text-align:left;\">{$val}</td></tr>";
		}
	}
		
	$ret = "<div><table style=\"width:auto;\">";
	if ($data[1]) $ret .= "<tr><td$td_title_style>���ƥ��꡼: </td><td style=\"text-align:left;\">$data[1]</td></tr>";
	if ($data[0]) $ret .= "<tr><td$td_title_style>�����ȥ�: </td><td style=\"text-align:left;\">{$amazon_a}$data[0]</a></td></tr>";
	if ($data[8]) $ret .= "<tr><td$td_title_style>����: </td><td style=\"text-align:left;\">$data[8]��</td></tr>";
	if ($data[2]) $ret .= "<tr><td$td_title_style>Amazon����: </td><td style=\"text-align:left;\">$data[2]��$off</td></tr>";
	if ($data[9]) $ret .= "<tr><td$td_title_style>USED����: </td><td style=\"text-align:left;\">$data[9]</td></tr>";
	//if ($data[3]) $ret .= "<tr><td$td_title_style>����: </td><td style=\"text-align:left;\">$data[3]</td></tr>";
	//if ($data[4]) $ret .= "<tr><td$td_title_style>�����ƥ�����: </td><td style=\"text-align:left;\">$data[4]</td></tr>";
	if ($addrow)  $ret .= $addrow;
	if ($data[5]) $ret .= "<tr><td$td_title_style>ȯ����: </td><td style=\"text-align:left;\">$data[5]</td></tr>";
	if ($data[6]) $ret .= "<tr><td$td_title_style>ȯ�丵: </td><td style=\"text-align:left;\">$data[6]</td></tr>";
	if ($data[7]) $ret .= "<tr><td$td_title_style>ȯ������: </td><td style=\"text-align:left;\">$data[7]</td></tr>";
	$ret .= "</table></div>";
	return $ret;
}

function plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header="",$listprice,$usedprice)
{
	$amazon_a = '<a href="'.str_replace('_ISBN_',$isbn,ISBN_AMAZON_SHOP).'" target="_blank" title="'.$alt.'">';
	if ($align == 'clear') {			// ��������
		return '<div style="clear:both"></div>';
	}

	if (! ($url = plugin_isbn_cache_image_fetch($isbn, UPLOAD_DIR))) return false;

	if ($title == 'image') {				// �����ȥ뤬�ʤ���С������Τ�ɽ��
		return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em">
 {$amazon_a}<img src="$url" alt="$alt" /></a>
</div>
EOD;
	} else {					// �̾�ɽ��
		$img_size = @getimagesize(str_replace(XOOPS_URL,XOOPS_ROOT_PATH,$url));
		//echo str_replace(XOOPS_URL,XOOPS_ROOT_PATH,$url);
		
		if (substr($isbn,0,1) == "B"){
				$code = "ASIN: ".$isbn;
		} else {
				$code = "ISBN: ".substr($isbn,0,1)."-".substr($isbn,1,3)."-".substr($isbn,4,5)."-".substr($isbn,9,1);
		}
		 if ($header != "header"){
return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em;text-align:center">
 {$amazon_a}<img src="$url" alt="$alt" /></a><br/>
 <table style="width:{$img_size[0]}px;border:0"><tr>
	<td style="text-align:left">{$amazon_a}$title</a></td>
 </tr></table>
</div>
EOD;
		} else {
return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em;text-align:center">
 {$amazon_a}<img src="$url" alt="$alt" /></a></div>
<h4 id="{$isid}" class="isbn_head">{$amazon_a}{$h_title}</a></h4>
<div style="text-align:right;">{$code}</div>
$listprice
$price
$usedprice
EOD;
		}
	}
}

function plugin_isbn_get_isbn_title(& $isbn, $check = true) {
	include_once XOOPS_TRUST_PATH . '/class/hyp_common/hsamazon/hyp_simple_amazon.php';
	$ama = new HypSimpleAmazon();
	$isbn = $ama->ISBN2ASIN($isbn);

	$nocache = $nocachable = 0;
	$title = $category = $price = $author = $artist = $releasedate = $manufacturer = $availability = $listprice = $usedprice = '';
	if ($title = plugin_isbn_cache_fetch($isbn, P_CACHE_DIR, $check)) {
		list($title,$category,$price,$author,$artist,$releasedate,$manufacturer,$availability,$listprice,$usedprice) = $title;
	} else {
		$title = 'ISBN:' . $isbn;
	}
	$tmpary = array($title,$category,$price,$author,$artist,$releasedate,$manufacturer,$availability,$listprice,$usedprice);
	return $tmpary;
}

// ����å��夬���뤫Ĵ�٤�
function plugin_isbn_cache_fetch($target, $dir, $check=true) {
	global $vars;

	$filename = $dir . $target . '.isbn';
	
	if (!file_exists($filename) ||
		($check && ISBN_AMAZON_EXPIRE_TIT * 3600 * 24 < time() - filemtime($filename))) {
		// �ǡ�������˹Ԥ�
		include_once XOOPS_TRUST_PATH . '/class/hyp_common/hsamazon/hyp_simple_amazon.php';
		$ama = new HypSimpleAmazon($this->config['AMAZON_ASE_ID']);
		$ama->encoding = SOURCE_ENCODING;
		$ama->itemLookup($target);
		$tmpary = $ama->getCompactArray();
		$ama = NULL;
		
		$title = '';
		if (!empty($tmpary['Items'])) {
			$tmpary = $tmpary['Items'][0];
			$title = $tmpary['TITLE'];
			$category = @$tmpary['BINDING'];
			$price = @$tmpary['PRICE'];
			$author = @$tmpary['CREATOR'];
			$artist = '';
			$releasedate = @$tmpary['RELEASEDATE'];
			$manufacturer = @ $tmpary['MANUFACTURER'];
			$availability = @ $tmpary['AVAILABILITY'];
			$listprice = @ $tmpary['LISTPRICE'];
			$usedprice = @ $tmpary['USEDPRICE'];
			$simg = $tmpary['SIMG']; //[10]
			$mimg = $tmpary['MIMG']; //[11]
			$limg = $tmpary['LIMG']; //[12]
		}

		if ($title != '') {	// �����ȥ뤬����С��Ǥ����������å������¸
			$title = "$title<>$category<>$price<>$author<>$artist<>$releasedate<>$manufacturer<>$availability<>$listprice<>$usedprice<>$simg<>$mimg<>$limg";
			plugin_isbn_cache_save($title, $filename);
		}
	} else {
		$title = file_get_contents($filename);
	}
	if (strlen($title) > 0) {
		return explode("<>",$title);
	} else {
		return array();
	}

}

// ��������å��夬���뤫Ĵ�٤�
function plugin_isbn_cache_image_fetch($target, $dir, $check=true) {
	global $vars;
	$_target = $target = strtoupper($target);
	$filename = $dir.encode($vars["page"])."_".encode("ISBN".$target.".jpg");

	if (!is_readable($filename) || (is_readable($filename) && $check && ISBN_AMAZON_EXPIRE_IMG * 3600 * 24 < time() - filemtime($filename))) {
		$size = 'M';
		$isbn = $target;
		if (preg_match("/^(?:(s|m|l)-)(.+)/i",$target,$match)) {
			$size = strtoupper($match[1]);
			$isbn = $match[2];
		}
		$ary = plugin_isbn_cache_fetch($isbn, P_CACHE_DIR);
		if ($size === 'S') {
			$url = $ary[10];
		} else if ($size === 'L') {
			$url = $ary[12];
		} else {
			$url = $ary[11];
		}
		
		if ($url) {
			$data = http_request($url);
			if ($data['rc'] == 200 && $data['data']) {
				$data = $data['data'];
			} else 	{
				$data = @join(@file(NOIMAGE));
			}
		} else {
			// ����å���� NOIMAGE �Υ��ԡ��Ȥ���
			$data = @join(@file(NOIMAGE));
		}
		plugin_isbn_cache_image_save($data, $target, UPLOAD_DIR);
		return str_replace("./",XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/",$filename);
	} else
		return str_replace("./",XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/",$filename);
}

// ����å������¸
function plugin_isbn_cache_save($data, $filename) {
	global $vars;
	
	//$filename = $dir . encode($target) . ".tmp";
	//$filename = $dir.encode($vars["page"])."_".encode("ISBN".$target.".dat");
	$fp = fopen($filename, "w");
	fwrite($fp, $data);
	fclose($fp);
	return $filename;
}

// ��������å������¸
function plugin_isbn_cache_image_save($data, $target, $dir) {
	global $vars;
	
	$name = "ISBN".$target.".jpg";
	$filename = $dir . encode($vars["page"])."_".encode($name);

	$fp = fopen($filename.".tmp", "wb");
	fwrite($fp, $data);
	fclose($fp);

	if (!exist_plugin('attach') or !function_exists('attach_upload'))
	{
		exit ('attach.inc.php not found or not correct version.');
	}
	
	$GLOBALS['pukiwiki_allow_extensions'] = "";
	do_upload($vars['page'],$name,$filename.".tmp",FALSE,NULL,TRUE);

	return $filename;
}

// ʸ�����URL���󥳡���
function plugin_isbn_jp_enc($word,$mode){
	switch( $mode ){
		case "sjis" : return rawurlencode(mb_convert_encoding($word, "SJIS", "EUC-JP"));
		case "euc" : return rawurlencode($word);
		case "utf8" : return rawurlencode(mb_convert_encoding($word, "UTF-8", "EUC-JP"));
	}
	return true;
}
?>