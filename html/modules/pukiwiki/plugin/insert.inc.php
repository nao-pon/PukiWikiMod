<?php
// $Id: insert.inc.php,v 1.5 2004/11/24 13:15:35 nao-pon Exp $

/////////////////////////////////////////////////
// テキストエリアのカラム数
define("INSERT_COLS",70);
/////////////////////////////////////////////////
// テキストエリアの行数
define("INSERT_ROWS",5);
/////////////////////////////////////////////////
// 挿入する位置 1:欄の前 0:欄の後
define("INSERT_INS",1);

function plugin_insert_action()
{
	global $post,$vars,$script,$cols,$rows,$del_backup,$do_backup;
	global $_title_collided,$_msg_collided,$_title_updated;

	if($post["msg"])
	{
		$postdata = "";
		$postdata_old  = file(get_filename(encode($post["refer"])));
		$insert_no = 0;

		if($post[msg])
		{
			$insert = $post[msg];
		}

		foreach($postdata_old as $line)
		{
			if(!INSERT_INS) $postdata .= $line;
			if(preg_match("/^#insert$/",$line))
			{
				if($insert_no == $post["insert_no"] && $post[msg]!="")
				{
					$postdata .= "$insert\n";
				}
				$insert_no++;
			}
			if(INSERT_INS) $postdata .= $line;
		}

		$postdata_input = "$insert\n";
	}
	else
		return;

	if(md5(@join("",@file(get_filename(encode($post["refer"]))))) != $post["digest"])
	{
		$title = $_title_collided;

		$body = "$_msg_collided\n";

		$body .= "<form action=\"$script?cmd=preview\" method=\"post\">\n"
			."<div>\n"
			."<input type=\"hidden\" name=\"refer\" value=\"".$post["refer"]."\" />\n"
			."<input type=\"hidden\" name=\"digest\" value=\"".$post["digest"]."\" />\n"
			."<textarea name=\"msg\" rows=\"$rows\" cols=\"$cols\" wrap=\"virtual\" id=\"textarea\">$postdata_input</textarea><br />\n"
			."</div>\n"
			."</form>\n";
	}
	else
	{
		// ファイルの書き込み
		page_write($post["refer"],$postdata,NULL,"","","","","","",array('plugin'=>'insert','mode'=>'add'));

		$title = $_title_updated;
	}
	$retvars["msg"] = $title;
	$retvars["body"] = $body;

	$post["page"] = $post["refer"];
	$vars["page"] = $post["refer"];

	return $retvars;
}
function plugin_insert_convert()
{
	global $script,$vars,$digest;
	global $_btn_insert,$vars;
	static $insert_no = 0;

	if((arg_check("read")||$vars["cmd"] == ""||arg_check("unfreeze")||arg_check("freeze")||$vars["write"]||$vars["insert"]))
		$button = "<input type=\"submit\" name=\"insert\" value=\"$_btn_insert\" />\n";

	$s_page = htmlspecialchars($vars['page']);
	
	$string = "<form action=\"$script\" method=\"post\">\n"
		 ."<div>\n"
		 ."<input type=\"hidden\" name=\"insert_no\" value=\"$insert_no\" />\n"
		 ."<input type=\"hidden\" name=\"refer\" value=\"$s_page\" />\n"
		 ."<input type=\"hidden\" name=\"plugin\" value=\"insert\" />\n"
		 ."<input type=\"hidden\" name=\"digest\" value=\"$digest\" />\n"
		 ."<textarea name=\"msg\" rows=\"".INSERT_ROWS."\" cols=\"".INSERT_COLS."\">\n</textarea><br />\n"
		 .$button
		 ."</div>\n"
		 ."</form>";

	$insert_no++;

	return $string;
}
?>