<?php
// $Id: nocomment.conf.php,v 1.2 2006/08/14 06:10:56 nao-pon Exp $

/////////////////////////////////////////////////
//
// article プラグイン設定ファイル
// #article(config:x) で config_x.php に対応
//
/////////////////////////////////////////////////

/////////////////////////////////////////////////
// テキストエリアのカラム数
$article_cols = 70;
/////////////////////////////////////////////////
// テキストエリアの行数
$article_rows = 5;
/////////////////////////////////////////////////
// 名前テキストエリアのカラム数
$article_name_cols = 24;
/////////////////////////////////////////////////
// 題名テキストエリアのカラム数
$article_subject_cols = 60;

/////////////////////////////////////////////////
// 名前の挿入フォーマット
$name_format = '[[%2$s>%1$s]]';
/////////////////////////////////////////////////
// 題名の挿入フォーマット
$subject_format = '***$subject';
/////////////////////////////////////////////////
// 題名が未記入の場合の表記 
$no_subject = '無題';
/////////////////////////////////////////////////
// 挿入する記事フォーマット
$article_body_format = '#block(w:90%,right)
$subject
RIGHT:by $name SIZE(12){ at $now}

$text
----
#block(end)';

/////////////////////////////////////////////////
// 挿入する位置 1:欄の前 0:欄の後
$article_ins = 0;
/////////////////////////////////////////////////
// 改行を自動的変換 1:する 0:しない
$article_auto_br = 1;
?>