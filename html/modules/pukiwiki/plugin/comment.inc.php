<?php
// $Id: comment.inc.php,v 1.23 2006/09/24 14:56:23 nao-pon Exp $

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
$name_format = '[[%2$s>%1$s]]';
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
/////////////////////////////////////////////////
// areaedit指定を規定値にする(Yes:1, No:0)
define('PUKIWIKI_CMT_AREAEDIT_ENABLE',1);
// areaedit指定の追加オプション
define('PUKIWIKI_CMT_OPTION',",preview:5");

// initialize
$comment_no = 0;

function plugin_comment_action()
{
	global $post,$vars,$script,$cols,$rows,$del_backup,$do_backup,$update_exec,$now;
	global $name_cols,$comment_cols,$name_format,$msg_format,$now_format,$comment_format,$comment_ins;
	global $_title_collided,$_msg_collided,$_title_updated;
	global $_msg_comment_collided,$_title_comment_collided;
	global $no_name,$X_uid;

	$_comment_format = $comment_format;

	if($post['msg']=="") {
		$retvars['redirect'] = XOOPS_WIKI_HOST.get_url_by_name($post["refer"]);
		return $retvars;
	}
	if($post['msg'])
	{
		$post['msg'] = preg_replace("/\n/","",$post['msg']);
		
		$postdata = "";
		$postdata_old  = get_source($post["refer"]);
		$comment_no = 0;
		if ($post['above']==1){
			$comment_ins = 1;
		} else {
			$comment_ins = 0;
		}
		
		if($post["msg"])
		{
			$match = array();
			if(preg_match("/^(-{1,2})(.*)/",$post["msg"],$match))
			{
				$head = $match[1];
				$post["msg"] = $match[2];
			}
			
			$_msg  = str_replace('$msg', $post['msg'], $msg_format);
			
			if (empty($post['noname']))
			{
				$_name = $post['name'];
				// 名前をフォーマット
				make_user_link($_name,$name_format);
			}
			else
				{$_name = "";}
			
			$_msg = rtrim($_msg);
			//areaedit指定
			if (PUKIWIKI_CMT_AREAEDIT_ENABLE || !empty($post['areaedit']))
			{
				if ($X_uid)
					{$_msg = "&areaedit(uid:".$X_uid.PUKIWIKI_CMT_OPTION."){".$_msg."};";}
				else
					{$_msg = "&areaedit(ucd:".PUKIWIKI_UCD.PUKIWIKI_CMT_OPTION."){".$_msg."};";}
			}
			
			$_now  = ($post['nodate'] == '1') ? '' : str_replace('$now',$now,$now_format);
			$comment = str_replace("\x08MSG\x08", $_msg, $comment_format);
			$comment = str_replace("\x08NAME\x08",$_name,$comment);
			$comment = str_replace("\x08NOW\x08", $_now, $comment);
			
			//表中でも使用できるようにしたので|をエスケープ nao-pon
			$comment = str_replace('|','&#124;',$comment);
			//areaedit指定
			//if (PUKIWIKI_CMT_AREAEDIT_ENABLE || !empty($post['areaedit']))
			//{
			//	//$comment = "&areaedit(uid:".$X_uid.PUKIWIKI_CMT_OPTION."){".$comment."};";
			//	if ($X_uid)
			//		$comment = "&areaedit(uid:".$X_uid.PUKIWIKI_CMT_OPTION."){".$comment."};";
			//	else
			//		$comment = "&areaedit(ucd:".PUKIWIKI_UCD.PUKIWIKI_CMT_OPTION."){".$comment."};";
			//}
			$comment = $head.$comment;
		}

		foreach($postdata_old as $line)
		{
			if (!preg_match("/\n$/",$line)) $line .= "\n";
			$reg = array();
			if(preg_match("/^(\|(.*)?)?#comment(.*?)?(\||->)?$/",rtrim($line),$reg)) {
				$celltag = $arg[1];
				if ($celltag) $celltag = cell_format_tag_del($celltag);

				if($comment_no == $post["comment_no"] && $post[msg]!="" && !$celltag) {
					$comment_data = "-$comment";
				} else {
					$comment_data = "";
				}
				if (($reg[1]) || ($reg[4])) { // テーブル内
					if($comment_data){
						if($comment_ins) { //上に追加
							$postdata .= $reg[1].$comment_data."->\n";
							$postdata .= "#comment$reg[3]".$reg[4]."\n";
						} else {  //下に追加
							$postdata .= $reg[1]."#comment$reg[3]->\n";
							$postdata .= $comment_data.$reg[4]."\n";
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
	
	// ファイルの書き込み
	page_write($post["refer"],$postdata,NULL,"","","","","","",array('plugin'=>'comment','mode'=>'add'));
	
	$retvars["msg"] = $title;
	$retvars["body"] = $body;
	
	$post["page"] = $post["refer"];
	$vars["page"] = $post["refer"];
	
	return $retvars;
}
function plugin_comment_convert()
{
	global $script,$comment_no,$vars,$name_cols,$comment_cols;
	global $_btn_comment,$_btn_name,$_msg_comment,$vars,$comment_ins;
	
	$options = func_get_args();
	$style = "";
	
	// 編集権限が必要？
	if (in_array('auth',$options) && !check_editable($vars["page"],false,false))
	{
		$comment_no++;
		return "";
	}
	
	//ボタンテキスト指定オプション
	$btn_text = $_btn_comment;
	$all_option = (is_array($options))? implode(" ",$options) : $options;
	$arg = array();
	if (preg_match("/(?: |^)btn:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$btn_text = $arg[1];
	}
	//コメントテキストボックスサイズ指定オプション
	$comment_size = $comment_cols;
	if (preg_match("/(?: |^)size:([0-9]+)(?: |$)/i",trim($all_option),$arg)){
		if ($comment_cols > $arg[1] && ($arg[1])) $comment_size = $arg[1];
		$w_style = "width:auto;";
	}
	else
	{
		$style = " style=\"width:98%;\"";
		$w_style = "width:100%;";
	}
	//areaedit指定
	$areaedit = "";
	if (preg_match("/(?: |^)areaedit(?: |$)/i",trim($all_option),$arg)){
		$areaedit = "<input type=\"hidden\" name=\"areaedit\" value=\"1\" />\n";
	}
	
	$nametags = "$_btn_name<input type=\"text\" name=\"name\" size=\"$name_cols\" value=\"".WIKI_NAME_DEF."\" />\n";
	if(is_array($options) && in_array("noname",$options)) {
		$nametags = "<input type=\"hidden\" name=\"noname\" value=\"1\" />\n".$_msg_comment;
	}

	$nodate = in_array('nodate',$options) ? '1' : '0';
	$above = in_array('up',$options) ? '1' : (in_array('down',$options) ? '0' : "");
	if ($above === ""){
		$above = in_array('above',$options) ? '1' : (in_array('below',$options) ? '0' : $comment_ins);
	}

	if((arg_check("read")||$vars["cmd"] == ""||arg_check("unfreeze")||arg_check("freeze")||$vars["write"]||$vars["comment"]))
		$button = "<input type=\"submit\" name=\"comment\" value=\"".htmlspecialchars($btn_text)."\" />\n";
	
	$digest = md5(@join("",get_source($vars["page"])));
	
	$string = "<br /><form action=\"$script\" method=\"post\">\n"
		 ."<div>\n"
		 ."<input type=\"hidden\" name=\"comment_no\" value=\"".htmlspecialchars($comment_no)."\" />\n"
		 ."<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\" />\n"
		 ."<input type=\"hidden\" name=\"plugin\" value=\"comment\" />\n"
		 ."<input type=\"hidden\" name=\"nodate\" value=\"".htmlspecialchars($nodate)."\" />\n"
		 ."<input type=\"hidden\" name=\"above\" value=\"$above\" />\n"
		 ."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($digest)."\" />\n"
		 ."$areaedit"
		 ."<table style=\"{$w_style}\"><tr><td style=\"vertical-align:bottom;white-space:nowrap;\">$nametags</td>"
		 ."<td style=\"vertical-align:bottom;{$w_style}\">".fontset_js_tag()."<br />"
		 ."<input type=\"text\" name=\"msg\" size=\"".htmlspecialchars($comment_size)."\"{$style} /></td>\n"
		 ."<td style=\"vertical-align:bottom;\">".$button."</td>"
		 ."</tr></table>"
		 ."</div>\n"
		 ."</form>";

	$comment_no++;

	return $string;
}
?>