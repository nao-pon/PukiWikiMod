<?php
// $Id: google_sitemap.inc.php,v 1.5 2006/09/19 07:35:19 nao-pon Exp $

function plugin_google_sitemap_action()
{
	global $xoopsDB, $vars, $google_sitemap_page;
	
	// 一つのサイトマップに表示するページ数
	$page_cnt = 1000;
	
	$where = " AND ((`vaids` LIKE '%all%') OR (`vgids` LIKE '%&3&%')) AND `editedtime` != 0 ";
	
	if (empty($vars['view']))
	{
		// index　ファイルの書き込み権限確認
		$cfile = XOOPS_WIKI_PATH."/".$google_sitemap_page.".xml";
		if (!is_writable($cfile))
		{
			// file.php から呼び出された場合
			if ($vars['need_return']) {return false;}
			
			$items = "<?xml version='1.0' encoding='UTF-8'?>\n";
			$items .= "<error>Please ";
			$tgfile = " a file (".XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/".$google_sitemap_page.".xml)";
			if (!file_exists($cfile))
			{
				$items .= "make{$tgfile}. And ";
				$tgfile = "";
			}
			
			$items .= "change permission{$tgfile} to writable by httpd.</error>\n";
		}
		else
		{		
			$query = "SELECT count(*) FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE (`name` NOT LIKE ':%')$where;";
			$res = $xoopsDB->query($query);
			if ($res)
			{
				list($count) = mysql_fetch_row($res);
				$pages = intval($count / $page_cnt) + 1;
				$items = "<?xml version='1.0' encoding='UTF-8'?>\n";
				$items .= '<sitemapindex xmlns="http://www.google.com/schemas/sitemap/0.84"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84
		http://www.google.com/schemas/sitemap/0.84/siteindex.xsd">'."\n";
				for ($i=1; $i<=$pages; $i++)
				{
					$time = gmdate('Y-m-d\TH:i:s+00:00');
	
					$items .= "<sitemap>\n";
					$items .= "<loc>".XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/?".$google_sitemap_page.$i."</loc>\n";
					$items .= "<lastmod>".$time."</lastmod>\n";
					$items .= "</sitemap>\n";
				}
				$items .= "</sitemapindex>\n";
			}
			// index ファイルの保存
			$ret = false;
			if ($fp = fopen($cfile, "wb"))
			{
				fputs($fp, $items);
				fclose($fp);
				$ret = true;
			}
			// file.php から呼び出された場合
			if ($vars['need_return']) {return $ret;}
		}
	}
	else
	{
		$view = (int)$vars['view'];
		$limit = ($view - 1) * $page_cnt;
		$limit = $limit . ", ".$page_cnt;

		$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." WHERE (`name` NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $limit;";
		$res = $xoopsDB->query($query);
		
		if ($res)
		{
			$date = $items = "";
			$cnt = 0;
			$items = "<?xml version='1.0' encoding='UTF-8'?>\n";
			$items .= '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84
	http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">'."\n";
			
			if ($view === 1)
			{
				$items .= "<url>\n";
				$items .= "<loc>";
				$items .= XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/";
				$items .= "</loc>\n";
				$items .= "<lastmod>";
				$items .= gmdate('Y-m-d\TH:i:s+00:00');
				$items .= "</lastmod>\n";
				$items .= "<changefreq>always</changefreq>\n";
				$items .= "<priority>1.0</priority>\n";
				$items .= "</url>\n";
				
				$items .= "<url>\n";
				$items .= "<loc>";
				$items .= XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/?RecentChanges";
				$items .= "</loc>\n";
				$items .= "<lastmod>";
				$items .= gmdate('Y-m-d\TH:i:s+00:00');
				$items .= "</lastmod>\n";
				$items .= "<changefreq>always</changefreq>\n";
				$items .= "<priority>0.9</priority>\n";
				$items .= "</url>\n";
			}

			while($data = mysql_fetch_row($res))
			{
				if (file_exists(P_CACHE_DIR.$data[0].".mcr"))
				{
					$data[3] = max($data[3], filemtime(P_CACHE_DIR.$data[0].".mcr"));
					//$data[3] = time();
				}
				$freq = (time() - $data[3]) / 3600;
				if ($freq < 1)
				{
					$freq = "hourly";
				}
				else if ($freq < 24)
				{
					$freq = "daily";
				}
				else if ($freq < 168)
				{
					$freq = "weekly";
				}
				//else if ($freq < 720)
				//{
				//	$freq = "monthly";
				//}
				else
				{
					$freq = "monthly";
				//	$freq = "yearly";
				}
				
				$items .= "<url>\n";
				$items .= "<loc>";
				$items .= XOOPS_WIKI_HOST.get_url_by_id($data[0]);
				$items .= "</loc>\n";
				$items .= "<lastmod>";
				$items .= gmdate('Y-m-d\TH:i:s+00:00',$data[3]);
				$items .= "</lastmod>\n";
				$items .= "<changefreq>{$freq}</changefreq>\n";
				$items .= "</url>\n";
			}
			$items .= "</urlset>\n";

		}
	}
	
	ob_end_clean();
	
	if (function_exists('mb_http_output'))
	{
		mb_http_output('pass');
	}
	
	header ('Content-Type: text/xml; charset=utf-8');
	echo $items;
	exit();
}
?>