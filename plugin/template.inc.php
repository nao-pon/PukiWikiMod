<?php
// $Id: template.inc.php,v 1.5 2004/05/27 14:01:19 nao-pon Exp $

define("MAX_LEN",60);
function plugin_template_action()
{
	global $vars,$script,$non_list,$whatsnew,$_btn_template;
	
	global $script,$rows,$cols,$hr,$vars,$function_freeze,$WikiName,$BracketName;
	global $_btn_addtop,$_btn_preview,$_btn_update,$_btn_freeze,$_msg_help,$_btn_notchangetimestamp;
	global $whatsnew,$_btn_template,$_btn_load,$non_list,$load_template_func;

	$ret = "";
	
	// edit
	if($vars["refer"] && $vars["page"] && $vars["check"] && !is_page($vars["refer"]))
	{
		// ページ名がWikiNameでなく、BracketNameでなければBracketNameとして解釈
		if(!preg_match("/^(($WikiName)|($BracketName))$/",$vars["refer"]))
		{
			$vars["refer"] = "[[$vars[refer]]]";
		}
		
		$page = $vars["refer"];
		
		// ページ作成権限チェック
		$up_freeze_info = get_freezed_uppage($page);
		
		if (!WIKI_ALLOW_NEWPAGE || !check_readable($page,false,false) || $up_freeze_info[4])
		{
			$ret['body'] = $ret['msg'] = str_replace('$1',htmlspecialchars(strip_bracket($page)),_MD_PUKIWIKI_NO_AUTH);
			$vars["page"] = "";
			return $ret;
		}
		else
		{
			global $X_uid,$author_uid,$freeze_check;

			$lines = join('',get_source($vars["page"]));
			delete_page_info($lines);
			$lines = explode("\n",rtrim($lines));
			
			if($vars["begin"] <= $vars["end"])
			{
				for($i=$vars["begin"];$i<=$vars["end"];$i++)
				{
					$postdata.= $lines[$i]."\n";
				}
			}
			//ページ作成者
			$author_uid = $X_uid;
			$freeze_check = ($up_freeze_info[0])? "checked " : "";
			$vars["page"] = $vars["refer"];
			$vars["refer"] = "";
			
			$retvar["body"] = edit_form($postdata,$page,0,$up_freeze_info[3],$up_freeze_info[2]);
			
			$retvar["msg"] = strip_bracket($vars["page"])." の編集";
			
			return $retvar;
		}
	}
	// input mb_strwidth()
	else if($vars["refer"])
	{
		if(is_page($vars["refer"]))
		{
			
			$begin_select = "";
			$end_select = "";
			$lines = join("",get_source($vars["refer"]));
			delete_page_info($lines);
			$lines = explode("\n",rtrim($lines));
			$begin_select.= "開始行:<br /><select name=\"begin\" size=\"10\">\n";
			for($i=0;$i<count($lines);$i++)
			{
				$lines[$i] = mb_strimwidth($lines[$i],0,MAX_LEN,"...");
				
				if($i==0) $tag = "selected=\"selected\"";
				else      $tag = "";
				$begin_select.= "<option value=\"$i\" $tag>$lines[$i]</option>\n";
			}
			$begin_select.= "</select><br />\n<br />\n";
			
			$end_select.= "終了行:<br /><select name=\"end\" size=\"10\">\n";
			for($i=0;$i<count($lines);$i++)
			{
				if($i==count($lines)-1) $tag = "selected=\"selected\"";
				else                    $tag = "";
				$end_select.= "<option value=\"$i\" $tag>$lines[$i]</option>\n";
			}
			$end_select.= "</select><br />\n<br />\n";
			
			
		}
		$s_refer = htmlspecialchars($vars['refer']);
		$ret.= "<form action=\"$script\" method=\"post\">\n";
		$ret.= "<div>\n";
		$ret.= "<input type=\"hidden\" name=\"plugin\" value=\"template\" />\n";
		$ret.= "<input type=\"hidden\" name=\"page\" value=\"$s_refer\" />\n";
		//$ret.= "ページ名: <input type=\"text\" name=\"refer\" value=\"$s_refer/複製\" />\n";
		//$ret.= "<input type=\"submit\" name=\"submit\" value=\"作成\" /><br />\n<br />\n";
		$ret.= $begin_select;
		$ret.= $end_select;
		//$ret.= $select;
		$ret.= "ページ名: <input type=\"text\" size=\"50\" name=\"refer\" value=\"$s_refer/複製\" />\n";
		$ret.= "<input type=\"hidden\" name=\"check\" value=\"1\" />";
		$ret.= "<input type=\"submit\" name=\"submit\" value=\"作成\" />\n";
		$ret.= "</div>\n";
		$ret.= "</form>\n";
		
		$retvar["msg"] = "$1 をテンプレートにして作成";
		$retvar["body"] = $ret;
		
		return $retvar;
	}

}
?>
