<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: rss.php,v 1.15 2005/01/29 03:13:54 nao-pon Exp $
/////////////////////////////////////////////////

// RecentChanges の RSS を出力
function catrss($rss,$page,$with_content="false",$list_count=0)
{
	global $rss_max,$page_title,$WikiName,$BracketName,$script,$trackback,$defaultpage,$use_static_url;
	global $vars,$post,$get;
	
	// 常にゲストユーザーとして処理
	global $X_admin,$X_uid;
	$X_admin =0;
	$X_uid =0;
	
	$catch_file = "";
	if ($list_count == 0)
	{
		$list_count = $rss_max;
		$catch_file = ($with_content && $rss == 2)? "1":"0";
		$catch_file = CACHE_DIR.encode(strip_bracket($page)).".rss".$rss.$catch_file;
	}
	
	header("Content-type: application/xml");
	
	if (file_exists($catch_file))
	{
		echo str_replace('<XOOPS_WIKI_URL>', XOOPS_WIKI_URL , join('',file($catch_file)));
		return;
	}
	$lines = get_existpages(false,$page,$list_count," ORDER BY editedtime DESC",true);

	$up_page = ($page)? $page : $defaultpage;
	if ($page)
	{
		if ($use_static_url)
		{
			$lpgid = get_pgid_by_name($up_page);
			$linkpage = '<XOOPS_WIKI_URL>'."/".$lpgid.".html";
		}
		else
			$linkpage = '<XOOPS_WIKI_URL>'."/?".rawurlencode($up_page);
	}
	else
	{
		$linkpage = '<XOOPS_WIKI_URL>'."/";
	}
	
	$description = htmlspecialchars(mb_convert_encoding(get_heading($up_page),"UTF-8",SOURCE_ENCODING));
	
	$page_title_utf8 = $page_title;
	$page_title_utf8 = mb_convert_encoding($page_title_utf8,"UTF-8",SOURCE_ENCODING);
	$page_utf8 = mb_convert_encoding(strip_bracket($page),"UTF-8",SOURCE_ENCODING);
	$page_add_utf8 = ($page)? "-".$page_utf8 : "";
	
	$item = "";
	$rdf_li = "";
	foreach($lines as $line)
	{
		$title = strip_bracket($line);
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$title,$reg_title)){
			$title = $reg_title[1].get_heading($line);
		}
		$title = mb_convert_encoding($title,"UTF-8",SOURCE_ENCODING);
		
		$url = strip_bracket($line);
		if ($page) $title = preg_replace("/^".preg_quote($page_utf8,"/")."\//","",$title);
		$title = htmlspecialchars($title);

//		$dcdate = substr_replace(date("Y-m-d\TH:i:sO"),':',-2,0);
		$desc = date("D, d M Y H:i:s T",filemtime(get_filename(encode($line))));
		$dcdate =  substr_replace(date("Y-m-d\TH:i:sO",filemtime(get_filename(encode($line)))),':',-2,0);
		
		if ($use_static_url){
			$pgid = get_pgid_by_name($line);
			$link_url = '<XOOPS_WIKI_URL>'."/".$pgid.".html";
		}
		else
			$link_url = '<XOOPS_WIKI_URL>'."/?".rawurlencode($url);
		
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
			$vars["page"] = $post["page"] = $get["page"] = $line;
			$content = convert_html($line,false,true,true);
			$desc=mb_convert_encoding(mb_substr(strip_htmltag($content),0,250,SOURCE_ENCODING),"UTF-8",SOURCE_ENCODING);
			$desc=htmlspecialchars($desc."...");
			$items.= " <description>$desc</description>\n";
			if($with_content=="true")
			{
//				$content = ereg_replace("\<form.*\/form\>","",$content);
//				$content = mb_ereg_replace("(\<form action=\")http://.*(\" method)","\\1#\\2",$content);
				$content = preg_replace("/<(script|meta|embed|object).*?<\/\\1>/is","",$content);
				$content = preg_replace("/\s*onMouseO(ver|ut)=\"[^\"]*\"/i","",$content);
				$content = mb_convert_encoding($content,"UTF-8",SOURCE_ENCODING);
				$items.= "<content:encoded>\n<![CDATA[\n";
				$items.= $content."\n";
				$items.= "]]>\n</content:encoded>\n";
			}
			//trackback
			if ($trackback)
			{
				$dc_identifier = $trackback_ping = '';
				$r_page=rawurlencode($url);
				$tb_id = tb_get_id($url);
				$dc_identifier = " <dc:identifer>$link_url</dc:identifer>\n";
				$trackback_ping = " <trackback:ping>".'<XOOPS_WIKI_URL>'."/?plugin=tb&amp;tb_id=$tb_id</trackback:ping>\n";
				$items.=$dc_identifier . $trackback_ping;
			}
			foreach(get_source($line) as $_line)
			{
				if (preg_match("/#category\((.*),:([^,]*),(.*)\)/i",$_line,$cat)) {
					$cats = explode(",",$cat[3]);
					foreach($cats as $cat_item) {
						$subject = $cat[2].":".$cat_item;
						$subject = mb_convert_encoding($subject,"UTF-8",SOURCE_ENCODING);
						$items .= "<dc:subject>$subject</dc:subject>\n";
					}
					break;
				}
			}
		}

		$items.= "</item>\n\n";
		$rdf_li.= "    <rdf:li rdf:resource=\"$link_url\" />\n";
	}

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
		$ret = '<?xml version="1.0" encoding="utf-8"?>

<rdf:RDF 
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"';
		if($with_content=="true") {
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
			fputs($fp,$ret);
			fclose($fp);
		}
	}
	//出力
	echo str_replace('<XOOPS_WIKI_URL>', XOOPS_WIKI_URL , $ret);
}
?>