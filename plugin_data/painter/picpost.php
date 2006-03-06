<?php
//----------------------------------------------------------------------
// picpost.php lot.040622  by SakaQ >> http://www.punyu.net/
//
// しぃからPOSTされたお絵かき画像をTEMPに保存
//
// このスクリプトはPaintBBS（藍珠CGI）のPNG保存ルーチンを参考に
// PHP用に作成したものです。
//----------------------------------------------------------------------
// 2004/06/22 ユーザーを識別するusercodeを投稿者情報に追加
// 2003/12/22 JPEG対応
// 2003/10/03 しぃペインターに対応
// 2003/09/10 IPアドレス取得方法変更
// 2003/09/09 PCHファイルに対応.投稿者情報の記録機能追加
// 2003/09/01 PHP風(?)に整理
// 2003/08/28 perl -> php 移植  by TakeponG >> http://www.chomkoubou.com/
// 2003/07/11 perl版初公開

//設定
//include("config.php");
//テンポラリディレクトリ
define(TEMP_DIR, './tmp/');

//テンポラリ内のファイル有効期限(日数)
define(TEMP_LIMIT, '14');

$syslog = TEMP_DIR."error.log";
$syslogmax = 100;

$time = time();
$imgfile = $time.substr(microtime(),2,3);	//画像ファイル名

/* エラー発生時にSystemLOGにエラーを記録 */
function error($error){
  global $imgfile,$syslog,$syslogmax;

  //時間
  $time = time();
  $youbi = array('日','月','火','水','木','金','土');
  $yd = $youbi[gmdate("w", $time+9*60*60)] ;
  $now = gmdate("y/m/d",$time+9*60*60)."(".(string)$yd.")".gmdate("H:i",$time+9*60*60);

  //ファイルを配列に読み込む
  if(@is_file($syslog)) $lines = file($syslog);

  //書き込みモードでオープン
  $ep = @fopen($syslog , "w") or die($syslog."が開けません");

  //ファイルロック
  flock($ep, LOCK_EX);

  //先頭に書き込む(ファイル名、エラーメッセージ、時間)
  fputs ($ep, $imgfile."  ".$error." [".$now."]\n");

  //いままでの分を追記
  for($i = 0; $i < $syslogmax; $i++)
    fputs($ep, $lines[$i]);

  //ファイルクローズ
  fclose ($ep);
}


/* ■■■■■ メイン処理 ■■■■■ */

//raw POST データ取得
ini_set("always_populate_raw_post_data", "1");
$buffer = $HTTP_RAW_POST_DATA;
if($buffer == "") {
  $stdin = fopen("php://input", "rb");
  $buffer = fread($stdin, $_ENV['CONTENT_LENGTH']);
  fclose ($stdin);
}

if($buffer == "") {
  error("raw POST データの取得に失敗しました。お絵かき画像は保存されません。");
  exit;
}

// 拡張ヘッダー長さを獲得
$headerLength = substr( $buffer , 1 , 8 );
// 画像ファイルの長さを取り出す
$imgLength = substr( $buffer , 1 + 8 + $headerLength , 8 );
// 画像イメージを取り出す
$imgdata = substr($buffer, 1 + 8 + $headerLength + 8 + 2 , $imgLength );
// 画像ヘッダーを獲得
$imgh = substr( $imgdata , 1 , 5 );
// 拡張子設定
if($imgh=="PNG\r\n"){
  $imgext = '.png';	// PNG
}else{
  $imgext = '.jpg';	// JPEG
}

// 同名のファイルが存在しないかチェック
if( file_exists( TEMP_DIR.$imgfile.$imgext ) ){	// ファイルが存在する場合
  error("同名の画像ファイルが存在します。上書きします。");
}

// 画像データをファイルに書き込む
$fp = fopen( TEMP_DIR.$imgfile.$imgext,"wb");

if( !$fp ){	// ファイルオープンエラー
  error("画像ファイルのオープンに失敗しました。お絵かき画像は保存されません。");
  exit;

}else{	// ファイルオープンに成功
  flock($fp, LOCK_EX);	//ファイルロック
  fwrite($fp, $imgdata);
  fclose ($fp);
}

// PCHファイルの長さを取り出す
$pchLength = substr( $buffer , 1 + 8 + $headerLength + 8 + 2 + $imgLength , 8 );

if($pchLength!=0){
  // PCHイメージを取り出す
  $PCHdata = substr($buffer, 1 + 8 + $headerLength + 8 + 2 + $imgLength + 8 , $pchLength );

  // ヘッダーを獲得
  $h = substr( $buffer , 0 , 1 );

  // 拡張子設定
  if($h=='S'){
    $ext = '.spch';	// しぃペインター
  }else{
    $ext = '.pch';	// PaintBBS
  }

  // 同名のファイルが存在しないかチェック
  if( file_exists( TEMP_DIR.$imgfile.$ext ) ){	// ファイルが存在する場合
    error("同名のPCHファイルが存在します。上書きします。");
  }

  // PCHデータをファイルに書き込む
  $fp = fopen( TEMP_DIR.$imgfile.$ext,"wb");

  if( !$fp ){	// ファイルオープンエラー
    error("PCHファイルのオープンに失敗しました。PCHは保存されません。");
    exit;

  }else{	// ファイルオープンに成功
    flock($fp, LOCK_EX);	//ファイルロック
    fwrite($fp, $PCHdata);
    fclose ($fp);
  }
}

/* ---------- 投稿者情報記録 ---------- */
$u_ip = getenv("HTTP_CLIENT_IP");
if(!$u_ip) $u_ip = getenv("HTTP_X_FORWARDED_FOR");
if(!$u_ip) $u_ip = getenv("REMOTE_ADDR");
$u_host = gethostbyaddr($u_ip);
$u_agent = getenv("HTTP_USER_AGENT");
$u_agent = str_replace("\t", "", $u_agent);

$userdata = "$u_ip\t$u_host\t$u_agent\t$imgext";

// 拡張ヘッダーを取り出す
$sendheader = substr( $buffer , 1 + 8 , $headerLength );
if($sendheader){
  $query_str = explode("&", $sendheader);
  foreach($query_str as $query_s){
    list($name,$value) = explode("=", $query_s);
    if($name == 'usercode') $userdata .= "\t$value"; // usercodeをセット
    elseif($name == 'refer') $userdata .= "\t$value"; // ページ名をセット
	elseif($name == 'uid') $userdata .= "\t$value"; // UserIDをセット
  }
}
$userdata .= "\n";

if( file_exists( TEMP_DIR.$imgfile.".dat" ) ){	// ファイルが存在する場合
  error("同名の情報ファイルが存在します。上書きします。");
}

// 情報データをファイルに書き込む
$fp = fopen( TEMP_DIR.$imgfile.".dat","w");
if( !$fp ){	// ファイルオープンエラー
  error("情報ファイルのオープンに失敗しました。投稿者情報は記録されません。");
  exit;

}else{	// ファイルオープンに成功
  flock($fp, LOCK_EX);	//ファイルロック
  fwrite($fp, $userdata);
  fclose ($fp);
}

die("ok");

?>