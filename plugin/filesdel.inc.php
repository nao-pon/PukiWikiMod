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
		attach_db_write(array('pgid'=>($vars['_pgid'])),'delete');
		
		// 添付ファイル
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
		
		//サムネイル
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
		
		//カウンターファイル
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