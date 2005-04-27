<?php
// $Id: admin.php,v 1.19 2005/04/27 14:28:10 nao-pon Exp $

define("_AM_WIKI_TITLE0", "PukiWiki 初期設定");
define("_AM_WIKI_INFO0", "初期設定を完了するために次の２つのリンク先にアクセスして処理を実行してください。<br />通常、初期導入時に１回のみ実行します。");
define("_AM_WIKI_DB_INIT", "データベース初期化");
define("_AM_WIKI_PAGE_INIT", "ページリンク情報初期化");

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
define("_AM_WIKI_FUNCTION_FREEZE", "凍結機能を有効にする");
define("_AM_WIKI_ADMINPASS", "凍結解除用の管理者パスワード<br>（パスワードを変更する場合のみ記入してください）");
define("_AM_WIKI_CSS", "スタイルシートのオーバーライド<br />（テーマによって見出し等が非常に見づらくなったり、<br />Wikiの色を変えたい時に有効です）");

define("_AM_WIKI_PERMIT_CHANGE", "パーミッションを変更したいファイルのあるディレクトリ<br>（この機能はモジュールの削除の時以外に使うことをお勧めしません。<br />nobodyしか書き込み出来なくなった物を0666にします。）");
define("_AM_WIKI_ANONWRITABLE", "編集を許可するユーザー(プラグインでの書き込みを除く)");
define("_AM_WIKI_HIDE_NAVI", "ページ閲覧時に上部のナビゲーションバーを隠す");
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

define("_AM_WIKI_FUNCTION_UNVISIBLE", "ページごとの閲覧制限機能を有効にする");
define("_AM_WIKI_BACKUP_TIME", "定期バックアップの間隔(時間(hour)で指定[0で更新毎])");
define("_AM_WIKI_BACKUP_AGE", "バックアップの最大世代数");
define("_AM_WIKI_PCMT_PAGE", 'pcommentプラグインでの新規作成ページ名のデフォルト (%sに設置ページ名が入る)');
define("_AM_WIKI_USER_DIR", 'フォームでの名前入力時のフォーマット<br />(%1$sに投稿時のNameが入る)<br />例: <b>[[%1$s>user/%1$s]]</b><br />ここで設定しない場合は各プラグインでの設定が適用されます。');
define("_AM_WIKI_FUNCTION_JPREADING", "ChaSen, KAKASI による、ページ名の読み取得を有効にする");
define("_AM_WIKI_KANJI2KANA_ENCODING", "ChaSen/KAKASI との受け渡しに使う漢字コード (UNIX系は EUC-JP、Win系は S-JIS が基本)");
define("_AM_WIKI_PAGEREADING_CHASEN_PATH", "ChaSen の実行ファイルパス (各自の環境に合わせて設定)");
define("_AM_WIKI_PAGEREADING_KAKASI_PATH", "KAKASI の実行ファイルパス (各自の環境に合わせて設定)");
define("_AM_WIKI_PAGEREADING_CONFIG_PAGE", "ページ名読みを格納したページの名前");
define("_AM_WIKI_SITE_NAME", "このサイトのWikiの名称");
define("_AM_WIKI_FUNCTION_TRACKBACK", "ページあたりのTrackBack Ping 最大送信数<br />( 0 でTrackBack機能はOFFになります。)");

// Ver 0.08 b5
define("_AM_WIKI_PAGE_CACHE_MIN", "HTMLへのコンバート結果をキャッシュする分数<br />ゲストユーザーのみキャッシュが有効となります。<br /> ( 0 を指定でキャッシュなし。)");
define("_AM_WIKI_USE_STATIC_URL", "WikiページのURLを[ページID].html といった静的ページURL風にする。<br />(.htaccess での設定が必須です。)");

define("_AM_WIKI_UPDATE_PING_TO", "ページ更新時、常にPing送信する送信先<br />改行または半角スペースで区切る");
define("_AM_WIKI_COMMON_DIRS", "共通リンク(仮想)ディレクトリ<br />ここで指定した(仮想)ディレクトリは省略しても正しくリンクされます。<br />最後に / (スラッシュ)が必要です。<br />改行または半角スペースで区切る");
define("_AM_SYSTEM_ADMENU","基本設定");
define("_AM_SYSTEM_ADMENU2","ブロック管理");

// Ver 1.0.6
define("_AM_WIKI_ANCHOR_VISIBLE","見出しに固定リンクアンカーを表示する");

// Ver 1.0.8
define("_AM_WIKI_TRACKBACK_ENCODING","トラックバック送信時の文字コード");

// ver 1.0.9.1
define("_AM_WIKI_COUNTUP_XOOPS","ページ作成時にXOOPSの投稿数をカウントアップする");
define("_AM_WIKI_TITLE3","全ユーザーの投稿数の再カウント");
define("_AM_WIKI_DBDENIED","アクセスが拒否されました。<br />(フォームの有効時間は10分間です)");
define("_AM_WIKI_CONFIG_SUBMIT","基本設定を更新する");
define("_AM_WIKI_PERM_SUBMIT","パーミッションを変更する");
define("_AM_WIKI_SYNC_SUBMIT","全ユーザーの投稿数を再カウントする");
define("_AM_WIKI_SYNC_MSG","<p>XOOPSの基本機能の投稿数を再カウントします。<br />PukiWikiModでのページ作成をカウントアップしてない場合は、このボタンをクリックしないでください。<br />ユーザー数に比例して処理時間が長くなります。終了するまで気長にお待ちください。</p><p>ここで再カウントされる対象は、フォーラムの投稿数、XOOPS標準のコメント件数とPukiWikiModのページ作成数です。<br />".XOOPS_ROOT_PATH."/modules/system/admin/users/users.php を独自に改造して、他のモジュールもカウント対象にしている場合は、同様に ".XOOPS_ROOT_PATH."/modules/pukiwiki/admin/index.php の29行目あたりを改造してください。</p>");

// ver 1.1.0
define("_AM_WIKI_USE_XOOPS_COMMENTS","ページコメント(XOOPSのコメント機能)を有効にする");

// ver 1.2.1
define("_AM_WIKI_ERROR02","トラックバックデータの変換が必要です、ここをクリックして、変換を行ってください。");

// ver 1.2.6
define("_AM_WIKI_FUSEN_ENABLE_ALLPAGE","付箋(wema)機能を全ページで有効にする");
define("_AM_WIKI_TB_CHECK_LINK_TO_ME","リンクなきトラックバックは受信しない機能を有効にする");
?>