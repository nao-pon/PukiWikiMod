<?php
// $Id: tb.inc.php,v 1.1 2003/10/31 12:22:59 nao-pon Exp $
/*
 * PukiWiki TrackBack �ץ������
 * (C) 2003, Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
 * License: GPL
 *
 * plugin_tb_action()   action
 * tb_save()            TrackBack Ping �ǡ�����¸(����)
 * tb_xml_msg($rc,$msg) XML ��̽���
 * tb_mode_rss($tb_id)  ?__mode=rss ����
 * tb_mode_view($tb_id) ?__mode=view ����
 */

function plugin_tb_action()
{
	global $script,$vars,$post,$trackback,$X_admin;
	
	// POST: cmd=delete �ǡ������
	if ($X_admin && $post['cmd']=="delete")
	{
		$i = 0;
		$del_urls = array();
		foreach($post['delete_check'] as $del_url)
		{
			$del_urls[] = addslashes($del_url);
		}
		tb_delete_url($del_urls,$post['tb_id']);
	}
	// POST: TrackBack Ping ����¸����
	if (!empty($post['url']))
	{
		tb_save();
	}
	if ($trackback and !empty($vars['__mode']) and !empty($vars['tb_id']))
	{
		switch ($vars['__mode'])
		{
			case 'rss':
				tb_mode_rss($vars['tb_id']);
				break;
			case 'view':
				tb_mode_view($vars['tb_id']);
				break;
		}
	}
	return array('msg'=>'','body'=>'');
}

// TrackBack Ping �ǡ�����¸(����)
function tb_save()
{
	global $script,$post,$vars,$trackback;
	static $fields = array(/* UTIME, */'url','title','excerpt','blog_name');
	
	// ���Ĥ��Ƥ��ʤ��Τ˸ƤФ줿�����б�
	if (!$trackback)
	{
		tb_xml_msg(1,'Feature inactive.');
	}
	// TrackBack Ping �ˤ����� URL �ѥ�᡼����ɬ�ܤǤ��롣
	if (empty($post['url']))
	{
		tb_xml_msg(1,'It is an indispensable parameter. URL is not set up.');
	}
	// Query String ������
	if (empty($vars['tb_id']))
	{
		tb_xml_msg(1,'TrackBack Ping URL is inaccurate.');
	}
	
	$url = $post['url'];
	$tb_id = $vars['tb_id'];
	
	// �ڡ���¸�ߥ����å�
	$page = tb_id2page($tb_id);
	if ($page === FALSE)
	{
		tb_xml_msg(1,'TrackBack ID is invalid.');
	}
	
	// URL �����������å� (����������Ƚ������֤����꤬�Ǥ�)
	$result = http_request($url,'HEAD');
	if ($result['rc'] !== 200)
	{
		tb_xml_msg(1,'URL is fictitious.');
	}
	
	// TRACKBACK_DIR ��¸�ߤȽ񤭹��߲�ǽ���γ�ǧ
	if (!file_exists(TRACKBACK_DIR))
	{
		tb_xml_msg(1,'No such directory');
	}
	if (!is_writable(TRACKBACK_DIR))
	{
		tb_xml_msg(1,'Permission denied');
	}
	
	$url = $title = $excerpt = $blog_name = "";
	
	// TrackBack Ping �Υǡ����򹹿�
	$filename = TRACKBACK_DIR.$tb_id.'.txt';
	$data = tb_get($filename);
	
	$items = array(UTIME);
	foreach ($fields as $field)
	{
		$value = array_key_exists($field,$post) ? $post[$field] : '';
		$value = preg_replace("/[ \t\r\n]+/",' ',$value);
		$$field = addslashes(mb_convert_encoding(trim($value), SOURCE_ENCODING, "AUTO"));
	}
	
	// DB�򹹿�
	global $xoopsDB,$HTTP_SERVER_VARS;

	$page_name = addslashes($page);
	$ip = $HTTP_SERVER_VARS["REMOTE_ADDR"];
	
	$query = "SELECT * FROM ".$xoopsDB->prefix("pukiwikimod_tb")." WHERE (url='$url' AND tb_id='$tb_id');";
	$result=$xoopsDB->query($query);

	if (mysql_num_rows($result))
	{
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_tb")." SET last_time=".UTIME.",title='$title',excerpt='$excerpt',blog_name='$blog_name',page_name='$page_name',ip='$ip' WHERE (url='$url' AND tb_id='$tb_id');";
		$result=$xoopsDB->queryF($query);
		if (!$result) tb_xml_msg(1,'MySQL ERROR !');
	}
	else
	{
		$query = "INSERT INTO ".$xoopsDB->prefix("pukiwikimod_tb")." (last_time,url,title,excerpt,blog_name,tb_id,page_name,ip) VALUES(".UTIME.",'$url','$title','$excerpt','$blog_name','$tb_id','$page_name','$ip');";
		$result=$xoopsDB->queryF($query);
		if (!$result) tb_xml_msg(1,'MySQL ERROR !');
	}
	
	if ($vars['__mode']=="view" || $vars['__mode']=="rss") return;
	tb_xml_msg(0,'');
}

// XML ��̽���
function tb_xml_msg($rc,$msg)
{
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="iso-8859-1"?>';
	echo <<<EOD

<response>
 <error>$rc</error>
 <message>$msg</message>
</response>
EOD;
	die;
}

// ?__mode=rss ����
function tb_mode_rss($tb_id)
{
	global $script,$vars,$entity_pattern;
	
	$page = tb_id2page($tb_id);
	if ($page === FALSE)
	{
		return FALSE;
	}
	
	$items = '';
	foreach (tb_get(TRACKBACK_DIR.$tb_id.'.txt') as $arr)
	{
		$utime = array_shift($arr);
		list ($url,$title,$excerpt,$blog_name) = array_map(
			create_function('$a','return htmlspecialchars($a);'),$arr);
		$items .= <<<EOD

   <item>
    <title>$title</title>
    <link>$url</link>
    <description>$excerpt</description>
   </item>
EOD;
	}
	
	$title = htmlspecialchars($page);
	$link = "$script?".rawurlencode($page);
	$vars['page'] = $page;
	$excerpt = strip_htmltag(convert_html(join('',get_source($page))));
	$excerpt = preg_replace("/&$entity_pattern;/",'',$excerpt);
	$excerpt = mb_strimwidth(preg_replace("/[\r\n]/",' ',$excerpt),0,255,'...');

	$rc = <<<EOD

<response>
 <error>0</error>
 <rss version="0.91">
  <channel>
   <title>$title</title>
   <link>$link</link>
   <description>$excerpt</description>
   <language>ja-Jp</language>$items
  </channel>
 </rss>
</response>
EOD;
	$rc = mb_convert_encoding($rc,'UTF-8',SOURCE_ENCODING);
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="utf-8" ?>';
	echo $rc;
	die;
}
// ?__mode=view ����
function tb_mode_view($tb_id)
{
	global $script,$page_title,$vars;
	global $_tb_title,$_tb_header,$_tb_entry,$_tb_refer,$_tb_date;
	global $_tb_header_Excerpt,$_tb_header_Weblog,$_tb_header_Tracked;
	global $X_admin;
	
	// TrackBack ID ����ڡ���̾�����
	$page = tb_id2page($tb_id);
	if ($page === FALSE)
	{
		return FALSE;
	}
	$r_page = rawurlencode($page);
	
	$tb_title = sprintf($_tb_title,$page);
	$tb_refer = sprintf($_tb_refer,"<a href=\"$script?$r_page\">'$page'</a>","<a href=\"$script\">$page_title</a>");

	
	//$data = tb_get(TRACKBACK_DIR.$tb_id.'.txt');
	$data = tb_get_db($tb_id);
	
	
	// �ǿ��Ǥ�������
	//usort($data,create_function('$a,$b','return $b[0] - $a[0];'));
	
	$tb_body = '';
	foreach ($data as $x)
	{
		list ($time,$url,$title,$excerpt,$blog_name,$dum,$dum,$ip) = $x;
		if ($title == '')
		{
			$title = 'no title';
		}
		$time = date($_tb_date, $time + LOCALZONE); // May 2, 2003 11:25 AM
		$del_form_tag = ($X_admin)? "<input type=\"checkbox\" name=\"delete_check[]\" value=\"$url\"/>" : "";
		$ip_tag = ($X_admin)? "<br />\n  <strong>IP:</strong> $ip" : "";
		$tb_body .= <<<EOD
<div class="trackback-body">
 <span class="trackback-post">
  $del_form_tag
  <a href="$url" target="new">$title</a><br />
  <strong>$_tb_header_Excerpt</strong> $excerpt<br />
  <strong>$_tb_header_Weblog</strong> $blog_name<br />
  <strong>$_tb_header_Tracked</strong> $time$ip_tag
 </span>
</div>
EOD;
	}
	
	if ($X_admin && $tb_body)
	{
		$tb_body = <<<EOD
<form method="post" action="$script">
<input type="hidden" name="plugin" value="tb" />
<input type="hidden" name="__mode" value="view" />
<input type="hidden" name="tb_id" value="$tb_id" />
<input type="hidden" name="cmd" value="delete" />
<input type="submit" value="�����å�������Τ���" />
$tb_body
</form>
EOD;
	}
	
	if (!$tb_body) $tb_body = <<<EOD
<div class="trackback-body">
 <span class="trackback-post">
	���Υ���ȥ꡼���Ф��ơ��������Τϡ��ޤ��Ϥ��Ƥ��ޤ���
 </span>
</div>
EOD;
	
	$sended_info = get_sended_pings($tb_id);
	$sended_ping = $sended_info['tag'];
	$sended_ping_num = $sended_info['count'];
	
	$tb_form = <<<EOD
  <form method="post" action="$script?plugin=tb&amp;__mode=view&amp;tb_id=$tb_id">
   <input type="hidden" name="plugin" value="tb" />
   <input type="hidden" name="__mode" value="view" />
   <input type="hidden" name="tb_id" value="$tb_id" />
    <table cellspacing="0" cellpadding="3" border="0">
      <tr>
        <td style="vertical-align:top;">
          <div id="banner-sendedping">��{$page}�פ�����������Ping ( {$sended_ping_num} )<a name="sended_ping">��</a></div>
          <div class="blog"><div class="trackback-url">$sended_ping</div></div>
        </td>
        <td align="center">
        <p><table border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td bgcolor="#99B380">
                  <table border="0" cellspacing="1" cellpadding="5" width="500">
                    <tr>
                      <td bgcolor="#bfcfaf" colspan=2><font size="+1" color="#3D3832"><b>��{$page}�פ� Ping ��ư����</b></font></td>
                    </tr>
                    <tr>
                      <td bgcolor="#bfcfaf"><font size="-1" color="#3D3832"><b>ping������URL</b></font></td>
                      <td bgcolor="#FFFFFF">$script?plugin=tb&amp;tb_id=$tb_id</td>
                    </tr>
                    <tr>
                      <td bgcolor="#bfcfaf"><font size="-1" color="#3D3832"><b>blog̾ (blog_name)</b></font></td>
                      <td bgcolor="#FFFFFF"><input type="text" name="blog_name" value="" size="50" maxlength="80"></td>
                    </tr>
                    <tr>
                      <td bgcolor="#bfcfaf"><font size="-1" color="#3D3832"><b>����URL (url)</b></font></td>
                      <td bgcolor="#FFFFFF"><input type="text" name="url" value="" size="50" maxlength="150"></td>
                    </tr>
                    <tr>
                      <td bgcolor="#bfcfaf"><font size="-1" color="#3D3832"><b>���������ȥ� (title)</b></font></td>
                      <td bgcolor="#FFFFFF"><input type="text" name="title" value="" size="50" maxlength="150"></td>
                    </tr>
                    <tr>
                      <td bgcolor="#bfcfaf"><font size="-1" color="#3D3832"><b>�������� (excerpt)</b></font></td>
                      <td bgcolor="#FFFFFF"><textarea name="excerpt" rows="5" cols="40" wrap="VIRTUAL"></textarea><br></td>
                    </tr>
                    <tr>
                      <td colspan=2><div class="trackback-post">��<a href="$script?{$r_page}">{$page}</a>�פε������Ƥ˸��ڤ���ĺ�������ˡ����Υե����फ�餽�Τ��Ȥ����Τ��뤳�Ȥ��Ǥ��ޤ���<br />TrackBack �� Ping ��ư������ǽ���ʤ����ˤ����Ѥ���������</div>
                      </td>
                    <tr/>
                  </table>
                </td>
              </tr>
            </table><br>
            <input type="submit" value="������"><br>
        </td>
      </tr>
    </table>
	</form>
EOD;
	$msg = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
 <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
 <title>$tb_title</title>
 <link rel="stylesheet" href="skin/trackback.css" type="text/css" />
</head>
<body>
 <div id="banner-commentspop">$_tb_header</div>
 <div class="blog">
  <div class="trackback-url">
   $_tb_entry<br />
   $script?plugin=tb&amp;tb_id=$tb_id<br /><br />
   $tb_refer
  </div>
  $tb_body
 </div>
 <div style="text-align:center;">
 $tb_form
 </div>
</body>
</html>
EOD;
	header("Content-Type: text/html;charset=UTF-8");
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	echo mb_convert_encoding($msg,'UTF-8',SOURCE_ENCODING);
	die;
}

function tb_delete_url($urls,$tb_id)
{
	global $xoopsDB;

	$where = " WHERE tb_id='$tb_id' AND (url='".join("' OR url='",$urls)."')";

	$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_tb")." $where;";
	$results=$xoopsDB->queryF($query);

}
?>