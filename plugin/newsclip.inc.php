<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: newsclip.inc.php,v 1.1 2004/07/31 06:48:05 nao-pon Exp $
//
//	 GNU/GPL �ˤ������ä����ۤ��롣
//

function plugin_newsclip_init()
{
	$data = array('plugin_newsclip_dataset'=>array(
	'cache_time'    => 1,                                  // ����å���ͭ������(h)
//	'def_max'       => 10,                                 // �ǥե����ɽ����
//	'max_limit'     => 50,                                 // ����ɽ����
	'head_msg'      => '<h4>�������: %s <span class="small">by NEWS������</span></h4><p class="empty"></p>',
	'research'      => 'goo �Ǥ���ˤ�õ��',
	'err_noresult'  => '%s�˴ؤ���˥塼���ϸ��Ĥ���ޤ���Ǥ�����',
	'err_noconnect' => 'NEWS������ ����³�Ǥ��ޤ���Ǥ�����',
	));
	set_plugin_messages($data);
}

function plugin_newsclip_split($_data)
{
	$arg = explode("<br />",$_data[1]);
	$data ="";
	$data .= "<div class=\"small\" style=\"text-align:right;\">".$arg[2]."</div>";
	$data .= "<p class=\"quotation\" style=\"margin-top:1px;\">".make_link($arg[0])."</p>";
	return $data;
}

function plugin_newsclip_convert()
{
	global $plugin_newsclip_dataset;
	
	$array = func_get_args();
	
	$word = "";
//	$def_max = $max = $plugin_newsclip_dataset['def_max'];
//	$max_limit = $plugin_newsclip_dataset['max_limit'];
	
	switch (func_num_args())
	{
		//case 2:
		//	$max = min($array[1],$max_limit);
		case 1:
			$word = trim($array[0]);
	}
	//if ($max < 1) $max = $def_max;
	
	// ����å���ͭ������(h)
	$cache_time = $plugin_newsclip_dataset['cache_time'];
	
	// ����å���ե�����̾
	$c_file = P_CACHE_DIR.md5($word).".ncp";

	if (file_exists($c_file) && $cache_time * 3600 > time() - filemtime($c_file))
	{
		$data = join('',file($c_file));
	}
	else
	{
		$r_word = rawurlencode($word);
		$goo = "http://news.goo.ne.jp";
		
		$target = $goo."/news/search/search.php?MT=".$r_word."&kind=web&day=all&web.x=44&web.y=14";
		
		$data = http_request($target);
		if ($data['rc'] !== 200)
		{
			if (file_exists($c_file))
				$data = join('',file($c_file));
			else
				return "<div>".$plugin_newsclip_dataset['err_noconnect']."(".$data['data'].")</div>";
		}
		$data = $data['data'];
			
		$data = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$data);
		
		// ���
		$data = (preg_match("/".preg_quote("<!--result_title-->","/")."(.+)".preg_quote("<!--/generalsearch_result-->","/")."/s",$data,$match))?
			$match[1] : "";

		//font,img����
		$data = preg_replace("/<\/?(font|img)[^>]*>/s","",$data);

		//table ����
		while(preg_match("/<table[^>]*>(?:(?!<table[^>]*>)(?!<\/table>).)*<\/table>/s",$data,$match))
		{
			$data = str_replace($match[0],"",$data);
		}

		// br->il
		$data = preg_replace("/<br>\d+\s((?:(?!<br>).)+)<br>/s","<li>$1",$data);
		
		//div
		$data = preg_replace("/<div[^>]*>/i","<p class=\"quotation\" style=\"margin-top:1px;\">",$data);
		$data = preg_replace("/<br><\/div>/i","</p></li>",$data);

		//a����
		$data = str_replace("<a href=\"/","<a target=\"_blank\" href=\"".$goo."/",$data);

		// br
		$data = preg_replace("/(^|\n|)(<br>)+(\n|$)/s","",$data);
		$data = str_replace("<br>","<br />",$data);
		
		//trim -> last
		$data = trim($data);
		if (!$data)
			$data = "<ul><li>".str_replace("%s",htmlspecialchars($word),$plugin_newsclip_dataset['err_noresult'])."</li></ul>";
		else
		{
			//����ʬ��
			$data = preg_replace_callback("/<p class=\"quotation\" style=\"margin-top:1px;\">(.+?)<\/p>/s","plugin_newsclip_split",$data);
			$data = "<ul>".$data."</ul>";
		}
		
		// ����å�����¸
		$fp = fopen($c_file, "wb");
		fwrite($fp, $data);
		fclose($fp);
		
		// plane_text DB �򹹿���ؼ�
		need_update_plaindb();
	}

	return "<div>".sprintf($plugin_newsclip_dataset['head_msg'],htmlspecialchars($word)).$data."</div>";
}
?>