<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: addline.inc.php,v 1.5 2005/12/18 14:10:47 nao-pon Exp $
// ORG: addline.inc.php,v 0.2 2003/07/29 22:47:10 sha Exp $
//
/* 
*�ץ饰���� addline
 ���ξ�ˡ������ʸ������ɲä��롣

*Usage
 #addline(����̾[,above|below])

*�ѥ�᡼��
  ����:   ��:config/plugin/addline/����פ�����̾�򵭺�
  above|below: �夫�����ɲä��롣

*����ڡ���������
 �ɲä���ʸ����򵭺ܤ��롣ʣ���ԤǤ�褤��
 �㡧
    |&attachref;|&attachref;|&attachref;|
    |������|������|������|
*/

/////////////////////////////////////////////////
// �����Ȥ������������ 1:����� 0:��θ�
define('ADDLINE_INS','1');

function plugin_addline_init()
{
	$messages = array(
		'_addline_messages' => array(
			'btn_submit'    => 'add',
			'title_collided' => '$1 �ǡڹ����ξ��ۤ͡������ޤ���',
			'msg_collided'  => '���ʤ���ʸ������ɲä��Ƥ���֤ˡ�¾�οͤ�Ʊ���ڡ����򹹿����Ƥ��ޤä��褦�Ǥ���<br />
ʸ���󤬰㤦���֤���������Ƥ��뤫�⤷��ޤ���<br />',
			'title_noauth'  => '$1 ���Խ����¤�����ޤ���',
			'msg_noauth'  => '���Υڡ��������Ƥ��ɲä��븢�¤�����ޤ���<br />',
		),
	);
	set_plugin_messages($messages);
}
function plugin_addline_action()
{
	global $script,$vars,$post,$now;
	global $_title_updated;
	global $_addline_messages;
	
	$postdata = '';
	$postdata_old  = get_source($post['refer']);
	$addline_no = 0;
	$addline_ins = ($post['above'] == '1');
	$configname = $post['configname'];


	$config = new Config('plugin/addline/'.$configname);
	if (!$config->read())
	{
		return "<p>config file '".htmlspecialchars($configname)."' is not exist.";
	}
	
	// �Խ����¥����å�
	if (!empty($vars['auth']))
	{
		if (is_freeze($post['refer']))
		{
			$retvars['msg'] = $_addline_messages['title_noauth'];
			$retvars['body'] = $_addline_messages['msg_noauth'] . make_pagelink($post['refer']);
			return $retvars;
		}
	}
	
	$config->config_name = $configname;
	$addline = join('', addline_get_source($config->page));
	$addline = rtrim($addline);

	foreach ($postdata_old as $line)
	{
		if (!$addline_ins)
		{
			$postdata .= $line;
		}
		if (preg_match('/(^|\|((LEFT|RIGHT|CENTER):)?)#addline/',$line) and $addline_no++ == $post['addline_no'])
		{
			//$postdata = rtrim($postdata)."\n$addline\n";
			$postdata = rtrim($postdata)."\n$addline\n";
			/*
			if ($addline_ins)
			{
				$postdata .= "\n";
			}
			*/
		}
		if ($addline_ins)
		{
			$postdata .= $line;
		}
	}
	$postdata = auto_br($postdata);
	$title = $_title_updated;
	$body = '';
	if (md5(@join('',get_source($post['refer']))) != $post['digest'])
	{
		$title = $_addline_messages['title_collided'];
		$body  = $_addline_messages['msg_collided'] . make_pagelink($post['refer']);
	}
	
	page_write($post['refer'],$postdata);
	
	$retvars['msg'] = $title;
	$retvars['body'] = $body;
	
	$post['page'] = $vars['page'] = $post['refer'];
	
	return $retvars;
}
function plugin_addline_convert()
{
	global $script,$vars,$digest;
	global $_addline_messages;
	static $numbers = array();
	
	if (!array_key_exists($vars['page'],$numbers))
	{
		$numbers[$vars['page']] = 0;
	}
	$addline_no = $numbers[$vars['page']]++;
	
	$above = ADDLINE_INS;
	$auth = 0;
	$configname = 'default';
	$btn_text = $_addline_messages['btn_submit'];
	if ( func_num_args() ){
		foreach ( func_get_args() as $opt ){
			if ( $opt === 'above' || $opt === 'up' )
			{
				$above = 1;
			}
			else if (preg_match("/btn(:.+)/i",$opt,$args))
			{
				$btn_text = htmlspecialchars($args[1]);
				if (strtolower(substr($btn_text,-5)) == ":auth")
				{
					$btn_text = substr($btn_text,0,strlen($btn_text)-5);
					$auth = 1;
					if (is_freeze($vars['page']))
						$btn_text = ":";
					else
						if (!$btn_text) $btn_text = ":". $_addline_messages['btn_submit'];
				}
				$btn_text = substr($btn_text,1);
			}
			else if ( $opt === 'below' || $opt === 'down' )
			{
				$above = 0;
			}
			else if ( $opt === 'auth' )
			{
				$auth = 1;
				if (is_freeze($vars['page'])) $btn_text = "";
			}
			else
			{
				$configname = $opt;
			}
		}
	}
	
	$s_page = htmlspecialchars($vars['page']);
	$string = "";
	if ($btn_text)
	{
		$string = <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="addline_no" value="$addline_no" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="plugin" value="addline" />
  <input type="hidden" name="above" value="$above" />
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="auth" value="$auth" />
  <input type="hidden" name="configname"  value="$configname" />
  <input type="submit" name="addline" value="$btn_text" />
 </div>
</form>
EOD;
	}
	return $string;
}
function addline_get_source($page) // tracker_list���顣
{
	$source = get_source($page);
	// ���Ф��θ�ͭID������
	$source = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m','$1$2',$source);
	// #freeze����
	$source = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",'',$source);
	$source = preg_replace("/^\/\/ author:([0-9]+)\n/","",$source);
	return $source;
}
?>