<?php
 /*
 
 PukiWiki BBS風プラグイン

 CopyRight 2002 OKAWARA,Satoshi
 http://www.dml.co.jp/~kawara/pukiwiki/pukiwiki.php
 kawara@dml.co.jp
 
 メッセージを変更したい場合はLANGUAGEファイルに下記の値を追加してからご使用ください
	$_btn_name = 'お名前';
	$_btn_article = '記事の投稿';
	$_btn_subject = '題名: ';

 ※$_btn_nameはcommentプラグインで既に設定されている場合があります

 $Id: article.inc.php,v 1.12 2005/02/24 15:35:33 nao-pon Exp $
 
 */

global $name_format, $subject_format, $no_subject, $_mailto;

/////////////////////////////////////////////////
// テキストエリアのカラム数
define("article_COLS",70);
/////////////////////////////////////////////////
// テキストエリアの行数
define("article_ROWS",5);
/////////////////////////////////////////////////
// 名前テキストエリアのカラム数
define("NAME_COLS",24);
/////////////////////////////////////////////////
// 題名テキストエリアのカラム数
define("SUBJECT_COLS",60);
/////////////////////////////////////////////////
// 名前の挿入フォーマット
$name_format = '[[$name]]';
/////////////////////////////////////////////////
// 題名の挿入フォーマット
$subject_format = '**$subject';
/////////////////////////////////////////////////
// 題名が未記入の場合の表記 
$no_subject = '無題';
/////////////////////////////////////////////////
// 挿入する位置 1:欄の前 0:欄の後
define("ARTICLE_INS",0);
/////////////////////////////////////////////////
// 書き込みの下に一行コメントを入れる 1:入れる 0:入れない
define("ARTICLE_COMMENT",1);
/////////////////////////////////////////////////
// 改行を自動的変換 1:する 0:しない
define("ARTICLE_AUTO_BR",1);

// initialize
$article_no = 0;


function plugin_article_init()
{
  $_plugin_article_messages = array(
    '_btn_name' => 'お名前',
    '_btn_article' => '記事の投稿',
    '_btn_subject' => '題名: '
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
		
		// 名前をクッキーに保存
		setcookie("pukiwiki_un", $post['name'], time()+86400*365);//1年間
		
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
			$article .= auto_br($post['msg']);
		} else {
			$article .= ">".$post['msg'];
		}

		if(ARTICLE_COMMENT){
			$article .= "\n#comment";
		}
		
		$article .= "\n----\n";

		foreach($postdata_old as $line)
		{
			if(!ARTICLE_INS) $postdata .= $line;
			if(preg_match("/^#article$/",trim($line)))
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
			.fontset_js_tag()."<br />\n"
			."<textarea name=\"msg\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" id=\"textarea\">".htmlspecialchars($postdata_input)."</textarea><br />\n"
			."</div>\n"
			."</form>\n";
	}
	else
	{
		// ページの書き込み
		page_write($post["refer"],$postdata,NULL,"","","","","","",array('plugin'=>'article','mode'=>'add'));
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
	global $article_no;

	if((arg_check("read")||$vars["cmd"] == ""||arg_check("unfreeze")||arg_check("freeze")||$vars["write"]||$vars["article"]))
		$button = "<input type=\"submit\" name=\"article\" value=\"$_btn_article\" />\n";

	$string = "<form action=\"$script\" method=\"post\">\n"
		 ."<div>\n"
		 ."<input type=\"hidden\" name=\"article_no\" value=\"$article_no\" />\n"
		 ."<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\" />\n"
		 ."<input type=\"hidden\" name=\"plugin\" value=\"article\" />\n"
		 ."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($digest)."\" />\n"
		 ."$_btn_name<input type=\"text\" name=\"name\" size=\"".NAME_COLS."\" value=\"".WIKI_NAME_DEF."\" /><br />\n"
		 ."$_btn_subject<input type=\"text\" name=\"subject\" size=\"".SUBJECT_COLS."\" /><br />\n"
		 .fontset_js_tag()."<br />\n"
		 ."<textarea name=\"msg\" rows=\"".article_ROWS."\" cols=\"".article_COLS."\">\n</textarea><br />\n"
		 .$button
		 ."</div>\n"
		 ."</form>";

	$article_no++;

	return $string;
}
?>