<?php
// メッセージ設定
function plugin_filesdel_init()
{
	$messages = array(
		'_filesdel_messages'=>array(
			'title'  => '添付ファイル・カウンターファイルの削除',
			'msg_done'      => '以下の添付ファイル・カウンターファイルの削除処理が完了しました。',
			'msg_err'      => '管理者以外はアクセスできません。',
			'msg_usage'     => "",
		)
	);
	set_plugin_messages($messages);
}


function plugin_filesdel_action()
{
	// 実行時間を制限しない
	//set_time_limit(0);
	// 出力をバッファリングしない
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
		// 添付ファイルDB
		$del_files = attach_db_write(array('pgid'=>($vars['_pgid'])),'delete');
		
		$att = $thm = array();
		
		if (is_array($del_files) && $del_files)
		{
			foreach($del_files as $del_file)
			{
				$name = $vars['tgt']."_".encode($del_file);
				// 添付ファイル
				if (file_exists(UPLOAD_DIR.$name))
				{
					unlink(UPLOAD_DIR.$name);
					$att[] = $del_file." [$name]";
				}
				//サムネイル
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
		
		// 添付ファイル
		$msg = "<hr />\nAttach files:<br />".join("<br />",$att)."\n";
		
		//サムネイル
		$msg .= "<hr />\nThumbnail files:<br />".join("<br />",$thm)."\n";
		
		//カウンターファイル
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