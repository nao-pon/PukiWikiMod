<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: links.inc.php,v 1.2 2003/10/31 12:22:59 nao-pon Exp $
// ORG: links.inc.php,v 1.17 2003/05/19 09:22:08 arino Exp $
//

// メッセージ設定
function plugin_links_init()
{
	$messages = array(
		'_links_messages'=>array(
			'title_update'  => 'キャッシュ更新',
			'msg_adminpass' => '管理者パスワード',
			'btn_submit'    => '実行',
			'msg_done'      => 'キャッシュの更新が完了しました。',
			'msg_usage'     => "
* 処理内容

:キャッシュを更新:全てのページをスキャンし、あるページがどのページからリンクされているかを調査して、キャッシュに記録します。

* 注意
実行には数分かかる場合もあります。実行ボタンを押したあと、しばらくお待ちください。

* 実行
[実行]ボタンを ''1回のみ'' クリックしてください。~
この下に実行ボタンが表示されていない場合は、管理者権限でログインして再表示してください。
"
		)
	);
	set_plugin_messages($messages);
}

function plugin_links_action()
{
	global $script,$post,$vars,$adminpass,$foot_explain;
	global $_links_messages,$X_admin;
	
	if (empty($vars['action']) or !$X_admin)
	{
		$body = convert_html($_links_messages['msg_usage']);
	if ($X_admin)
	{
		$body .= <<<EOD
<form method="POST" action="$script">
 <div>
  <input type="hidden" name="plugin" value="links" />
  <input type="hidden" name="action" value="update" />
  <input type="submit" value="{$_links_messages['btn_submit']}" />
 </div>
</form>
EOD;
	}
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$body
		);
	}
	else if ($vars['action'] == 'update')
	{
		error_reporting(E_ALL);
		links_init();
		
		// 注釈を空にする
		$foot_explain = array();
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$_links_messages['msg_done']
		);
	}
	
	return array(
		'msg'=>$_links_messages['title_update'],
		'body'=>$_links_messages['err_invalid']
	);
}
?>
