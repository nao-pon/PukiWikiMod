<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: rss.php,v 1.23 2005/11/06 05:35:00 nao-pon Exp $
/////////////////////////////////////////////////

// RecentChanges の RSS を出力
function catrss($rss,$page,$with_content="",$list_count=0)
{
	global $rss_max,$page_title,$WikiName,$BracketName,$script,$trackback,$defaultpage;
	global $vars,$post,$get,$entity_pattern;
	
	// 常にゲストユーザーとして処理
	global $X_admin,$X_uid;
	$X_admin =0;
	$X_uid =0;
	
	$catch_file = "";
	if ($list_count == 0)
	{
		$list_count = $rss_max;
		$catch_file = 
		
		$catch_file = "0";
		if ($rss == 2)
		{
			if ($with_content)
			{
				$with_content = strtolower($with_content);
				if ($with_content == "s")
					$catch_file = "s";
				else
				{
					$with_content = $catch_file = "l";
				}
			}
		}
		
		$catch_file = CACHE_DIR.get_pgid_by_name($page).".rss".$rss.$catch_file;
	}
	
	header("Content-type: application/xml; charset=utf-8");
	
	if (file_exists($catch_file))
	{
		echo str_replace('<XOOPS_WIKI_URL>', XOOPS_WIKI_HOST.XOOPS_WIKI_URL , join('',file($catch_file)));
		return;
	}
	$lines = get_existpages(false,$page,$list_count," ORDER BY editedtime DESC",true);

	$up_page = ($page)? $page : $defaultpage;
	if ($page)
	{
		$linkpage = XOOPS_WIKI_HOST.get_url_by_name($up_page);
	}
	else
	{
		$linkpage = XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/";
	}
	
	//$description = htmlspecialchars(mb_convert_encoding(get_heading($up_page),"UTF-8",SOURCE_ENCODING));
	$description = mb_convert_encoding(get_heading($up_page),"UTF-8",SOURCE_ENCODING);
	
	$page_title_utf8 = $page_title;
	$page_title_utf8 = mb_convert_encoding($page_title_utf8,"UTF-8",SOURCE_ENCODING);
	$page_utf8 = mb_convert_encoding(strip_bracket($page),"UTF-8",SOURCE_ENCODING);
	$page_add_utf8 = ($page)? "-".$page_utf8 : "";
	
	$items = "";
	$rdf_li = "";
	$size_over = 0;
	$user = new XoopsUser();
	
	if ($rss=2)
	{
		$pcon = new pukiwiki_converter();
		$pcon->page_cvt = TRUE;
		$pcon->cache = TRUE;
	}
	
	foreach($lines as $line)
	{
		$title = strip_bracket($line);
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$title,$reg_title)){
			$title = $reg_title[1].get_heading($line);
		}
		
		$url = strip_bracket($line);
		if ($page) $title = preg_replace("/^".preg_quote($page_utf8,"/")."\//","",$title);
		$title = htmlspecialchars($title);

//		$dcdate = substr_replace(date("Y-m-d\TH:i:sO"),':',-2,0);
		$desc = date("D, d M Y H:i:s T",filemtime(get_filename(encode($line))));
		$dcdate =  substr_replace(date("Y-m-d\TH:i:sO",filemtime(get_filename(encode($line)))),':',-2,0);
		$pgid = get_pgid_by_name($line);
		$link_url = XOOPS_WIKI_HOST.get_url_by_id($pgid);
		
		if($rss==2)
			$items.= "<item rdf:about=\"".$link_url."\">\n";
		else
			$items.= "<item>\n";
		$items.= " <title>$title</title>\n";
		$items.= " <link>".$link_url."</link>\n";
		if($rss==2)
		{
			$items.= " <dc:date>$dcdate</dc:date>\n";
		}
		if($rss==1)
		{
			$items.= " <description>$desc</description>\n";
		}
		else
		{
		}
		if($rss==2)
		{
			// 新規追加行
			$addfile = DIFF_DIR."add_".$pgid.".cgi";
			$addtext = "";
			if (file_exists($addfile))
			{
				$addtext = trim(join('',file($addfile)));
				if ($addtext)
				{
					$addtext = strip_tags(str_replace(array("\r","\n"),"",$addtext));
					$addtext = preg_replace("/&$entity_pattern;/",'',$addtext);
					//$addtext = htmlspecialchars($addtext);
				}
			}
			
			$vars["page"] = $post["page"] = $get["page"] = $line;
			
			//$content = convert_html($line,false,true,true);
			
			$pcon->string = $line;
			$content = $pcon->convert();
			
			if (strlen($addtext) < 250)
			{
				if ($addtext) $addtext .= "...";
				$vars["page"] = $post["page"] = $get["page"] = $line;
				$desc = strip_tags(str_replace(array("\r","\n"),"",$content));
				$desc = preg_replace("/&$entity_pattern;/",'',$desc);
				//$desc = $addtext.htmlspecialchars($desc);
			}
			else
			{
				$desc = $addtext;
			}
			$desc = mb_substr($desc,0,250,SOURCE_ENCODING);
			$desc .= (strlen($desc) > 250)? "..." : "";
			
			
			$items.= " <description>".htmlspecialchars($desc)."</description>\n";
			if($with_content && !$size_over)
			{
				if ($with_content == "l")
				{
					$content = preg_replace("/(<form.*?action=(\"|')?)[^ ]*?\\2/is","$1#$2",$content);
					$content = preg_replace("/<(script|meta|embed|object).*?<\/\\1>/is","",$content);
					$content = preg_replace("/\s*onMouseO(ver|ut)=\"[^\"]*\"/i","",$content);
				}
				else if ($with_content == "s")
				{
					$content = nl2br(trim(preg_replace("/\s*[\r\n]+/","\n",strip_tags($content))));
				}
				$items.= "<content:encoded>\n<![CDATA[\n";
				$items.= $content."\n";
				$items.= "]]>\n</content:encoded>\n";
			}
			//trackback
			if ($trackback)
			{
				$dc_identifier = $trackback_ping = '';
				$dc_identifier = " <dc:identifier>$link_url</dc:identifier>\n";
				//$trackback_ping = " <trackback:ping>".tb_get_my_tb_url($pgid)."</trackback:ping>\n";
				$items.= $dc_identifier . $trackback_ping;
			}
			
			//author
			$pginfo = get_pg_info_db($line);
			$pg_auther_name= htmlspecialchars($user->getUnameFromId($pginfo['uid']));
			$last_editer = htmlspecialchars($user->getUnameFromId($pginfo['lastediter']));
			if ($pg_auther_name != $last_editer)
				$pg_auther_name .= ", ".$last_editer;
			$items.= "<dc:creator>$pg_auther_name</dc:creator>\n";

			foreach(get_source($line) as $_line)
			{
				if (preg_match("/#category\((.*),:([^,]*),(.*)\)/i",$_line,$cat)) {
					$cats = explode(",",$cat[3]);
					foreach($cats as $cat_item) {
						$subject = $cat[2].":".$cat_item;
						$items .= "<dc:subject>$subject</dc:subject>\n";
					}
					break;
				}
			}
		}

		$items.= "</item>\n\n";
		$rdf_li.= "    <rdf:li rdf:resource=\"$link_url\" />\n";
		
		// with_content = s の場合、サイズを制限 (70kbぐらいで with_content を抑止)
		if ($with_content == "s" && strlen($items) > 70000)
			$size_over = 1;
	}
	$items = mb_convert_encoding($items,"UTF-8",SOURCE_ENCODING);

	if($rss==1)
	{
		$ret = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN"
            "http://my.netscape.com/publish/formats/rss-0.91.dtd">

<rss version="0.91">

<channel>
<title>'.$page_title_utf8.$page_add_utf8.'</title>
<link>'.$linkpage.'</link>
<description>'.$description.'</description>
<language>ja</language>

'.$items.'
</channel>
</rss>';
	}
	else if($rss==2)
	{
		$ret = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" media="screen" href="'.XOOPS_WIKI_HOST.XOOPS_WIKI_URL.'/skin/rss.xml" ?>
<rdf:RDF 
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"';
		if($with_content) {
			$ret .= '
  xmlns:content="http://purl.org/rss/1.0/modules/content/"';
		}
		if($trackback) {
			$ret .= '
  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"';
		}
		$ret .= '
  xml:lang="ja">

 <channel rdf:about="'.$linkpage.'">
  <title>'.$page_title_utf8.$page_add_utf8.'</title>
  <link>'.$linkpage.'</link>
  <description>'.$description.'</description>
  <dc:date>'.substr_replace(date("Y-m-d\TH:i:sO"),':',-2,0).'</dc:date>
  <items>
   <rdf:Seq>
'.$rdf_li.'
   </rdf:Seq>
  </items>
 </channel>

'.$items.'
</rdf:RDF>';
	}
	$ret = input_filter($ret);
	
	//キャッシュ書き込み
	if ($catch_file)
	{
		if ($fp = @fopen($catch_file,"wb"))
		{
			fputs($fp,str_replace(XOOPS_WIKI_HOST.XOOPS_WIKI_URL , '<XOOPS_WIKI_URL>' , $ret));
			fclose($fp);
		}
	}
	//出力
	echo $ret;
}
?>