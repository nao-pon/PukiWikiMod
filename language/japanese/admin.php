<?php
// $Id: admin.php,v 1.8 2003/10/13 12:23:28 nao-pon Exp $

define("_AM_WIKI_TITLE1", "PukiWiki 基本設定");
define("_AM_WIKI_TITLE2", "パーミッションの変更");
define("_AM_WIKI_SUBMIT", "変更");
define("_AM_WIKI_ENABLE", "有効");
define("_AM_WIKI_DISABLE", "無効");
define("_AM_WIKI_NOLEAVE", "許可しない");
define("_AM_WIKI_LEAVE", "許可する");
define("_AM_WIKI_NONAVI", "隠す");
define("_AM_WIKI_NAVI", "隠さない");

define("_AM_DBUPDATED", "ファイルへの書き込みが完了しました。");

define("_AM_WIKI_ERROR01", "書き込み権限がありません。");

define("_AM_WIKI_DEFAULTPAGE", "デフォルトページ");
define("_AM_WIKI_MODIFIER", "編集者の名前");
define("_AM_WIKI_MODIFIERLINK", "編集者のホームページ");
define("_AM_WIKI_FUNCTION_FREEZE", "凍結機能を有効にしますか");
define("_AM_WIKI_ADMINPASS", "凍結解除用の管理者パスワード<br>（パスワードを変更する場合のみ記入してください）");
define("_AM_WIKI_CSS", "スタイルシートのオーバーライド<br />（テーマによって見出し等が非常に見づらくなったり、<br />Wikiの色を変えたい時に有効です）");

define("_AM_WIKI_PERMIT_CHANGE", "パーミッションを変更したいファイルのあるディレクトリ<br>（この機能はモジュールの削除の時以外に使うことをお勧めしません。<br />nobodyしか書き込み出来なくなった物を0666にします。）");
define("_AM_WIKI_ANONWRITABLE", "編集を許可するユーザー(プラグインでの書き込みを除く)");
define("_AM_WIKI_HIDE_NAVI", "ページをフリーズした時に上部のナビゲーションバーを隠す(Webmasterのみ表示されます)");
define("_AM_WIKI_MAIL_SW", "記事投稿時の管理者へのメール通知は？");
define("_AM_WIKI_ALL", "すべての訪問者");
define("_AM_WIKI_REGIST", "登録ユーザーのみ");
define("_AM_WIKI_ADMIN", "管理者のみ");
define("_AM_WIKI_MAIL_ALL", "すべて通知");
define("_AM_WIKI_MAIL_NOADMIN", "管理者投稿以外を通知");
define("_AM_WIKI_MAIL_NONE", "通知しない");
define("_AM_ALLOW_EDIT_VALDEF", "ページ新規作成時のページごとの編集権限規定値");
define("_AM_WIKI_WRITABLE", "上記設定の<b>編集を許可するユーザー</b>");
define("_AM_WIKI_ANONWRITABLE_MSG", "<dl><dt>(説明) 編集を許可するユーザー</dt><dd>不許可において下の「<b>ページごとの編集権限</b>」より優先します。<br />例えば・・・<br />「<b>管理者のみ</b>」を選択した場合、「<b>ページごとの編集権限</b>」に関わらず、すべてのページが管理者のみしか編集できません。<br />「<b>すべての訪問者</b>」を選択すると、「<b>ページごとの編集権限</b>」で各ページの編集権限をコントロールできるようになります。</dd></dl>");
define("_AM_WIKI_ALLOW_NEW", "ページの新規作成を許可するユーザー");

define("_AM_WIKI_FUNCTION_UNVISIBLE", "ページごとの閲覧制限を有効にしますか");
define("_AM_WIKI_BACKUP_TIME", "定期バックアップの間隔(時間(hour)で指定[0で更新毎])");
define("_AM_WIKI_BACKUP_AGE", "バックアップの最大世代数");
define("_AM_WIKI_PCMT_PAGE", 'pcommentプラグインでの新規作成ページ名のデフォルト (%sに設置ページ名が入る)');
define("_AM_WIKI_USER_DIR", 'フォームでの名前入力時のフォーマット<br />(%1$sに投稿時のNameが入る)<br />例: <b>[[%1$s>user/%1$s]]</b><br />ここで設定しない場合は各プラグインでの設定が適用されます。');
define("_AM_WIKI_FUNCTION_JPREADING", "ChaSen, KAKASI による、ページ名の読み取得を有効にしますか");
define("_AM_WIKI_KANJI2KANA_ENCODING", "ChaSen/KAKASI との受け渡しに使う漢字コード (UNIX系は EUC-JP、Win系は S-JIS が基本)");
define("_AM_WIKI_PAGEREADING_CHASEN_PATH", "ChaSen の実行ファイルパス (各自の環境に合わせて設定)");
define("_AM_WIKI_PAGEREADING_KAKASI_PATH", "KAKASI の実行ファイルパス (各自の環境に合わせて設定)");
define("_AM_WIKI_PAGEREADING_CONFIG_PAGE", "ページ名読みを格納したページの名前");
?>
