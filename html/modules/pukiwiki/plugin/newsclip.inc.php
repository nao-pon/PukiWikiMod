<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: newsclip.inc.php,v 1.11 2006/07/08 01:33:44 nao-pon Exp $
//
//	 GNU/GPL にしたがって配布する。
//

function plugin_newsclip_init()
{
	$data = array('plugin_newsclip_dataset'=>array(
	'cache_time'    => 6,                                  // キャッシュ有効時間(h)
	'def_max'       => 10,                                 // デフォルト表示数
	'max_limit'     => 10,                                 // 最大表示数
	'head_msg'      => '<h4>検索結果: %s <span class="small">by NEWSサイト</span></h4><p class="empty"></p>',
	'research'      => 'goo でさらにで探す',
	'err_noresult'  => '%sに関するニュースは見つかりませんでした。',
	'err_noconnect' => 'NEWSサイト に接続できませんでした。',
	));
	set_plugin_messages($data);
}

function plugin_newsclip_split($_data)
{
	$arg = explode("<br />",$_data[1]);
	
	$data ="";
	$data .= "<div class=\"small\" style=\"text-align:right;\">".$arg[2]."</div>";
	$arg[0] = str_replace(array("&LT;","&lt;","&GT;","&gt;","&quot;","&QUOT;","&amp;","&AMP;"),array("<","<",">",">",'"','"',"&","&"),$arg[0]);
	$data .= "<p class=\"quotation\" style=\"margin-top:1px;\">".make_link($arg[0])."</p>";
	return $data;
}

function plugin_newsclip_action()
{
	global $get,$plugin_newsclip_dataset,$vars;
	
	
	if ($get['pmode'] == "refresh")
	{
		$word = (isset($get['q']))? $get['q'] : "";
		$page = (isset($get['ref']))? $get['ref'] : "";
		$vars['page'] = add_bracket($page);
		$vars['cmd'] = "read";
		
		// キャッシュファイル名
		$filename = P_CACHE_DIR.md5($word).".ncp";
		
		$old_time = filemtime($filename);

		if (!is_readable($filename) || time() - filemtime($filename) > $plugin_newsclip_dataset['cache_time'] * 3600 )
		{
			// 処理中に別スレッドが走らないように
			touch($filename);
			
			@list($ret,$refresh) = plugin_newsclip_get($word,TRUE);
			
			if ($ret)
			{
				// ページHTMLキャッシュを削除
				delete_page_html($page,"html");
			}
			else
			{
				// 失敗したのでタイムスタンプを戻す
				touch($filename,$old_time);
			}
		}
		exit;
	}
	
	return false;
}

function plugin_newsclip_convert()
{
	global $plugin_newsclip_dataset,$script,$vars;
	
	//$start = getmicrotime();
	
	$array = func_get_args();
	
	$word = "";
	$def_max = $max = $plugin_newsclip_dataset['def_max'];
	$max_limit = $plugin_newsclip_dataset['max_limit'];
	
	switch (func_num_args())
	{
		case 2:
			$max = min($array[1],$max_limit);
		case 1:
			$word = trim($array[0]);
	}
	if ($max < 1) $max = $def_max;

	@list($data,$refresh) = plugin_newsclip_get($word);
	
	// 指定件数切り出し
	$data = join("</li>\n",(array_slice(explode("</li>",$data),0,$max)));
	
	if ($refresh)
	{
		$vars['mc_refresh'][] = "?plugin=newsclip&pmode=refresh&ref=".rawurlencode(strip_bracket($vars["page"]))."&q=".rawurlencode($word);
	}

	//$taketime = "<div style=\"text-align:right;\">".sprintf("%01.03f",getmicrotime() - $start)."</div>";
	return "<div>".sprintf($plugin_newsclip_dataset['head_msg'],htmlspecialchars($word)).$data."</div>";
}

function plugin_newsclip_get($word,$do_refresh=FALSE)
{
	global $plugin_newsclip_dataset,$link_target;
	
	$data = "";
	$refresh = FALSE;
	
	// キャッシュ有効時間(h)
	$cache_time = $plugin_newsclip_dataset['cache_time'];
	
	// キャッシュファイル名
	$c_file = P_CACHE_DIR.md5($word).".ncp";

	if (!$do_refresh && file_exists($c_file))
	{
		$data = join('',file($c_file));
		if (time() - filemtime($c_file) > $cache_time * 3600)
		{
			$refresh = TRUE;
		}
	}
	
	if (!$data)
	{
		$r_word = rawurlencode($word);
		$goo = "http://news.goo.ne.jp";
		
		$target = $goo."/news/search/search.php?MT=".$r_word."&kind=web&day=all&web.x=44&web.y=14";
		
		$data = pkwk_http_request($target);
		if ($data['rc'] !== 200)
		{
			if (file_exists($c_file))
				$data = join('',file($c_file));
			else
				return "<div>".$plugin_newsclip_dataset['err_noconnect']."(".$data['data'].")</div>";
		}
		$data = $data['data'];
			
		$data = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$data);
		
		// 抽出
		$match = array();
		$data = (preg_match("/".preg_quote("<!--result_title-->","/")."(.+?)".preg_quote('<table border="0" cellpadding="3" cellspacing="0" width="100%">',"/")."/s",$data,$match))?
			$match[1] : "";

		//font,img除去
		$data = preg_replace("/<\/?(font|img)[^>]*>/s","",$data);

		//table 除去
		while(preg_match("/<table[^>]*>(?:(?!<table[^>]*>)(?!<\/table>).)*<\/table>/s",$data,$match))
		{
			$data = str_replace($match[0],"",$data);
		}
		
		//別ウィンドウ表示 除去
		$data = preg_replace('#\-\s*<a[^>]+>別ウィンドウ表示</a>\s*\-#is',"",$data);
		
		//NEW 除去
		$data = preg_replace('#\s*<b>NEW</b>\s*#i',"",$data);
		
		//(時間|日)前 除去
		$data = preg_replace('#\s*\d+(時間|日)前\s*#',"",$data);
		
		// br->il
		$data = preg_replace("/<br>\d+\s((?:(?!<br>).)+)<br>/s","<li>$1",$data);
		
		//div
		$data = preg_replace("/<div[^>]*>/i","<p class=\"quotation\" style=\"margin-top:1px;\">",$data);
		$data = preg_replace("/<br><\/div>/i","</p></li>",$data);

		//aタグ
		$data = str_replace("<a href=\"/","<a target=\"{$link_target}\" href=\"".$goo."/",$data);
		$data = str_replace("<a href=","<a target=\"{$link_target}\" href=",$data);
		
		//bタグ
		$data = str_replace(array("<B>","</B>"),"",$data);

		// br
		$data = preg_replace("/(^|\n|)(<br>)+(\n|$)/s","",$data);
		$data = str_replace("<br>","<br />",$data);
		
		//trim -> last
		$data = trim($data);
		
		if (!$data)
			$data = "<ul><li>".str_replace("%s",htmlspecialchars($word),$plugin_newsclip_dataset['err_noresult'])."</li></ul>";
		else
		{
			//内容分割
			$data = preg_replace_callback("/<p class=\"quotation\" style=\"margin-top:1px;\">(.+?)<\/p>/s","plugin_newsclip_split",$data);
			$data = "<ul>".$data."</ul>";
		}
		
		// キャッシュ保存
		$fp = fopen($c_file, "wb");
		fwrite($fp, $data);
		fclose($fp);
	}
	
	return array($data,$refresh);
}
?>