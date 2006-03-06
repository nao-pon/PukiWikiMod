<?php
// $Id: vote.inc.php,v 1.14 2006/03/06 06:20:30 nao-pon Exp $

function plugin_vote_init()
{
  $_plugin_vote_messages = array(
    '_vote_plugin_rank' => '順位',
    '_vote_plugin_choice' => '選択肢',
    '_vote_plugin_votes' => '投票',
    '_vote_plugin_deny' => '無効な値です',
    '_vote_plugin_bad' => '重複投票はできません',
    );
  set_plugin_messages($_plugin_vote_messages);
}

function plugin_vote_action()
{
	global $post,$vars,$script,$cols,$rows,$del_backup,$do_backup;
	global $_title_collided,$_msg_collided,$_title_updated;
	global $_vote_plugin_choice, $_vote_plugin_votes, $_vote_plugin_deny, $_vote_plugin_bad;

	if (preg_match("/^(#add|#(k)?sort|#notimestamp)(\[\d+\])?$/i",$post['vote_newitem']))
	{
		$retvars["msg"] = $_vote_plugin_deny;
		return $retvars;
	}
	
	$postdata_old  = get_source($post["refer"]);
	$vote_no = 0;
	$notimestamp = FALSE;
	$nomail = FALSE;

	// エスケープ＆文字実体参照へ
	//$post['vote_newitem'] = htmlspecialchars($post['vote_newitem']);
	//$post['vote_newitem'] = str_replace("&amp;","&",$post['vote_newitem']);
	//$post['vote_newitem'] = str_replace(",","&sbquo;",$post['vote_newitem']);
	$post['vote_newitem'] = str_replace("|","&#x7c;",$post['vote_newitem']);
	
	//表中改行を連結
	$postdata_old = join('',$postdata_old);
	$postdata_old = str_replace("->\n","->&br;",$postdata_old);
	$postdata_old = explode("\n",$postdata_old);
	foreach($postdata_old as $lines)
	{
		if ($lines{0} == "|")
			$_line = explode("|",$lines);
		else
			$_line = array($lines);

		$cell_line = array();
		foreach($_line as $__line)
		{
			$__line = explode("->&br;",$__line);
			$celldata = array();
			foreach($__line as $line)
			{
				$arg = array();
				if(preg_match("/^(.*)?#vote\((.*)\)(.*)$/i",$line,$arg))
				{
					$celltag = $arg[1];
					if ($celltag) $celltag = cell_format_tag_del($celltag);
					
					if(($vote_no == $post["vote_no"]) && !$celltag)
					{
						$args = $arg[2];
						// "と"で囲んだパラメータは、,を含む事ができるように
						// 制御文字へ置換
						$args = str_replace("\",\"","\x1d\x1c",$args);
						$args = str_replace(",\"","\x1c",$args);
						$args = str_replace("\",","\x1d",$args);
						$args = preg_replace("/^\"/","\x00\x1c",$args);
						$args = preg_replace("/\"$/","\x1d\x00",$args);
						// , を \x08 に変換
						$args = preg_replace("/(\x1c.*?\x1d)/e","str_replace(',','\x08','$1')",$args);
						// 制御文字を戻す
						$args = str_replace(array("\x00\x1c","\x1d\x00"),"",$args);
						$args = str_replace(array("\x1d\x1c","\x1c","\x1d"),",",$args);
						
						// 配列に格納
						$args = ($args !== '') ? explode(',',$args) : array();

						// \x08 を , に戻す
						$args = str_replace("\x08",",",$args);
						
						$lefts = empty($arg[3]) ? '' : $arg[3];
						$lastvote = "";
						$_add = FALSE;
						foreach($args as $item)
						{
							if(preg_match("/^#lastvote:(.+)$/",$item,$arg))
							{
								$lastvote = $arg[1];
								continue;
							}
							$match = array();
							if(preg_match("/^(.+)\[(\d+)\]$/",$item,$match))
							{
								$item = $match[1];
								$cnt = $match[2];
								$is_cmd = 0;
							}
							else
							{
								if (strtolower($item) == "#notimestamp")
								{
									$notimestamp = TRUE;
									$is_cmd = 1;
								}
								elseif (strtolower($item) == "#nomail")
								{
									$nomail = TRUE;
									$is_cmd = 1;
								}
								elseif (strtolower($item) == "#sort" || strtolower($item) == "#ksort")
									$is_cmd = 1;
								else
									$is_cmd = 0;

								$cnt = 0;
							}
							
							if (!$is_cmd)
							{
								$e_arg = encode($item);
								if ($item == $post['vote_newitem']) {
									$post['vote_newitem'] = "";
									$post["vote_$e_arg"] = $_vote_plugin_votes;
								}
								if (strtolower($item) == "#add" && $post['vote_newitem'] && strtolower($post['vote_newitem']) != "#add")
								{
									$item = $post['vote_newitem'];
									$cnt = 1;
									$notimestamp = $nomail = FALSE;
									$_add = TRUE;
									$thisvote = md5($item.$_SERVER["REMOTE_ADDR"]);
								}
								elseif($post["vote_$e_arg"]==$_vote_plugin_votes)
								{
									$thisvote = md5($item.$_SERVER["REMOTE_ADDR"]);
									if ($thisvote == $lastvote)
									{
										$retvars["msg"] = $_vote_plugin_bad;
										return $retvars;
									}
									$cnt++;
								}
								if ($cnt) $item .= '['.$cnt.']';
							}
							if (strpos($item,",") !== FALSE)
							{
								$item = '"'.$item.'"';
							}
							$votes[] = $item;
							if ($_add)
							{
								$votes[] = "#add";
								$_add = FALSE;
							}
						}

						$vote_str = "$arg[1]#vote(" . "#lastvote:" . $thisvote .",". @join(",",$votes) . ")" . $lefts;

						$postdata_input = $vote_str;
						$celldata[] = $vote_str;
					}
					else
						$celldata[] = $line;

					if (!$celltag) $vote_no++;
				}
				else
					$celldata[] = $line;

			}
			$cell_line[] = join("->&br;",$celldata);
		}
		$postdata .= join("|",$cell_line)."\n";
	}

	//行中改行連結解除
	$postdata = str_replace("->&br;","->\n",rtrim($postdata));

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
		if(is_page($post["refer"]))
			$oldpostdata = join('',get_source($post["refer"]));
		else
			$oldpostdata = "\n";
		if($postdata)
			$diffdata = do_diff($oldpostdata,$postdata);
		file_write(DIFF_DIR,$post["refer"],$diffdata);

		if(is_page($post["refer"]))
			$oldposttime = filemtime(get_filename(encode($post["refer"])));
		else
			$oldposttime = time();
		$mail_op = ($nomail)? "nomail" : array('plugin'=>'vote','mode'=>'del&add');
		page_write($post["refer"],$postdata,$notimestamp,"","","","","","",$mail_op);

		$title = $_title_updated;
	}

	$retvars["msg"] = $title;
	$retvars["body"] = $body;

	$post["page"] = $post["refer"];
	$vars["page"] = $post["refer"];

	return $retvars;
}
function plugin_vote_convert()
{
	global $script,$vars,$digest;
	global $_vote_plugin_choice, $_vote_plugin_votes, $_vote_plugin_rank;
	static $vote_no = 0;

	$args = func_get_args();

	if(!func_num_args()) return FALSE;

	$tdcnt = 0;
	$lines = $s_items = $items = $cnts = array();
	$line = $sort = $ksort = $add = 0;
	
	foreach($args as $arg)
	{
		if (substr($arg,0,10) == "#lastvote:") continue;
		if (strtolower($arg) == "#nomail") continue;
		if (strtolower($arg) == "#sort") $sort = 1;
		elseif (strtolower($arg) == "#ksort") $ksort = 1;
		elseif (strtolower($arg) != "#notimestamp")
		{
			$match = array();
			if(preg_match("/^(.+)\[(\d+)\]$/",$arg,$match))
			{
				$arg = $match[1];
				$cnt = $match[2];
			}
			else
				$cnt = 0;
			
			if (strtolower($arg) == "#add")
			{
				$addcnt = $cnt;
				$add = 1;
			}
			else
			{
				$lines[] = $line;
				$items[] = $arg;
				$links[] = $_item = make_link($arg);
				$s_items[] = strip_tags($_item);
				$cnts[] = $cnt;
				$line ++;
			}
		}
		if ($sort && $ksort) 
			array_multisort (	$cnts,SORT_NUMERIC, SORT_DESC,
												$s_items,SORT_REGULAR, SORT_ASC,
												$lines,SORT_NUMERIC, SORT_ASC,
												$items,$links);
		elseif ($sort)
			array_multisort (	$cnts,SORT_NUMERIC, SORT_DESC,
												$lines,SORT_NUMERIC, SORT_ASC,
												$s_items,SORT_REGULAR, SORT_ASC,
												$items,$links);
		elseif ($ksort)
			array_multisort (	$s_items,SORT_REGULAR, SORT_ASC,
												$lines,SORT_NUMERIC, SORT_ASC,
												$cnts,SORT_NUMERIC, SORT_DESC,
												$items,$links);
	}

	$count_label = ($sort)? "<td align=\"left\" class=\"vote_label\" style=\"padding-left:1em;padding-right:1em\"><strong>$_vote_plugin_rank</strong>" : "";
	$string = ""
		. "<form action=\"$script\" method=\"post\">\n"
 		. "<table cellspacing=\"0\" cellpadding=\"2\" class=\"style_table\">\n"
 		. "<tr>\n"
 		. $count_label
 		. "<td align=\"left\" class=\"vote_label\" style=\"padding-left:1em;padding-right:1em\"><strong>$_vote_plugin_choice</strong>"
		. "<input type=\"hidden\" name=\"plugin\" value=\"vote\" />\n"
		. "<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\" />\n"
		. "<input type=\"hidden\" name=\"vote_no\" value=\"".htmlspecialchars($vote_no)."\" />\n"
		. "<input type=\"hidden\" name=\"digest\" value=\"".htmlspecialchars($digest)."\" />\n"
		. "</td>\n"
		. "<td align=\"center\" class=\"vote_label\"><strong>$_vote_plugin_votes</strong></td>\n"
		. "</tr>\n";

	$line = 0;
	$cnt_tag = "";
	$bef_point = 0;
	foreach($items as $arg)
	{
		$cnt = $cnts[$line];
		$link = $links[$line];
		$e_arg = encode($arg);

		if($tdcnt++ % 2) $cls = "vote_td1";
		else           $cls = "vote_td2";
		
		$cnt_point = ($cnt != $bef_point)? $tdcnt:"&middot;";
		$bef_point = $cnt;
		if ($sort) $cnt_tag = "<td align=\"center\" class=\"$cls\" nowrap=\"nowrap\">$cnt_point</td>";
		
		$string .= "<tr>".$cnt_tag
			.  "<td align=\"left\" class=\"$cls\" style=\"padding-left:1em;padding-right:1em;\">$link</td>"
			.  "<td align=\"right\" class=\"$cls\" nowrap=\"nowrap\">$cnt&nbsp;&nbsp;<input type=\"submit\" name=\"vote_".htmlspecialchars($e_arg)."\" value=\"$_vote_plugin_votes\" class=\"submit\" /></td>"
			.  "</tr>\n";
		$line ++;
	}
	if ($add){
		if($tdcnt++ % 2) $cls = "vote_td1";
		else           $cls = "vote_td2";
		
		if ($sort) $cnt_tag = "<td align=\"center\" class=\"$cls\" nowrap=\"nowrap\">New!</td>";
		
		if (!$addcnt) $addcnt = 30;
		
		$string .= "<tr>".$cnt_tag
			.  "<td align=\"left\" class=\"$cls\" style=\"padding-left:1em;padding-right:1em;\"><input type=\"text\" name=\"vote_newitem\" size=\"$addcnt\"/></td>"
			.  "<td align=\"right\" class=\"$cls\" nowrap=\"nowrap\">0&nbsp;&nbsp;<input type=\"submit\" name=\"vote_\" value=\"$_vote_plugin_votes\" class=\"submit\" /></td>"
			.  "</tr>\n";
	}

	$string .= "</table></form>\n";

	$vote_no++;

	return $string;
}
?>