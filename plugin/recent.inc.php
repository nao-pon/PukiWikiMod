<?php
/*
 * PukiWiki 最新の?件を表示するプラグイン
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 * 
 * 変更履歴:
 *  2002.04.08: patさん、みのるさんの指摘により、リンク先が日本語の場合に
 *              化けるのを修正
 * 
 *  2002.06.17: plugin_recent_init()を設定
 *  2002.07.02: <ul>による出力に変更し構造化
 *
 * $id$
 */

function plugin_recent_init()
{
	if (LANG == "ja") {
		$_plugin_recent_messages = array(
    '_recent_plugin_frame '=>'<h5 class="side_label" style="margin:auto;margin-top:0px;margin-bottom:.5em">最新の%d件</h5><div class="small" style="margin-left:.8em;margin-right:.8em">%s</div>');
  } else {
		$_plugin_recent_messages = array(
    '_recent_plugin_frame '=>'<h5 class="side_label" style="margin:auto;margin-top:0px;margin-bottom:.5em">Recent(%d)</h5><div class="small" style="margin-left:.8em;margin-right:.8em">%s</div>');
	}
  set_plugin_messages($_plugin_recent_messages);
}

function plugin_recent_convert()
{
	global $_recent_plugin_frame;
	global $WikiName,$BracketName,$script,$whatsnew,$X_admin;
	
	$recent_lines = 10;
	if(func_num_args()>0) {
		$array = func_get_args();
		$recent_lines = $array[0];
	}
	
	//$lines = file(get_filename(encode($whatsnew)));
	$lines = get_source($whatsnew);
	$date = $items = "";
	$cnt = 0;
	$i = 1;
	//foreach($lines as $line)
	while (isset($lines[$i]))
	{
		if($cnt > $recent_lines - 1) break;
		
		list($auth['owner'],$auth['user'],$auth['group']) = split("\t",substr($lines[$i],3));
		$auth = preg_replace("/^.*:/","",$auth);
		
		if ($X_admin || get_readable($auth))
		{
			if(preg_match("/(($WikiName)|($BracketName))/",$lines[$i+1],$match))
			{
				$name = $match[1];
				if($match[2])
				{
					$title = $match[1];
				}
				else
				{
					$title = strip_bracket($match[1]);
	 			}
				if(preg_match("/([0-9]{4}-[0-9]{2}-[0-9]{2})/",$lines[$i+1],$match)) {
					if($date != $match[0]) {
						if($date != '') {
							$items .= "</ul>";
						}
						$items .= "<div class=\"recent_date\">".$match[0]."</div><ul class=\"recent_list\">";
						$date = $match[0];
					}
				}
				$title = htmlspecialchars($title);
				//$items .="<li><a href=\"".$script."?".rawurlencode($name)."\" title=\"$title ".get_pg_passage($name,false)."\">".$title."</a></li>\n";
				$items .="<li>".make_pagelink($name)."</a></li>\n";
				$cnt++;
			}
		}
		$i = $i + 2;
	}
	$items .="</ul>";
	return sprintf($_recent_plugin_frame,$cnt,$items);
}
?>
