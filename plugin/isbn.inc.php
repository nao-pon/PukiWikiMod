<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: isbn.inc.php,v 1.6 2003/09/14 13:05:36 nao-pon Exp $
//
// *0.5: URL ��¸�ߤ��ʤ���硢������ɽ�����ʤ���
//       Thanks to reimy.
//	 GNU/GPL �ˤ������ä����ۤ��롣
//
if (!defined('NOIMAGE')) define('NOIMAGE','./image/noimage.png'); // ���������뤤�� pukiwiki.ini.php �˻��äƤ�����
if (!defined('SOURCE_ENCODING')) define('SOURCE_ENCODING','EUC'); // ���������뤤�� pukiwiki.ini.php �˻��äƤ�����
if (!defined('AMAZON_ASE_ID')) define('AMAZON_ASE_ID','hypweb-22'); // Amazon������������ID
// upload dir(must set end of /)
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR','./attach/');


function plugin_isbn_convert() {
  if (func_num_args() < 1 or func_num_args() > 3) {
    return false;
  }
  $aryargs = func_get_args();
  $isbn = htmlspecialchars($aryargs[0]);  // for XSS
  $isbn = str_replace("-","",$isbn);

  $header = strtolower(htmlspecialchars($aryargs[2]));
  if ($header == 'header' || $header == 'h') $aryargs[2]="";
  if ($aryargs[2] != '') {		  // �����ȥ���꤫��ư������
    $alt = htmlspecialchars($aryargs[2]); // for XSS
    $title = $alt;
    if ($alt == 'image') {
      $alt = plugin_isbn_get_isbn_title($isbn);
      $title = "";
    }
  } else {				  // �����ȥ뼫ư����
    $tmpary = array();
    $tmpary = plugin_isbn_get_isbn_title($isbn);
    if ($tmpary[2]) $price = "<div style=\"text-align:right;\">$tmpary[2]��</div>";
    $title = "[ $tmpary[1] ]<br />$tmpary[0]$price";
    $h_title = "$tmpary[0]";
    if ($tmpary[2]) $price2 = "&#13;&#10;���Ͳ��ʡ�$tmpary[2]��";
    $alt = "[ $tmpary[1] ]&#13;&#10;$tmpary[0]$price2";
  }
  $align = strtolower($aryargs[1]);
  if ($align == "header" || $align == "h") {
		$header = $align;
		$align= "";
	}
  if ($align != 'left' and $align != 'clear') { // ���ַ���
    $align = 'right';
  }
  if ($header == "h") $header = "header";
  return plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header);
}

function plugin_isbn_inline() {
  list($isbn,$option) = func_get_args();
  $isbn = htmlspecialchars($isbn); // for XSS
  $isbn = str_replace("-","",$isbn);
	$tmpary = array();
	$tmpary = plugin_isbn_get_isbn_title($isbn);
	if ($tmpary[2]) $price = "<div style=\"text-align:right;\">$tmpary[2]��</div>";
	$title = "$tmpary[0]";
	if ($tmpary[2]) $price2 = "&#13;&#10;���Ͳ��ʡ�$tmpary[2]��";
	$alt = "[ $tmpary[1] ]&#13;&#10;$tmpary[0]$price2";  
  if ($option != 'img'){
	  return '<a href="http://www.amazon.co.jp/exec/obidos/ASIN/'.$isbn.'/ref=ase_'.AMAZON_ASE_ID.'" target="_blank" title="'.$alt.'">' . $title . '</a>';
	} else {
		$url = plugin_isbn_cache_image_fetch($isbn, UPLOAD_DIR);
		return '<a href="http://www.amazon.co.jp/exec/obidos/ASIN/'.$isbn.'/ref=ase_'.AMAZON_ASE_ID.'" target="_blank"><img src="'.$url.'" alt="'.$alt.'" /></a>';
	}
}

function plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header="") {
	$AMAZON_ASE_ID = AMAZON_ASE_ID;
  if ($align == 'clear') {		  // ��������
    return '<div style="clear:both"></div>';
  }

  if (! ($url = plugin_isbn_cache_image_fetch($isbn, UPLOAD_DIR))) return false;

  if ($title == '') {			  // �����ȥ뤬�ʤ���С������Τ�ɽ��
    return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em">
 <a href="http://www.amazon.co.jp/exec/obidos/ASIN/{$isbn}/ref=ase_{$AMAZON_ASE_ID}" target="_blank"><img src="$url" alt="$alt" /></a>
</div>
EOD;
  } else {				  // �̾�ɽ��
     $img_size = GetImageSize($url);
		if (substr($isbn,0,1) == "B"){
				$code = "ASIN: ".$isbn;
		} else {
				$code = "ISBN: ".substr($isbn,0,1)."-".substr($isbn,1,3)."-".substr($isbn,4,5)."-".substr($isbn,9,1);
		}
     if ($header != "header"){
return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em;text-align:center">
 <a href="http://www.amazon.co.jp/exec/obidos/ASIN/{$isbn}/ref=ase_{$AMAZON_ASE_ID}" target="_blank"><img src="$url" alt="$alt" /></a><br/>
 <table style="width:{$img_size[0]}px;border:0"><tr>
  <td style="text-align:left"><a href="http://www.amazon.co.jp/exec/obidos/ASIN/{$isbn}/ref=ase_{$AMAZON_ASE_ID}" target="_blank">$title</a></td>
 </tr></table>
</div>
EOD;
		} else {
return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em;text-align:center">
 <a href="http://www.amazon.co.jp/exec/obidos/ASIN/{$isbn}/ref=ase_{$AMAZON_ASE_ID}" target="_blank"><img src="$url" alt="$alt" /></a></div>
<h4 id="{$isid}" class="isbn_head"><a href="http://www.amazon.co.jp/exec/obidos/ASIN/{$isbn}/ref=ase_{$AMAZON_ASE_ID}" target="_blank" title="{$alt}">{$h_title}</a></h4>
<div style="text-align:right;">{$code}</div>$price
EOD;
  	}
	}
}

function plugin_isbn_get_isbn_title($isbn) {
  $nocache = $nocachable = 0;
  $title = '';
  $url = "http://www.amazon.co.jp/exec/obidos/ASIN/$isbn";
  if (file_exists(UPLOAD_DIR) === false or is_writable(UPLOAD_DIR) === false) {
    $nocachable = 1;		          // ����å����ԲĤξ��
  }
  if ($title = plugin_isbn_cache_fetch($isbn, UPLOAD_DIR)) {
		list($title,$category,$price) = $title;
  } else {
    $nocache = 1;			  // ����å��師�Ĥ��餺
    $body = implode('', file($url));	  // �������ʤ��ΤǼ��ˤ���
    $body = mb_convert_encoding($body,SOURCE_ENCODING,"AUTO");
    $tmpary = array();
    preg_match('/Amazon.co.jp�� ([^:]*):(.*)</', $body, $tmpary);
    $category = trim($tmpary[1]);
    $title = trim($tmpary[2]);
    $body = str_replace("\r","",$body);
    $body = str_replace("\n","",$body);
    $body = strip_tags($body);
    //echo $body;
    preg_match('/���ʡ���([0-9,]+)/',$body,$tmpary);
    $price = trim($tmpary[1]);
  }
  if ($title != '') {			  // �����ȥ뤬����С��Ǥ����������å������¸
    if ($nocache == 1 and $nocachable != 1) {
      plugin_isbn_cache_save("$title<>$category<>$price", $isbn, UPLOAD_DIR);
    }
  } else {				  // �������ʤ���� ISBN:xxxxxxxx �����Υ����ȥ�
    $title = 'ISBN:' . $isbn;
  }
  $tmpary = array($title,$category,$price);
  return $tmpary;
}

// ����å��夬���뤫Ĵ�٤�
function plugin_isbn_cache_fetch($target, $dir) {
	global $vars;

  //$filename = $dir . encode($target) . ".tmp";
  $filename = $dir.encode($vars["page"])."_".encode("ISBN".$target.".dat");
  if (!is_readable($filename)) {
    return false;
  }
  if (!($fp = @fopen($filename, "r"))) return false;
  $title = fread($fp, 4096);
  fclose($fp);
  if (strlen($title) > 0) {
    return explode("<>",$title);
  }
  return false;
}

// ��������å��夬���뤫Ĵ�٤�
function plugin_isbn_cache_image_fetch($target, $dir) {
	global $vars;

	$filename = $dir.encode($vars["page"])."_".encode("ISBN".$target.".jpg");

	if (!is_readable($filename)) {
		$url = "http://images-jp.amazon.com/images/P/" . $target . ".09.MZZZZZZZ.jpg";
		if (!is_url($url)) return false; // URL ���������å�
		$size = @getimagesize($url);
		if ($size[0] <= 1) {
			$url = "http://images-jp.amazon.com/images/P/" . $target . ".01.MZZZZZZZ.jpg";
			$size = @getimagesize($url);
			if ($size[0] <= 1) $url = NOIMAGE;
		}

		if ($url != NOIMAGE){
			$file = fopen($url, "rb");
			// ��⡼�ȥե�����Υѥ��å�ͭ�����к�
			// http://search.net-newbie.com/php/function.fread.html
			$contents = "";
			do {
				$data = fread($file, 8192);
				if (strlen($data) == 0) {
					break;
				}
				$contents .= $data;
			} while(true);
			
			fclose ($file);
			
			$data = $contents;
			unset ($contents);
			$url = $filename;
		} else {
			// ����å���� NOIMAGE �Υ��ԡ��Ȥ���
			$file = fopen($url, "rb");
			if (! $file) return false;
			$data = fread($file, 100000); 
			fclose ($file);
		}
		plugin_isbn_cache_image_save($data, $target, UPLOAD_DIR);
		return $filename;
	} else
		return $filename;
}

// ����å������¸
function plugin_isbn_cache_save($data, $target, $dir) {
	global $vars;
	
  //$filename = $dir . encode($target) . ".tmp";
  $filename = $dir.encode($vars["page"])."_".encode("ISBN".$target.".dat");
  $fp = fopen($filename, "w");
  fwrite($fp, $data);
  fclose($fp);
  return $filename;
}

// ��������å������¸
function plugin_isbn_cache_image_save($data, $target, $dir) {
	global $vars;
	
	$filename = $dir . encode($vars["page"])."_".encode("ISBN".$target.".jpg");
  //$filename = $dir . "ISBN" . $target . ".jpg";

  $fp = fopen($filename, "wb");
  fwrite($fp, $data);
  fclose($fp);

  return $filename;
}


?>
