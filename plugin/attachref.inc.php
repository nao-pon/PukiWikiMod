<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: attachref.inc.php,v 1.5 2004/06/22 14:34:34 nao-pon Exp $
// ORG: attachref.inc.php,v0.5 2003/07/31 14:15:29 sha Exp $
//

/*
*�ץ饰���� attachref
 ���ξ��ź�դ��롣attach & ref

*Usage
 &attachref;
 &attachref(file[,<ref options>][,button]);

*�ѥ�᡼��
-file: attach����ȼ�ưŪ���ɲä���롣
-<ref options>: &ref;�Ѥΰ�����
-button: [attach]�Τ褦�ʥ�󥯤Ǥʤ���<form></form>�Υܥ���ˤ��롣

*ư��
(1)&attachref;���ɲä���ȡ�[attach]�ܥ���ɽ������롣
(2)[attach]�ܥ���򲡤��ȡ����åץ��ɥե����ब�����ơ����ꤷ���ե�����
  ��ź�դǤ��롣
(3)ź�դ��줿�ե������&ref(...);�ǻ��Ȥ����褦��Ž���դ����롣
(4)���Υե������������ȡ�"file not found"��[attach]�ܥ���ɽ������롣
(5)(4)�ΤȤ��ˡ����Υե����뤬�ɲä����ȺƤ����褹�롣

*/

// max file size for upload on script of PukiWiki(default 1MB)
define('MAX_FILESIZE',1000000);

// �����Ԥ�����ź�եե�����򥢥åץ��ɤǤ���褦�ˤ���
define('ATTACHREF_UPLOAD_ADMIN_ONLY',FALSE); // FALSE or TRUE
// ���åץ���/������˥ѥ���ɤ��׵᤹��(ADMIN_ONLY��ͥ��)
define('ATTACHREF_PASSWORD_REQUIRE',FALSE); // FALSE or TRUE


// upload dir(must set end of /) attach.inc.php�ȹ�碌��
define('ATTACHREF_UPLOAD_DIR','./attach/');


function plugin_attachref_init()
{
	$messages = array(
		'_attachref_messages' => array(
			'btn_submit'    => 'ź��',
			'msg_title'     => '$1 �ػ���ե������ź�դ��ƻ��Ȥ����ꤷ�ޤ�����',
			'msg_title_collided' => '$1 �ػ���ե������ź�դ��ƻ��Ȥ����ꤷ�ޤ������ڹ����ξ��ۤ͡������ޤ�����',
			'msg_collided'  => '���ʤ����ե������ź�դ��Ƥ���֤ˡ�¾�οͤ�Ʊ���ڡ����򹹿����Ƥ��ޤä��褦�Ǥ���<br />
�ե����뤬�㤦���֤���������Ƥ��뤫�⤷��ޤ���<br />'
		),
	);
	set_plugin_messages($messages);
	require_once(PLUGIN_DIR."ref.inc.php");
}
function plugin_attachref_inline()
{
	global $script,$vars,$digest;
	global $_attachref_messages;
	static $numbers = array();
	
	if (!array_key_exists($vars['page'],$numbers))
	{
		$numbers[$vars['page']] = 0;
	}
	$attachref_no = $numbers[$vars['page']]++;
	
	//�����
	$ret = '';
	$dispattach = 1;
	$button = 0;
	$btn_text = $_attachref_messages['btn_submit'];

	$args = func_get_args();
	if ( $args[count($args)-1] == '' ){
	    array_pop($args);
	}
	if ( $args[count($args)-1] === 'button' ){
	    $button = 1;
	    array_pop($args);
	}
	if ( preg_match("/^btn(:.+)/",$args[count($args)-1],$tmp))
	{
	    $btn_text = htmlspecialchars($tmp[1]);
	    if (strtolower(substr($btn_text,-5)) == ":auth")
	    {
	    	$btn_text = substr($btn_text,0,strlen($btn_text)-5);
	    	if (is_freeze($vars['page']))
	    		$btn_text = ":";
	    	else
	    		if (!$btn_text) $btn_text = ":".$_attachref_messages['btn_submit'];
	    }
	    $btn_text = substr($btn_text,1);
	    array_pop($args);
	}
	if (func_num_args() and $args[0]!='' )
	{
		//ref�ѥѥ�᡼���Ѵ�
		//ź�եե�����̾�����
		//$args = func_get_args();
		$name = array_shift($args);

		$params = array('_args'=>array(),'nocache'=>FALSE,'_size'=>FALSE,'_w'=>0,'_h'=>0);
		
		foreach($args as $val){
			if ($val == nocache) {
				$params['nocache'] = TRUE;
				continue;
			}
			$params['_args'][] = $val;
		}

		$rets = plugin_ref_body($name,$args,$params);
		if ($rets['_error']) {
			$ret = $rets['_error'];
			$dispattach = 1;
			array_unshift($args,"");
		} else {
			$ret = $rets['_body'];
			$dispattach = 0;
		}

	}
	if ( $dispattach && $btn_text) {
		//XSS�ȼ������� - ���������褿�ѿ��򥨥�������
		$f_page = htmlspecialchars($vars['page']);
		$args = str_replace(",","\x08",$args);
		$s_args = trim(join(",", $args));
		if ( $button ){
			$s_args .= ",button";
			$f_args = htmlspecialchars($s_args);
			$ret = <<<EOD
  <form action="$script" method="post">
  <div>
  $ret
  <input type="hidden" name="attachref_no" value="$attachref_no" />
  <input type="hidden" name="attachref_opt" value="$f_args" />
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="plugin" value="attachref" />
  <input type="hidden" name="refer" value="$f_page" />
  <input type="submit" value="{$btn_text}" />
  </div>
  </form>
EOD;
		} else {
			$f_args = urlencode(htmlspecialchars($s_args));
			$f_page = urlencode($f_page);
			$ret = <<<EOD
  $ret<a href="$script?plugin=attachref&attachref_no=$attachref_no&attachref_opt=$f_args&refer=$f_page&digest=$digest">[{$btn_text}]</a>
EOD;
		}
	}
	return $ret;
}
function plugin_attachref_action()
{
	global $script,$vars,$post;
	global $_attachref_messages;
	global $html_transitional;


	//����ͤ�����
	$retval['msg'] = $_attachref_messages['msg_title'];
	$retval['body'] = '';
	
	if (is_uploaded_file($_FILES['attach_file']['tmp_name'])
		and array_key_exists('refer',$vars)
		and is_page($vars['refer']))
	{
		$file = $_FILES['attach_file'];
		$attachname = basename(str_replace("\\","/",$file['name']));
		$filename = preg_replace('/\..+$/','', $attachname,1);

		//���Ǥ�¸�ߤ�����硢 �ե�����̾��'_0','_1',...���դ��Ʋ���(��©)
		$count = '_0';
		while (file_exists(ATTACHREF_UPLOAD_DIR.encode($vars['refer']).'_'.encode($attachname)))
		{
			$attachname = preg_replace('/^[^\.]+/',$filename.$count++,$file['name']);
		}
		
		$file['name'] = $attachname;
		
		if (!exist_plugin('attach') or !function_exists('attach_upload'))
		{
			return array('msg'=>'attach.inc.php not found or not correct version.');
		}
		
		$copyright = (isset($post['copyright']))? TRUE : FALSE ;
		
		$retval = attach_upload($file,$vars['refer'],TRUE,$copyright);
		if ($retval['result'] == TRUE)
		{
			$retval = attachref_insert_ref($file['name']);
		}
	}
	else
	{
		if ($vars['url'] && is_url($vars['url'])){
			$retval = attachref_insert_ref($vars['url']);
		} else {
			$retval = attachref_showform();
			// XHTML 1.0 Transitional
			$html_transitional = TRUE;
		}
	}
	return $retval;
}

function attachref_insert_ref($filename)
{
	global $script,$vars,$now,$do_backup;
	global $_attachref_messages;
	
	$ret['msg'] = $_attachref_messages['msg_title'];
	
	$args = split(",", $vars['attachref_opt']);
	if ( count($args) ){
		array_shift($args);
		array_unshift($args,$filename);
		$s_args = join(",", $args);
	} else {
		$s_args = $filename;
	}
	//����������
	if ($vars['comment']) {
		$vars['comment'] = htmlspecialchars($vars['comment']);
		$vars['comment'] = str_replace("|","&#x7c;",$vars['comment']);
		$s_args .= ",\"t:".$vars['comment']."\"";
	}
	
	$msg = "&attachref($s_args);";
	
	$refer = $vars['refer'];
	$digest = $vars['digest'];
	$postdata_old = get_source($refer);
	$thedigest = md5(join('',$postdata_old));

	$postdata = '';
	$attachref_no = 0; //'#attachref'�νи����
	$skipflag = 0;
	foreach ($postdata_old as $line)
	{
		if ( $skipflag || substr($line,0,1) === ' '){
			$postdata .= $line;
			continue;
		}
		$ct = preg_match_all('/&attachref(\(((?:(?!\)[;{]).)*)\))?;/',$line, $out);
		if ( $ct ){
			for($i=0; $i < $ct; $i++){
				if ($attachref_no == $vars['attachref_no'] ){
					$line = preg_replace('/&attachref(\(((?:(?!\)[;{]).)*)\))?;/',$msg,$line,1);
					$skipflag = 1;
					break;
				} else {
					$line = preg_replace('/&attachref(\(((?:(?!\)[;{]).)*)\))?;/','&___attachref$1___;',$line,1);
				}
				$attachref_no++;
			}
			$line = preg_replace('/&___attachref(\(((?:(?!\)___[;{]).)*)\))?___;/','&attachref$1;',$line);
		}
		$postdata .= $line;
		
	}
	
	// �����ξ��ͤ򸡽�
	if ( $thedigest != $digest )
	{
		$ret['msg'] = $_attachref_messages['msg_title_collided'];
		$ret['body'] = $_attachref_messages['msg_collided'];
	}
	$mail_body = "Attached File: ".$filename."\n";
	page_write($vars['refer'],$postdata,NULL,"","","","","","",array('plugin'=>'attachref','mode'=>'none','text'=>$mail_body));
	
	return $ret;
}
//���åץ��ɥե������ɽ��
function attachref_showform()
{
	global $vars;
	global $_attach_messages;
	
	$vars['page'] = $vars['refer'];
	$body = ini_get('file_uploads') ? attachref_form($vars['page']) : 'file_uploads disabled.';
	
	return array('msg'=>$_attach_messages['msg_upload'],'body'=>$body);
}
//���åץ��ɥե�����
function attachref_form($page)
{
	global $script,$vars;
	global $_attach_messages;
	
	$s_page = htmlspecialchars($page);

	$f_digest = array_key_exists('digest',$vars) ? $vars['digest'] : '';
	$f_no = (array_key_exists('attachref_no',$vars) and is_numeric($vars['attachref_no'])) ?
		$vars['attachref_no'] + 0 : 0;


	if (!(bool)ini_get('file_uploads'))
	{
		return "";
	}
	
	$maxsize = MAX_FILESIZE;
	$msg_maxsize = sprintf($_attach_messages['msg_maxsize'],number_format($maxsize/1000)."KB");

	$pass = '';
	if (ATTACHREF_PASSWORD_REQUIRE or ATTACHREF_UPLOAD_ADMIN_ONLY)
	{
		$title = $_attach_messages[ATTACHREF_UPLOAD_ADMIN_ONLY ? 'msg_adminpass' : 'msg_password'];
		$pass = '<br />'.$title.': <input type="password" name="pass" size="8" />';
	}
	
	$s_args = htmlspecialchars($vars['attachref_opt']);
	$comment = "";
	if (preg_match("/t:([^,]*)/i",$s_args,$m_args)){
		$comment = $m_args[1];
		$comment = str_replace("\x08",",",$comment);
		$s_args = preg_replace("/(,)?t:([^,]*)/i","",$s_args);
	}

	return <<<EOD
<form enctype="multipart/form-data" action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attachref" />
  <input type="hidden" name="pcmd" value="post" />
  <input type="hidden" name="attachref_no" value="$f_no" />
  <input type="hidden" name="attachref_opt" value="{$s_args}" />
  <input type="hidden" name="digest" value="$f_digest" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
  <span class="small">
   $msg_maxsize
  </span><br /><br />
  {$_attach_messages['msg_file']}: <input type="file" name="attach_file" size="40" /><br />
  <input type="checkbox" name="copyright" value="1" /> &uarr; {$_attach_messages['msg_copyright']}<hr />
  or URL: <input type="text" name="url" size="60" /><hr />
  Comment: <input type="text" name="comment" size="60" value="{$comment}" /><br /><br />
  $pass
  <input type="submit" value="{$_attach_messages['btn_upload']}" />
 </div>
</form>
EOD;
}
?>
