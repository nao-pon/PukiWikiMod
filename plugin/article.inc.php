<?php
 /*
 
 PukiWiki BBS���ץ饰����

 CopyRight 2002 OKAWARA,Satoshi
 http://www.dml.co.jp/~kawara/pukiwiki/pukiwiki.php
 kawara@dml.co.jp
 
 ��å��������ѹ�����������LANGUAGE�ե�����˲������ͤ��ɲä��Ƥ��餴���Ѥ�������
	$_btn_name = '��̾��';
	$_btn_article = '���������';
	$_btn_subject = '��̾: ';

 ��$_btn_name��comment�ץ饰����Ǵ������ꤵ��Ƥ����礬����ޤ�
 
 ������Ƥμ�ư�᡼��ž����ǽ�򤴻��Ѥˤʤꤿ������
 -������ƤΥ᡼�뼫ư�ۿ�
 -������ƤΥ᡼�뼫ư�ۿ���
 ������ξ塢�����Ѥ���������

 $Id: article.inc.php,v 1.5 2003/10/13 12:23:28 nao-pon Exp $
 
 */

global $name_format, $subject_format, $no_subject, $_mailto;

/////////////////////////////////////////////////
// �ƥ����ȥ��ꥢ�Υ�����
define("article_COLS",70);
/////////////////////////////////////////////////
// �ƥ����ȥ��ꥢ�ιԿ�
define("article_ROWS",5);
/////////////////////////////////////////////////
// ̾���ƥ����ȥ��ꥢ�Υ�����
define("NAME_COLS",24);
/////////////////////////////////////////////////
// ��̾�ƥ����ȥ��ꥢ�Υ�����
define("SUBJECT_COLS",60);
/////////////////////////////////////////////////
// ̾���������ե����ޥå�
$name_format = '[[$name]]';
/////////////////////////////////////////////////
// ��̾�������ե����ޥå�
$subject_format = '**$subject';
/////////////////////////////////////////////////
// ��̾��̤�����ξ���ɽ�� 
$no_subject = '̵��';
/////////////////////////////////////////////////
// ����������� 1:����� 0:��θ�
define("ARTICLE_INS",0);
/////////////////////////////////////////////////
// �񤭹��ߤβ��˰�ԥ����Ȥ������ 1:����� 0:����ʤ�
define("ARTICLE_COMMENT",1);
/////////////////////////////////////////////////
// ���Ԥ�ưŪ�Ѵ� 1:���� 0:���ʤ�
define("ARTICLE_AUTO_BR",1);

/////////////////////////////////////////////////
// ������ƤΥ᡼�뼫ư�ۿ� 1:���� 0:���ʤ�
define("MAIL_AUTO_SEND",0);
/////////////////////////////////////////////////
// ������ƤΥ᡼���������������ԥ᡼�륢�ɥ쥹
define("MAIL_FROM",'');
/////////////////////////////////////////////////
// ������ƤΥ᡼������������̾
define("MAIL_SUBJECT_PREFIX",'[someone\'sPukiWiki]');
/////////////////////////////////////////////////
// ������ƤΥ᡼�뼫ư�ۿ���
$_mailto = array (
	''
);


function plugin_article_init()
{
  $_plugin_article_messages = array(
    '_btn_name' => '��̾��',
    '_btn_article' => '���������',
    '_btn_subject' => '��̾: '
    );
  set_plugin_messages($_plugin_article_messages);
}

function plugin_article_action()
{
	global $post,$vars,$script,$cols,$rows,$del_backup,$do_backup,$now;
	global $name_format, $subject_format, $no_subject, $name, $subject, $article;
	global $_title_collided,$_msg_collided,$_title_updated;
	global $_mailto,$no_name;;
	
	if (!$post["msg"]) $post["msg"]="\t";
	if ($post["msg"])
	{
		$postdata = "";
		//$postdata_old  = file(get_filename(encode($post["refer"])));
		$postdata_old  = get_source($post["refer"]);
		$article_no = 0;

		$name = ($post['name'])? $post['name'] : $no_name;
		if (WIKI_USER_DIR)
			make_user_link($name);
		else
			$name = str_replace('$name',$name,$name_format);
		
		
		if($post['subject'])
		{
			$subject = str_replace('$subject',$post[subject],$subject_format);
		} else {
			$subject = str_replace('$subject',$no_subject,$subject_format);
		}

//		$article  = $subject."\n>";
//		$article .= $name." (".$now.")\n>~\n";
		$article  = $subject." SIZE(10){> ".$name." (".$now.")}\n";

		if(ARTICLE_AUTO_BR){
			//���Ԥμ�갷���Ϥ��ä�������ä�URL�������Ȥ��ϡ�
//			$article_body = $post[msg];
//			$article_body = str_replace("\n","\n>~\n",$article_body);
//			$article_body = str_replace("\n","~\n",$article_body);
//			$article_body = preg_replace("/\n\n/","\n",$article_body);
//			$article .= $article_body;
			//��ư�����������Τǽ��� nao-pon
			//���礨����������~����
			$post["msg"] = preg_replace("/~\n/","\n",$post["msg"]);
			//�Ԥ�������Ѵ�����1����˽���
			$post_lines = array();
			$post_lines = split("\n",$post["msg"]);
			$post_lines = preg_replace("/(^[^ #\-+*|].*)/","\\1~",$post_lines);
			$post["msg"] = join("\n",$post_lines);
			//;ʬ��~����
			$post["msg"] = preg_replace("/~\n([ #\-+*|\n]|$)/","\n\\1",$post["msg"]);
			$post["msg"] = preg_replace("/~$/","",$post["msg"]);
			$article .= $post[msg];
		} else {
			$article .= ">".$post[msg];
		}

		if(ARTICLE_COMMENT){
			$article .= "\n#comment";
		}

		foreach($postdata_old as $line)
		{
			if(!ARTICLE_INS) $postdata .= $line;
			if(preg_match("/^#article$/",trim($line)))
//			if(preg_match("/^#article\s?$/",$line))
			{
				if($article_no == $post["article_no"] && $post[msg]!="")
				{
					$postdata .= "\n$article\n";
				}
				$article_no++;
			}
			if(ARTICLE_INS) $postdata .= $line;
		}

		$postdata_input = "$article\n";
	}
	else
		return;

	if(md5(@join("",get_source($post["refer"]))) != $post["digest"])
	{
		$title = $_title_collided;

		$body = "$_msg_collided\n";

		$body .= "<form action=\"$script?cmd=preview\" method=\"post\">\n"
			."<div>\n"
			."<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($post["refer"])."\" />\n"
			."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($post["digest"])."\" />\n"
			."<textarea name=\"msg\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" id=\"textarea\">".htmlspecialchars($postdata_input)."</textarea><br />\n"
			."</div>\n"
			."</form>\n";
	}
	else
	{
		$postdata = user_rules_str($postdata);

		// ��ʬ�ե�����κ���
		if(is_page($post["refer"]))
			$oldpostdata = join("",file(get_filename(encode($post["refer"]))));
		else
			$oldpostdata = "\n";
		if($postdata)
			$diffdata = do_diff($oldpostdata,$postdata);
		file_write(DIFF_DIR,$post["refer"],$diffdata);

		// �Хå����åפκ���
		if(is_page($post["refer"]))
			$oldposttime = filemtime(get_filename(encode($post["refer"])));
		else
			$oldposttime = time();

		// �Խ����Ƥ�����񤫤�Ƥ��ʤ��ȥХå����åפ�������?���ʤ��Ǥ���͡�
		if(!$postdata && $del_backup)
			backup_delete(BACKUP_DIR.encode($post["refer"]).".txt");
		else if($do_backup && is_page($post["refer"]))
			make_backup(encode($post["refer"]).".txt",$oldpostdata,$oldposttime);

		// �ե�����ν񤭹���
		file_write(DATA_DIR,$post["refer"],$postdata);

		if (WIKI_MAIL_NOTISE) {
			// �᡼������ by nao-pon
			global $xoopsConfig;

			 //- �᡼���Ѻ�ʬ�ǡ����κ���
			$mail_add = $mail_del = "";
			$diffdata_ar = array();
			$diffdata_ar=split("\n",$diffdata);
			foreach($diffdata_ar as $diffdata_line){
				if (ereg("^\+(.*)",$diffdata_line,$regs)){
					$mail_add .= $regs[1]."\n";
				}
				if (ereg("^\-(.*)",$diffdata_line,$regs)){
					$mail_del .= $regs[1]."\n";
				}
			}

			$mail_body = "PukiWiki�ذʲ�����Ƥ�����ޤ�����\n";
			$mail_body .= "URL: ".XOOPS_URL."/modules/pukiwiki/?".rawurlencode(trim($post["refer"]))."\n";
			$mail_body .= "�ڡ���̾: ".strip_bracket(trim($post["refer"]))."\n";
			$mail_body .= "��Ƽ�: ".$name."\n";
			$mail_body .= "----------������줿��------------\n";
			$mail_body .= $mail_del;
			$mail_body .= "----------�ɲä��줿��------------\n";
			$mail_body .= $mail_add;
			$mail_body .= "----------����ʸ------------------\n";
			$mail_body .= $postdata;
			$xoopsMailer =& getMailer();
			$xoopsMailer->useMail();
			$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
			$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
			$xoopsMailer->setFromName($xoopsConfig['sitename']);
			$xoopsMailer->setSubject("PukiWiki�ؤ����:".strip_bracket(trim($post["refer"])));
			$xoopsMailer->setBody($mail_body);
			$xoopsMailer->send();
			//�᡼�����������ޤ� by nao-pon
		}
		
		// is_page�Υ���å���򥯥ꥢ���롣
		is_page($post["refer"],true);

		$title = $_title_updated;
	}
	$retvars["msg"] = $title;
	$retvars["body"] = $body;

	$post["page"] = $post["refer"];
	$vars["page"] = $post["refer"];

	return $retvars;
}
function plugin_article_convert()
{
	global $script,$vars,$digest;
	global $_btn_article,$_btn_name,$_btn_subject,$vars;
	static $article_no = 0;

	// xoops //
	global $xoopsUser;
	if ($xoopsUser){
		//$name = $xoopsUser->name();
		$uname = $xoopsUser->uname();
		if($name == "") {
		    $name = $uname;
		}
	}
	// ---- //

	if((arg_check("read")||$vars["cmd"] == ""||arg_check("unfreeze")||arg_check("freeze")||$vars["write"]||$vars["article"]))
		$button = "<input type=\"submit\" name=\"article\" value=\"$_btn_article\" />\n";

	$string = "<form action=\"$script\" method=\"post\">\n"
		 ."<div>\n"
		 ."<input type=\"hidden\" name=\"article_no\" value=\"$article_no\" />\n"
		 ."<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\" />\n"
		 ."<input type=\"hidden\" name=\"plugin\" value=\"article\" />\n"
		 ."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($digest)."\" />\n"
		 ."$_btn_name<input type=\"text\" name=\"name\" size=\"".NAME_COLS."\" value=\"$name\" /><br />\n"
		 ."$_btn_subject<input type=\"text\" name=\"subject\" size=\"".SUBJECT_COLS."\" /><br />\n"
		 ."<textarea name=\"msg\" rows=\"".article_ROWS."\" cols=\"".article_COLS."\">\n</textarea><br />\n"
		 .$button
		 ."</div>\n"
		 ."</form>";

	$article_no++;

	return $string;
}
?>
