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

 $Id: article.inc.php,v 1.13 2005/03/05 02:14:50 nao-pon Exp $
 
 */

global $article_no;

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
	global $_title_collided,$_msg_collided,$_title_updated,$_msg_comment_collided;
	global $_mailto,$no_name,$article_body_format;
	
	// 設定ファイル読み込み
	$conf = (isset($post['conf']))?  preg_replace("/[^\w]+/","",$post['conf']) : 0;
	$conf = "article/".$conf.".conf.php";
	if(file_exists(PLUGIN_DATA_DIR.$conf))
		include (PLUGIN_DATA_DIR.$conf);
	else
		return (array("msg"=>"ERROR: Config file 'PLUGIN_DATA_DIR/{$conf}' is not found.","body"=>"ERROR: Config file 'PLUGIN_DATA_DIR/{$conf}' is not found."));
	
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
			$subject = str_replace('$subject',$post['subject'],$subject_format);
		} else {
			$subject = str_replace('$subject',$no_subject,$subject_format);
		}

		if($article_auto_br){
			$text = auto_br($post['msg']);
		} else {
			$text = ">".$post['msg'];
		}
		
		$article = str_replace(array('$subject','$name','$now','$text','\n',"\r"),array($subject,$name,$now,$text,"\n",""),trim($article_body_format));
		
		foreach($postdata_old as $line)
		{
			if(!$article_ins) $postdata .= $line;
			if(preg_match("/^#article.*$/",rtrim($line)))
			{
				if($article_no == $post["article_no"] && $post[msg]!="")
				{
					$postdata .= "\n$article\n";
				}
				$article_no++;
			}
			if($article_ins) $postdata .= $line;
		}
	}
	else
		return;

	if(md5(@join("",get_source($post["refer"]))) != $post["digest"])
		$title = $_msg_comment_collided;
	else
		$title = $_title_updated;
	
	// ページの書き込み
	page_write($post["refer"],$postdata,NULL,"","","","","","",array('plugin'=>'article','mode'=>'add'));
	
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
	
	$conf = "default";
	
	$style = $btn = $nsize = $ssize = $col = $row = $auth = $plugin = "";
	
	foreach(func_get_args() as $op)
	{
		if (strtolower($op == "auth"))
			$auth = TRUE;
		if (strtolower(substr($op,0,7)) == "config:")
			$conf = preg_replace("/[^\w]+/","",substr($op,7));
		if (strtolower(substr($op,0,4)) == "btn:")
			$btn = htmlspecialchars(substr($op,4));
		if (strtolower(substr($op,0,6)) == "nsize:")
			$nsize = min((int)substr($op,6),100);
		if (strtolower(substr($op,0,6)) == "ssize:")
			$ssize = min((int)substr($op,6),100);
		if (strtolower(substr($op,0,4)) == "col:")
			$col = min((int)substr($op,4),100);
		if (strtolower(substr($op,0,4)) == "row:")
			$row = min((int)substr($op,4),100);
	}
	
	if ($auth && is_freeze($vars["page"]))
	{
		$article_no++;
		return "";
	}
	
	$conf_file = "article/".$conf.".conf.php";
	if(file_exists(PLUGIN_DATA_DIR.$conf_file))
		include (PLUGIN_DATA_DIR.$conf_file);
	else
		return "ERROR: Config file 'PLUGIN_DATA_DIR/{$conf_file}' is not found.";
	
	if (!$btn) $btn = $_btn_article;
	if (!$nsize) $nsize = $article_name_cols;
	if (!$ssize) $ssize = $article_subject_cols;
	if (!$col) $col = $article_cols;
	if (!$row) $row = $article_rows;

	$style = " style=\"width:auto;\"";
	$button = '';
	if((arg_check("read")||$vars["cmd"] == ""||arg_check("unfreeze")||arg_check("freeze")||$vars["write"]||$vars["article"]))
		$button = "<input type=\"submit\" name=\"article\" value=\"$btn\" />\n";
	
	$string = "<form action=\"$script\" method=\"post\">\n"
		 ."<div>\n"
		 ."<input type=\"hidden\" name=\"article_no\" value=\"$article_no\" />\n"
		 ."<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\" />\n"
		 ."<input type=\"hidden\" name=\"plugin\" value=\"article\" />\n"
		 ."<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($digest)."\" />\n"
		 ."<input type=\"hidden\" name=\"conf\" value=\"".htmlspecialchars($conf)."\" />\n"
		 ."$_btn_name<input type=\"text\" name=\"name\" size=\"".$nsize."\" value=\"".WIKI_NAME_DEF."\" /><br />\n"
		 ."$_btn_subject<input type=\"text\" name=\"subject\" size=\"".$ssize."\" /><br />\n"
		 .fontset_js_tag()."<br />\n"
		 ."<textarea name=\"msg\" rows=\"".$row."\" cols=\"".$col."\"{$style}>\n</textarea><br />\n"
		 .$button
		 ."</div>\n"
		 ."</form><hr />";

	$article_no++;

	return $string;
}
?>