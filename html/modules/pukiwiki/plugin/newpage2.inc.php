<?php
//////////////////////////////////////////////////////////////////////
// newpage2.inc.php
// 指定した仮想ディレクトリ以下に新規ページを作るプラグイン
// newpage.inc.php の改造版です。 
//     modified by XZR rev.5 (2004030401)
// Based on
// $Id: newpage2.inc.php,v 1.4 2005/03/11 15:00:38 nao-pon Exp $
//////////////////////////////////////////////////////////////////////
// 引数：
// #newpage2(表示名,ディレクトリ,......)
// 奇数のn番目の引数：表示名
// （n+1）番目の引数：ディレクトリ
//  の組を並べます。thisを指定すると、現在のページ。 
//////////////////////////////////////////////////////////////////////


function plugin_newpage2_init()
{
	$_plugin_recent_messages = plugin_newpage2_retMsg(LANG);
	set_plugin_messages($_plugin_recent_messages);
}

function plugin_newpage2_retMsg($lang) {
	if($lang == 'ja') {
		$msg = array(
			'_msg_newpage2' => 'ページ新規作成',
			'_msg_newpage2_args_err' => '引数が正しくありません',
			'_msg_newpage2_this_page' => '現在のページ',
			'_msg_newpage2_under_this' => '　以下に'
			);
	}
	else {
		$msg = array(
			'_msg_newpage2' => 'new page',
			'_msg_newpage2_args_err' => 'invalid args',
			'_msg_newpage2_this_page' => 'this page',
			'_msg_newpage2_under_this' => 'under... '
			);
	}
	return $msg;
}

function plugin_newpage2_convert()
{
	global $script,$vars,$_btn_edit,$_msg_newpage2, $_msg_newpage2_args_err, $_msg_newpage2_this_page, $_msg_newpage2_under_this;
	static $load = array();
	
	$pgid = get_pgid_by_name($vars['page']);
	if (isset($load[$pgid]))
		$load[$pgid]++;
	else
		$load[$pgid] = 0;
	
	$num = func_num_args();
	$tags = func_get_args();

	if($num%2 != 0) {
		return($_msg_newpage2_args_err);
	}

	$ret = "<div style=\"text-align:left;\">\n";
	$ret.= "<form action=\"$script\" method=\"post\">\n";
	$ret.= "<input type=\"hidden\" name=\"plugin\" value=\"newpage2\" />\n";
	$ret.= "<input type=\"hidden\" name=\"refer\" value=\"".$vars['page']."\" />\n";

	if($num != 0){
		if(LANG != 'ja') {
			$ret .= $_msg_newpage2_under_this;
		}
		for($i = 0 ; $i < $num ; $i++) {
			//$label = htmlspecialchars(trim(strip_tags($tags[$i++])));
			//$value = htmlspecialchars(trim(strip_tags($tags[$i])));
			$label = trim(strip_tags($tags[$i++]));
			$value = trim(strip_tags($tags[$i]));
			
			$ret .= "<input type=\"hidden\" name=\"args[]\" value=\"".htmlspecialchars($label)."\" />\n";
			$ret .= "<input type=\"hidden\" name=\"args[]\" value=\"".htmlspecialchars($value)."\" />\n";

			
			if($label == "this") {
				$label = $_msg_newpage2_this_page;
				$value = $vars['page'];
			}

			if($value == "this") {
				$value = $vars['page'];
			}
			
			$value = strip_bracket($value);
			$value = htmlspecialchars($value);
			$id = md5($value)."_{$load[$pgid]}_{$pgid}";
			$label = "<label for=\"_p_newpage2_prefix_{$id}\">".htmlspecialchars($label)."</label>";
			
			if($i == 1) {
				$ret.= "<input type=\"radio\" id=\"_p_newpage2_prefix_{$id}\" name=\"prefix\" value=\"".$value."\" checked />".$label."\n";
			}
			else {
				$ret.= "<input type=\"radio\" id=\"_p_newpage2_prefix_{$id}\" name=\"prefix\" value=\"".$value."\" />".$label."\n";
			}
		}
		if(LANG == 'ja') {
			$ret.= $_msg_newpage2_under_this;
		}
	}

	$ret.= "<br>\n";
	$ret.= $_msg_newpage2.":<input type=\"text\" name=\"page\" size=\"30\" value=\"\" />\n";
	$ret.= "<input type=\"submit\" value=\"$_btn_edit\" />\n";
	$ret.= "</form>\n";
	$ret.= "</div>\n";
	$ret.= "<br>\n";

	return $ret;
}

function plugin_newpage2_action()
{
	global $script,$vars,$_btn_edit,$_msg_newpage2, $_msg_newpage2_args_err, $_msg_newpage2_this_page, $_msg_newpage2_under_this;

	if(!$vars["page"]) {
		$tags = $vars['args'];
		$num = count($tags);

		if($num%2 != 0) {
			return($_msg_newpage2_args_err);
		}

		$retvars["msg"] = $_msg_newpage2;

		$retvars["body"]  = "<div style=\"text-align:left;\">\n";
		$retvars["body"] .= "<form action=\"$script\" method=\"post\">\n";
		$retvars["body"] .= "<input type=\"hidden\" name=\"plugin\" value=\"newpage2\" />\n";
		$retvars["body"] .= "<input type=\"hidden\" name=\"refer\" value=\"".$vars['page']."\" />\n";

		if($num != 0){
			if(LANG != 'ja') {
				$retvars["body"] = $_msg_newpage2_under_this.$retvars["body"];
			}
			for($i = 0 ; $i < $num ; $i++)
			{
				$label = htmlspecialchars(trim(strip_tags($tags[$i++])));
				$value = htmlspecialchars(trim(strip_tags($tags[$i])));
				$retvars["body"] .= "<input type=\"hidden\" name=\"args[]\" value=\"".htmlspecialchars($label)."\" />\n";
				$retvars["body"] .= "<input type=\"hidden\" name=\"args[]\" value=\"".htmlspecialchars($value)."\" />\n";
				$id = md5($value);
				$label = "<label for=\"prefix_{$id}\">".$label."</label>";

				if($i == 1) {
					$retvars["body"] .= "<input type=\"radio\" id=\"prefix_{$id}\" name=\"prefix\" value=\"".$value."\" checked/>".$label."\n";
				}
				else {
					$retvars["body"] .= "<input type=\"radio\" id=\"prefix_{$id}\" name=\"prefix\" value=\"".$value."\" />".$label."\n";
				}
			}
			if(LANG == 'ja') {
				$retvars["body"] .= $_msg_newpage2_under_this;
			}

		}
		$retvars["body"] .= "<br>\n";
		$retvars["body"] .= $_msg_newpage2."：<input type=\"text\" name=\"page\" size=\"30\" value=\"\" />\n";
		$retvars["body"] .= "<input type=\"submit\" value=\"$_btn_edit\" />\n";
		$retvars["body"] .= "</form>\n";
		$retvars["body"] .= "</div>\n";
		$retvars["body"] .= "<br>\n";

		return $retvars;
	}

	if($vars["prefix"]) {
		$vars["prefix"] = ereg_replace("/*$", '', $vars["prefix"]);
		$vars["page"] = $vars["prefix"].'/'.strip_bracket($vars["page"]);
	}

	//if(!preg_match("/^($BracketName)|($InterWikiName)$/",$vars["page"]))
	//{
	//	$vars["page"] = "[[$vars[page]]]";
	//}
	$vars["page"] = add_bracket($vars['page']);

	$wikiname = rawurlencode($vars["page"]);

	header("Location: $script?$wikiname");
	die();
}
?>