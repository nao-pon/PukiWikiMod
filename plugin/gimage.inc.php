<?php

function plugin_gimage_init()
{
	$data = array('plugin_gimage_dataset'=>array(
	'cache_time'    => 24,                                  // キャッシュ有効時間(h)
	'head_msg'      => '<h4>イメージ検索結果(%s): %s <span class="small">from WWW</span></h4><p class="empty"></p>',
	'research'      => 'さらに Google で探す',
	'err_noresult'  => 'Google では見つかりませんでした。',
	'err_noconnect' => 'Google に接続できませんでした。',
	));
	set_plugin_messages($data);
}

function plugin_gimage_convert()
{
	global $plugin_gimage_dataset;
	
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
	
	return "<div>".sprintf($plugin_gimage_dataset['head_msg'],$_qmode,htmlspecialchars($query)).plugin_gimage_search($query,$qmode)."</div>";
}

function plugin_gimage_search($q,$qmode)
{
	global $plugin_gimage_dataset;

	// キャッシュ有効時間(h)
	$cache_time = $plugin_gimage_dataset['cache_time'];
	
	// キャッシュファイル名
	$c_file = P_CACHE_DIR.md5($q.$qmode).".ggi";

	if (file_exists($c_file) && $cache_time * 3600 > time() - filemtime($c_file))
	{
		return join('',file($c_file));
	}
	else
	{
		//$file = "http://images.google.co.jp/images?hl=ja&inlang=ja&lr=&ie=EUC-JP&oe=EUC-JP&{$safe}&c2coff=1&q=".rawurlencode($q);
		$qmode = ($qmode)? "op_q_or" : "q" ;
		//$q = str_replace("・","",$q);
		$file = "http://cgi.search.biglobe.ne.jp/cgi-bin/pict/search?{$qmode}=".rawurlencode($q);
		$result = http_request($file);
		if ($result['rc'] != 200)
			return  $plugin_gimage_dataset['err_noconnect'];
		$res = $result['data'];
	}
	
	if (!$res) return $plugin_gimage_dataset['err_noresult'];

	//preg_match_all("/<a href=[^<]+<img src=\/image[^>]+><\/a>/",$res,$imgs,PREG_SET_ORDER);
	$imgtag = preg_quote('<IMG SRC="http://images-partners.google.com/images?q=',"/");
	preg_match_all("/<A HREF[^<]+".$imgtag."[^>]+><\/A>/",$res,$imgs,PREG_SET_ORDER);
	preg_match_all("/([\d]+\s?x\s?[\d]+\s?)ピクセル-\s?[\d]+k/",$res,$infos,PREG_SET_ORDER);
	$ret = "<table><tr>";
	$cnt = 0;
	$col = 5;
	$width = 100 / $col;
	foreach($imgs as $img)
	{
		if ($cnt % $col === 0) $ret .= "</tr><tr>";
		
		$size = $info = "";
		if ($infos[$cnt][0])
		{
			$info = " (".$infos[$cnt][0].")";
			$size = $infos[$cnt][1]."<br />";
		}
		
		$link = "";
		if (preg_match("/".$imgtag."tbn\:[^\:]+\:([^\"]+)/",$img[0],$tgt))
		{
			$fname = preg_replace("/.+\/([^\/]+)$/","$1",$tgt[1]);
			$fname_s = substr($fname,-12);
			if ($fname != $fname_s) $fname_s = "..".$fname_s;
			$link = "<br /><a href=\"{$tgt[1]}\" title=\"{$fname}{$info}\" target=\"_blank\"><small>".$size.$fname_s."</small></a>";
		}
		
		$ret .= "<td style=\"text-align:center;vertical-align:middle;width={$width}%;padding:1px;\">";
		$ret .= str_replace(
		array(
			"<A HREF",
			"</A>",
			"<IMG SRC=\"http://images-partners.google.com/images",
			"WIDTH",
			"HEIGHT",
		),
		array(
	 		"<a target=\"_blank\" href",
	 		"</a>",
	 		"<img alt=\"To Site.{$info}\" src=\"./images.php",
			"width",
			"height",
		),$img[0]);
	 	$ret .= $link;
		$ret .= "</td>";
		$cnt ++;
	}
	$ret .= "</tr></table>\n";
	$ret .= "<p><a href='http://images.google.co.jp/images?hl=ja&inlang=ja&lr=&ie=EUC-JP&oe=EUC-JP&safe=off&c2coff=1&q=".rawurlencode($q)."'>".$plugin_gimage_dataset['research']."</a></p>\n";
	
	// キャッシュ保存
	$fp = fopen($c_file, "wb");
	fwrite($fp, $ret);
	fclose($fp);
	
	// plane_text DB を更新を指示
	need_update_plaindb();
	
	return $ret;
}
?>