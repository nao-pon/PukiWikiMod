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
		$col = (isset($get['c']))? min(10,(int)$get['c']) : 0;
		$row = (isset($get['r']))? min(10,(int)$get['r']) : 0;
		$page = (isset($get['ref']))? $get['ref'] : "";
		
		if (!$col) $col = 5;
		if (!$row) $row = 4;
		
		// キャッシュファイル名
		$filename = P_CACHE_DIR.md5($query.$qmode.$col.$row).".ggi";
		
		$old_time = filemtime($filename);

		if (!is_readable($filename) || time() - filemtime($filename) > $plugin_gimage_dataset['cache_time'] * 3600 )
		{
			// 処理中に別スレッドが走らないように
			touch($filename);
			
			@list($ret,$refresh) = plugin_gimage_search($query,$qmode,TRUE,$col,$row);
			
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
	$col = 5;
	$row = 4;
	
	$i = 0;
	$_array = array();
	foreach($array as $prm)
	{
		if (preg_match("/^c(?:ol)?:([\d]+)$/",$prm,$match))
		{
			$col = min(10,$match[1]);
		}
		else if (preg_match("/^r(?:ow)?:([\d]+)$/",$prm,$match))
		{
			$row = min(10,$match[1]);
		}
		else
		{
			$_array[] = $prm;
		}
	}
	$array = $_array;
	
	switch (count($array))
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
	
	list($ret,$refresh) = plugin_gimage_search($query,$qmode,FALSE,$col,$row);
	
	if ($refresh)
	{
		$vars['mc_refresh'][] = "?plugin=gimage&pmode=refresh&ref=".rawurlencode(strip_bracket($vars["page"]))."&q=".rawurlencode($query)."&m=".rawurlencode($qmode)."&c=".$col."&r=".$row;
	}
	
	//return "<div>".sprintf($plugin_gimage_dataset['head_msg'],$_qmode,htmlspecialchars($query)).$ret."</div>"."<div style=\"text-align:right;\">".sprintf("%01.03f",getmicrotime() - $start)."($refresh)</div>";
	return "<div class=\"plugin_gimage\">".sprintf($plugin_gimage_dataset['head_msg'],$_qmode,htmlspecialchars($query)).$ret."</div>";
}

function plugin_gimage_search($q,$qmode,$do_refresh=FALSE,$col=5,$row=4)
{
	global $plugin_gimage_dataset;
	
	$refresh = FALSE;
	$ret = "";

	// キャッシュ有効時間(h)
	$cache_time = $plugin_gimage_dataset['cache_time'];
	
	// キャッシュファイル名
	$c_file = P_CACHE_DIR.md5($q.$qmode.$col.$row).".ggi";

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
		//$file = "http://bsearch.goo.ne.jp/image.jsp?CAT=image&DC=20&MT=".rawurlencode($q);
		$url = "http://bsearch.goo.ne.jp/image.jsp?UI=web&TAB=web&DC=20&MT=".rawurlencode($q);
		$result = http_request($url);
		if ($result['rc'] != 200)
			return  $plugin_gimage_dataset['err_noconnect'];
		$res = $result['data'];

		if (!$res) return $plugin_gimage_dataset['err_noresult'];

		$images = array();
		
		if (preg_match_all("/<!\-\-a image\-\->(.+?)<!\-\-\/a image\-\->/is",$res,$dats,PREG_PATTERN_ORDER))
		{
			$dats[1] = array_slice($dats[1],0,$col * $row);
			foreach($dats[1] as $dat)
			{
				$image = array();
				//image
				if (preg_match("/<a href=\"imgdt\.jsp.+?サムネイル.+?<\/a>/is",$dat,$match))
				{
					$image['thumb'] = $match[0];
				}
				else
				{
					$image['thumb'] = "";
				}
				
				//filename
				if (preg_match("/<b>([^\.]+?\.([^\.]+?))<\/b>/is",$dat,$match))
				{
					$image['name'] = $match[1];
					//$image['exp'] = $match[2];
				}
				else
				{
					$image['name'] = "";
					//$image['exp'] = "";
				}
				$image['exp'] = "jpg";
				
				//info
				if (preg_match("/[\d]+×[\d]+\s+-\s+[\d]+KByte/is",$dat,$match))
				{
					$image['info'] = $match[0];
				}
				else
				{
					$image['info'] = "";
				}
				
				$images[] = $image;
			}
		}
		
		$ret = "<table><tr>";
		$cnt = 0;
		foreach($images as $image)
		{
			if ($cnt % $col === 0) $ret .= "</tr><tr>";
			
			$image['thumb'] = str_replace(
				array(
					"imgdt.jsp",
					"<img src=\"http://thumb1.goo.ne.jp/img/relay.php?",
				),
				array(
					"http://bsearch.goo.ne.jp/imgdt.jsp",
			 		"<img src=\"http://hypweb.net/xoops/modules/pukiwiki/images.php?exp=".$image['exp']."&",
				),$image['thumb']);
			
			$image['info'] = str_replace(
				array(
					"Byte",
				),
				array(
			 		"",
				),$image['info']);

			$ret .= "<td style=\"text-align:center;\">";
			$ret .= trim($image['thumb'])."<br /><small>".trim($image['name'])."<br />".trim($image['info'])."</small>";
			$ret .= "</td>";
			$cnt ++;
		}
		$ret .= "</tr></table>";
		
		$ret = str_replace("<a ","<A target=\"_blank\" ",$ret);
		
		$ret .= "<p><a href='".str_replace("&","&amp;",$url)."&amp;FR=".($col * $row + 1)."' target=\"_blank\">".$plugin_gimage_dataset['research']."</a></p>\n";
		
		// キャッシュ保存
		$fp = fopen($c_file, "wb");
		fwrite($fp, $ret);
		fclose($fp);
	}
	return array($ret,$refresh);
}
?>