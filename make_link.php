<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: make_link.php,v 1.22 2004/08/29 12:23:43 nao-pon Exp $
// ORG: make_link.php,v 1.64 2003/11/22 04:50:26 arino Exp $
//

// ��󥯤��ղä���
function make_link($string,$page = '')
{
	global $vars;
	static $converter;
	
	$string = str_replace('&amp;','&',$string);
	if (!isset($converter))
	{
		$converter = new InlineConverter();
	}
	$_converter = $converter; // copy
	return $_converter->convert($string, ($page != '') ? $page : $vars['page']);
}
//����饤�����Ǥ��ִ�����
class InlineConverter
{
	var $converters; // as array()
	var $pattern;
	var $pos;
	var $result;
	
	function InlineConverter($converters=NULL,$excludes=NULL)
	{
		if ($converters === NULL)
		{
			$converters = array(
				'plugin',        // ����饤��ץ饰����
				'note',          // ��� 
				'url',           // URL
				'url_interwiki', // URL (interwiki definition)
				'mailto',        // mailto:
				'interwikiname', // InterWikiName
				'autolink',      // AutoLink
				'bracketname',   // BracketName
				'wikiname',      // WikiName
				'autolink_a',    // AutoLink(����ե��٥å�)
				'escape',        // escape
			);
		}
		if ($excludes !== NULL)
		{
			$converters = array_diff($converters,$excludes);
		}
		$this->converters = array();
		$patterns = array();
		$start = 1;
		
		foreach ($converters as $name)
		{
			$classname = "Link_$name";
			$converter = new $classname($start);
			$pattern = $converter->get_pattern();
			if ($pattern === FALSE)
			{
				continue;
			}
			$patterns[] = "(\n$pattern\n)";
			$this->converters[$start] = $converter;
			$start += $converter->get_count();
			$start++;
		}
		$this->pattern = join('|',$patterns);
	}
	function convert($string,$page)
	{
		$this->page = $page;
		$this->result = array();
		
		$string = preg_replace_callback("/{$this->pattern}/x",array(&$this,'replace'),$string);
		
		//$arr = explode("\x08",make_line_rules(htmlspecialchars($string)));
		$arr = explode("\x08",make_line_rules($string));
		$retval = '';
		while (count($arr))
		{
			$retval .= array_shift($arr).array_shift($this->result);
		}
		return $retval;
	}
	function replace($arr)
	{
		$obj = $this->get_converter($arr);
		$this->result[] = ($obj !== NULL and $obj->set($arr,$this->page) !== FALSE) ?
			$obj->toString() : make_line_rules(htmlspecialchars($arr[0]));
		
		return "\x08"; //�����Ѥߤ���ʬ�˥ޡ����������
	}
	function get_objects($string,$page)
	{
		preg_match_all("/{$this->pattern}/x",$string,$matches,PREG_SET_ORDER);
		
		$arr = array();
		foreach ($matches as $match)
		{
			$obj = $this->get_converter($match);
			if ($obj->set($match,$page) !== FALSE)
			{
				$arr[] = $obj; // copy
 				if ($obj->body != '')
 				{
 					$arr = array_merge($arr,$this->get_objects($obj->body,$page));
 				}
			}
		}
		return $arr;
	}
	function &get_converter(&$arr)
	{
		foreach (array_keys($this->converters) as $start)
		{
		if ($arr[$start] == $arr[0])
			{
				return $this->converters[$start];
			}
		}
		return NULL;
	}
}
//����饤�����ǽ���Υ١������饹
class Link
{
	var $start;   // ��̤���Ƭ�ֹ�(0���ꥸ��)
	var $text;    // �ޥå�����ʸ��������

	var $type;
	var $page;
	var $name;
 	var $body;
	var $alias;

	// constructor
	function Link($start)
	{
		$this->start = $start;
	}
	// �ޥå��˻��Ѥ���ѥ�������֤�
	function get_pattern()
	{
	}
	// ���Ѥ��Ƥ����̤ο����֤� ((?:...)�����)
	function get_count()
	{
	}
	// �ޥå������ѥ���������ꤹ��
	function set($arr,$page)
	{
	}
	// ʸ������Ѵ�����
	function toString()
	{
	}
	
	//private
	// �ޥå��������󤫤顢��ʬ��ɬ�פ���ʬ��������Ф�
	function splice($arr)
	{
		$count = $this->get_count() + 1;
		$arr = array_pad(array_splice($arr,$this->start,$count),$count,'');
		$this->text = $arr[0];
		return $arr;
	}
	// ���ܥѥ�᡼�������ꤹ��
	function setParam($page,$name,$body,$type='',$alias='')
	{
		static $converter = NULL;
		
		$this->page = $page;
		$this->name = $name;
		$this->body = $body;
		$this->type = $type;
		//if ($type != 'InterWikiName' and preg_match('/\.(gif|png|jpe?g)$/i',$alias))
		if (is_url($alias) && preg_match('/\.(gif|png|jpe?g)$/i',$alias))
		{
			$alias = htmlspecialchars($alias);
			$alias = "$separator<img src=\"$alias\" alt=\"$name\" />";
		}
		else if ($alias != '')
		{
			if ($converter === NULL)
			{
				$converter = new InlineConverter(array('plugin'));
			}
			$alias = make_line_rules($converter->convert($alias,$page));
			$alias = preg_replace('#</?a[^>]*>#i','',$alias);
		}
		$this->alias = $alias;
		
		return TRUE;
	}
}
// ����饤��ץ饰����
class Link_plugin extends Link
{
	var $pattern;
	var $plain,$param;
	
	function Link_plugin($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		$this->pattern = <<<EOD
&
(      # (1) plain
 (\w+) # (2) plugin name
 (?:
  \(
   ((?:(?!\)[;{]).)*) # (3) parameter
  \)
 )?
)
EOD;
		return <<<EOD
{$this->pattern}
(?:
 \{
  ((?:(?R)|(?!};).)*) # (4) body
 \}
)?
;
EOD;
	}
	function get_count()
	{
		return 4;
	}
	function set($arr,$page)
	{
		list($all,$this->plain,$name,$this->param,$body) = $this->splice($arr);
		
		// ����Υץ饰����̾����ӥѥ�᡼����������ʤ��� PHP4.1.2 (?R)�к�
		if (preg_match("/^{$this->pattern}/x",$all,$matches)
			and $matches[1] != $this->plain)

		{
			list(,$this->plain,$name,$this->param) = $matches;
		}
		return parent::setParam($page,$name,$body,'plugin');
	}
	function toString()
	{
		$body = ($this->body == '') ? '' : make_link($this->body);
		
		// �ץ饰����ƤӽФ�
		if (exist_plugin_inline($this->name))
		{
			$str = do_plugin_inline($this->name,$this->param,$body);
			if ($str !== FALSE) //����
			{
				return $str;
			}
		}
		
		// �ץ饰����¸�ߤ��ʤ������Ѵ��˼���
		$body = ($body == '') ? ';' : "\{$body};";
		return make_line_rules(htmlspecialchars('&'.$this->plain).$body);
	}
}
// ���
class Link_note extends Link
{
	function Link_note($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		return <<<EOD
\(\(
 ((?:(?R)|(?!\)\)).)*) # (1) note body
\)\)
EOD;
	}
	function get_count()
	{
		return 1;
	}
	function set($arr,$page)
	{
		global $foot_explain;
		static $note_id = 0;
		
		list(,$body) = $this->splice($arr);
		
		$id = ++$note_id;
		$note = make_link($body);
		
		$foot_explain[$id] = <<<EOD
<a id="notefoot_$id" href="#notetext_$id" class="note_super">*$id</a>
<span class="small">$note</span>
<br />
EOD;
		$name = "<a id=\"notetext_$id\" href=\"#notefoot_$id\" class=\"note_super\" title=\"".strip_tags($note)."\">*$id</a>";
		
		return parent::setParam($page,$name,$body);
	}
	function toString()
	{
		return $this->name;
	}
}
// url
class Link_url extends Link
{
	function Link_url($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		$s1 = $this->start + 1;
		return <<<EOD
(\[\[             # (1) open bracket
 ((?:(?!\]\]).)+) # (2) alias
 (&gt;|>|:)       # (3) separator
)?
(                 # (4) url
 (?:https?|ftp|news):\/\/[!~*'();\/?:\@&=+\$,%#\w.-]+
)
(?($s1)\]\])      # close bracket
EOD;
	}
	function get_count()
	{
		return 4;
	}
	function set($arr,$page)
	{
		list(,,$alias,$separator,$name) = $this->splice($arr);
		if ($separator == "&gt;") $separator = ">";
		$this->separator = $separator;
		// https?:\/\/\/ -> XOOPS_URL
		$name = preg_replace("/^https?:\/\/\//",XOOPS_URL."/",$name);
		return parent::setParam($page,htmlspecialchars($name),'','url',$alias == '' ? $name : $alias);
	}
	function toString()
	{
		global $link_target,$alias_set_status;
		//�ץ饰������ղä��줿<a href>�����������
		$this->alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);
		$status_script = ($alias_set_status)? " onMouseOver=\"window.status='".str_replace("'","\'",strip_tags($this->alias))."';return true\" onMouseOut=\"window.status='';return true\"":"";
		if ($this->separator == ">")
			return "<a href=\"{$this->name}\"{$status_script}>{$this->alias}</a>";
		else
			return "<a href=\"{$this->name}\" target=\"$link_target\"{$status_script}>{$this->alias}</a>";
	}
}
// url (InterWiki definition type)
class Link_url_interwiki extends Link
{
	function Link_url_interwiki($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		return <<<EOD
\[       # open bracket
(        # (1) url
 (?:(?:https?|ftp|news):\/\/|\.\.?\/)[!~*'();\/?:\@&=+\$,%#\w.-]*
)
\s
([^\]]+) # (2) alias
\]       # close bracket
EOD;
	}
	function get_count()
	{
		return 2;
	}
	function set($arr,$page)
	{
		list(,$name,$alias) = $this->splice($arr);
		return parent::setParam($page,htmlspecialchars($name),'','url',$alias);
	}
	function toString()
	{
		global $interwiki_target;
		//�ץ饰������ղä��줿<a href>�����������
		$this->alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);
		return "<a href=\"{$this->name}\" target=\"$interwiki_target\">{$this->alias}</a>";
	}
}
//mailto:
class Link_mailto extends Link
{
	var $is_image,$image;
	
	function Link_mailto($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		$s1 = $this->start + 1;
		return <<<EOD
(?:
 \[\[
 ((?:(?!\]\]).)+)(?:>|:)  # (1) alias
)?
([\w.-]+@[\w-]+\.[\w.-]+) # (2) mailto
(?($s1)\]\])              # close bracket if (1)
EOD;
	}
	function get_count()
	{
		return 2;
	}
	function set($arr,$page)
	{
		list(,$alias,$name) = $this->splice($arr);
		return parent::setParam($page,$name,'','mailto',$alias == '' ? $name : $alias);
	}
	function toString()
	{
		return "<a href=\"mailto:{$this->name}\">{$this->alias}</a>";
	}
}
//InterWikiName
class Link_interwikiname extends Link
{
	var $url = '';
	var $param = '';
	var $anchor = '';
	
	function Link_interwikiname($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		$s2 = $this->start + 2;
		$s5 = $this->start + 5;
		return <<<EOD
\[\[                  # open bracket
(?:
 ((?:(?!\]\]).)+)(?:&gt;|>)    # (1) alias
)?
(\[\[)?               # (2) open bracket
((?:(?!\s|:|\]\]).)+) # (3) InterWiki
(?<! > | &gt; | >\[\[ | &gt;\[\[ )      # not '>' or '>[['
:                     # separator
(                     # (4) param 
    (\[\[)?              # (5) open bracket 
    (?:(?!>|&gt;|\]\]).)+ 
    (?($s5)\]\])         # close bracket if (5) 
) 

(?($s2)\]\])          # close bracket if (2)
\]\]                  # close bracket
EOD;
	}
	function get_count()
	{
		return 5;
	}
	function set($arr,$page)
	{
		global $script;
		
		list(,$alias,,$name,$this->param) = $this->splice($arr);
		
		if (preg_match('/^([^#]+)(#[A-Za-z][\w-]*)$/',$this->param,$matches))
		{
			list(,$this->param,$this->anchor) = $matches;
		}
		$url = get_interwiki_url($name,$this->param);
		$this->url = ($url === FALSE) ?
			$script.'?'.rawurlencode('[['.$name.':'.$this->param.']]') :
			htmlspecialchars($url);
		
		return parent::setParam(
			$page,
			htmlspecialchars($name.':'.$this->param),
			'',
			'InterWikiName',
			$alias == '' ? $name.':'.$this->param : $alias
		);
	}
	function toString()
	{
		global $interwiki_target;
		//�ץ饰������ղä��줿<a href>�����������
		$this->alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);
		return "<a href=\"{$this->url}{$this->anchor}\" title=\"{$this->name}\" target=\"$interwiki_target\">{$this->alias}</a>";
	}
}
// BracketName
class Link_bracketname extends Link
{
	var $anchor,$refer;
	
	function Link_bracketname($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		//global $WikiName,$BracketName;
		global $WikiName;
		$BracketName = '(?!\s):?[^\r\n\t\f\[\]<>#&":]+:?(?<!\s)';
		//$WikiName = '(?:[A-Z][a-z]+){2,}(?!\w)';
		//$WikiName = '(?<!(!|\w))(?:[A-Z][a-z]+){2,}(?!\w)';

		$s2 = $this->start + 2;
		return <<<EOD
\[\[                     # open bracket
(?:((?:(?!\]\]).)+)(?:&gt;|>))?   # (1) alias
(\[\[)?                  # (2) open bracket
(                        # (3) PageName
 (?:$WikiName)
 |
 (?:$BracketName)
)?
(\#(?:[a-zA-Z][\w-]*)?)? # (4) anchor
(?($s2)\]\])             # close bracket if (2)
\]\]                     # close bracket
EOD;
	}
	function get_count()
	{
		return 4;
	}
	function set($arr,$page)
	{
		global $WikiName;
		
		list(,$alias,,$name,$this->anchor) = $this->splice($arr);
		
		if ($name == '' and $this->anchor == '')
		{
			return FALSE;
		}
		if ($name != '' and preg_match("/^$WikiName$/",$name))
		{
			// ���̥�󥯥ǥ��쥯�ȥ��õ��
			if (!is_page($name))
			{
				$_name = get_real_pagename($name);
				if ($_name) $name = $_name;
			}
			
			return parent::setParam($page,$name,'','pagename',$alias);
		}
		if ($alias == '')
		{
			$alias = $name.$this->anchor;
		}
		if ($name == '')
		{
			if ($this->anchor == '')
			{
				return FALSE;
			}
		}
		else
		{
			$name = get_fullname($name,$page);
			if (!is_pagename($name))
			{
				return FALSE;
			}
			
			// ���̥�󥯥ǥ��쥯�ȥ��õ��
			if (!is_page($name))
			{
				$_name = get_real_pagename($name);
				if ($_name) $name = $_name;
			}
		}
		return parent::setParam($page,$name,'','pagename',$alias);
	}
	function toString()
	{
		//�ץ饰������ղä��줿<a href>�����������
		$this->alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);
		//���ꥢ�����ڡ���̾�ʤ�ѥ���ꥹ�Ȼ���
		//if ($this->name == $this->alias) $this->alias = "#/#";
		if ($this->name == get_fullname($this->alias,$this->page)) $this->alias = "#/#";
		return make_pagelink(
			$this->name,
			$this->alias,
			$this->anchor,
			$this->page
		);
	}
}
// WikiName
class Link_wikiname extends Link
{
	function Link_wikiname($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		global $WikiName,$nowikiname;
		//global $nowikiname;
		//$WikiName = '(?:[A-Z][a-z]+){2,}(?!\w)';
		//$WikiName = '(?<!(!|\w))(?:[A-Z][a-z]+){2,}(?!\w)';
		
		return $nowikiname ? FALSE : "($WikiName)";
	}
	function get_count()
	{
		return 1;
	}
	function set($arr,$page)
	{
		list($name) = $this->splice($arr);
		$alias = $name;
		
		// ���̥�󥯥ǥ��쥯�ȥ��õ��
		if (!is_page($name))
		{
			$_name = get_real_pagename($name);
			if ($_name) $name = $_name;
		}
		
		return parent::setParam($page,$name,'','pagename',$alias);
	}
	function toString()
	{
		return make_pagelink(
			$this->name,
			$this->alias,
			'',
			$this->page
		);
	}
}
// escape
class Link_escape extends Link
{
	function Link_escape($start)
	{
		parent::Link($start);
	}
	function get_pattern()
	{
		return "\x1c([^\x1c\x1d]*)\x1d";
	}
	function get_count()
	{
		return 2;
	}
	function set($arr,$page)
	{
		list(,$name) = $this->splice($arr);
		return parent::setParam($page,$name,'escape');
	}
	function toString()
	{
		return $this->name;
	}
}

// AutoLink
class Link_autolink extends Link
{
	var $forceignorepages = array();
	var $auto;
	var $auto_a; // alphabet only
	
	function Link_autolink($start)
	{
		global $autolink;
		
		parent::Link($start);
		
		if (!$autolink or !file_exists(CACHE_DIR.'autolink.dat'))
		{
			return;
		}
		@list($auto,$auto_a,$forceignorepages) = file(CACHE_DIR.'autolink.dat');
		$this->auto = $auto;
		$this->auto_a = $auto_a; 
		$this->forceignorepages = explode("\t",trim($forceignorepages));
	}
	function get_pattern()
	{
		return isset($this->auto) ? "({$this->auto})" : FALSE;
  	}
  	function get_count()
  	{
		return 1;
	}
	function set($arr,$page)
	{
		global $WikiName;
		
		list($name) = $this->splice($arr);
		$alias = $name;
		// ̵��ꥹ�Ȥ˴ޤޤ�Ƥ���ڡ�����ΤƤ�
		if (in_array($name,$this->forceignorepages))
			return FALSE;
		
		// ���̥�󥯥ǥ��쥯�ȥ��õ��
		if (!is_page($name))
		{
			if (!$name = get_real_pagename($name))
				return FALSE;
		}
		
		return parent::setParam($page,$name,'','pagename',$alias);
	}
	function toString()
	{
		return make_pagelink(
			$this->name,
			$this->alias,
			'',
			$this->page
		);
	}
}

class Link_autolink_a extends Link_autolink
{
	function Link_autolink_a($start)
	{
		parent::Link_autolink($start);
	}
	function get_pattern()
	{
		return isset($this->auto_a) ? "({$this->auto_a})" : FALSE;
	}
}

// �ڡ���̾�Υ�󥯤����
function make_pagelink($page,$alias='#/#',$anchor='',$refer='')
{
	global $script,$vars,$show_title,$show_passage,$link_compact,$related;
	global $_symbol_noexists,$use_static_url;
	
	static $linktag = array();
	
	$page = add_bracket($page);
	
	if (isset($linktag[$page.$alias])) return $linktag[$page.$alias];
	
	$s_page = htmlspecialchars(strip_bracket($page));
	$s_alias = ($alias == '') ? $s_page : $alias;
	
	if ($page == '')
	{
		return "<a href=\"$anchor\">$s_alias</a>";
	}
	
	//$r_page = rawurlencode($page);
	$r_page = rawurlencode($s_page);
	$r_refer = ($refer == '') ? '' : '&amp;refer='.rawurlencode($refer);

	if (!array_key_exists($page,$related) and $page != $vars['page'] and is_page($page))
	{
		$related[strip_bracket($page)] = get_filetime($page);
		
	}
	
	if (preg_match("/^#(.*)#$/",$alias,$sep))
	{
		// �ѥ���ꥹ�Ƚ���
		$sep = htmlspecialchars($sep[1]);
		$prefix = strip_bracket($page);
		$page_names = array();
		$page_names = explode("/",$prefix);
		$access_name = "";
		$i = 0;
		foreach ($page_names as $page_name){
			$access_name .= $page_name."/";
			$name = substr($access_name,0,strlen($access_name)-1);
			if (preg_match("/^[0-9\-]+$/",$page_name))
			{
				$heading = get_heading($page);
				if ($heading) $page_name = $heading;
				// ̵�¥롼���ɻߡ���©������
				$page_name = preg_replace("/^(#.*#)$/"," $1",$page_name);
			}
			$link = make_pagelink($name,$page_name);
			if ($i)
				$retval .= $sep.$link;
			else
				$retval = $link;
			$i++;
		}
	}
	elseif (is_page($page))
	{
		//�ڡ���̾���ֿ�����-�פ����ξ��ϡ�*(**)�Ԥ�������Ƥߤ�
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$s_alias,$f_name) && !$alias)
			$s_alias = $f_name[1].get_heading($page);
		$passage = get_pg_passage($page,FALSE);
		$title = $link_compact ? '' : " title=\"$s_page$passage\"";

		//if ($vars['page'] !== $page)
		//{
			if ($use_static_url)
				$retval = "<a href=\"".XOOPS_WIKI_URL."/".get_pgid_by_name($page).".html{$anchor}\"$title>$s_alias</a>";
			else
				$retval = "<a href=\"$script?$r_page$anchor\"$title>$s_alias</a>";
		//}
		//else
		//	$retval = "$s_alias";
	}
	else
	{
		if (WIKI_ALLOW_NEWPAGE)
			$retval = "$s_alias<a href=\"$script?cmd=edit&amp;page=$r_page$r_refer\">$_symbol_noexists</a>";
		else
			$retval = $s_alias;

		if (!$link_compact && WIKI_ALLOW_NEWPAGE)
		{
			$retval = "<span class=\"noexists\">$retval</span>";
		}
	}
	$linktag[$page.$alias] = $retval;
	return $retval;
}
// ���л��Ȥ�Ÿ��
function get_fullname($name,$refer)
{
	global $defaultpage;
	
	if ($name == '')
	{
		return $refer;
	}
	
	if ($name{0} == '/')
	{
		$name = substr($name,1);
		return ($name == '') ? $defaultpage : $name;
	}
	
	if ($name == './')
	{
		return $refer;
	}
	$refer = strip_bracket($refer);
	if (substr($name,0,2) == './')
	{
		$arrn = preg_split('/\//',$name,-1,PREG_SPLIT_NO_EMPTY);
		$arrn[0] = $refer;
		return join('/',$arrn);
	}
	
	if (substr($name,0,3) == '../')
	{
		$arrn = preg_split('/\//',$name,-1,PREG_SPLIT_NO_EMPTY);
		$arrp = preg_split('/\//',$refer,-1,PREG_SPLIT_NO_EMPTY);
		
		while (count($arrn) > 0 and $arrn[0] == '..')
		{
			array_shift($arrn);
			array_pop($arrp);
		}
		$name = count($arrp) ? join('/',array_merge($arrp,$arrn)) :
			(count($arrn) ? "$defaultpage/".join('/',$arrn) : $defaultpage);
	}
	return $name;
}

// InterWikiName��Ÿ��
function get_interwiki_url($name,$param)
{
	global $WikiName,$interwiki;
	static $interwikinames;
	static $encode_aliases = array('sjis'=>'SJIS','euc'=>'EUC-JP','utf8'=>'UTF-8');
	
	if (!isset($interwikinames))
	{
		$interwikinames = array();
		foreach (get_source($interwiki) as $line)
		{
			if (preg_match('/\[((?:(?:https?|ftp|news):\/\/|\.\.?\/)[!~*\'();\/?:\@&=+\$,%#\w.-]*)\s([^\]]+)\]\s?([^\s]*)/',$line,$matches))
			{
				$interwikinames[$matches[2]] = array($matches[1],$matches[3]);
			}
		}
	}
	if (!array_key_exists($name,$interwikinames))
	{
		return FALSE;
	}
	list($url,$opt) = $interwikinames[$name];
	
	// ʸ�����󥳡��ǥ���
	switch ($opt)
	{
		// YukiWiki��
		case 'yw':
			if (!preg_match("/$WikiName/",$param))
			{
				$param = '[['.mb_convert_encoding($param,'SJIS',SOURCE_ENCODING).']]';
			}
//			$param = htmlspecialchars($param);
			break;
		
		// moin��
		case 'moin':
			$param = str_replace('%','_',rawurlencode($param));
			break;
		
		// ����ʸ�����󥳡��ǥ��󥰤Τޤ�URL���󥳡���
		case '':
		case 'std':
			$param = rawurlencode($param);
			break;
		
		// URL���󥳡��ɤ��ʤ�
		case 'asis':
		case 'raw':
//			$param = htmlspecialchars($param);
			break;
		
		default:
			// �����ꥢ�����Ѵ�
			if (array_key_exists($opt,$encode_aliases))
			{
				$opt = $encode_aliases[$opt];
			}
			// ���ꤵ�줿ʸ�������ɤإ��󥳡��ɤ���URL���󥳡���
			$param = rawurlencode(mb_convert_encoding($param,$opt,'auto'));
	}
	
	// �ѥ�᡼�����ִ�
	if (strpos($url,'$1') !== FALSE)
	{
		$url = str_replace('$1',$param,$url);
	}
	else
	{
		$url .= $param;
	}
	
	return $url;
}

// ���̥�󥯥ǥ��쥯�ȥ�ν���(�����ե�͡�����֤�:�֥饱�åȤʤ�)
function get_real_pagename($page)
{
	global $wiki_common_dirs;
	static $real_pages = array();
	
	$page = strip_bracket($page);
	
	if (isset($real_pages[$page])) return $real_pages[$page];
	
	$real_pages[$page] = false;
	foreach($wiki_common_dirs as $dir)
	{
		$check = $dir.$page;
		if (is_page($check))
		{
			$real_pages[$page] = $check;
			break;
		}
	}
	return $real_pages[$page];
}
?>
