<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: isbn.inc.php,v 1.4 2003/07/04 01:55:53 nao-pon Exp $
//
// *0.5: URL が存在しない場合、画像を表示しない。
//       Thanks to reimy.
//	 GNU/GPL にしたがって配布する。
//
define('NOIMAGE','./image/noimage.png'); // ここ、あるいは pukiwiki.ini.php に持っていく。
define('SOURCE_ENCODING','EUC'); // ここ、あるいは pukiwiki.ini.php に持っていく。
define('AMAZON_ASE_ID','hypweb-22'); // AmazonアソシエイトID


function plugin_isbn_convert() {
  if (func_num_args() < 1 or func_num_args() > 3) {
    return false;
  }
  $aryargs = func_get_args();
  $isbn = htmlspecialchars($aryargs[0]);  // for XSS
  $isbn = str_replace("-","",$isbn);

  $header = strtolower(htmlspecialchars($aryargs[2]));
  if ($header == 'header' || $header == 'h') $aryargs[2]="";
  if ($aryargs[2] != '') {		  // タイトル指定か自動取得か
    $alt = htmlspecialchars($aryargs[2]); // for XSS
    $title = $alt;
    if ($alt == 'image') {
      $alt = plugin_isbn_get_isbn_title($isbn);
      $title = "";
    }
  } else {				  // タイトル自動取得
    $tmpary = array();
    $tmpary = plugin_isbn_get_isbn_title($isbn);
    if ($tmpary[2]) $price = "<div style=\"text-align:right;\">$tmpary[2]円</div>";
    $title = "[ $tmpary[1] ]<br />$tmpary[0]$price";
    $h_title = "$tmpary[0]";
    if ($tmpary[2]) $price2 = "&#13;&#10;参考価格：$tmpary[2]円";
    $alt = "[ $tmpary[1] ]&#13;&#10;$tmpary[0]$price2";
  }
  $align = strtolower($aryargs[1]);
  if ($align == "header" || $align == "h") {
		$header = $align;
		$align= "";
	}
  if ($align != 'left' and $align != 'clear') { // 配置決定
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
	if ($tmpary[2]) $price = "<div style=\"text-align:right;\">$tmpary[2]円</div>";
	$title = "$tmpary[0]";
	if ($tmpary[2]) $price2 = "&#13;&#10;参考価格：$tmpary[2]円";
	$alt = "[ $tmpary[1] ]&#13;&#10;$tmpary[0]$price2";  
  if ($option != 'img'){
	  return '<a href="http://www.amazon.co.jp/exec/obidos/ASIN/'.$isbn.'/ref=ase_'.AMAZON_ASE_ID.'" target="_blank" title="'.$alt.'">' . $title . '</a>';
	} else {
		$url = plugin_isbn_cache_image_fetch($isbn, CACHE_DIR);
		return '<a href="http://www.amazon.co.jp/exec/obidos/ASIN/'.$isbn.'/ref=ase_'.AMAZON_ASE_ID.'" target="_blank"><img src="'.$url.'" alt="'.$alt.'" /></a>';
	}
}

function plugin_isbn_print_isbn_img($isbn, $align, $alt, $title, $h_title, $price, $header="") {
	$AMAZON_ASE_ID = AMAZON_ASE_ID;
  if ($align == 'clear') {		  // 改行挿入
    return '<div style="clear:both"></div>';
  }

  if (! ($url = plugin_isbn_cache_image_fetch($isbn, CACHE_DIR))) return false;

  if ($title == '') {			  // タイトルがなければ、画像のみ表示
    return <<<EOD
<div style="float:$align;padding:.5em 1.5em .5em 1.5em">
 <a href="http://www.amazon.co.jp/exec/obidos/ASIN/{$isbn}/ref=ase_{$AMAZON_ASE_ID}" target="_blank"><img src="$url" alt="$alt" /></a>
</div>
EOD;
  } else {				  // 通常表示
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
  if (file_exists(CACHE_DIR) === false or is_writable(CACHE_DIR) === false) {
    $nocachable = 1;		          // キャッシュ不可の場合
  }
  if ($title = plugin_isbn_cache_fetch($isbn, CACHE_DIR)) {
		list($title,$category,$price) = $title;
  } else {
    $nocache = 1;			  // キャッシュ見つからず
    $body = implode('', file($url));	  // しかたないので取りにいく
    $body = mb_convert_encoding($body,SOURCE_ENCODING,"AUTO");
    $tmpary = array();
    preg_match('/Amazon.co.jp： ([^:]*):(.*)</', $body, $tmpary);
    $category = trim($tmpary[1]);
    $title = trim($tmpary[2]);
    $body = str_replace("\r","",$body);
    $body = str_replace("\n","",$body);
    $body = strip_tags($body);
    preg_match('/価格：￥([0-9,]+)/',$body,$tmpary);
    $price = trim($tmpary[2]);
  }
  if ($title != '') {			  // タイトルがあれば、できるだけキャッシュに保存
    if ($nocache == 1 and $nocachable != 1) {
      plugin_isbn_cache_save("$title<>$category<>$price", $isbn, CACHE_DIR);
    }
  } else {				  // しかたない場合 ISBN:xxxxxxxx 形式のタイトル
    $title = 'ISBN:' . $isbn;
  }
  $tmpary = array($title,$category,$price);
  return $tmpary;
}

// キャッシュがあるか調べる
function plugin_isbn_cache_fetch($target, $dir) {
  $filename = $dir . encode($target) . ".tmp";
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

// 画像キャッシュがあるか調べる
function plugin_isbn_cache_image_fetch($target, $dir) {
  $filename = $dir . "ISBN" . $target . ".jpg";

  if (!is_readable($filename)) {
    $url = "http://images-jp.amazon.com/images/P/" . $target . ".09.MZZZZZZZ.jpg";
    if (!is_url($url)) return false; // URL 形式チェック
    $file = fopen($url, "rb"); // たぶん size 取得よりこちらが原始的だからやや速い
    if (! $file) {
      fclose($file);
      $url = NOIMAGE;
    } else {
      $data = fread($file, 100000); 
      fclose ($file);
      $size = @getimagesize($url); // あったら、size を取得、通常は1が返るが念のため0の場合も(reimy)
      if ($size[0] <= 1)
        $url = NOIMAGE;
      else
        $url = $filename;
    }
    // キャッシュを NOIMAGE のコピーとする
    if ($url == NOIMAGE) {
      $file = fopen($url, "rb");
      if (! $file) return false;
      $data = fread($file, 100000); 
      fclose ($file);
    }
    plugin_isbn_cache_image_save($data, $target, CACHE_DIR);
    return $filename;
  } else
    return $filename;
}

// キャッシュを保存
function plugin_isbn_cache_save($data, $target, $dir) {
  $filename = $dir . encode($target) . ".tmp";
  $fp = fopen($filename, "w");
  fwrite($fp, $data);
  fclose($fp);
  return $filename;
}

// 画像キャッシュを保存
function plugin_isbn_cache_image_save($data, $target, $dir) {
  $filename = $dir . "ISBN" . $target . ".jpg";

  $fp = fopen($filename, "wb");
  fwrite($fp, $data);
  fclose($fp);

  return $filename;
}


?>
