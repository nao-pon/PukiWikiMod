<?php
// ��å���������
function plugin_filesdel_init()
{
	$messages = array(
		'_filesdel_messages'=>array(
			'title'  => 'ź�եե����롦�����󥿡��ե�����κ��',
			'msg_done'      => '�ʲ���ź�եե����롦�����󥿡��ե�����κ����������λ���ޤ�����',
			'msg_err'      => '�����԰ʳ��ϥ��������Ǥ��ޤ���',
			'msg_usage'     => "",
		)
	);
	set_plugin_messages($messages);
}


function plugin_filesdel_action()
{
	global $script,$post,$vars,$adminpass,$foot_explain;
	global $_filesdel_messages,$X_admin;
	
	if (!$X_admin)
	{
		return array(
			'msg'=>strip_bracket(decode($vars['tgt']))." - ".$_filesdel_messages['title'],
			'body'=>$_filesdel_messages['msg_err']
		);
	}
	else
	{
		// ź�եե�����DB
		attach_db_write(array('pgid'=>($vars['_pgid'])),'delete');
		
		// ź�եե�����
		$msg = "<hr />\nAttach files:<br />\n";
		$pattern = $vars['tgt'];
		if ($dir = @opendir(UPLOAD_DIR)) {
			while ($name = readdir($dir)) {
				if ($name == '..' || $name == '.') { continue; }
				if (strpos($name, $pattern) === 0) {
					$msg .= $name."<br />";
					unlink(UPLOAD_DIR.$name);
				}
			}
			closedir($dir);
		}
		
		//����ͥ���
		$msg .= "<hr />\nThumbnail files:<br />\n";
		$pattern = $vars['tgt'];
		if ($dir = @opendir(UPLOAD_DIR."s/")) {
			while ($name = readdir($dir)) {
				if ($name == '..' || $name == '.') { continue; }
				if (strpos($name, $pattern) === 0) {
					$msg .= $name."<br />";
					unlink(UPLOAD_DIR."s/".$name);
				}
			}
			closedir($dir);
		}
		
		//�����󥿡��ե�����
		$msg .= "<hr />\nCounter file:<br />\n";
		if ($dir = @opendir(COUNTER_DIR)) {
			while ($name = readdir($dir)) {
				if ($name == '..' || $name == '.') { continue; }
				if (strpos($name, $pattern) === 0) {
					$msg .= $name."<br />";
					unlink(COUNTER_DIR.$name);
				}
			}
			closedir($dir);
		}
		
		
		return array(
			'msg'=>strip_bracket(decode($vars['tgt']))." - ".$_filesdel_messages['title'],
			'body'=>$_filesdel_messages['msg_done'].$msg
		);
	}
}

?>