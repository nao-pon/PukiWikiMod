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
	// �¹Ի��֤����¤��ʤ�
	//set_time_limit(0);
	// ���Ϥ�Хåե���󥰤��ʤ�
	//ob_end_clean();
	//echo str_pad('',256);//for IE
	
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
		$del_files = attach_db_write(array('pgid'=>($vars['_pgid'])),'delete');
		
		$att = $thm = array();
		
		if (is_array($del_files) && $del_files)
		{
			foreach($del_files as $del_file)
			{
				$name = $vars['tgt']."_".encode($del_file);
				// ź�եե�����
				if (file_exists(UPLOAD_DIR.$name))
				{
					unlink(UPLOAD_DIR.$name);
					$att[] = $del_file." [$name]";
				}
				//����ͥ���
				/*
				if (file_exists(UPLOAD_DIR."s/".$name))
				{
					unlink(UPLOAD_DIR."s/".$name);
				}
				*/
				for ($i = 1; $i < 100; $i++)
				{
					$file = $vars['tgt'].'_'.encode($i."%").encode($del_file);
					if (file_exists(UPLOAD_DIR."s/".$file))
					{
						unlink(UPLOAD_DIR."s/".$file);
						$thm[] = $i.'%'.$del_file." [$name]";
					}
				}
			}
		}
		
		// ź�եե�����
		$msg = "<hr />\nAttach files:<br />".join("<br />",$att)."\n";
		
		//����ͥ���
		$msg .= "<hr />\nThumbnail files:<br />".join("<br />",$thm)."\n";
		
		//�����󥿡��ե�����
		include_once("./plugin/counter.inc.php");
		
		$msg .= "<hr />\nCounter file:<br />\n";
		
		$name = $vars['tgt'].COUNTER_EXT;
		if (file_exists(COUNTER_DIR.$name))
		{
			unlink(COUNTER_DIR.$name);
			$msg .= $name."<br />";
		}
		
		return array(
			'msg'=>strip_bracket(decode($vars['tgt']))." - ".$_filesdel_messages['title'],
			'body'=>$_filesdel_messages['msg_done'].$msg
		);
	}
}

?>