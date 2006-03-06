<?php
// $Id: comment.inc.php,v 1.21 2006/03/06 06:20:30 nao-pon Exp $

global $name_cols, $comment_cols, $msg_format, $name_format;
global $msg_format, $now_format, $comment_format;
global $comment_ins, $comment_mail, $comment_no;


/////////////////////////////////////////////////
// �����Ȥ�̾���ƥ����ȥ��ꥢ�Υ�����
$name_cols = 15;
/////////////////////////////////////////////////
// �����ȤΥƥ����ȥ��ꥢ�Υ�����
$comment_cols = 70;
/////////////////////////////////////////////////
// �����Ȥ������ե����ޥå�
$name_format = '[[%2$s>%1$s]]';
$msg_format = '$msg';
$now_format = '&new{$now};';
/////////////////////////////////////////////////
// �����Ȥ������ե����ޥå�(����������)
$comment_format = "\x08MSG\x08 -- \x08NAME\x08 \x08NOW\x08";
/////////////////////////////////////////////////
// �����Ȥ������������ 1:����� 0:��θ�
$comment_ins = 1;
/////////////////////////////////////////////////
// �����Ȥ���Ƥ��줿��硢���Ƥ�᡼���������
$comment_mail = FALSE;
/////////////////////////////////////////////////
// areaedit��������ͤˤ���(Yes:1, No:0)
define('PUKIWIKI_CMT_AREAEDIT_ENABLE',1);
// areaedit������ɲå��ץ����
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
		$retvars['redirect'] = get_url_by_name($post["refer"]);
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
				// ̾����ե����ޥå�
				make_user_link($_name,$name_format);
			}
			else
				{$_name = "";}
			
			$_msg = rtrim($_msg);
			//areaedit����
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
			
			//ɽ��Ǥ���ѤǤ���褦�ˤ����Τ�|�򥨥������� nao-pon
			$comment = str_replace('|','&#124;',$comment);
			//areaedit����
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
				if (($reg[1]) || ($reg[4])) { // �ơ��֥���
					if($comment_data){
						if($comment_ins) { //����ɲ�
							$postdata .= $reg[1].$comment_data."->\n";
							$postdata .= "#comment$reg[3]".$reg[4]."\n";
						} else {  //�����ɲ�
							$postdata .= $reg[1]."#comment$reg[3]->\n";
							$postdata .= $comment_data.$reg[4]."\n";
						}
					} else {
						$postdata .= $line;
					}
				} else { // �̾�
					if($comment_data){
						if($comment_ins) { //����ɲ�
							$postdata .= $comment_data."\n";
							$postdata .= $line;
						} else {  //�����ɲ�
							$postdata .= $line;
							$postdata .= $comment_data."\n";
						}
					} else {
						$postdata .= $line;
					}
				}
				$comment_no++;

			} else { // �������ʤ���
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
	
	// �ե�����ν񤭹���
	page_write($post["refer"],$postdata,NULL,"","","","","","",array('plugin'=>'comment','mode'=>'add'));
	
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
	
	$options = func_get_args();
	$style = "";
	
	// �Խ����¤�ɬ�ס�
	if (in_array('auth',$options) && !check_editable($vars["page"],false,false))
	{
		$comment_no++;
		return "";
	}
	
	//�ܥ���ƥ����Ȼ��ꥪ�ץ����
	$btn_text = $_btn_comment;
	$all_option = (is_array($options))? implode(" ",$options) : $options;
	$arg = array();
	if (preg_match("/(?: |^)btn:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$btn_text = $arg[1];
	}
	//�����ȥƥ����ȥܥå������������ꥪ�ץ����
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
	//areaedit����
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