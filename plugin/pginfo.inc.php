<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: pginfo.inc.php,v 1.6 2004/10/29 12:54:09 nao-pon Exp $
//

// メッセージ設定
function plugin_pginfo_init()
{
	$messages = array(
		'_links_messages'=>array(
			'title_update'  => 'ページ情報DB更新',
			'msg_adminpass' => '管理者パスワード',
			'msg_all' => 'DBをすべて初期化&再設定',
			'msg_select' => '以下から選択して初期化&再設定',
			'msg_hint' => '初期導入時はすべてにチェックをつけて実行してください。',
			'msg_init' => 'ページ基本情報DB',
			'msg_retitle' => 'タイトル情報DB',
			'msg_plain_init' => '検索用PlainDB',
			'msg_attach_init' => '添付ファイル情報DB',
			'btn_submit'    => '実行',
			'msg_done'      => 'ページ情報DBの更新が完了しました。',
			'msg_usage'     => "
* 処理内容

:ページ情報DBを更新:全てのページをスキャンし、ページ情報DBを作成し直します。

* 注意
実行には数分かかる場合もあります。実行ボタンを押したあと、しばらくお待ちください。

* 実行
[実行]ボタンを ''1回のみ'' クリックしてください。~
この下に実行ボタンが表示されていない場合は、管理者権限でログインして再表示してください。
"
		)
	);
	set_plugin_messages($messages);
}

function plugin_pginfo_action()
{
	global $script,$post,$vars,$adminpass,$foot_explain;
	global $_links_messages,$X_admin;
	
	if (empty($vars['action']) or !$X_admin)
	{
		$body = convert_html($_links_messages['msg_usage']);
	if ($X_admin)
	{
		$body .= <<<EOD
<form method="POST" action="$script">
 <div>
  <input type="hidden" name="plugin" value="pginfo" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="mode" value="select" />
  {$_links_messages['msg_hint']}
  <div style="margin-left:20px;">
  <input type="checkbox" name="init" value="on" checked="true" />{$_links_messages['msg_init']}<br />
  <input type="checkbox" name="title" value="on" checked="true" />{$_links_messages['msg_retitle']}<br />
  <input type="checkbox" name="plain" value="on" checked="true" />{$_links_messages['msg_plain_init']}<br />
  <input type="checkbox" name="attach" value="on" checked="true" />{$_links_messages['msg_attach_init']}<br />
 </div>
  <br />
  <input type="submit" value="{$_links_messages['btn_submit']}" />
 </div>
</form>
EOD;
	}
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$body
		);
	}
	else if ($vars['action'] == 'update' && ($vars['mode'] == 'all' || $vars['mode'] == 'select'))
	{
		//error_reporting(E_ALL);
		echo "<html><body>\n";
		if ($vars['mode'] == 'all' || !empty($vars['init'])) pginfo_db_init();
		if ($vars['mode'] == 'all' || !empty($vars['title'])) pginfo_db_retitle();
		if ($vars['mode'] == 'all' || !empty($vars['plain'])) plain_db_init();
		if ($vars['mode'] == 'all' || !empty($vars['attach'])) attach_db_init();
		
		redirect_header("$script?plugin=pginfo",3,$_links_messages['msg_done']);
		exit();
	}
	
	return array(
		'msg'=>$_links_messages['title_update'],
		'body'=>$_links_messages['err_invalid']
	);
}

// ページ情報データベース初期化
function pginfo_db_init()
{
	global $xoopsDB,$whatsnew;
	if ($dir = @opendir(DATA_DIR))
	{
		//name テーブルの属性を BINARY にセット(検索で大文字・小文字を区別する)
		$query = 'ALTER TABLE `'.$xoopsDB->prefix("pukiwikimod_pginfo").'` CHANGE `name` `name` VARCHAR( 255 ) BINARY NOT NULL ';
		$result=$xoopsDB->queryF($query);
		
		//$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo");
		//$result=$xoopsDB->queryF($query);
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_count");
		$result=$xoopsDB->queryF($query);
		
		// ページ閲覧権限のキャッシュをクリアー
		get_pg_allow_viewer("",false,true);

		// 実行時間を制限しない
		set_time_limit(0);
		// 出力をバッファリングしない
		ob_end_clean();
		echo str_pad('',256);//for IE
		//echo "<html><body>";
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod_pginfo' Now converting... </b>( * = 10 Pages)<hr>";
		flush();
		$counter = 0;
		
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			
			$name=$aids=$gids=$vaids=$vgids= "";
			$buildtime=$editedtime=$lastediter=$uid=$freeze=$unvisible = 0;
			
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));

			if ($page === $whatsnew)
			{
				@unlink($file);
				continue;
			}
			
			$name = strip_bracket($page);
			
			// id取得
			$id = get_pgid_by_name($page);
			
			$name = addslashes($name);
			$buildtime = filectime(DATA_DIR.$file);
			$editedtime = filemtime(DATA_DIR.$file);
			if (!$buildtime) $buildtime = $editedtime;
			
			$checkpostdata = join("",get_source($page));
			if (!$checkpostdata)
			{
				@unlink($file);
				continue;
			}
			
			$counter++;
			if (($counter/10) == (floor($counter/10))){
				echo "*";
				flush();
			}
			if (($counter/100) == (floor($counter/100))) echo " ( Done ".$counter." Pages !)<br />";
			
			//echo $page."<hr />";
			// 編集権限
			if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
			{
				$freeze = 1;
				if (isset($arg[1])) $uid = $arg[1];
				if (isset($arg[2])) $aids = "&".str_replace(",","&",$arg[2])."&";
				if (isset($arg[3])) $gids = "&".str_replace(",","&",$arg[3])."&";
				$checkpostdata = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
			}
			else
			{
				$aids = "&all";
				$gids = "&3&";
				$freeze = 0;
			}
			
			// 閲覧権限
			if (preg_match("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
			{
				$unvisible = 1;
				$checkpostdata = preg_replace("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
			}
			$info = get_pg_allow_viewer($page);
			if (!isset($uid)) $uid = $info['owner'];
			$vaids = "&".str_replace(",","&",$info['user']);
			$vgids = "&".str_replace(",","&",$info['group']);

			// ページ作成者 元ページに記載があればその値を取得
			$author_uid = 0;
			if (preg_match("/^\/\/ author:([0-9]+)($|\n)/",$checkpostdata,$arg))
				if (!isset($uid)) $uid = $arg[1];
			
			if (!isset($uid) || $uid ==="") $uid = 0;
			$lastediter = $uid;
			
			if (!$id)
			{
				// 新規作成
				$query = "insert into ".$xoopsDB->prefix("pukiwikimod_pginfo")." (`name`,`buildtime`,`editedtime`,`aids`,`gids`,`vaids`,`vgids`,`lastediter`,`uid`,`freeze`,`unvisible`,`update`) values('$name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible,1);";
			}
			else
			{
				// アップデート
				
				$value = "`name`='$name'"
				.",`buildtime`='$buildtime'"
				.",`editedtime`='$editedtime'"
				.",`lastediter`='$lastediter'"
				.",`title`='$title'"
				.",`aids`='$aids'"
				.",`gids`='$gids'"
				.",`vaids`='$vaids'"
				.",`vgids`='$vgids'"
				.",`uid`='$uid'"
				.",`freeze`='$freeze'"
				.",`unvisible`='$unvisible'"
				.",`update`='1'";
				$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET $value WHERE id = '$id' LIMIT 1;";
			}
			$result=$xoopsDB->queryF($query);
			//echo $query."<hr>";
		}
		closedir($dir);
		// アップデートしなかったページ情報を削除
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo")." WHERE `update` = '0';";
		$result=$xoopsDB->queryF($query);
		
		// アップデートフラグ戻し
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET `update` = '0';";
		$result=$xoopsDB->queryF($query);
		
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";

	}
	// カウント情報
	if ($dir = @opendir(COUNTER_DIR))
	{

		// 実行時間を制限しない
		set_time_limit(0);
		// 出力をバッファリングしない
		ob_end_clean();
		echo str_pad('',256);//for IE
		//echo "<html><body>";
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod_counter' Now converting... </b>( * = 10 Pages)<hr>";
		flush();
		$counter = 0;
		
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_count");
		$result=$xoopsDB->queryF($query);
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".count")===FALSE) continue;
			//echo $file."<hr/>";
			
			$name=$today=$ip="";
			$count=$today_count=$yesterday_count=0;
			
			$page = decode(trim(preg_replace("/\.count$/"," ",$file)));
			// 存在しないページ
			if ($page == $whatsnew || !file_exists(DATA_DIR.encode($page).".txt"))
			{
				@unlink($file);
				continue;
			}
			
			$array = file(COUNTER_DIR.$file);
			$name = addslashes(strip_bracket($page));
			$count = rtrim($array[0]);
			$today = rtrim($array[1]);
			$today_count = rtrim($array[2]);
			$yesterday_count = rtrim($array[3]);
			$ip = rtrim($array[4]);
			
			$query = "insert into ".$xoopsDB->prefix("pukiwikimod_count")." (name,count,today,today_count,yesterday_count,ip) values('$name',$count,'$today',$today_count,$yesterday_count,'$ip');";
			$result=$xoopsDB->queryF($query);
			
			$counter++;
			if (($counter/10) == (floor($counter/10))){
				echo "*";
				flush();
			}
			if (($counter/100) == (floor($counter/100))) echo " ( Done ".$counter." Pages !)<br />";
		}
		closedir($dir);
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";
	}
	return ;
}

//titleフィールド再設定
function pginfo_db_retitle()
{
	global $xoopsDB,$whatsnew,$vars,$related;
	if ($dir = @opendir(DATA_DIR))
	{
		// 実行時間を制限しない
		set_time_limit(0);
		// 出力をバッファリングしない
		ob_end_clean();
		echo str_pad('',256);//for IE
		//echo "<html><body>";
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod_pginfo(only title)' Now converting... </b>( * = 10 Pages)<hr>";
		flush();
		$counter = 0;
		
		while($file = readdir($dir))
		{
			$related = array();
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			$vars['page'] = $page;
			if($page === $whatsnew) continue;
			$title = addslashes(str_replace(array('&lt;','&gt;','&amp;','&quot;','&#039;'),array('<','>','&','"',"'"),get_heading_init($page)));
			$name = addslashes(strip_bracket($page));
			$value = "title='$title'";
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod_pginfo")." SET $value WHERE name = '$name';";
			$result=$xoopsDB->queryF($query);
			
			$counter++;
			if (($counter/10) == (floor($counter/10))){
				echo "*";
				flush();
			}
			if (($counter/100) == (floor($counter/100))) echo " ( Done ".$counter." Pages !)<br />";

		}
		$vars['page'] = "";
		
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";
	}
}

// 検索用 plain DB 再設定
function plain_db_init()
{
	global $xoopsDB,$whatsnew,$vars,$post,$get,$related,$comment_no;
	
	// 実行時間を制限しない
	set_time_limit(0);
	// 出力をバッファリングしない
	ob_end_clean();
	echo str_pad('',256);//for IE
	//echo "<html><body>";
	echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod_plain' Now converting... </b>( * = 10 Pages)<hr>";
	flush();

	$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_plain");
	$result=$xoopsDB->queryF($query);
	if ($dir = @opendir(DATA_DIR))
	{
		$counter = 0;
		while($file = readdir($dir))
		{
			$related = array();
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			$post['page']=$get['page']=$vars['page'] = $page;
			$comment_no = 0;
			if($page === $whatsnew) continue;
			if (plain_db_write($page,"insert"))
			{
				$counter++;
				if (($counter/10) == (floor($counter/10))){
					echo "*";
					flush();
				}
				if (($counter/100) == (floor($counter/100))) echo " ( Done ".$counter." Pages !)<br />";
			}
		}
		$post['page']=$get['page']=$vars['page'] = "";
	}
	echo " ( Done ".$counter." Pages !)<hr />";
	echo "</div>";
	//echo "</body></html>";
}

// 添付ファイル DB 再設定
function attach_db_init()
{
	global $xoopsDB,$vars,$post,$get;
	
	// 実行時間を制限しない
	set_time_limit(0);
	// 出力をバッファリングしない
	ob_end_clean();
	echo str_pad('',256);//for IE
	//echo "<html><body>";
	echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod_attach' Now converting... </b>( * = 10 Pages)<hr>";
	flush();
	
	include_once(PLUGIN_DIR."attach.inc.php");

	$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_attach");
	$result=$xoopsDB->queryF($query);
	if ($dir = @opendir(UPLOAD_DIR))
	{
		$counter = 0;
		
		$page_pattern = '(?:[0-9A-F]{2})+';
		$age_pattern = '(?:\.([0-9]+))?';
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";
		
		while($file = readdir($dir))
		{
			if (!preg_match($pattern,$file,$matches))
			{
				continue;
			}
			$page = decode($matches[1]);
			$name = decode($matches[2]);
			$age = array_key_exists(3,$matches) ? $matches[3] : 0;
			
			// サムネイルは除外
			if (preg_match("/^\d\d?%/",$name)) continue;
			
			$obj = &new AttachFile($page,$name,$age);
			$obj->getstatus();
			
			$data['pgid'] = get_pgid_by_name($page);
			$data['name'] = $name;
			$data['mtime'] = $obj->time;
			$data['size'] = $obj->size;
			$data['type'] = $obj->type;
			$data['status'] = $obj->status;

			if (attach_db_write($data,"insert"))
			{
				$counter++;
				if (($counter/10) == (floor($counter/10))){
					echo "*";
					flush();
				}
				if (($counter/100) == (floor($counter/100))) echo " ( Done ".$counter." Files !)<br />";
			}
		}
		closedir($dir);
	}
	echo " ( Done ".$counter." Files !)<hr />";
	echo "</div>";
	//echo "</body></html>";
}

?>