<?php
// $Id: google_sitemap.inc.php,v 1.1 2005/12/18 14:10:47 nao-pon Exp $

function plugin_google_sitemap_action()
{
	global $xoopsDB, $vars, $google_sitemap_page;
	
	// 一つのサイトマップに表示するページ数
	$page_cnt = 1000;
	
	$where = " AND ((`vaids` LIKE '%all%') OR (`vgids` LIKE '%&3&%')) AND `editedtime` != 0 ";
	
	if (empty($vars['view']))
	{
		$query = "SELECT count(*) FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE (`name` NOT LIKE ':%')$where;";
		$res = $xoopsDB->query($query);
		//echo $query."<br>";
		if ($res)
		{
			list($count) = mysql_fetch_row($res);
			$pages = intval($count / $page_cnt) + 1;
			$items = '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.google.com/schemas/sitemap/0.84">';
			//$time = gmdate('Y-m-d\TH:i:s\Z');
			for ($i=1; $i<=$pages; $i++)
			{
				//$res = FALSE;
				//if ($i > 1)
				//{
				//	$limit = ($i - 1) * $page_cnt;
				//	$limit = $limit . ", 1";

				//	$query = "SELECT `editedtime` FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE (`name` NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $limit;";
				//	$res = $xoopsDB->query($query);
				//}
				//if ($res)
				//{
				//	list($time) = mysql_fetch_row($res);
				//	$time = gmdate('Y-m-d\TH:i:s\Z', $time);
				//}
				//else
				//{
					$time = gmdate('Y-m-d\TH:i:s\Z');
				//}

				$items .= "<sitemap>";
				$items .= "<loc>".XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/?".$google_sitemap_page.$i."</loc>";
				$items .= "<lastmod>".$time."</lastmod>";
				$items .= "</sitemap>";
			}
			$items .= "</sitemapindex>";
		}
	}
	else
	{
		$view = (int)$vars['view'];
		$limit = ($view - 1) * $page_cnt;
		$limit = $limit . ", ".$page_cnt;

		$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE (`name` NOT LIKE ':%')$where ORDER BY editedtime DESC LIMIT $limit;";
		$res = $xoopsDB->query($query);
		
		if ($res)
		{
			$date = $items = "";
			$cnt = 0;
			$items = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.google.com/schemas/sitemap/0.84">';
			
			if ($view === 1)
			{
				$items .= "<url>";
				$items .= "<loc>";
				$items .= XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/";
				$items .= "</loc>";
				$items .= "<lastmod>";
				$items .= gmdate('Y-m-d\TH:i:s\Z');
				$items .= "</lastmod>";
				$items .= "<changefreq>always</changefreq>";
				$items .= "<priority>1.0</priority>";
				$items .= "</url>";
				
				$items .= "<url>";
				$items .= "<loc>";
				$items .= XOOPS_WIKI_HOST.XOOPS_WIKI_URL."/?RecentChanges";
				$items .= "</loc>";
				$items .= "<lastmod>";
				$items .= gmdate('Y-m-d\TH:i:s\Z');
				$items .= "</lastmod>";
				$items .= "<changefreq>always</changefreq>";
				$items .= "<priority>0.9</priority>";
				$items .= "</url>";
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
				
				$items .= "<url>";
				$items .= "<loc>";
				$items .= XOOPS_WIKI_HOST.get_url_by_id($data[0]);
				$items .= "</loc>";
				$items .= "<lastmod>";
				$items .= gmdate('Y-m-d\TH:i:s\Z',$data[3]);
				$items .= "</lastmod>";
				$items .= "<changefreq>{$freq}</changefreq>";
				$items .= "</url>";
			}
			$items .= '</urlset>';

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