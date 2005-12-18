<?php
/**
 *
 * showrss �ץ饰����
 *
 * �饤���󥹤� PukiWiki ���Τ�Ʊ���� GNU General Public License (GPL) �Ǥ���
 * http://www.gnu.org/licenses/gpl.txt
 *
 * pukiwiki�ѤΥץ饰����Ǥ���
 * pukiwiki1.3.2�ʾ��ư���Ȼפ��ޤ���
 *
 * ���ΤȤ���ư����뤿��ˤ�PHP �� xml extension ��ɬ�ܤǤ���PHP���Ȥ߹��ޤ�Ƥʤ����Ϥ��ä��ʤ����顼���Ф�Ȼפ��ޤ���
 * ����ɽ�� or ʸ����ؿ��Ǥʤ�Ȥ��ʤ�ʤ���ʤ����ʤ�Ǥ������פäƤɤ줯�餤����Τ��狼�餤�Τ���α�Ǥ���
 * mbstring �⤢��ۤ��������Ǥ���
 *
 * �ʤ����ϡ� jcode.phps ����礳�äȤ����ä� mb_convert_encoding �Ȥ����ؿ���������Ƥ����ФȤꤢ��������äݤ��Ѵ��Ǥ��뤫��Ǥ���
 * http://www.spencernetwork.org/
 *
 * ��Ϣ����:
 * do3ob wiki   ->   http://do3ob.com/
 * email        ->   hiro_do3ob@yahoo.co.jp
 *
 * �����       ->   http://do3ob.s20.xrea.com/
 *
 * version: $Id: showrss.inc.php,v 1.22 2005/12/18 14:10:47 nao-pon Exp $
 *
 */

function plugin_showrss_init()
{
	global $nothanks_rss_url;
	
	// ��������URL(��������)������
	$nothanks_rss_url = array();
	
	if (file_exists(PLUGIN_DATA_DIR."showrss/config.php"))
	{
		//�ե����뤬������ɤ߹���
		include(PLUGIN_DATA_DIR."showrss/config.php");
	}
}

// showrss�ץ饰���󤬻��Ѳ�ǽ���ɤ�����ɽ��
function plugin_showrss_action()
{
	global $vars;
	
	$pmode = (!empty($vars['pmode']))? $vars['pmode'] : "";
	$target = (!empty($vars['tgt']))? $vars['tgt'] : "";
	$usecache = (!empty($vars['uc']))? $vars['uc'] : 1;
	$page = (!empty($vars['ref']))? $vars['ref'] : "";
	
	// ����å���ǡ�����������	
	if ($pmode == "refresh")
	{
		$filename = P_CACHE_DIR . md5($target) . '.srs';
		
		$old_time = filemtime($filename);
		
		if (!is_readable($filename) || time() - filemtime($filename) > $usecache * 60 * 60)
		{
			// ��������̥���åɤ�����ʤ��褦��
			touch($filename);
			
			// RSS����å���򹹿�
			@list($rss,$time,$refresh) = plugin_showrss_get_rss($target,$usecache,true);
			
			if ($rss)
			{
				// plane_text DB �򹹿�
				need_update_plaindb($page);
				// �ڡ���HTML����å������
				delete_page_html($page,"html");
			}
			else
			{
				// ���Ԥ����Τǥ����ॹ����פ��᤹
				touch($filename,$old_time);
			}
		}
		header("Content-Type: image/gif");
		readfile('image/transparent.gif');
		exit;
	}
	
	$xml_extension = extension_loaded('xml');
	$mbstring_extension = extension_loaded('mbstring');

	$xml_msg      = $xml_extension      ? 'xml extension is loaded' : 'COLOR(RED){xml extension is not loaded}';
	$mbstring_msg = $mbstring_extension ? 'mbstring extension is loaded' : 'COLOR(RED){mbstring extension is not loaded}';

	$showrss_info = '';
	$showrss_info .= "| xml parser | $xml_msg |\n";
	$showrss_info .= "| multibyte | $mbstring_msg |\n";

	return array('msg' => 'showrss_info', 'body' => convert_html($showrss_info));
}

function plugin_showrss_convert()
{
	global $script,$vars;
	
	//$start = getmicrotime();
	
	if (func_num_args() == 0)
	{
		// �������ʤ����ϥ��顼
		return "<p>showrss: no parameter(s).</p>\n";
	}
	if (!extension_loaded('xml'))
	{
		// xml ��ĥ��ǽ��ͭ���Ǥʤ���硣
		// http://www18.tok2.com/home/koumori27/xml/phpsax/phpsax_menu.html ����Ѥ����Ʊ�����ȤǤ����������ɥˡ������뤫�ʡ�
		return "<p>showrss: xml extension is not loaded</p>\n";
	}

	$array = func_get_args();
	$rssurl = $usetimestamp = $show_description = '';
	$usecache = 1;
	$tmplname = "menubar";
	$max = 10;

	switch (func_num_args())
	{
		case 6:
			$max = trim($array[5]);
		case 5:
			$show_description = trim($array[4]);
		case 4:
			$usetimestamp = trim($array[3]);
		case 3:
			$usecache = $array[2];
		case 2:
			$tmplname = strtolower(trim($array[1]));
		case 1:
			$rssurl = trim($array[0]);
	}

	// RSS �ѥ����ͥ����å�
	if (!is_url($rssurl))
	{
		return '<p><strong>showrss</strong>:syntax error. '.htmlspecialchars($rssurl)."</p>\n";
	}

	$class = "ShowRSS_html_$tmplname";
	if (!class_exists($class))
	{
		$class = 'ShowRSS_html';
	}
	

	@list($rss,$time,$refresh) = plugin_showrss_get_rss($rssurl,$usecache);

	if ($rss === FALSE)
	{
		return "<p><a href=\"{$rssurl}\" target=\"_blank\">showrss: cannot get rss from server.</a></p>\n";
	}
	
	$obj = new $class($rss,$show_description,$max);

	$timestamp = '';
	
	if ($refresh)
	{
		$vars['mc_refresh'][] = "?plugin=showrss&pmode=refresh&uc={$usecache}&ref=".rawurlencode(strip_bracket($vars["page"]))."&tgt=".rawurlencode($rssurl);
	}
	
	if ($usetimestamp > 0)
	{
		$time = get_date('Y/m/d H:i:s',$time);
		$timestamp .= "<p style=\"font-size:10px; font-weight:bold\">Last-Modified:$time</p>";
	}
	
	//$taketime = "<div style=\"text-align:right;\">".sprintf("%01.03f",getmicrotime() - $start)."</div>";
	return $obj->toString($timestamp);
}
// rss���󤫤�html����
class ShowRSS_html
{
	var $items = array();
	var $class = '';

	function ShowRSS_html($rss,$show_description="",$max=10)
	{
		global $nothanks_rss_url;

		$count = 1;
		foreach ($rss as $date=>$items)
		{
			if ($count > $max) break;
			foreach ($items as $item)
			{
				if ($count > $max) break;
				
				// ��������URL
				if ($nothanks_rss_url)
				{
					$flg_nothanks = false;
					foreach($nothanks_rss_url as $check_url)
					{
						//echo $check_url;
						if (preg_match("/^".preg_quote($check_url,"/")."/",$item['LINK']))
						{
							$flg_nothanks = true;
							break;
						}
					}
					if ($flg_nothanks) continue;
				}
				$count ++;
				
				$link = $item['LINK'];
				$title = str_replace(array("&lt;b&gt;","&lt;/b&gt;"),"",$item['TITLE']);
				$passage = get_passage($item['_TIMESTAMP']);
				if ($item['AG:SOURCE'])
					$title .= " [".$item['AG:SOURCE']."]";
				else if ($item['DC:PUBLISHER'])
					$title .= " [".$item['DC:PUBLISHER']."]";
				
				$link = "<a href=\"$link\" title=\"$title $passage\" target=\"_blank\">$title</a>";
				if ($show_description)
				{
					$item['DESCRIPTION'] = htmlspecialchars(strip_tags(str_replace(array("&lt;","&gt;"),array("<",">"),$item['DESCRIPTION'])));
					$link .= "<br />"."<p class=\"quotation\">".make_link($item['DESCRIPTION'])."</p>";
				}
				$this->items[$date][] = $this->format_link($link);
			}
		}
	}
	function format_link($link)
	{
		return "$link<br />\n";
	}
	function format_list($date,$str)
	{
		return $str;
	}
	function format_body($str)
	{
		return $str;
	}
	function toString($timestamp)
	{
		$retval = '';
		foreach ($this->items as $date=>$items)
		{
			$retval .= $this->format_list($date,join('',$items));
		}
		$retval = $this->format_body($retval);
		return <<<EOD
<div{$this->class}>
$retval$timestamp
</div>
EOD;
	}
}
class ShowRSS_html_menubar extends ShowRSS_html
{
	var $class = ' class="wiki_showrss"';

	function format_link($link)
	{
		return "<li>$link</li>\n";
	}
	function format_body($str)
	{
		return "<ul class=\"recent_list\">\n$str</ul>\n";
	}
}
class ShowRSS_html_recent extends ShowRSS_html
{
	var $class = ' class="wiki_showrss"';

	function format_link($link)
	{
		return "<li>$link</li>\n";
	}
	function format_list($date,$str)
	{
		return "<strong>$date</strong>\n<ul class=\"recent_list\">\n$str</ul>\n";
	}
}
// rss���������
function plugin_showrss_get_rss($target,$usecache,$do_refresh=false)
{
	$buf = '';
	$time = NULL;
	$refresh = 0;
	
	$filename = P_CACHE_DIR . md5($target) . '.srs';
	
	//global $X_admin;
	//if ($X_admin) echo $filename;
	
	if ($usecache && !$do_refresh)
	{
		// ����å��夬����м�������
		if (is_readable($filename))
		{
			if (time() - filemtime($filename) > $usecache * 60 * 60)
			{
				// ������ɬ��
				$refresh = 1;
			}
			$buf = join('',file($filename));
			$time = filemtime($filename) - LOCALZONE;
		}
	}
	if ($time === NULL)
	{
		// rss���Τ����
		$data = http_request($target);
		if ($data['rc'] !== 200)
		{
			// ���顼������å��夬����Х���å�����֤�
			if (is_readable($filename))
			{
				$buf = join('',file($filename));
				$time = filemtime($filename) - LOCALZONE;
			}
			else
				return array(FALSE,0,FALSE);
		}
		else
		{
			$buf = $data['data'];
			// <content:encoded> ����
			$buf = preg_replace("#<content:encoded>(.*)</content:encoded>#isU","",$buf);
			// ;ʬ��ʸ��ʸ������
			$buf = preg_replace("/[\x01-\x08\x0b\x0c\x0e-\x1f\x7f]+/","",$buf);
			$buf = str_replace("\0","",$buf);
			// &amp; �Ǥʤ� & ���ִ�
			$buf = preg_replace("/&(?!amp;)/","&amp;",$buf);
			// �������� < > �򥨥�������
			//$buf = preg_replace("#(<[^<>]+>)(.+?)(</[^<>]+>)#e","'$1'.str_replace(array('\r','\n'),'','$2').'$3'",$buf);
			//$buf = preg_replace("#(<[^<>]+>)(.+?)(</[^<>]+>)#e","'$1'.str_replace(array('<','>'),array('&lt;','&gt;'),'$2').'$3'",$buf);
			
			$time = UTIME;
			// ����å������¸
			if ($usecache)
			{
				$fp = fopen($filename, 'wb');
				fwrite($fp,$buf);
				fclose($fp);
			}
		}
		
		if ($do_refresh)
		{
			return array(TRUE,$time,FALSE);
		}
		
	}
	
	// parse
	$obj = new ShowRSS_XML();

	return array($obj->parse($buf),$time,$refresh);
}

// rss�����������
class ShowRSS_XML
{
	var $items;
	var $item;
	var $is_item;
	var $tag;

	function parse($buf)
	{
		// �����
		$this->items = array();
		$this->item = array();
		$this->is_item = FALSE;
		$this->tag = '';

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser,array(&$this,'start_element'),array(&$this,'end_element'));
		xml_set_character_data_handler($xml_parser,array(&$this,'character_data'));

		if (!xml_parse($xml_parser,$buf,1))
		{
			return(sprintf('XML error: %s at line %d in %s',
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser),$buf));
		}
		xml_parser_free($xml_parser);

		return $this->items;
	}
	function escape($str)
	{
		// RSS��� "&lt; &gt; &amp;" �ʤɤ� ��ö "< > &" ���ᤷ�� �� "&amp;" �� "&amp;amp;" �ˤʤä��㤦���к�
		// ���θ��ä���"< > &"�ʤɤ�"&lt; &gt; &amp;"�ˤ���  �� XSS�к���
		$str = strtr($str, array_flip(get_html_translation_table(ENT_COMPAT)));
		$str = htmlspecialchars($str);
		// &amp; -> & by nao-pon
		$str = str_replace('&amp;','&',$str);

		// ʸ���������Ѵ�
		$str = mb_convert_encoding($str, SOURCE_ENCODING, 'AUTO');

		return trim($str);
	}

	// ��������
	function start_element($parser,$name,$attrs)
	{
		if ($this->is_item)
		{
			$this->tag = $name;
		}
		else if ($name == 'ITEM')
		{
			$this->is_item = TRUE;
		}
	}
	// ������λ
	function end_element($parser,$name)
	{
		if (!$this->is_item or $name != 'ITEM')
		{
			return;
		}
		$item = array_map(array(&$this,'escape'),$this->item);

		$this->item = array();

		if (array_key_exists('DC:DATE',$item))
		{
			$time = plugin_showrss_get_timestamp($item['DC:DATE']);
		}
		else if (array_key_exists('PUBDATE',$item))
		{
			$time = plugin_showrss_get_timestamp($item['PUBDATE']);
		}
		else if (array_key_exists('DESCRIPTION',$item) and strtotime($item['DESCRIPTION']) != -1)
		{
			$time = strtotime($item['DESCRIPTION']) - LOCALZONE;
		}
		else
		{
			$time = time() - LOCALZONE;
		}
		$item['_TIMESTAMP'] = $time;
		$date = get_date('Y-m-d',$item['_TIMESTAMP']);

		$this->items[$date][] = $item;
		$this->is_item = FALSE;
	}
	// ����饯��
	function character_data($parser,$data)
	{
		if (!$this->is_item)
		{
			return;
		}
		if (!array_key_exists($this->tag,$this->item))
		{
			$this->item[$this->tag] = '';
		}
		$this->item[$this->tag] .= $data;
	}
}
function plugin_showrss_get_timestamp($str)
{
	if (!preg_match('/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})(([+-])(\d{2}):(\d{2}))?/',$str,$matches))
	{
		$time = strtotime($str);
		return ($time == -1 ? time() : $time) - LOCALZONE;
	}
	$str = $matches[1];
	$time = strtotime($matches[1].' '.$matches[2]);
	if (!empty($matches[3]))
	{
		//$diff = ($matches[5]*60+$matches[6])*60;
		$diff = ($matches[5]*60+$matches[6])*60 - date('Z');
		$time += ($matches[4] == '-' ? $diff : -$diff);
	}
	return $time;
}
?>