<?php
// $Id: pcomment.inc.php,v 1.27 2005/11/08 08:27:20 nao-pon Exp $
/*
Last-Update:2002-09-12 rev.15

*プラグイン pcomment
指定したページにコメントを挿入

*Usage
 #pcomment([ページ名][,表示するコメント数][,オプション])

*パラメータ
-ページ名~
 投稿されたコメントを記録するページの名前
-表示するコメント数~
 過去のコメントを何件表示するか(0で全件)

*オプション
-above OR down~
 コメントをフィールドの前に表示(新しい記事が下)
-below OR up~
 コメントをフィールドの後に表示(新しい記事が上)
-reply~
 2レベルまでのコメントにリプライをつけるradioボタンを表示
-btn:[String]~
 ボタンに表示するテキスト
-size:[Integer]~
 コメントテキストボックスのサイズ
-cname:[String]~
 コメントページの接頭子名
*/
// 表示するコメント数のデフォルト
define('PCMT_NUM_COMMENTS',10);
//
// コメントの名前テキストエリアのカラム数
define('PCMT_COLS_NAME',15);
//
// コメントのテキストエリアのカラム数
define('PCMT_COLS_COMMENT',70);
//
// 挿入する位置 1:末尾 0:先頭
define('PCMT_INSERT_INS',1);
//
//コメントの挿入フォーマット
define('PCMT_FORMAT_NAME','[[%1$s>%2$s]]');
define('PCMT_FORMAT_MSG','%s');
define('PCMT_FORMAT_DATE','&new{%s};');
// \x08は、投稿された文字列中に現れない文字であればなんでもいい。
define("PCMT_FORMAT","\x08MSG\x08 -- \x08NAME\x08 \x08DATE\x08");
//
// 自動過去ログ化 1ページあたりの件数を指定 0で無効 
define('PCMT_AUTO_LOG',30); 

/////////////////////////////////////////////////
// areaedit指定を規定値にする(Yes:1, No:0)
define('PCMT_AREAEDIT_ENABLE',1);
// areaedit指定の追加オプション
define('PCMT_AREAEDIT_OPTION',",preview:5");

function plugin_pcomment_init() {
	
	if (LANG == "ja") {
		$_plugin_pcmt_messages = array(
			'_pcmt_btn_name' => 'お名前: ',
			'_pcmt_btn_comment' => 'コメントの挿入',
			'_pcmt_msg_comment' => 'コメント: ',
			'_pcmt_msg_recent' => '最新の%d件を表示しています。',
			'_pcmt_msg_all' => 'コメントページを参照',
			'_pcmt_msg_edit' => '編集',
			'_pcmt_msg_none' => 'コメントはありません。',
			'_pcmt_msg_log_title' => '過去ログ',
			'_pcmt_msg_reply_this' => 'このメッセージに返信',
			'_title_pcmt_collided' => '$1 で【更新の衝突】が起きました',
			'_msg_pcmt_collided' => 'あなたがこのページを編集している間に、他の人が同じページを更新してしまったようです。<br />
			コメントを追加しましたが、違う位置に挿入されているかもしれません。<br />',
		);
	} else {
		$_plugin_pcmt_messages = array(
			'_pcmt_btn_name' => 'Name:',
			'_pcmt_btn_comment' => 'Post comment',
			'_pcmt_msg_comment' => 'Comment: ',
			'_pcmt_msg_recent' => 'The newest %d comments',
			'_pcmt_msg_all' => 'See comment page',
			'_pcmt_msg_edit' => 'Edit',
			'_pcmt_msg_none' => 'No comments yet',
			'_pcmt_msg_log_title' => 'Old Log',
			'_title_pcmt_collided' => 'Conflicts found in $1',
			'_pcmt_msg_reply_this' => 'Reply to this comment',
			'_msg_pcmt_collided' => 'Other user has updated the page you are editing.<br />
			Your comment was added anyway but may be at wrong line.<br />',
		);
	}
  set_plugin_messages($_plugin_pcmt_messages);
}
function plugin_pcomment_action() {
	global $post;

	$retval = '';
	if($post['msg']) { $retval = pcmt_insert(); }
	return $retval;
}

function plugin_pcomment_convert() {
	global $script,$vars,$BracketName,$WikiName,$digest;
	global $_pcmt_btn_name, $_pcmt_btn_comment, $_pcmt_msg_comment, $_pcmt_msg_all, $_pcmt_msg_edit, $_pcmt_msg_recent;
	
	$style = "";
	
	//戻り値
	$ret = '';

	//パラメータ変換
	$args = func_get_args();
	
	//array_walk($args, 'pcmt_check_arg', $params);
	//なぜか $args のメンバー数が多い時 array_walk ではPHPが落ちることがある
	foreach($args as $key=>$val)
	{
		pcmt_check_arg($val, $key, $params);
	}

	$all_option = (is_array($args))? implode(" ",$args) : $args;
	//ボタンテキスト指定オプション
	$btn_text = $_pcmt_btn_comment;
	if (preg_match("/(?: |^)btn:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$btn_text = htmlspecialchars($arg[1]);
	}
	//コメントテキストボックスサイズ指定オプション
	$comment_size = PCMT_COLS_COMMENT;
	if (preg_match("/(?: |^)size:([0-9]+)(?: |$)/i",trim($all_option),$arg))
	{
		if (PCMT_COLS_COMMENT > $arg[1] && ($arg[1])) $comment_size = $arg[1];
		$w_style = "width:auto;";
	}
	else
	{
		$style = " style=\"width:98%;\"";
		$w_style = "width:100%;";
	}
	//コメントページ名指定オプション
	$comment_pg_name = PCMT_PAGE;
	if (preg_match("/(?: |^)cname:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$comment_pg_name = "[[".trim($arg[1])."]]";
		if (preg_match("/^(($BracketName)|($WikiName))$/",$comment_pg_name)){
			$comment_pg_name = "[[".htmlspecialchars(trim($arg[1]))."/%s]]";
		}
	}
	//areaedit指定
	$areaedit = "";
	if (preg_match("/(?: |^)areaedit(?: |$)/i",trim($all_option),$arg)){
		$areaedit = "<input type=\"hidden\" name=\"areaedit\" value=\"1\" />\n";
	}

	unset($args);

	//文字列を取得
	list($page, $count) = $params['arg'];
	//if ($page == '') { $page = sprintf(PCMT_PAGE,strip_bracket($vars['page'])); }
	if ($page == '') { $page = sprintf($comment_pg_name,strip_bracket($vars['page'])); }

	$_page = get_fullname($page,$vars['page']);
	if (!preg_match("/^$BracketName$/",$_page))
		return 'invalid page name: '.htmlspecialchars($_page);
	
	// 閲覧権限
	if (!check_readable($_page,false,false))
		return str_replace('$1',strip_bracket($_page),_MD_PUKIWIKI_NO_VISIBLE);;

	if ($count == 0 and $count !== '0') { $count = PCMT_NUM_COMMENTS; }

	//向きを決定
	$dir = PCMT_INSERT_INS;
	if ($params['above'] || $params['up']) { $dir = 1; }
	if ($params['below'] || $params['down']) { $dir = 0; } //両方指定されたら下に (^^;
	
	//タイムスタンプ更新？
	$notimestamp = (!empty($params['notimestamp']))? '<input type="hidden" name="notimestamp" value="1" />' : '';
	
	//コメントを取得
	$data = @file(get_filename(encode($_page)));
	
	//ページ名などの入れ替え
	$now_page = $vars['page'];
	$now_digest = $digest;
	if (is_page($_page)) $vars['page'] = $_page;
	list($comments, $digest) = pcmt_get_comments($data,$count,$dir,$params['reply']);

	//フォームを表示
	if($params['noname']) {
		$title = $_pcmt_msg_comment;
		$name = '<input type="hidden" name="noname" value="1" />';
	} else {
		$title = $_pcmt_btn_name;
		$name = '<input type="text" name="name" size="'.PCMT_COLS_NAME.'" value="'.WIKI_NAME_DEF.'" />';
	}

	$radio = $params['reply'] ? '<input type="radio" name="reply" value="0" checked />' : '';
	$comment = '<input type="text" name="msg" size="'.$comment_size.'"'.$style.' />';

	//XSS脆弱性問題 - 外部から来た変数をエスケープ
	$f_page = htmlspecialchars($page);
	$f_refer = htmlspecialchars($now_page);
	$f_nodate = htmlspecialchars($params['nodate']);
	$s_count = htmlspecialchars($count);
	
	$fontset_js_tag = fontset_js_tag();
	$form = <<<EOD
  <div>
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="plugin" value="pcomment" />
  <input type="hidden" name="refer" value="$f_refer" />
  <input type="hidden" name="page" value="$f_page" />
  <input type="hidden" name="nodate" value="$f_nodate" />
  <input type="hidden" name="dir" value="$dir" />
  <input type="hidden" name="count" value="$s_count" />
  $notimestamp
  $areaedit
  <table style=\"{$w_style}\"><tr>
  <td style="vertical-align:bottom;white-space:nowrap;">{$radio}{$title} {$name}</td>
  <td style="vertical-align:bottom;{$w_style}">$fontset_js_tag<br />$comment</td>
  <td style="vertical-align:bottom;"><input type="submit" value="$btn_text" /></td>
  </tr></table>
  </div>
EOD;
	$link = $_page;
	if (!is_page($_page)) {
		$recent = $_pcmt_msg_none;
		$link = make_pagelink($link);
	} else {
		if ($_pcmt_msg_all != '')
			$link = make_pagelink($link,$_pcmt_msg_all);
		$recent = '';
		if ($count > 0) { $recent = sprintf($_pcmt_msg_recent,$count); }
		$edit_tag =  (is_freeze($_page,false))? "" : " | <a href=\"$script?cmd=edit&amp;page=".rawurlencode($_page)."\">$_pcmt_msg_edit</a>";
	}
	//$link = make_pagelink($link);
	
	//退避した変数を戻す
	$vars['page'] = $now_page;
	$digest = $now_digest;

	return $dir ?
		"<div><p>$recent $link$edit_tag</p>\n<form action=\"$script\" method=\"post\">$comments$form</form></div>" :
		"<div><form action=\"$script\" method=\"post\">$form$comments</form>\n<p>$recent $link</p></div>";
}

function pcmt_insert($page) {
	global $post,$vars,$script,$now,$do_backup,$BracketName;
	global $_title_updated,$no_name,$X_uid,$X_uname;

	$page = $post['page'];
	if (!preg_match("/^$BracketName$/",$page))
		return array('msg'=>'invalid page name.','body'=>'cannot add comment.','collided'=>TRUE);

	$ret['msg'] = $_title_updated;

	//表中でも使用できるように|をエスケープ nao-pon
	$msg = str_replace('|','&#124;',$msg);
	
	//コメントフォーマットを適用
	$msg = sprintf(PCMT_FORMAT_MSG, rtrim($post['msg']));
	
	$name = "";
	if (!empty($post['name']))
	{
		// 名前をクッキーに保存
		setcookie("pukiwiki_un", $post['name'], time()+86400*365);//1年間
		
		$name = ($post['name'] == '') ? $no_name : $post['name'];
		
		// 名前をフォーマット
		make_user_link($name,PCMT_FORMAT_NAME);
	}
	
	$msg = rtrim($msg);
	//areaedit指定
	if (PCMT_AREAEDIT_ENABLE || !empty($post['areaedit']))
	{
		if ($X_uid)
			{$msg = "&areaedit(uid:".$X_uid.PCMT_AREAEDIT_OPTION."){".$msg."};";}
		else
			{$msg = "&areaedit(ucd:".PUKIWIKI_UCD.PCMT_AREAEDIT_OPTION."){".$msg."};";}
	}
	
	$date = ($post['nodate'] == '1') ? '' : sprintf(PCMT_FORMAT_DATE, $now);
	if ($date != '' or $name != '') { 
		$msg = str_replace("\x08MSG\x08", $msg,  PCMT_FORMAT);
		$msg = str_replace("\x08NAME\x08",$name, $msg);
		$msg = str_replace("\x08DATE\x08",$date, $msg);
		//$msg = str_replace("\x08NEW\x08",$mnew, $msg);
	}
	if ($post['reply'] or !is_page($page)) {
		$msg = preg_replace('/^\-+/','',$msg);
	}
	
	if (!is_page($page))
	{
		//$new = PCMT_CATEGORY.' '.htmlspecialchars($post['refer'])."\n\n-$msg\n";
		$_page = htmlspecialchars(strip_bracket($post['refer']));
		$_page = "[[$_page]]";
		if (page_exists("[[:config/plugin/pcomment/root]]"))
		{
			$new = plugin_pcomment_get_source("[[:config/plugin/pcomment/root]]");
			$new = str_replace(array("_REFER_PAGE_","_MESSAGE_","_PCMT_CATEGORY_"),array($_page,$msg,PCMT_CATEGORY),$new);
		}
		else
		{
			$new = '***'.$_page."のコメント一覧\n".PCMT_CATEGORY."\n\n-$msg\n";
		}
	} else {
		//ページを読み出す
		$postdata = get_source($page);

		$reply = $post['reply'];

		// 更新の衝突を検出
		if (md5(join('',$postdata)) != $post['digest']) {
			$ret['msg'] = $_title_paint_collided;
			$ret['body'] = $_msg_paint_collided;
			$reply = 0; //リプライでなくする
		}

		// ページ末尾の調整
		if (substr($postdata[count($postdata) - 1],-1,1) != "\n") { $postdata[] = "\n"; }

		//基準点を決定
		$level = 1;
		if ($post['dir'] == '1') {
			$pos = count($postdata) - 1;
			$step = -1;
		} else {
			$pos = -1;
			foreach ($postdata as $line) {
				if (preg_match('/^\-/',$line)) break;
				$pos++;
			}
			$step = 1;
		}
		//リプライ先のコメントを検索
		if ($reply > 0) {
			while ($pos >= 0 and $pos < count($postdata)) {
				if (preg_match('/^(\-{1,2})(?!\-)/',$postdata[$pos], $matches) and --$reply == 0) {
					$level = strlen($matches[1]) + 1; //挿入するレベル
					break;
				}
				$pos += $step;
			}
			while (++$pos < count($postdata)) {
				if (preg_match('/^(\-{1,2})(?!\-)/',$postdata[$pos], $matches)) {
					if (strlen($matches[1]) < $level) { break; }
				}
			}
		} else {
			$pos++;
		}
		//行頭文字
		$head = str_repeat('-',$level);
		//コメントを挿入
		array_splice($postdata,$pos,0,"$head$msg\n");
		
		// 過去ログ処理 
		pcmt_auto_log($page,$post['dir'],$post['count'],$postdata); 

		$new = join('',$postdata);
	}

	//親ページのファイルタイム更新
	if (empty($post['notimestamp']))
	{
		touch(DATA_DIR.encode($post['refer']).".txt");
		//親ページのDB更新
		pginfo_db_write($post['refer'],"update");
		//Pingを送信するページ
		$vars['ping_send_page'] = $post['refer'];
	}
	
	if (!is_page($page))
	{
		//ページ新規作成
		$aids = $gids = $freeze = "";
		//編集権限継承がセットされている上位ページ凍結情報を得る
		$up_freezed = get_pg_allow_editer($post['refer']);
		$page_info = "";
		//ページ情報のセット
		if ($up_freezed['uid'] !== "")
		{
			//編集権限継承あり
			$freeze = 1;
			$uid = $up_freezed['uid'];
			$aids = preg_replace("/(($|,)$uid,|,$)/","",$up_freezed['user']);
			$gids = preg_replace("/,$/","",$up_freezed['group']);
			$page_info = "#freeze\tuid:{$uid}\taid:{$aids}\tgid:{$gids}\n// author:{$uid}\n";
		}
		else
		{
			$page_info = "// author:".get_pg_auther($post['refer'])."\n";
		}
		$new = $page_info.$new;
		// ページ作成
		page_write($page, $new, NULL,$aids,$gids,"","",$freeze,"",array('plugin'=>'pcomment','mode'=>'add'));
	}
	else
	{
		// コメントファイルの書き込み 第4引数:最終更新しない=true
		page_write($page, $new, true,"","","","","","",array('plugin'=>'pcomment','mode'=>'add'));
	}

	$vars['page'] = $post['page'] = $post['refer'];

	return $ret;
}

// 過去ログ処理
function pcmt_auto_log($page, $dir, $count, &$postdata)
{
	if (!PCMT_AUTO_LOG)
		return;
	
	global $post,$_pcmt_msg_log_title;
	
	$page = strip_bracket($page);
	
	$keys = array_keys(preg_grep('/(?:^-(?!-).*$)/m', $postdata));
	if (count($keys) < (PCMT_AUTO_LOG + $count))
		return;

	if ($dir) { //前からPCMT_AUTO_LOG件
		$old = array_splice($postdata, $keys[0], $keys[PCMT_AUTO_LOG] - $keys[0]);
	} else { //後ろからPCMT_AUTO_LOG件
		$old = array_splice($postdata, $keys[count($keys) - PCMT_AUTO_LOG]);
	}

	// ページ名を決定
	$i = 0;
	do {
		++$i;
		$_page = "$page/$i";
	} while (is_page($_page));
	$head = "*".$_pcmt_msg_log_title."($i)\n\n";

	//ページ新規作成
	$aids = $gids = $freeze = "";
	//編集権限継承がセットされている上位ページ凍結情報を得る
	$up_freezed = get_pg_allow_editer($post['refer']);
	$page_info = "";
	//ページ情報のセット
	if ($up_freezed['uid'] !== "")
	{
		//編集権限継承あり
		$freeze = 1;
		$uid = $up_freezed['uid'];
		$aids = preg_replace("/(($|,)$uid,|,$)/","",$up_freezed['user']);
		$gids = preg_replace("/,$/","",$up_freezed['group']);
		$page_info = "#freeze\tuid:{$uid}\taid:{$aids}\tgid:{$gids}\n// author:{$uid}\n";
	}
	else
	{
		$page_info = "// author:".get_pg_auther($post['refer'])."\n";
	}
	
	// ページ作成
	page_write($_page, $head."#navi(../)\n\n[[$page]]\n\n".$page_info.join('', $old)."\n#navi(../)", NULL,$aids,$gids,"","",$freeze);

	// 繰り返す :)
	pcmt_auto_log($page, $dir, $count, $postdata);
}

//オプションを解析する
function pcmt_check_arg($val, $key, &$params)
{
	static $valid_args = array('noname','nodate','below','above','reply','mail','up','down','notimestamp');
	
	$found = false;
	foreach ($valid_args as $valid)
	{
		if (strpos($valid, strtolower($val)) === 0)
		{
			$params[$valid] = 1;
			$found = true;
			break;
		}
	}
	if (!$found) {$params['arg'][] = $val;}
	return;
}
function pcmt_get_comments($data,$count,$dir,$reply) {
	global $script,$vars,$_pcmt_msg_reply_this;
	
	if (!is_array($data)) { return array('',0); }

	$digest = md5(join('',$data));
	
	$pgid = get_pgid_by_name($vars['page']);
	
	//コメントを指定された件数だけ切り取る
	if ($dir)
	{
		$data = array_reverse($data);
		//$marker = "end";
	}
	else
	{
		//$marker = "start";
	}
	$num = $cnt = 0;
	$cmts = array();
	foreach ($data as $line) {
		if ($count > 0 and $dir and $cnt == $count) { break; }
		//if (rtrim(strtolower($line)) == "//{$marker} of comments") { break; }
		if (preg_match('/^(\-{1,2})(?!\-)(.*)$/', $line, $matches)) {
			if ($count > 0 and strlen($matches[1]) == 1 and ++$cnt > $count) { break; }
			if ($reply) {
				++$num;
				$cmts[] = "$matches[1]\x01$num\x02$matches[2]\x03\n";
			} else {
				$cmts[] = $line;
			}
		} else {
			$cmts[] = $line;
		}
	}
	$data = $cmts;
	if ($dir) { $data = array_reverse($data); }
	unset($cmts);

	//コメントより前のデータを取り除く。
	while (count($data) > 0 and (substr($data[0],0,1) != '-')) { array_shift($data); }
	
	//areaedit用スタートマーカーセット
	$start = md5(rtrim(str_replace("\x03","",preg_replace("/\x01\d+\x02/","",$data[0]))));

	//html変換
	$comments = convert_html($data);

	//areaedit用スタートマーカー付加
	$comments = str_replace("<a href=\"".$script."?plugin=areaedit","<a href=\"".$script."?plugin=areaedit&amp;start=$start",$comments);
	unset($data);

	//コメントにラジオボタンの印をつける
	if ($reply) {
		$comments = preg_replace("/<li>\x01(\d+)\x02/",'<li class="pcmt"><input id="_p_pcomment_reply_$1_'.$pgid.'" class="pcmt" type="radio" name="reply" value="$1" title="'.$_pcmt_msg_reply_this.'"/><label for="_p_pcomment_reply_$1_'.$pgid.'" title="'.$_pcmt_msg_reply_this.'">', $comments);
		$comments =str_replace("\x03","</label>",$comments);

	}
	return array($comments,$digest);
}
function plugin_pcomment_get_source($page)
{
	$source = get_source($page);
	// 見出しの固有ID部を削除
	$source = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m','$1$2',$source);
	// ページ情報削除
	delete_page_info($source);
	return join('',$source);
}

?>