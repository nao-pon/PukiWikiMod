<?php
// $Id: urlbookmark.inc.php,v 1.4 2004/12/23 14:00:22 nao-pon Exp $

/*
 * PukiWiki urlbookmark �ץ饰����
 * Copyright (C) 2003, Kazunori Mizushima <kazunori@mzsm.net>
 *
 * URL �Υ֥å��ޡ������뤿��Υץ饰����Ǥ���
 * URL �����Ϥ���С����Υ����Ȥ�HTML���ɹ��ߥ����ȥ��ưŪ�˼������ޤ���
 * comment �ץ饰�����١����˺�ä��Τǡ�comment �ץ饰�����Ʊ���������Ȥ��ޤ���
 *
 * [������]
 * #urlbookmark
 * #urlbookmark(below)
 * #urlbookmark(nodate)
 * #urlbookmark(nodate,notitle)
 *
 * [����]
 * ���ΰ����򥫥�ޤǶ��ڤäƻ��ꤹ�뤳�Ȥ��Ǥ��ޤ���
 * below   ���ϥե�����β����ɲä���Ƥ����ޤ��� 
 * nodate  ���դ��Ĥ��ޤ��� 
 * notile  �����ȥ�����Ϲ��ܤ�ɽ������ޤ���
 */

/////////////////////////////////////////////////
// URL�ƥ����ȥ��ꥢ�Υ�����
define('URLBOOKMARK_URL_COLS',70);
/////////////////////////////////////////////////
// �����ȥ�ƥ����ȥ��ꥢ�Υ�����
define('URLBOOKMARK_TITLE_COLS',40);
/////////////////////////////////////////////////
// �����ȤΥƥ����ȥ��ꥢ�Υ�����
define('URLBOOKMARK_COMMENT_COLS',70);
/////////////////////////////////////////////////
// �֥å��ޡ����������ե����ޥå�
define('URLBOOKMARK_NAME_FORMAT','$name');
define('URLBOOKMARK_MSG_FORMAT',' -- $msg');
define('URLBOOKMARK_NOW_FORMAT','&new{$now};');
/////////////////////////////////////////////////
// �֥å��ޡ����������ե����ޥå�(����������)
define('URLBOOKMARK_FORMAT',"\x08NAME\x08 \x08MSG\x08 \x08NOW\x08");
/////////////////////////////////////////////////
// �֥å��ޡ���������������� 1:����� 0:��θ�
define('URLBOOKMARK_INS',1);
/////////////////////////////////////////////////
// �֥å��ޡ�������Ƥ��줿��硢���Ƥ�᡼���������
//define('URLBOOKMARK_MAIL',FALSE);

function plugin_urlbookmark_init()
{
	$messages = array(
		'_btn_url'		=> 'URL: ',
		'_title_urlbookmark'	=> '�����ȥ�: ',
		'_title_auto'	=> '(��ư����)',
		'_btn_urlbookmark'	=> 'URL���ɲ�',
		'_msg_urlbookmark'	=> '������(��ά��): ',
		'_title_urlbookmark_collided'	=> '$1 �ǡڹ����ξ��ۤ͡������ޤ���',
		'_msg_urlbookmark_collided'	=> '���ʤ������Υڡ������Խ����Ƥ���֤ˡ�¾�οͤ�Ʊ���ڡ����򹹿����Ƥ��ޤä��褦�Ǥ���<br />�֥å��ޡ������ɲä��ޤ��������㤦���֤���������Ƥ��뤫�⤷��ޤ���<br />'
	);
	set_plugin_messages($messages);
}

function plugin_urlbookmark_action()
{
	global $script,$vars,$post,$now;
	global $_title_updated;
	global $_msg_urlbookmark_collided,$_title_urlbookmark_collided,$_title_auto;
	
	$post['msg'] = preg_replace("/\n/",'',$post['msg']);

	$url = $post['url'];
	
	if ($url == '') {
		return array('msg'=>'','body'=>'');
	}
	
	$head = '';
	if (preg_match('/^(-{1,2})(.*)/',$post['msg'],$match))
	{
		$head = $match[1];
		$post['msg'] = $match[2];
	}

	$title = $post['title'];
	if ($title == '' || $title == $_title_auto)
	{
		// try to get the title from the site
		$title = plugin_urlbookmark_get_title($url);
	}
	

	if ($title == '')
	{
		$_name = str_replace('$name',$url,URLBOOKMARK_NAME_FORMAT);
	}
	else
	{
		$patterns = array ("/:/", "/\[/", "/\]/");
		$replace  = array (" ", "(", ")");
		$title = preg_replace($patterns, $replace,$title);
		$_name = str_replace('$name','[['.$title.":".$url.']]',URLBOOKMARK_NAME_FORMAT);
	}

	$_msg  =                                 str_replace('$msg', $post['msg'], URLBOOKMARK_MSG_FORMAT);
	$_now  = ($post['nodate'] == '1') ? '' : str_replace('$now', $now,         URLBOOKMARK_NOW_FORMAT);
	
	$urlbookmark = str_replace("\x08MSG\x08", $_msg, URLBOOKMARK_FORMAT);
	$urlbookmark = str_replace("\x08NAME\x08",$_name,$urlbookmark);
	$urlbookmark = str_replace("\x08NOW\x08", $_now, $urlbookmark);
	$urlbookmark = $head.$urlbookmark;
	
	$postdata = '';
	$postdata_old  = get_source($post['refer']);
	$urlbookmark_no = 0;
	$urlbookmark_ins = ($post['above'] == '1');
	
	foreach ($postdata_old as $line)
	{
		if (!$urlbookmark_ins)
		{
			$postdata .= $line;
		}
		if (preg_match('/^#urlbookmark/',$line) and $urlbookmark_no++ == $post['urlbookmark_no'])
		{
			$postdata = rtrim($postdata)."\n-$urlbookmark\n";
			if ($urlbookmark_ins)
			{
				$postdata .= "\n";
			}
		}
		if ($urlbookmark_ins)
		{
			$postdata .= $line;
		}
	}
	
	$title = $_title_updated;
	$body = '';
	if (md5(@join('',get_source($post['refer']))) != $post['digest'])
	{
		$title = $_title_urlbookmark_collided;
		$body = $_msg_urlbookmark_collided . make_pagelink($post['refer']);
	}
	
	page_write($post['refer'],$postdata);
	
	$retvars['msg'] = $title;
	$retvars['body'] = $body;
	
	$post['page'] = $vars['page'] = $post['refer'];
	
	return $retvars;
}
function plugin_urlbookmark_convert()
{
	global $script,$vars,$digest;
	global $_btn_urlbookmark,$_btn_url,$_msg_urlbookmark, $_title_urlbookmark, $_title_auto;
	static $numbers = array();
	
	if (!array_key_exists($vars['page'],$numbers))
	{
		$numbers[$vars['page']] = 0;
	}
	$urlbookmark_no = $numbers[$vars['page']]++;
	
	$options = func_num_args() ? func_get_args() : array();
	
	// �Խ����¤�ɬ�ס�
	if (in_array('auth',$options) && !check_editable($vars["page"],false,false))
	{
		return "";
	}
	
	if (in_array('notitle',$options)) {
		$titletags = "";
	}
	else {
		$titletags = $_title_urlbookmark . "<input type='text' name='title' size='".URLBOOKMARK_TITLE_COLS."' value='{$_title_auto}' /><br/>\n";
	}
		
	$nodate = in_array('nodate',$options) ? '1' : '0';
	$above = in_array('above',$options) ? '1' : (in_array('below',$options) ? '0' : URLBOOKMARK_INS);
	
	$s_page = htmlspecialchars($vars['page']);
	$urlbookmark_cols = URLBOOKMARK_COMMENT_COLS;
	$url_cols = URLBOOKMARK_URL_COLS;
	$string = <<<EOD
<br />
<form action="$script" method="post">
 <div>
  <input type="hidden" name="urlbookmark_no" value="$urlbookmark_no" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="plugin" value="urlbookmark" />
  <input type="hidden" name="nodate" value="$nodate" />
  <input type="hidden" name="above" value="$above" />
  <input type="hidden" name="digest" value="$digest" />
  $_btn_url <input type="text" name="url" size="$url_cols" /><br/>
  $titletags
  $_msg_urlbookmark <input type="text" name="msg" size="$urlbookmark_cols" /><br/>
  <input type="submit" value="$_btn_urlbookmark" />
 </div>
</form>
EOD;
	
	return $string;
}

function plugin_urlbookmark_get_title($url) {
	$str = "";
	$found_title = false;
	
	$data = http_request($url);
	if ($data['rc'] !== 200)
	{
		return '';
	}
	$buf = preg_replace("/(\r|\n)+/i", "", $data['data']);
	$buf = mb_convert_encoding($buf,SOURCE_ENCODING,"auto");

	$tmpary = array();
	preg_match('/<title(\s+[^>]+)*>(.*)<\/title\s*>/i', $buf, $tmpary);

	return trim($tmpary[2]);
}
?>