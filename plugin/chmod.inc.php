<?php
// $Id: chmod.inc.php,v 1.1 2003/06/28 06:01:54 nao-pon Exp $
if(LANG == "ja"){
	define("CHMOD_TITLE", "パーミッションの変更");
	define("CHMOD_EXECUTE", "実行");
	define("CHMOD_INVALID_PASS", "パスワードが違います。");
	define("CHMOD_COMPLATE", "処理が完了しました。");
	define("CHMOD_ADMINPASS", "管理者パスワード");
	define("CHMOD_CANNOT_OPENDIR", "ディレクトリが開けません。");
	define("CHMOD_CANNOT_CHANGE_PERMIT", "パーミッションの変更に失敗しました");
} else {
	define("CHMOD_TITLE", "Change Permit");
	define("CHMOD_EXECUTE", "Execute");
	define("CHMOD_INVALID_PASS", "Invalid Password.");
	define("CHMOD_COMPLATE", "Processing was completed.");
	define("CHMOD_ADMINPASS", "Administrator Password.");
	define("CHMOD_CANNOT_OPENDIR", "Can not Open dir");
	define("CHMOD_CANNOT_CHANGE_PERMIT", "Can not Change Permit");
}

function plugin_chmod_action() {
	global $vars,$adminpass,$script;

	$ret['msg'] = CHMOD_TITLE;
	if($_SERVER['REQUEST_METHOD'] == "GET"){
		$ret['body'] = "
			<div style='text-align:left;'>
				<form method='post' action='$script?plugin=chmod'>
					<input type='checkbox' name='attach'>attach<br>
					<input type='checkbox' name='backup'>backup<br>
					<input type='checkbox' name='cache'>cache<br>
					<input type='checkbox' name='counter'>counter<br>
					<input type='checkbox' name='diff'>diff<br>
					<input type='checkbox' name='wiki'>wiki<p>
					".CHMOD_ADMINPASS."<br>
					<input type='password' name='password' value=''><br>
					<input type='submit' value='".CHMOD_EXECUTE."'>
				</form>
			</div>";
	} else {
		if(md5($vars['password']) != $adminpass){
			$ret['body'] = CHMOD_INVALID_PASS;
		} else {
			if(isset($vars['attach']))
				$ret['body'] .= _changePermit('./attach/');
			if(isset($vars['cache']))
				$ret['body'] .= _changePermit('./cache/');
			if(isset($vars['backup']))
				$ret['body'] .= _changePermit('./backup/');
			if(isset($vars['counter']))
				$ret['body'] .= _changePermit('./counter/');
			if(isset($vars['diff']))
				$ret['body'] .= _changePermit('./diff/');
			if(isset($vars['wiki']))
				$ret['body'] .= _changePermit('./wiki/');
			$ret['body'] .= CHMOD_COMPLATE;

		}
	}

	return $ret;
}

function _changePermit($_target_dir){
	if ($dir = @opendir($_target_dir)) {
		while($file = readdir($dir)) {
			if($file == ".." || $file == ".") continue;
			if(!chmod(trim($_target_dir."$file"), 0666))
				$msg .= CHMOD_CANNOT_CHANGE_PERMIT." => $_target_dir$file<br>";
		}
		closedir($dir);
		return $msg;
	} else {
		return CHMOD_CANNOT_OPENDIR."$_target_dir<br>";
	}
}

?>
