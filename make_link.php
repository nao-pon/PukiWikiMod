<?php
// Last-Update:2002-09-24 rev.1

// リンクを付加する
function p_make_link($name,$page = '')
{
	global $vars,$LinkPattern;

	if ($page == '')
		$page = $vars["page"];

	$obj = new link_wrapper($page);
	return $obj->make_link($name);
}
class link_wrapper
{
	var $page;
	function link_wrapper($page)
	{
		$this->page = $page; 
	}
	function &_convert($arr)
	{
		if ($arr[4]  != '')
			return new link_url($arr[4],$arr[2].$arr[5]);
		if ($arr[7]  != '')
			return new link_mailto($arr[7],$arr[6]);
		if ($arr[16] != '')
			return new link_interwiki("[[$arr[16]$arr[18]]]",$arr[10]);
		if ($arr[12] != '' or $arr[14] != '')
			return expand_bracket($arr,$this->page);
		if ($arr[19] != '')
			return new link_wikiname($arr[19],$arr[19],'',$this->page);
		if ($arr[20] != '')
			return new link($arr[0]);

		return new link($arr[0]); //どれでもない
	}
	function &_replace_link($arr)
	{
		$obj = $this->_convert($arr);

		return $obj->toString();
	}
	function &_replace($str)
	{
		global $LinkPattern;

		return preg_replace_callback($LinkPattern,array($this,'_replace_link'), $str);
	}
	function &make_link($str)
	{
		if (!is_array($str))
			return $this->_replace($str);

		$tmp = array();

		foreach ($str as $line)
			$tmp[] = $this->_replace($line);

		return $tmp;
	}
	function &get_link($str)
	{
		global $LinkPattern;

		preg_match_all($LinkPattern,$str,$matches,PREG_SET_ORDER);

		$tmp = array();

		foreach ($matches as $arr)
			$tmp[] =& $this->_convert($arr);

		return $tmp;
	}
}
//BracketNameの処理
function &expand_bracket($name,$refer)
{
	global $WikiName,$BracketName,$LinkPattern,$defaultpage;
	
	if (is_array($name))
		$arr = $name;
	else if (preg_match("/^$WikiName$/",$name))
		return new link_wikiname($name);
	else if (!preg_match($LinkPattern,$name,$arr) or $arr[12] == '')
		return new link($name);
	
	$arr = array_slice($arr,8,7);
	$_name = array_shift($arr);
	
	$bracket = ($arr[0] or $arr[2]);
	$alias = $arr[1];
	$name = $arr[3];
	$anchor = $arr[5];
	
	if ($name != '')
	{
		if ($alias == '' and $anchor == '')
			$name = "[[$name]]";
		else if (!$bracket and preg_match("/^$WikiName$/",$name))
			return new link_wikiname($name,$alias,$anchor,$refer);
		else
			$name = "[[$name]]";
	}
	
	if ($alias == '')
		$alias = strip_bracket($name).$anchor;
	
	if ($name == '')
		return ($anchor == '') ? new link($_name) : new link_wikiname($name,$alias,$anchor,$refer);
	
	$name = get_fullname($name,$refer);
	
	if ($name == '' or preg_match("/^$WikiName$/",$name))
		return new link_wikiname($name,$alias,$anchor,$refer);
	else if (!preg_match("/^$BracketName$/",$name)) 
		return new link($_name);
	
	//return new link_wikiname(strip_bracket($name),$alias,$anchor,$refer);
	return new link_wikiname($name,$alias,$anchor,$refer);
}
// 相対参照を展開
function get_fullname($name,$refer)
{
	global $defaultpage,$WikiName;
	
	if ($name == '[[./]]')
		return $refer;

	if (substr($name,0,4) == '[[./')
		return '[['.strip_bracket($refer).substr($name,3);
	
	if (substr($name,0,5) == '[[../')
	{
		$arrn = preg_split("/\//",strip_bracket($name),-1,PREG_SPLIT_NO_EMPTY);
		$arrp = preg_split("/\//",strip_bracket($refer),-1,PREG_SPLIT_NO_EMPTY);
		while ($arrn[0] == '..') { array_shift($arrn); array_pop($arrp); }
		$name = (count($arrp)) ? '[['.join('/',array_merge($arrp,$arrn)).']]' :
			((count($arrn)) ? "[[$defaultpage/".join('/',$arrn).']]' : $defaultpage);
		
		// [[FrontPage/hoge]]の親は[[FrontPage]]ではなくFrontPage(という仕様)
		$_name = strip_bracket($name);
		if (preg_match("/^$WikiName$/",$_name))
			$name = $_name;
	}
	
	return $name;
}
class link
{
	var $type,$name,$char,$alias;

	function link($name,$type = '',$alias = '')
	{
		$this->name = $name;
		$this->type = $type;
		$this->char = '0'.$name;
		$this->alias = $alias;
	}
	function toString()
	{
		return $this->name;
	}
	function compare($a,$b)
	{
		return strnatcasecmp($a->char,$b->char);
	}
}
class link_url extends link
{
	var $is_image,$image;
	function link_url($name,$alias)
	{
		parent::link($name,'url',($alias == '') ? $name : $alias);
		
		if ($alias == '' and preg_match("/\.(gif|png|jpeg|jpg)$/i",$name)) {
			$this->is_image = TRUE;
			$this->image = "<img src=\"$name\" border=\"0\" alt=\"$alias\">";
		} else if (preg_match("/\.(gif|png|jpeg|jpg)$/i",$alias)) {
			$this->is_image = TRUE;
			$this->image = "<img src=\"$alias\" border=\"0\" alt=\"$name\">";
		} else {
			$this->is_image = FALSE;
			$this->image = '';
		}
	}
	function toString()
	{
		global $link_target;
		//プラグインで付加された<a href>タグを取り除く
		$this_alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);
		return "<a href=\"{$this->name}\" target=\"$link_target\">"
			.($this->is_image ? $this->image : $this_alias)
			.'</a>';
	}
}
class link_mailto extends link
{
	function link_mailto($name,$alias)
	{
		parent::link($name,'mailto',($alias == '') ? $name : $alias);
	}
	function toString()
	{
		return "<a href=\"mailto:$this->name\">{$this->alias}</a>";
	}
}
class link_interwiki extends link
{
	var $rawname;

	function link_interwiki($name,$alias)
	{
		global $script;
		parent::link($name,'InterWikiName',($alias == '') ? strip_bracket($name) : $alias);
		$this->rawname = rawurlencode($name);
	}
	function toString()
	{
		global $script,$interwiki_target;
		$strip_name = strip_bracket($this->name);
		
		// ./ は自分自身($script)に変換
		$strip_name = preg_replace("/^\.\//",$script,$strip_name);

		if (preg_match("/^(https?|ftp|news):\/\/[!~*'();\/?:\@&=+\$,%#\w.-]+$/",$strip_name)){
			//URLへのエリアスの場合
			///プラグインで付加された<a href>タグを取り除く
			$this_alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);
			return "<a href=\"$strip_name\">{$this_alias}</a>";
		} else {
			return "<a href=\"$script?$this->rawname\" target=\"$interwiki_target\">{$this->alias}</a>";
		}
	}
}
class link_wikiname extends link
{
	var $is_bracketname; //FALSE:'WikiName' TRUE:'BracketName';
	var $anchor;
	var $strip,$special,$rawname,$rawrefer,$passage;

	function link_wikiname($name,$alias='',$anchor='',$refer='')
	{
		global $script,$vars,$related;

		$this->is_bracketname = (substr($name,0,1) == '[');
		parent::link($name,$this->is_bracketname ? 'BracketName' : 'WikiName',($alias == '') ? strip_bracket($name).$anchor : $alias);
		$this->anchor = $anchor;
		$this->strip = strip_bracket($name);
		$this->char = ((ord($this->strip) < 128) ? '0' : '1').$this->strip;
		$this->special = htmlspecialchars($this->strip);
		//$this->rawname = rawurlencode($name);
		$this->rawname = rawurlencode(strip_bracket($name));
		$this->rawrefer = rawurlencode($refer);

		if ($vars['page'] != $name and is_page($name))
			$related['t'.filemtime(get_filename(encode($name)))] = "<a href=\"$script?{$this->rawname}\">{$this->special}</a>".$this->passage(TRUE);
			//$related[filemtime(get_filename(encode($name)))] = "<a href=\"$script?{$this->rawname}\">{$this->special}</a>".$this->passage();
	}

	function passage($sw=FALSE)
	{
		global $show_passage;
		$passage = get_pg_passage($this->name,$sw);
		//$passage = get_pg_passage($this->name);
		$this->passage = $show_passage ? $passage : '';
		return $passage;
	}

	function toString($refer = '')
	{
		global $script;
		//プラグインで付加された<a href>タグを取り除く
		$this_alias = preg_replace("/<a href[^>]*>(.*)<\/a>/s","$1",$this->alias);

		if ($this->name == '' and $this->anchor != ''){
			return "<a href=\"{$this->anchor}\">{$this_alias}</a>";
		}

		if (is_page($this->name))
			return "<a href=\"$script?{$this->rawname}{$this->anchor}\" title=\"{$this->special}".$this->passage()."\">{$this_alias}</a>";
		else {
			$rawrefer = ($refer != '') ? rawurlencode($refer) : $this->rawrefer;
			return "<span class=\"noexists\">$this->alias<a href=\"$script?cmd=edit&amp;page={$this->rawname}&amp;refer=$rawrefer\">?</a></span>";
		}
	}
}

// ページ名のリンクを作成
function make_pagelink($page,$alias='',$anchor='',$refer='')
{
	global $script,$vars,$show_title,$show_passage,$link_compact,$related;
	global $_symbol_noexists;
	
	$page = add_bracket($page);
	
	//echo $page;
	
	$s_page = htmlspecialchars(strip_bracket($page));
	$s_alias = ($alias == '') ? $s_page : $alias;
	
	if ($page == '')
	{
		return "<a href=\"$anchor\">$s_alias</a>";
	}
	
	//$r_page = rawurlencode($page);
	$r_page = rawurlencode($s_page);
	$r_refer = ($refer == '') ? '' : '&amp;refer='.rawurlencode($refer);

/*	
	if (!array_key_exists($page,$related) and $page != $vars['page'] and is_page($page))
	{
		$related[$page] = get_filetime($page);
	}
*/	
	if (is_page($page))
	{
		//ページ名が「数字と-」だけの場合は、*(**)行を取得してみる
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$s_alias) && !$alias){
			$_body = get_source($page);
			foreach($_body as $line){
				if (preg_match("/^\*{1,3}(.*)/",$line,$reg)){
					$s_alias = str_replace(array("[[","]]"),"",$reg[1]);
					break;
				}
			}
		}
		$passage = get_pg_passage($page,FALSE);
		$title = $link_compact ? '' : " title=\"$s_page$passage\"";
		return "<a href=\"$script?$r_page$anchor\"$title>$s_alias</a>";
	}
	else
	{
		$retval = "$s_alias<a href=\"$script?cmd=edit&amp;page=$r_page$r_refer\">$_symbol_noexists</a>";
		if (!$link_compact)
		{
			$retval = "<span class=\"noexists\">$retval</span>";
		}
		return $retval;
	}
}

?>
