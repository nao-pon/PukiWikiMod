<?php

function plugin_gimage_init()
{
	$data = array('plugin_gimage_dataset'=>array(
	'cache_time'    => 36,                                  // キャッシュ有効時間(h)
	'head_msg'      => '<h4>イメージ検索結果(%s): %s <span class="small">from WWW</span></h4><p class="empty"></p>',
	'research'      => 'さらに goo で探す',
	'err_noresult'  => 'Google では見つかりませんでした。',
	'err_noconnect' => 'Google に接続できませんでした。',
	));
	set_plugin_messages($data);
}

function plugin_gimage_action()
{
	global $get,$plugin_gimage_dataset;
	
	
	if ($get['pmode'] == "refresh")
	{
		$query = (isset($get['q']))? $get['q'] : "";
		$qmode = (isset($get['m']))? $get['m'] : "";
		$page = (isset($get['ref']))? $get['ref'] : "";
		
		// キャッシュファイル名
		$filename = P_CACHE_DIR.md5($query.$qmode).".ggi";
		
		$old_time = filemtime($filename);

		if (!is_readable($filename) || time() - filemtime($filename) > $plugin_gimage_dataset['cache_time'] * 3600 )
		{
			// 処理中に別スレッドが走らないように
			touch($filename);
			
			@list($ret,$refresh) = plugin_gimage_search($query,$qmode,TRUE);
			
			if ($ret)
			{
				// plane_text DB を更新
				need_update_plaindb($page);
				// ページHTMLキャッシュを削除
				delete_page_html($page,"html");
			}
			else
			{
				// 失敗したのでタイムスタンプを戻す
				touch($filename,$old_time);
			}
		}
		
		header("Content-Type: image/gif");
		readfile('image/transparent.gif');
		exit;
	}
	
	return false;
}

function plugin_gimage_convert()
{
	global $plugin_gimage_dataset,$script,$vars;
	
	//$start = getmicrotime();
	
	$array = func_get_args();
	
	$query = "";
	$qmode = 0;
	
	switch (func_num_args())
	{
		case 2:
			$qmode = trim($array[1]);
		case 1:
			$query = trim($array[0]);
	}
	
	if ($qmode)
	{
		$qmode = 1;
		$_qmode = "OR";
	}
	else
		$_qmode = "AND";
	
	list($ret,$refresh) = plugin_gimage_search($query,$qmode);
	
	if ($refresh)
	{
		$vars['mc_refresh'][] = "?plugin=gimage&pmode=refresh&ref=".rawurlencode(strip_bracket($vars["page"]))."&q=".rawurlencode($query)."&m=".rawurlencode($qmode);
	}
	
	//return "<div>".sprintf($plugin_gimage_dataset['head_msg'],$_qmode,htmlspecialchars($query)).$ret."</div>"."<div style=\"text-align:right;\">".sprintf("%01.03f",getmicrotime() - $start)."($refresh)</div>";
	return "<div class=\"plugin_gimage\">".sprintf($plugin_gimage_dataset['head_msg'],$_qmode,htmlspecialchars($query)).$ret."</div>";
}

function plugin_gimage_search($q,$qmode,$do_refresh=FALSE)
{
	global $plugin_gimage_dataset;
	
	$refresh = FALSE;
	$ret = "";

	// キャッシュ有効時間(h)
	$cache_time = $plugin_gimage_dataset['cache_time'];
	
	// キャッシュファイル名
	$c_file = P_CACHE_DIR.md5($q.$qmode).".ggi";

	if (!$do_refresh && is_readable($c_file))
	{
		$ret = join('',file($c_file));
		if (time() - filemtime($c_file) > $cache_time * 3600)
		{
			$refresh = TRUE;
			$ret = $ret;
		}
	}
	
	if (!$ret)
	{
		if ($qmode) $q = str_replace(" "," OR ",$q);
		$file = "http://bsearch.goo.ne.jp/image.jsp?CAT=image&DC=20&MT=".rawurlencode($q);
		$result = http_request($file);
		if ($result['rc'] != 200)
			return  $plugin_gimage_dataset['err_noconnect'];
		$res = $result['data'];

		if (!$res) return $plugin_gimage_dataset['err_noresult'];

		if (preg_match("#<!--loop-->(.+)<!--/loop-->#s",$res,$data))
		{
			preg_match_all("#<!--thumbnail-->(.+)<!--/thumbnail-->#sU",$data[1],$thumbs,PREG_PATTERN_ORDER);
			preg_match_all("#<!--title and attributes-->(.+)<!--/title and attributes-->#sU",$data[1],$titles,PREG_PATTERN_ORDER);
			
			preg_match_all("#<td[^>]+>(.+)</td>#sU",join('',$thumbs[1]),$thumbs,PREG_PATTERN_ORDER);
			$thumbs = $thumbs[1];
			
			preg_match_all("#<td[^>]+>(.+)</td>#sU",join('',$titles[1]),$titles,PREG_PATTERN_ORDER);
			$titles = $titles[1];
			
		}

		$ret = "<table><tr>";
		$cnt = 0;
		foreach($thumbs as $thumb)
		{
			if ($cnt % 5 === 0) $ret .= "</tr><tr>";
			$thumb = str_replace(
				array(
					"<img src=\"http://images-partners.google.com/images",
					"&client=nttx-images",
				),
				array(
			 		"<img src=\"./images.php",
			 		"",
				),$thumb);
			
			$title = str_replace(
				array(
					"<b>",
					"</b>",
				),
				array(
			 		"<font size=\"-1\">",
			 		"</font>",
				),$titles[$cnt]);

			$ret .= "<td style=\"text-align:center;\">";
			$ret .= trim($thumb)."<br />".trim($title);
			$ret .= "</td>";
			$cnt ++;
		}
		$ret .= "</tr></table>";
		
		$ret = str_replace("<a ","<a target=\"_blank\" ",$ret);
		
		$ret .= "<p><a href='".str_replace("&","&amp;",$file)."&amp;FR=20' target=\"_blank\">".$plugin_gimage_dataset['research']."</a></p>\n";
		
		// キャッシュ保存
		$fp = fopen($c_file, "wb");
		fwrite($fp, $ret);
		fclose($fp);
	}
	return array($ret,$refresh);
}
?>