<?php
// $Id: comment.inc.php,v 1.7 2003/10/13 12:23:28 nao-pon Exp $

global $name_cols, $comment_cols, $msg_format, $name_format;
global $msg_format, $now_format, $comment_format;
global $comment_ins, $comment_mail, $comment_no;


/////////////////////////////////////////////////
// コメントの名前テキストエリアのカラム数
$name_cols = 15;
/////////////////////////////////////////////////
// コメントのテキストエリアのカラム数
$comment_cols = 70;
/////////////////////////////////////////////////
// コメントの挿入フォーマット
$name_format = '[[$name]]';
$msg_format = '$msg';
$now_format = '&new{$now};';
/////////////////////////////////////////////////
// コメントの挿入フォーマット(コメント内容)
$comment_format = "\x08MSG\x08 -- \x08NAME\x08 \x08NOW\x08";
/////////////////////////////////////////////////
// コメントを挿入する位置 1:欄の前 0:欄の後
$comment_ins = 1;
/////////////////////////////////////////////////
// コメントが投稿された場合、内容をメールで送る先
$comment_mail = FALSE;

// initialize
$comment_no = 0;

function plugin_comment_action()
{
	global $post,$vars,$script,$cols,$rows,$del_backup,$do_backup,$update_exec,$now;
	global $name_cols,$comment_cols,$name_format,$msg_format,$now_format,$comment_format,$comment_ins;
	global $_title_collided,$_msg_collided,$_title_updated;
	global $_msg_comment_collided,$_title_comment_collided;
	global $no_name;

	$_comment_format = $comment_format;

	if($post['msg']=="") {
		$retvars['msg'] = $name;
		$post['page'] = $post['refer'];
		$vars['page'] = $post['refer'];
		$retvars['body'] = convert_html(join("",file(get_filename(encode($post['refer'])))));
		return $retvars;
	}
	if($post['msg'])
	{
		$post['msg'] = preg_replace("/\n/","",$post['msg']);
		
		$postdata = "";
		$postdata_old  = file(get_filename(encode($post["refer"])));
		$comment_no = 0;
		if ($post['above']==1){
			$comment_ins = 1;
		} else {
			$comment_ins = 0;
		}

		if($post["msg"])
		{
			if(preg_match("/^(-{1,2})(.*)/",$post["msg"],$match))
			{
				$head = $match[1];
				$post["msg"] = $match[2];
			}
			
			$_msg  = str_replace('$msg', $post['msg'], $msg_format);
			$_name = ($post['name'] == '')? $no_name : $post['name'];

			if (WIKI_USER_DIR)
				make_user_link($_name);
			else
				$_name = str_replace('$name',$_name,$name_format);

			$_now  = ($post['nodate'] == '1') ? '' : str_replace('$now',$now,$now_format);
			
			$comment = str_replace("\x08MSG\x08", $_msg, $comment_format);
			$comment = str_replace("\x08NAME\x08",$_name,$comment);
			$comment = str_replace("\x08NOW\x08", $_now, $comment);
			
			//表中でも使用できるようにしたので|をエスケープ nao-pon
			$comment = str_replace('|','&#124;',$comment);

			$comment = $head.$comment;
		}

		foreach($postdata_old as $line)
		{
			if (!preg_match("/\n$/",$line)) $line .= "\n";
			//if(preg_match("/^(\|.*)?#comment(\((?:up|down|above|below|nodate|noname)\))?((\||->).*)?/i",$line,$reg)) {
			if(preg_match("/^(\|)?#comment(.*?)?(\||->)?$/",rtrim($line),$reg)) {
				if($comment_no == $post["comment_no"] && $post[msg]!="") {
					$comment_data = "-$comment";
				} else {
					$comment_data = "";
				}
				if (($reg[1]) || ($reg[3])) { // テーブル内
					if($comment_data){
						if($comment_ins) { //上に追加
							$postdata .= $reg[1].$comment_data."->\n";
							$postdata .= "#comment$reg[2]".$reg[3]."\n";
						} else {  //下に追加
							$postdata .= $reg[1]."#comment$reg[2]->\n";
							$postdata .= $comment_data.$reg[3]."\n";
						}
					} else {
						$postdata .= $line;
					}
				} else { // 通常
					if($comment_data){
						if($comment_ins) { //上に追加
							$postdata .= $comment_data."\n";
							$postdata .= $line;
						} else {  //下に追加
							$postdata .= $line;
							$postdata .= $comment_data."\n";
						}
					} else {
						$postdata .= $line;
					}
				}
				$comment_no++;

			} else { // 処理しない行
				$postdata .= $line;
			}
		}

		$postdata_input = "-$comment\n";
	}

	$title = $_title_updated;
	if(md5(@join("",get_source($post["refer"]))) != $post["digest"])
	{
		$title = $_title_comment_collided;
		$body = $_msg_comment_collided . make_link($post["refer"]);
	}
	
	$postdata = user_rules_str($postdata);

/*差分＆バックアップは必要ないですね
	// 差分ファイルの作成
	if(is_page($post["refer"]))
		$oldpostdata = join("",file(get_filename(encode($post["refer"]))));
	else
		$oldpostdata = "\n";
	if($postdata)
		$diffdata = do_diff($oldpostdata,$postdata);
	file_write(DIFF_DIR,$post["refer"],$diffdata);
		// バックアップの作成
	if(is_page($post["refer"]))
		$oldposttime = filemtime(get_filename(encode($post["refer"])));
	else
		$oldposttime = time();

	// 編集内容が何も書かれていないとバックアップも削除する?しないですよね。
	if(!$postdata && $del_backup)
		backup_delete(BACKUP_DIR.encode($post["refer"]).".txt");
	else if($do_backup && is_page($post["refer"]))
	make_backup(encode($post["refer"]).".txt",$oldpostdata,$oldposttime);
*/

	// ファイルの書き込み
	file_write(DATA_DIR,$post["refer"],$postdata);
	
	if (WIKI_MAIL_NOTISE) {
		// メール送信 by nao-pon
		global $xoopsConfig;
		$mail_body = _MD_PUKIWIKI_MAIL_FIRST."\n";
		$mail_body .= _MD_PUKIWIKI_MAIL_URL."XOOPS_URL/modules/pukiwiki/?".rawurlencode(trim($post["refer"]))."\n";
		$mail_body .= _MD_PUKIWIKI_MAIL_PAGENAME.strip_bracket(trim($post["refer"]))."\n";
		$mail_body .= _MD_PUKIWIKI_MAIL_POSTER.strip_bracket(trim($name))."\n";
		$mail_body .= sprintf(_MD_PUKIWIKI_MAIL_HEAD,"comment")."\n";
		$mail_body .= $postdata_input;
		$mail_body .= _MD_PUKIWIKI_MAIL_FOOT."\n";
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
		$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
		$xoopsMailer->setFromName($xoopsConfig['sitename']);
		$xoopsMailer->setSubject(_MD_PUKIWIKI_MAIL_SUBJECT.strip_bracket(trim($post["refer"])));
		$xoopsMailer->setBody($mail_body);
		$xoopsMailer->send();
		//メール送信ここまで by nao-pon
	}

	// is_pageのキャッシュをクリアする。
	is_page($post["refer"],true);

	$retvars["msg"] = $title;
	$retvars["body"] = $body;
	
	$post["page"] = $post["refer"];
	$vars["page"] = $post["refer"];
	
	return $retvars;
}
function plugin_comment_convert()
{
	global $script,$comment_no,$vars,$name_cols,$comment_cols,$digest;
	global $_btn_comment,$_btn_name,$_msg_comment,$vars,$comment_ins;
	
	// xoops //
	global $xoopsUser;
	if ($xoopsUser){
		$name = $xoopsUser->uname();
	}
	// ---- //
	$options = func_get_args();
	//ボタンテキスト指定オプション
	$btn_text = $_btn_comment;
	$all_option = (is_array($options))? implode(" ",$options) : $options;
	if (preg_match("/(?: |^)btn:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$btn_text = $arg[1];
	}
	//コメントテキストボックスサイズ指定オプション
	$comment_size = $comment_cols;
	if (preg_match("/(?: |^)size:([0-9]+)(?: |$)/i",trim($all_option),$arg)){
		if ($comment_cols > $arg[1] && ($arg[1])) $comment_size = $arg[1];
	}

	$nametags = "$_btn_name<input type=\"text\" name=\"name\" size=\"$name_cols\" value=\"$name\" />\n";
	if(is_array($options) && in_array("noname",$options)) {
		$nametags = $_msg_comment;
	}

	$nodate = in_array('nodate',$options) ? '1' : '0';
	$above = in_array('up',$options) ? '1' : (in_array('down',$options) ? '0' : "");
	if ($above === ""){
		$above = in_array('above',$options) ? '1' : (in_array('below',$options) ? '0' : $comment_ins);
	}

	if((arg_check("read")||$vars["cmd"] == ""||arg_check("unfreeze")||arg_check("freeze")||$vars["write"]||$vars["comment"]))
		$button = "<input type=\"submit\" name=\"comment\" value=\"".htmlspecialchars($btn_text)."\" />\n";

	$string = "<br /><form action=\"$script\" method=\"post\">\n"
		 ."<div>\n"
		 ."<input type=\"hidden\" name=\"comment_no\" value=\"".htmlspecialchars($comment_no)."\" />\n"
		 ."<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\" />\n"
		 ."<input type=\"hidden\" name=\"plugin\" value=\"comment\" />\n"
		 ."<input type=\"hidden\" name=\"nodate\" value=\"".htmlspecialchars($nodate)."\" />\n"
		 ."<input type=\"hidden\" name=\"above\" value=\"$above\" />\n"
		 ."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($digest)."\" />\n"
		 ."$nametags"
		 ."<input type=\"text\" name=\"msg\" size=\"".htmlspecialchars($comment_size)."\" />\n"
		 .$button
		 ."</div>\n"
		 ."</form>";

	$comment_no++;

	return $string;
}
?>
