<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
//  $Id: config.def.php,v 1.1 2005/03/09 12:14:51 nao-pon Exp $
//

/*
 プラグイン attach 設定ファイル
*/

// 管理者だけが添付ファイルをアップロードできるようにする
define('ATTACH_UPLOAD_ADMIN_ONLY',FALSE); // FALSE or TRUE

// ページ編集権限がある人のみ添付ファイルをアップロードできるようにする
define('ATTACH_UPLOAD_EDITER_ONLY',FALSE); // FALSE or TRUE

// ページ編集権限がない場合にアップロードできる拡張子(カンマ区切り)
// ATTACH_UPLOAD_EDITER_ONLY = FALSE のときに使用
define('ATTACH_UPLOAD_EXTENSION','jpg, jpeg, gif, png, txt, spch, zip, lzh, tar, taz, tgz, gz, z');

// 管理者とページ作成者だけが添付ファイルを削除できるようにする
define('ATTACH_DELETE_ADMIN_ONLY',FALSE); // FALSE or TRUE

// 管理者とページ作成者が添付ファイルを削除するときは、バックアップを作らない
define('ATTACH_DELETE_ADMIN_NOBACKUP',TRUE); // FALSE or TRUE 

// ゲストユーザーのアップロード/削除時にパスワードを要求する
// (ADMIN_ONLYが優先 TRUE を強く奨励)
define('ATTACH_PASSWORD_REQUIRE',TRUE); // FALSE or TRUE

// ファイルのアクセス権 
define('ATTACH_FILE_MODE',0644); 
//define('ATTACH_FILE_MODE',0604); // for XREA.COM 

// open, delete, upload 時にリファラをチェックする
// 0:チェックしない, 1:未定義は許可, 2:未定義も不許可
define('ATTACH_REFCHECK',1);

// file icon image
if (!defined('FILE_ICON'))
{
	define('FILE_ICON','<img src="./image/file.png" width="20" height="20" alt="file" style="border-width:0px" />');
}

// mime-typeを記述したページ
define('ATTACH_CONFIG_PAGE_MIME','plugin/attach/mime-type');

// 詳細情報・ファイル一覧(イメージモード)で使用する ref プラグインの追加オプション
define('ATTACH_CONFIG_REF_OPTION',',mw:160,mh:120');

// tar
define('TAR_HDR_LEN',512);			// ヘッダの大きさ
define('TAR_BLK_LEN',512);			// 単位ブロック長さ
define('TAR_HDR_NAME_OFFSET',0);	// ファイル名のオフセット
define('TAR_HDR_NAME_LEN',100);		// ファイル名の最大長さ
define('TAR_HDR_SIZE_OFFSET',124);	// サイズへのオフセット
define('TAR_HDR_SIZE_LEN',12);		// サイズの長さ
define('TAR_HDR_TYPE_OFFSET',156);	// ファイルタイプへのオフセット
define('TAR_HDR_TYPE_LEN',1);		// ファイルタイプの長さ

?>
