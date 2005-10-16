<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: tracker.inc.php,v 1.20 2005/10/16 02:32:47 nao-pon Exp $
// ORG: tracker.inc.php,v 1.29 2005/03/02 13:31:05 henoheno Exp $

//
// Issue tracker plugin (See Also bugtrack plugin)

// tracker_list��ɽ�����ʤ��ڡ���̾(����ɽ����)
// 'SubMenu'�ڡ��� ����� '/'��ޤ�ڡ������������
define('TRACKER_LIST_EXCLUDE_PATTERN','#^SubMenu$|/#');
// ���¤��ʤ����Ϥ�����
//define('TRACKER_LIST_EXCLUDE_PATTERN','#(?!)#');

// ���ܤμ��Ф��˼��Ԥ����ڡ����������ɽ������
define('TRACKER_LIST_SHOW_ERROR_PAGE',TRUE);

function plugin_tracker_convert()
{
	global $script,$vars;

	//if (PKWK_READONLY) return ''; // Show nothing

	$base = $refer = $vars['page'];

	$config_name = 'default';
	$form = 'form';
	$options = array();
	if (func_num_args())
	{
		$args = func_get_args();
		switch (count($args))
		{
			case 3:
				$options = array_splice($args,2);
			case 2:
				$args[1] = get_fullname($args[1],$base);
				$base = is_pagename($args[1]) ? $args[1] : $base;
			case 1:
				$config_name = ($args[0] != '') ? $args[0] : $config_name;
				list($config_name,$form) = array_pad(explode('/',$config_name,2),2,$form);
		}
	}

	$config = new Config('plugin/tracker/'.$config_name);

	if (!$config->read())
	{
		return "<p>config file '".htmlspecialchars($config_name)."' not found.</p>";
	}

	$config->config_name = $config_name;

	$fields = plugin_tracker_get_fields($base,$refer,$config);

	$form = $config->page.'/'.$form;
	if (!is_page($form))
	{
		return "<p>config file '".make_pagelink($form)."' not found.</p>";
	}
	$retval = convert_html(plugin_tracker_get_source($form));
	$hiddens = '';
	if (in_array("guestauth",$options)) $hiddens .= "<input type=\"hidden\" name=\"guestauth\" value=\"1\" />";

	foreach (array_keys($fields) as $name)
	{
		$replace = $fields[$name]->get_tag();
		if (is_a($fields[$name],'Tracker_field_hidden'))
		{
			$hiddens .= $replace;
			$replace = '';
		}
		$retval = str_replace("[$name]",$replace,$retval);
	}
	return <<<EOD
<form enctype="multipart/form-data" action="$script" method="post">
<div>
$retval
$hiddens
</div>
</form>
EOD;
}
function plugin_tracker_action()
{
	global $post, $vars, $now, $X_uid, $X_uname, $_tracker_messages;
	
	// ̾���򥯥å�������¸
	setcookie("pukiwiki_un", $post['name'], time()+86400*365);//1ǯ��
			
	// �����ȤϾ�ǧɬ�ס�
	$guestauth = (!empty($post['guestauth']))? 1:0;

	//if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	$config_name = array_key_exists('_config',$post) ? $post['_config'] : '';

	$config = new Config('plugin/tracker/'.$config_name);
	if (!$config->read())
	{
		return "<p>config file '".htmlspecialchars($config_name)."' not found.</p>";
	}
	$config->config_name = $config_name;
	$source = $config->page.'/page';

	$refer = array_key_exists('_refer',$post) ? $post['_refer'] : $post['_base'];

	if (!is_pagename($refer))
	{
		return array(
			'msg'=>'cannot write',
			'body'=>'page name ('.htmlspecialchars($refer).') is not valid.'
		);
	}
	if (!is_page($source))
	{
		return array(
			'msg'=>'cannot write',
			'body'=>'page template ('.htmlspecialchars($source).') is not exist.'
		);
	}
	// �ڡ���̾�����
	$base = $post['_base'];
	$num = 0;
	$name = (array_key_exists('_name',$post)) ? $post['_name'] : '';
	if (array_key_exists('_page',$post))
	{
		$page = $real = $post['_page'];
	}
	else
	{
		$real = is_pagename($name) ? $name : ++$num;
		$page = add_bracket(get_fullname('./'.$real,$base));
	}
	if (!is_pagename($page))
	{
		$page = $base;
	}

	while (is_page($page))
	{
		$real = ++$num;
		//$page = "$base/$real";
		$page = "[[".strip_bracket($base)."/$real]]";
	}
	// �ڡ����ǡ���������
	$postdata = plugin_tracker_get_source($source);

	// author:uid ���ɲ�
	array_unshift ($postdata,"// author:".$X_uid."\n");

	// ����Υǡ���
	$_post = array_merge($post,$_FILES);
	$_post['_date'] = $now;
	$_post['_page'] = $page;
	$_post['_name'] = $name;
	$_post['_real'] = $real;
	// $_post['_refer'] = $_post['refer'];

	$fields = plugin_tracker_get_fields($page,$refer,$config);

	// Creating an empty page, before attaching files
	//touch(get_filename(encode($page)));

	foreach (array_keys($fields) as $key)
	{
		//���ԥ��������� by nao-pon
		$_post[$key] = preg_replace("/\x0D\x0A|\x0D|\x0A/","\n",$_post[$key]);
		
		//����ͭ�� by nao-pon
		if($_post['enter_enable'][$key]) {
			$_post[$key] = auto_br($_post[$key]);
		}
		
		//�ڡ���̾�˼�ư��� by nao-pon
		if ($_post['auto_bra_enable'][$key]) {
			$_post[$key] = auto_braket($_post[$key],$page);
		}

		$value = array_key_exists($key,$_post) ?
			$fields[$key]->format_value($_post[$key]) : '';

		foreach (array_keys($postdata) as $num)
		{
			if (trim($postdata[$num]) == '')
			{
				continue;
			}
			$postdata[$num] = str_replace(
				"[$key]",
				($postdata[$num]{0} == '|' or $postdata[$num]{0} == ':') ?
					str_replace('|','&#x7c;',$value) : $value,
				$postdata[$num]
			);
		}
	}

	// Writing page data, without touch
	// �ե�����ν񤭹���
	$postdata = join('',$postdata);
	
	if (!$guestauth || $X_uid)
	{
		//��Ͽ�桼�����Ͼ�ǧ�ʤ�
		page_write($page,$postdata,NULL,"","","","","","",array('plugin'=>'tracker','mode'=>'all'));
		$r_page = rawurlencode($page);
		
		header("Location: ".XOOPS_WIKI_HOST.get_url_by_name($page));
		exit;
	}
	else
	{
		//�����ȤϾ�ǧ��ɬ��
		$postdata = "#unvisible\tuid:1\taid:0\tgid:0\n".$postdata;
		page_write($page,$postdata,NULL,"","","","","","1",array('plugin'=>'tracker','mode'=>'all'));
		return array(
			'msg'=>$_tracker_messages['msg_done'],
			'body'=>$_tracker_messages['msg_guestauth']
		);
	}
}
/*
function plugin_tracker_inline()
{
	global $vars;

	//if (PKWK_READONLY) return ''; // Show nothing

	$args = func_get_args();
	if (count($args) < 3)
	{
		return FALSE;
	}
	$body = array_pop($args);
	list($config_name,$field) = $args;

	$config = new Config('plugin/tracker/'.$config_name);

	if (!$config->read())
	{
		return "config file '".htmlspecialchars($config_name)."' not found.";
	}

	$config->config_name = $config_name;

	$fields = plugin_tracker_get_fields($vars['page'],$vars['page'],$config);
	$fields[$field]->default_value = $body;
	return $fields[$field]->get_tag();
}
*/
// �ե�����ɥ��֥������Ȥ��ۤ���
function plugin_tracker_get_fields($base,$refer,&$config)
{
	global $now,$_tracker_messages;

	$fields = array();
	// ͽ���
	foreach (array(
		'_date'=>'text',    // �������
		'_update'=>'date',  // �ǽ�����
		'_past'=>'past',    // �в�(passage)
		'_page'=>'page',    // �ڡ���̾
		'_name'=>'text',    // ���ꤵ�줿�ڡ���̾
		'_real'=>'real',    // �ºݤΥڡ���̾
		'_refer'=>'page',   // ���ȸ�(�ե�����Τ���ڡ���)
		'_base'=>'page',    // ���ڡ���
		'_submit'=>'submit' // �ɲåܥ���
		) as $field=>$class)
	{
		$class = 'Tracker_field_'.$class;
		$fields[$field] = &new $class(array($field,$_tracker_messages["btn$field"],'','20',''),$base,$refer,$config);
	}

	foreach ($config->get('fields') as $field)
	{
		// 0=>����̾ 1=>���Ф� 2=>���� 3=>���ץ���� 4=>�ǥե������
		$class = 'Tracker_field_'.$field[2];
		if (!class_exists($class))
		{ // �ǥե����
			$class = 'Tracker_field_text';
			$field[2] = 'text';
			$field[3] = '20';
		}
		$fields[$field[0]] = &new $class($field,$base,$refer,$config);
	}
	return $fields;
}
// �ե�����ɥ��饹
class Tracker_field
{
	var $name;
	var $title;
	var $values;
	var $default_value;
	var $page;
	var $refer;
	var $config;
	var $data;
	var $sort_type = SORT_REGULAR;
	var $id = 0;

	function Tracker_field($field,$page,$refer,&$config)
	{
		global $post;
		static $id = 0;
		
		$this->id = ++$id."_".get_pgid_by_name($page);
		$this->name = $field[0];
		$this->title = $field[1];
		$this->values = explode(',',$field[3]);
		$this->default_value = $field[4];
		$this->page = $page;
		$this->refer = $refer;
		$this->config = &$config;
		$this->data = array_key_exists($this->name,$post) ? $post[$this->name] : '';
	}
	function get_tag()
	{
	}
	function get_style($str)
	{
		return '%s';
	}
	function format_value($value)
	{
		return $value;
	}
	function format_cell($str)
	{
		return $str;
	}
	function get_value($value)
	{
		return $value;
	}
}
class Tracker_field_text extends Tracker_field
{
	var $sort_type = SORT_STRING;

	function get_tag()
	{
		global $X_uname;
		$s_name = htmlspecialchars($this->name);
		$s_size = htmlspecialchars($this->values[0]);
		$s_value = htmlspecialchars($this->default_value);
		if ($s_value == '$X_uname') $s_value = WIKI_NAME_DEF;
		return "<input type=\"text\" name=\"$s_name\" size=\"$s_size\" value=\"$s_value\" />";
	}
}
class Tracker_field_uname extends Tracker_field_text
{
	var $sort_type = SORT_STRING;

	function format_value($value)
	{
		$value = make_user_link($value);
		return parent::format_value($value);
	}
}
class Tracker_field_page extends Tracker_field_text
{
	var $sort_type = SORT_STRING;

	function format_value($value)
	{
		global $WikiName;

		$value = strip_bracket($value);
		if (is_pagename($value))
		{
			//$value = "[[$value]]";
			$value = add_bracket($value);
		}
		return parent::format_value($value);
	}
}
class Tracker_field_real extends Tracker_field_text
{
	var $sort_type = SORT_REGULAR;
}
class Tracker_field_title extends Tracker_field_text
{
	var $sort_type = SORT_STRING;

	function format_cell($str)
	{
		//make_heading($str);
		$str = str_replace("[[","",$str);
		$str = str_replace("]]","",$str);
		$str = preg_replace("/\(\(.*\)\)/","",$str);
		return $str;
	}
}
class Tracker_field_textarea extends Tracker_field
{
	var $sort_type = SORT_STRING;

	function get_tag()
	{
		global $_btn_enter_enable,$_btn_autobracket_enable,$autolink;
		$s_name = htmlspecialchars($this->name);
		$s_cols = htmlspecialchars($this->values[0]);
		$s_rows = htmlspecialchars($this->values[1]);
		$s_value = htmlspecialchars($this->default_value);
		$auto_bra_enable = ($autolink)? "":" checked";
		$s_id = '_p_tracker_' . $s_name . '_' . $this->id;
		
		return "<input type=\"checkbox\" id=\"{$s_id}_ee\" name=\"enter_enable[$s_name]\" value=\"true\" checked /><label for=\"{$s_id}_ee\"><span class=\"small\">$_btn_enter_enable</span></label> <input type=\"checkbox\" id=\"{$s_id}_be\" name=\"auto_bra_enable[$s_name]\" value=\"true\"".$auto_bra_enable." /><label for=\"{$s_id}_be\"><span class=\"small\">$_btn_autobracket_enable</span></label><br />".fontset_js_tag()."<br /><textarea name=\"$s_name\" cols=\"$s_cols\" rows=\"$s_rows\" style=\"width:auto;\">$s_value</textarea>";
		//return "<textarea name=\"$s_name\" cols=\"$s_cols\" rows=\"$s_rows\">$s_value</textarea>";
	}
	function format_cell($str)
	{
		$str = preg_replace('/[\r\n]+/','',$str);
		if (!empty($this->values[2]) and strlen($str) > ($this->values[2] + 3))
		{
			$str = mb_substr($str,0,$this->values[2]).'...';
		}
		return $str;
	}
}
class Tracker_field_format extends Tracker_field
{
	var $sort_type = SORT_STRING;

	var $styles = array();
	var $formats = array();

	function Tracker_field_format($field,$page,$refer,&$config)
	{
		parent::Tracker_field($field,$page,$refer,$config);

		foreach ($this->config->get($this->name) as $option)
		{
			list($key,$style,$format) = array_pad(array_map(create_function('$a','return trim($a);'),$option),3,'');
			if ($style != '')
			{
				$this->styles[$key] = $style;
			}
			if ($format != '')
			{
				$this->formats[$key] = $format;
			}
		}
	}
	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$s_size = htmlspecialchars($this->values[0]);
		return "<input type=\"text\" name=\"$s_name\" size=\"$s_size\" />";
	}
	function get_key($str)
	{
		return ($str == '') ? 'IS NULL' : 'IS NOT NULL';
	}
	function format_value($str)
	{
		if (is_array($str))
		{
			return join(', ',array_map(array($this,'format_value'),$str));
		}
		$key = $this->get_key($str);
		return array_key_exists($key,$this->formats) ? str_replace('%s',$str,$this->formats[$key]) : $str;
	}
	function get_style($str)
	{
		$key = $this->get_key($str);
		return array_key_exists($key,$this->styles) ? $this->styles[$key] : '%s';
	}
}
class Tracker_field_file extends Tracker_field_format
{
	var $sort_type = SORT_STRING;

	function get_tag()
	{
		global $_attach_messages;
		$s_name = htmlspecialchars($this->name);
		$s_size = htmlspecialchars($this->values[0]);
		$s_id = '_p_tracker_' . $s_name . '_' . $this->id;
		
		return "<input type=\"file\" name=\"$s_name\" size=\"$s_size\" /> <input type=\"checkbox\" id=\"{$s_id}_copyright\" name=\"{$s_name}_copyright\" value=\"1\" /> <label for=\"{$s_id}_copyright\">{$_attach_messages['msg_copyright']}</label>";
	}
	function format_value($str)
	{
		if (array_key_exists($this->name,$_FILES))
		{
			require_once(PLUGIN_DIR.'attach.inc.php');
			$copyright = (isset($_POST[$this->name.'_copyright']))? TRUE : FALSE;
			$result = attach_upload($_FILES[$this->name],$this->page,NULL,$copyright);
			if ($result['result']) // ���åץ�������
			{
				return parent::format_value(strip_bracket($this->page).'/'.$_FILES[$this->name]['name']);
			}
		}
		// �ե����뤬���ꤵ��Ƥ��ʤ��������åץ��ɤ˼���
		return parent::format_value('');
	}
}
class Tracker_field_radio extends Tracker_field_format
{
	var $sort_type = SORT_NUMERIC;

	function get_tag()
	{
		$s_name = htmlspecialchars($this->name);
		$retval = '';
		$id = 0;
		foreach ($this->config->get($this->name) as $option)
		{
			$s_option = htmlspecialchars($option[0]);
			$checked = trim($option[0]) == trim($this->default_value) ? ' checked="checked"' : '';
			++$id;
			$s_id = '_p_tracker_' . $s_name . '_' . $this->id . '_' . $id;
			$retval .= "<input type=\"radio\" id=\"{$s_id}\" name=\"$s_name\" value=\"$s_option\"$checked /><label for=\"{$s_id}\">$s_option</label>\n";
		}

		return $retval;
	}
	function get_key($str)
	{
		return $str;
	}
	function get_value($value)
	{
		static $options = array();
		if (!array_key_exists($this->name,$options))
		{
			$options[$this->name] = array_flip(array_map(create_function('$arr','return $arr[0];'),$this->config->get($this->name)));
		}
		return array_key_exists($value,$options[$this->name]) ? $options[$this->name][$value] : $value;
	}
}
class Tracker_field_select extends Tracker_field_radio
{
	var $sort_type = SORT_NUMERIC;

	function get_tag($empty=FALSE)
	{
		$s_name = htmlspecialchars($this->name);
		$s_size = (array_key_exists(0,$this->values) and is_numeric($this->values[0])) ?
			' size="'.htmlspecialchars($this->values[0]).'"' : '';
		$s_multiple = (array_key_exists(1,$this->values) and strtolower($this->values[1]) == 'multiple') ?
			' multiple="multiple"' : '';
		$retval = "<select name=\"{$s_name}[]\"$s_size$s_multiple>\n";
		if ($empty)
		{
			$retval .= " <option value=\"\"></option>\n";
		}
		$defaults = array_flip(preg_split('/\s*,\s*/',$this->default_value,-1,PREG_SPLIT_NO_EMPTY));
		foreach ($this->config->get($this->name) as $option)
		{
			$s_option = htmlspecialchars($option[0]);
			$selected = array_key_exists(trim($option[0]),$defaults) ? ' selected="selected"' : '';
			$retval .= " <option value=\"$s_option\"$selected>$s_option</option>\n";
		}
		$retval .= "</select>";

		return $retval;
	}
}
class Tracker_field_checkbox extends Tracker_field_radio
{
	var $sort_type = SORT_NUMERIC;

	function get_tag($empty=FALSE)
	{
		$s_name = htmlspecialchars($this->name);
		$defaults = array_flip(preg_split('/\s*,\s*/',$this->default_value,-1,PREG_SPLIT_NO_EMPTY));
		$retval = '';
		$id = 0;
		foreach ($this->config->get($this->name) as $option)
		{
			$s_option = htmlspecialchars($option[0]);
			$checked = array_key_exists(trim($option[0]),$defaults) ? ' checked="checked"' : '';
			++$id;
			$s_id = '_p_tracker_' . $s_name . '_' . $this->id . '_' . $id;
			$retval .= "<input type=\"checkbox\" id=\"{$s_id}\" name=\"{$s_name}[]\" value=\"$s_option\"$checked /><label for=\"{$s_id}\">$s_option</label>\n";
		}

		return $retval;
	}
}
class Tracker_field_hidden extends Tracker_field_radio
{
	var $sort_type = SORT_NUMERIC;

	function get_tag($empty=FALSE)
	{
		$s_name = htmlspecialchars($this->name);
		$s_default = htmlspecialchars($this->default_value);
		$retval = "<input type=\"hidden\" name=\"$s_name\" value=\"$s_default\" />\n";

		return $retval;
	}
}
class Tracker_field_submit extends Tracker_field
{
	function get_tag()
	{
		$s_title = htmlspecialchars($this->title);
		$s_page = htmlspecialchars($this->page);
		$s_refer = htmlspecialchars($this->refer);
		$s_config = htmlspecialchars($this->config->config_name);

		return <<<EOD
<input type="submit" value="$s_title" />
<input type="hidden" name="plugin" value="tracker" />
<input type="hidden" name="_refer" value="$s_refer" />
<input type="hidden" name="_base" value="$s_page" />
<input type="hidden" name="_config" value="$s_config" />
EOD;
	}
}
class Tracker_field_date extends Tracker_field
{
	var $sort_type = SORT_NUMERIC;

	function format_cell($timestamp)
	{
		return format_date($timestamp);
	}
}
class Tracker_field_past extends Tracker_field
{
	var $sort_type = SORT_NUMERIC;

	function format_cell($timestamp)
	{
		return get_passage($timestamp,FALSE);
	}
	function get_value($value)
	{
		return UTIME - $value;
	}
}
///////////////////////////////////////////////////////////////////////////
// ����ɽ��
function plugin_tracker_list_convert()
{
	global $vars;

	$config = 'default';
	$page = $refer = $vars['page'];
	$field = '_page';
	$order = '';
	$list = 'list';
	$limit = NULL;
	if (func_num_args())
	{
		$args = func_get_args();
		switch (count($args))
		{
			case 4:
				$limit = is_numeric($args[3]) ? $args[3] : $limit;
			case 3:
				$order = $args[2];
			case 2:
				$args[1] = get_fullname($args[1],$page);
				$page = is_pagename($args[1]) ? $args[1] : $page;
			case 1:
				$config = ($args[0] != '') ? $args[0] : $config;
				list($config,$list) = array_pad(explode('/',$config,2),2,$list);
		}
		// �����ȵ�����
		//if (!$order) $order = '_real:SORT_DESC';
	}
	return plugin_tracker_getlist($page,$refer,$config,$list,$order,$limit);
}
function plugin_tracker_list_action()
{
	global $script,$vars,$_tracker_messages;

	$page = $refer = $vars['refer'];
	$s_page = make_pagelink($page);
	$config = $vars['config'];
	$list = array_key_exists('list',$vars) ? $vars['list'] : 'list';
	$order = array_key_exists('order',$vars) ? $vars['order'] : '_real:SORT_DESC';

	return array(
		'msg' => $_tracker_messages['msg_list'],
		'body'=> str_replace('$1',$s_page,$_tracker_messages['msg_back']).
			plugin_tracker_getlist($page,$refer,$config,$list,$order)
	);
}
function plugin_tracker_getlist($page,$refer,$config_name,$list,$order='',$limit=NULL)
{
	$config = new Config('plugin/tracker/'.$config_name);

	if (!$config->read())
	{
		return "<p>config file '".htmlspecialchars($config_name)."' is not exist.";
	}

	$config->config_name = $config_name;

	if (!is_page($config->page.'/'.$list))
	{
		return "<p>config file '".make_pagelink($config->page.'/'.$list)."' not found.</p>";
	}

	$list = &new Tracker_list($page,$refer,$config,$list);
	$list->sort($order);
	return $list->toString($limit);
}

// �������饹
class Tracker_list
{
	var $page;
	var $config;
	var $list;
	var $fields;
	var $pattern;
	var $pattern_fields;
	var $rows;
	var $order;

	function Tracker_list($page,$refer,&$config,$list)
	{
		$page = strip_bracket($page);
		$this->page = $page;
		$this->config = &$config;
		$this->list = $list;
		$this->fields = plugin_tracker_get_fields($page,$refer,$config);

		$pattern = join('',plugin_tracker_get_source($config->page.'/page'));
		// �֥�å��ץ饰�����ե�����ɤ��ִ�
		// #comment�ʤɤ������ʸ��������������ä����ˡ�[_block_xxx]�˵ۤ����ޤ���褦�ˤ���
		$pattern = preg_replace('/^\#([^\(\s]+)(?:\((.*)\))?\s*$/m','[_block_$1]',$pattern);

		// �ѥ����������
		$this->pattern = '';
		$this->pattern_fields = array();
		$pattern = preg_split('/\\\\\[(\w+)\\\\\]/',preg_quote($pattern,'/'),-1,PREG_SPLIT_DELIM_CAPTURE);
		while (count($pattern))
		{
			$this->pattern .= preg_replace('/\s+/','\\s*','(?>\\s*'.trim(array_shift($pattern)).'\\s*)');
			if (count($pattern))
			{
				$field = array_shift($pattern);
				$this->pattern_fields[] = $field;
				$this->pattern .= '(.*)';
			}
		}
		// �ڡ��������ȼ�����
		$this->rows = array();
		$pattern = "$page/";
		$pattern_len = strlen($pattern);
		foreach (get_existpages_db(false,$page."/",0," ORDER BY `buildtime` ASC ") as $_page)
		{
			$_page = strip_bracket($_page);
			$name = substr($_page,$pattern_len);
			if (preg_match(TRACKER_LIST_EXCLUDE_PATTERN,$name))
			{
				continue;
			}
			$this->add($_page,$name);
		}
	}
	function add($page,$name)
	{
		static $moved = array();

		// ̵�¥롼���ɻ�
		if (array_key_exists($name,$this->rows))
		{
			return;
		}

		$source = plugin_tracker_get_source($page);
		if (preg_match('/move\sto\s(.+)/',$source[0],$matches))
		{
			$page = strip_bracket(trim($matches[1]));
			if (array_key_exists($page,$moved) or !is_page($page))
			{
				return;
			}
			$moved[$page] = TRUE;
			return $this->add($page,$name);
		}
		$source = join('',preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/','$1$2',$source));

		// �ǥե������
		$this->rows[$name] = array(
			'_page'  => "[[$page]]",
			'_refer' => $this->page,
			'_real'  => $name,
			'_update'=> get_filetime(add_bracket($page)),
			'_past'  => get_filetime(add_bracket($page))
		);
		if ($this->rows[$name]['_match'] = preg_match("/{$this->pattern}/s",$source,$matches))
		{
			array_shift($matches);
			foreach ($this->pattern_fields as $key=>$field)
			{
				$this->rows[$name][$field] = trim($matches[$key]);
			}
		}
	}
	function sort($order)
	{
		if ($order == '')
		{
			return;
		}
		$names = array_flip(array_keys($this->fields));
		$this->order = array();
		foreach (explode(';',$order) as $item)
		{
			list($key,$dir) = array_pad(explode(':',$item),1,'ASC');
			if (!array_key_exists($key,$names))
			{
				continue;
			}
			switch (strtoupper($dir))
			{
				case 'SORT_ASC':
				case 'ASC':
				case SORT_ASC:
					$dir = SORT_ASC;
					break;
				case 'SORT_DESC':
				case 'DESC':
				case SORT_DESC:
					$dir = SORT_DESC;
					break;
				default:
					continue;
			}
			$this->order[$key] = $dir;
		}
		$keys = array();
		$params = array();
		foreach ($this->order as $field=>$order)
		{
			if (!array_key_exists($field,$names))
			{
				continue;
			}
			foreach ($this->rows as $row)
			{
				$keys[$field][] = $this->fields[$field]->get_value($row[$field]);
			}
			$params[] = $keys[$field];
			$params[] = $this->fields[$field]->sort_type;
			$params[] = $order;

		}
		$params[] = &$this->rows;

		call_user_func_array('array_multisort',$params);
	}
	function replace_item($arr)
	{
		$params = explode(',',$arr[1]);
		$name = array_shift($params);
		if ($name == '')
		{
			$str = '';
		}
		else if (array_key_exists($name,$this->items))
		{
			$str = $this->items[$name];
			if (array_key_exists($name,$this->fields))
			{
				$str = $this->fields[$name]->format_cell($str);
			}
		}
		else
		{
			return $this->pipe ? str_replace('|','&#x7c;',$arr[0]) : $arr[0];
		}
		$style = count($params) ? $params[0] : $name;
		if (array_key_exists($style,$this->items)
			and array_key_exists($style,$this->fields))
		{
			$str = sprintf($this->fields[$style]->get_style($this->items[$style]),$str);
		}
		return $this->pipe ? str_replace('|','&#x7c;',$str) : $str;
	}
	function replace_title($arr)
	{
		global $script;

		$field = $sort = $arr[1];
		if ($sort == '_name' or $sort == '_page')
		{
			$sort = '_real';
		}
		if (!array_key_exists($field,$this->fields))
		{
			return $arr[0];
		}
		$dir = SORT_ASC;
		$arrow = '';
		$order = $this->order;

		if (is_array($order) && isset($order[$sort]))
		{
			$index = array_flip(array_keys($order));
			$pos = 1 + $index[$sort];
			$b_end = ($sort == array_shift(array_keys($order)));
			$b_order = ($order[$sort] == SORT_ASC);
			$dir = ($b_end xor $b_order) ? SORT_ASC : SORT_DESC;
			$arrow = '&br;'.($b_order ? '&uarr;' : '&darr;')."($pos)";
			unset($order[$sort]);
		}
		$title = $this->fields[$field]->title;
		$r_page = rawurlencode($this->page);
		$r_config = rawurlencode($this->config->config_name);
		$r_list = rawurlencode($this->list);
		$_order = array("$sort:$dir");
		if (is_array($order))
			foreach ($order as $key=>$value)
				$_order[] = "$key:$value";
		$r_order = rawurlencode(join(';',$_order));

		return "[[$title$arrow>".XOOPS_WIKI_HOST."$script?plugin=tracker_list&refer=$r_page&config=$r_config&order=$r_order]]";
	}
	function toString($limit=NULL)
	{
		global $_tracker_messages;

		$source = '';
		$body = array();

		if ($limit !== NULL and count($this->rows) > $limit)
		{
			$source = str_replace(
				array('$1','$2'),
				array(count($this->rows),$limit),
				$_tracker_messages['msg_limit'])."\n";
			$this->rows = array_splice($this->rows,0,$limit);
		}
		if (count($this->rows) == 0)
		{
			return '';
		}
		foreach (plugin_tracker_get_source($this->config->page.'/'.$this->list) as $line)
		{
			if (preg_match('/^\|(.+)\|[hHfFcC]$/',$line))
			{
				$source .= preg_replace_callback('/\[([^\[\]]+)\]/',array(&$this,'replace_title'),$line);
			}
			else
			{
				$body[] = $line;
			}
		}
		foreach ($this->rows as $key=>$row)
		{
			if (!TRACKER_LIST_SHOW_ERROR_PAGE and !$row['_match'])
			{
				continue;
			}
			$this->items = $row;
			foreach ($body as $line)
			{
				if (trim($line) == '')
				{
					$source .= $line;
					continue;
				}
				$this->pipe = ($line{0} == '|' or $line{0} == ':');
				//$source .= preg_replace_callback('/\[([^\[\]]+)\]/',array(&$this,'replace_item'),$line);
				$source .= rtrim(preg_replace_callback('/\[([^\[\]]+)\]/',array(&$this,'replace_item'),$line))."\n";
			}
		}
		return convert_html($source);
	}
}
function plugin_tracker_get_source($page)
{
	$source = get_source($page);
	// ���Ф��θ�ͭID������
	$source = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m','$1$2',$source);
	// �ڡ���������
	delete_page_info($source);
	return $source;
}
?>
