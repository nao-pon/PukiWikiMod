<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: rename.inc.php,v 1.10 2006/10/12 23:33:47 nao-pon Exp $
//

/*
 * プラグイン rename
 * ページの名前を変える
 * 
 * Usage
 *  http:.../pukiwiki.php?plugin=rename(&refer=ページ名)
 *
 * パラメータ
 * &refer=ページ名
 * ページを指定
 *
 */

define('RENAME_LOGPAGE',':RenameLog');
function plugin_rename_init() {
	$messages = array('_rename_messages'=>array(
	'err' => '<p>エラー:%s</p>',
	'err_nomatch' => 'マッチするページがありません。',
	'err_notvalid' => 'リネーム後のページ名が正しくありません。',
	'err_adminpass' => '管理者権限でログインしてください。',
	'err_notpage' => '%sはページ名ではありません。',
	'err_norename' => '%sをリネームすることはできません。',
	'err_already' => 'ページがすでに存在します。:%s',
	'err_already_below' => '以下のファイルがすでに存在します。',
	'msg_title' => 'ページ名の変更',
	'msg_page' => '変更元ページを指定',
	'msg_regex' => '正規表現で置換',
	'msg_related' => '関連ページ',
	'msg_do_related' => '関連ページもリネームする',
	'msg_rename' => '%sの名前を変更します。',
	'msg_oldname' => '現在の名前',
	'msg_newname' => '新しい名前',
	'msg_adminpass' => '管理者パスワード',
	'msg_arrow' => '&font(red,b){→};',
	'msg_exist_none' => 'そのページを処理しない',
	'msg_exist_overwrite' => 'そのファイルを上書きする',
	'msg_confirm' => '以下のファイルをリネームします。',
	'msg_result' => '以下のファイルを上書きしました。',
	'msg_otherpage' => 'このほかのページのリネーム',
	'btn_submit' => '実行',
	'btn_next' => '次へ',
	));
	set_plugin_messages($messages);
}

function plugin_rename_action()
{
	global $adminpass,$whatsnew,$WikiName,$BracketName;
	global $_rename_messages;
	
	@set_time_limit(60);
	
	$method = rename_getvar('method');
	if ($method == 'regex')
	{
		$src = rename_getvar('src');
		$dst = rename_getvar('dst');
		if ($src == '')
		{
			return rename_phase1();
		}
		$src_pattern = '/'.preg_quote($src,'/').'/';
		$arr0 = preg_grep($src_pattern,get_existpages());
		if (!is_array($arr0) or count($arr0) == 0)
		{
			return rename_phase1('nomatch');
		}
		$arr1 = preg_replace($src_pattern,$dst,$arr0);
		$arr2 = preg_grep("/^$BracketName$/",$arr1);
		if (count($arr2) != count($arr1))
		{
			return rename_phase1('notvalid');
		}
		return rename_regex($arr0,$arr1);
	}
	else // $method == 'page'
	{
		$page = rename_getvar('page');
		$refer = rename_getvar('refer');
		if ($refer == '')
		{
			return rename_phase1('not set pagename',$refer);
		}
		if (!is_page($refer))
		{
			return rename_phase1('notpage',$refer);
		}
		if ($refer == $whatsnew)
		{
			return rename_phase1('norename',$refer);
		}
		if ($page == '' or $page == $refer)
		{
			return rename_phase2();
		}
		if (!preg_match("/^$BracketName$/",$page))
		{
			return rename_phase2('notvalid');
		}
		return rename_refer();
	}
}
// 変数を取得する
function rename_getvar($key)
{
	global $vars;
	$bracket = ($key == 'page' || $key == 'refer')? 1 : 0 ;
	return array_key_exists($key,$vars)? ($bracket)? add_bracket($vars[$key]):$vars[$key] : '';
}
// エラーメッセージを作る
function rename_err($err,$page='')
{
	global $_rename_messages;
	
	if ($err == '')
	{
		return '';
	}
	$body = $_rename_messages["err_$err"];
	if (is_array($page))
	{
		$tmp = '';
		foreach ($page as $_page)
		{
			$tmp .= "<br />$_page";
		}
		$page = $tmp;
	}
	if ($page != '')
	{
		$body = sprintf($body,htmlspecialchars($page));
	}
	$msg = sprintf($_rename_messages['err'],$body);
	return $msg;
}
//第一段階:ページ名または正規表現の入力
function rename_phase1($err='',$page='')
{
	global $script,$_rename_messages;
	
	$msg = rename_err($err,$page);
	$refer = rename_getvar('refer');
	$method = rename_getvar('method');

	$radio_regex = $radio_page = '';
	if ($method == 'regex')
	{
		$radio_regex =' checked'; 
	}
	else
	{
		$radio_page = ' checked'; 
	}
	$select_refer = rename_getselecttag($refer);
	
	$s_src = htmlspecialchars(rename_getvar('src'));
	$s_dst = htmlspecialchars(rename_getvar('dst'));
	
	$ret['msg'] = $_rename_messages['msg_title'];
	$ret['body'] = <<<EOD
$msg
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="rename" />
  <input type="radio" id="method_0" name="method" value="page"$radio_page />
  <label for="method_0">{$_rename_messages['msg_page']}</label>:$select_refer<br />
  <input type="radio" id="method_1" name="method" value="regex"$radio_regex />
  <label for="method_1">{$_rename_messages['msg_regex']}</label>:<br />
  From:<br />
  <input type="text" name="src" size="80" value="$s_src" /><br />
  To:<br />
  <input type="text" name="dst" size="80" value="$s_dst" /><br />
  <input type="submit" value="{$_rename_messages['btn_next']}" /><br />
 </div>
</form>
EOD;
	return $ret;
}
//第二段階:新しい名前の入力
function rename_phase2($err='')
{
	global $script,$_rename_messages;
	
	$msg = rename_err($err);
	$page = rename_getvar('page');
	$refer = rename_getvar('refer');
	if ($page == '')
	{
		$page = $refer;
	}
	
	$related = rename_getrelated($refer);
	$msg_related = '';
	if (count($related) > 0)
	{
		$msg_related = '<label for="related">'.$_rename_messages['msg_do_related'].'</label>'.
			'<input type="checkbox" id="related" name="related" value="1" checked="checked" /><br />';
	}
	$msg_rename = sprintf($_rename_messages['msg_rename'],make_pagelink($refer,strip_bracket($refer)));
	
	$s_page = htmlspecialchars(strip_bracket($page));
	$s_refer = htmlspecialchars(strip_bracket($refer));
	
	$ret['msg'] = $_rename_messages['msg_title'];
	$ret['body'] = <<<EOD
$msg
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="rename" />
  <input type="hidden" name="refer" value="$s_refer" />
  $msg_rename<br />
  {$_rename_messages['msg_newname']}:<input type="text" name="page" size="80" value="$s_page" /><br />
  $rename_related
  <input type="submit" value="{$_rename_messages['btn_next']}" /><hr />
  <a href="{$script}?plugin=rename">{$_rename_messages['msg_otherpage']}</a>
 </div>
</form>
EOD;
	if (count($related) > 0)
	{
		$ret['body'].= "<hr /><p>{$_rename_messages['msg_related']}</p><ul>";
		sort($related);
		foreach ($related as $name)
		{
			$ret['body'].= '<li>'.make_pagelink($name,strip_bracket($name)).'</li>'; 
		}
		$ret['body'].= '</ul>';
	}
	return $ret;
}
//ページ名と関連するページを列挙し、phase3へ
function rename_refer()
{
	$page = rename_getvar('page');
	$refer = rename_getvar('refer');
	
	$pages[encode($refer)] = encode($page);
	if (rename_getvar('related') != '')
	{
		$from = strip_bracket($refer);
		$to =   strip_bracket($page);
		foreach (rename_getrelated($refer) as $_page)
		{
			$pages[encode($_page)] = encode(str_replace($from,$to,$_page));
		}
	}
	return rename_phase3($pages);
}
//正規表現でページを置換
function rename_regex($arr_from,$arr_to)
{
	$exists = array();
	foreach ($arr_to as $page)
	{
		if (is_page($page))
		{
			$exists[] = $page;
		}
	}
	if (count($exists) > 0)
	{
		return rename_phase1('already',$exists);
	}
	
	$pages = array();
	foreach ($arr_from as $refer)
	{
		$pages[encode($refer)] = encode(array_shift($arr_to));
	}
	return rename_phase3($pages);
}
function rename_phase3($pages)
{
	global $script,$adminpass,$_rename_messages;
	global $X_admin;
	
	$msg = $input = '';
	$files = rename_get_files($pages);
	
	$exists = array();
	foreach ($files as $_page=>$arr)
	{
		foreach ($arr as $old=>$new)
		{
			if (file_exists($new))
			{
				$exists[$_page][$old] = $new;
			}
		}
	}
	$pass = rename_getvar('pass');
	if ($pass && $X_admin)
	{
		return rename_proceed($pages,$files,$exists);
	}
	else if ($pass != '')
	{
		$msg = rename_err('adminpass');
	}
	$method = rename_getvar('method');
	if ($method == 'regex')
	{
		$s_src = htmlspecialchars(rename_getvar('src'));
		$s_dst = htmlspecialchars(rename_getvar('dst'));
		$msg .= $_rename_messages['msg_regex']."<br />";
		$input .= "<input type=\"hidden\" name=\"method\" value=\"regex\" />";
		$input .= "<input type=\"hidden\" name=\"src\" value=\"$s_src\" />";
		$input .= "<input type=\"hidden\" name=\"dst\" value=\"$s_dst\" />";
	}
	else
	{
		$s_refer = htmlspecialchars(rename_getvar('refer'));
		$s_page = htmlspecialchars(rename_getvar('page'));
		$s_related = htmlspecialchars(rename_getvar('related'));
		$msg .= $_rename_messages['msg_page']."<br />";
		$input .= "<input type=\"hidden\" name=\"method\" value=\"page\" />";
		$input .= "<input type=\"hidden\" name=\"refer\" value=\"$s_refer\" />";
		$input .= "<input type=\"hidden\" name=\"page\" value=\"$s_page\" />";
		$input .= "<input type=\"hidden\" name=\"related\" value=\"$s_related\" />";
	}
	
	if (count($exists) > 0)
	{
		$msg .= $_rename_messages['err_already_below']."<ul>";
		foreach ($exists as $page=>$arr)
		{
			$msg .= '<li>';
			$msg .= make_pagelink(decode($page),strip_bracket(decode($page)));
			$msg .= make_link($_rename_messages['msg_arrow']);
			$msg .= htmlspecialchars(strip_bracket(decode($pages[$page])));
			if (count($arr) > 0)
			{
				$msg .= "<ul>\n";
				foreach ($arr as $ofile=>$nfile)
				{
					$msg .= '<li>'.$ofile.make_link($_rename_messages['msg_arrow']).$nfile."</li>\n";
				}
				$msg .= '</ul>';
			}
			$msg .= "</li>\n";
		}
		$msg .= "</ul><hr />\n";
	
		$input .= '<input id="exist_0" type="radio" name="exist" value="0" checked /><label for="exist_0">'.$_rename_messages['msg_exist_none'].'</label><br />';
		$input .= '<input id="exist_1" type="radio" name="exist" value="1" /><label for="exist_1">'.$_rename_messages['msg_exist_overwrite'].'</label><br />';
	}
	$ret_btn = ($X_admin)? "<input type=\"submit\" value=\"{$_rename_messages['btn_submit']}\" />":rename_err('adminpass');
	$ret['msg'] = $_rename_messages['msg_title'];
	$ret['body'] = <<<EOD
<p>$msg</p>
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="rename" />
  <input type="hidden" name="pass" value="1" />
  $input
  {$ret_btn}
 </div>
</form>
<p>{$_rename_messages['msg_confirm']}</p>
EOD;
	
	ksort($pages);
	$ret['body'] .= "<ul>\n";
	foreach ($pages as $old=>$new)
	{
		$ret['body'] .= "<li>".
			//make_pagelink(decode($old)).
			strip_bracket(decode($old)).
			make_link($_rename_messages['msg_arrow']).
			strip_bracket(htmlspecialchars(decode($new))).
			"</li>\n";
	}
	$ret['body'] .= "</ul>\n";
	return $ret;
}
function rename_get_files($pages)
{
	$files = array();
	$dirs = array(BACKUP_DIR,DIFF_DIR,DATA_DIR);
	if (exist_plugin_convert('attach'))
	{
		$dirs[] = UPLOAD_DIR; 
	}
	if (exist_plugin_convert('counter'))
	{
		$dirs[] = COUNTER_DIR;
	}
	// and more ...
	
	foreach ($dirs as $path)
	{
		if (!$dir = opendir($path))
		{
			continue;
		}
		while ($file = readdir($dir))
		{
			if ($file == '.' or $file == '..')
			{
				continue; 
			}
			foreach ($pages as $from=>$to)
			{
				$pattern = '/^'.str_replace('/','\/',$from).'([._].+)$/';
				$matches = array();
				if (!preg_match($pattern,$file,$matches))
				{
					continue; 
				}
				$newfile = $to.$matches[1];
				$files[$from][$path.$file] = $path.$newfile;
			}
		}
	}
	return $files;
}
function rename_proceed($pages,$files,$exists)
{
	global $script,$now,$_rename_messages;
	global $xoopsDB;
	
	if (rename_getvar('exist') == '')
	{
		foreach ($exists as $key=>$arr)
		{
			unset($files[$key]); 
		}
	}
	
	foreach ($files as $page=>$arr)
	{
		foreach ($arr as $old=>$new)
		{
			@set_time_limit(60);
			if ($exists[$page][$old])
			{
				unlink($new);
				//echo "unlink:".$new."<br />";
			}
			rename($old,$new);
			//echo "rename:".$old." -> ".$new."<br />";
			
		}
	}
	//exit;
	$postdata = get_source(RENAME_LOGPAGE);
	$postdata[] = '*'.$now."\n";
	if (rename_getvar('method') == 'regex')
	{
		$postdata[] = '-'.$_rename_messages['msg_regex']."\n";
		$postdata[] = '--From:[['.rename_getvar('src')."]]\n";
		$postdata[] = '--To:[['.rename_getvar('dst')."]]\n";
	}
	else
	{
		$postdata[] = '-'.$_rename_messages['msg_page']."\n";
		$postdata[] = '--From:[['.rename_getvar('refer')."]]\n";
		$postdata[] = '--To:[['.rename_getvar('page')."]]\n";
	}
	if (count($exists) > 0)
	{
		$postdata[] = "\n".$_rename_messages['msg_result']."\n";
		foreach ($exists as $page=>$arr)
		{
			$postdata[] = '-'.decode($page).
				$_rename_messages['msg_arrow'].decode($pages[$page])."\n";
			foreach ($arr as $ofile=>$nfile)
			{
				$postdata[] = '--'.$ofile.
					$_rename_messages['msg_arrow'].$nfile."\n";
			}
		}
		$postdata[] = "----\n";
	}
	foreach ($pages as $old=>$new)
	{
		$old = strip_bracket(decode($old));
		$new = strip_bracket(decode($new));
		$postdata[] = '-'.$old.
			$_rename_messages['msg_arrow']."[[".$new."]]\n";
			
		// linkデータベースを更新
		links_update($old);
		// 念のため'rel'ファイルを削除しておく
		@unlink(CACHE_DIR.encode($new).'.rel');
		links_update($new);

		// DB更新(ページ名はブラケットなし)
		$db_old = addslashes($old);
		$db_new = addslashes($new);
		// pukiwikimod{PUKIWIKI_DIR_NUM}_count
		//  -name
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." SET name='$db_new' WHERE name = '$db_old';";
		$result=$xoopsDB->queryF($query);
		
		// pukiwikimod{PUKIWIKI_DIR_NUM}_pginfo
		//  -name
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET name='$db_new' WHERE name = '$db_old';";
		$result=$xoopsDB->queryF($query);
		
		// pukiwikimod{PUKIWIKI_DIR_NUM}_tb
		//  -tb_id (MD5値)
		//  -page_name
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_tb")." SET page_name='$db_new',tb_id='".md5($new)."' WHERE page_name = '$db_old';";
		$result=$xoopsDB->queryF($query);
		
		// ページスタイルシート
		$oldcss = CACHE_DIR.encode(strip_bracket($old)).".css";
		if (file_exists($oldcss)) rename($oldcss,CACHE_DIR.encode(strip_bracket($new)).".css");
		
		// 送信済みPING
		// [md5値(ブラケットなし)].ping
		// Ver 1.2.1 以降は、[pgid].ping になったので必要なし
		//@unlink("./tracback/".md5($new).".ping");
		//@rename("./tracback/".md5($old).".ping","./tracback/".md5($new).".ping");
	}
	
	// 更新の衝突はチェックしない。
	
	// ファイルの書き込み
	page_write(RENAME_LOGPAGE, join('',$postdata));
	
	//リダイレクト
	$page = rename_getvar('page');
	if ($page == '')
	{
		$page = RENAME_LOGPAGE;
	}
	header("Location: $script?".rawurlencode($page));
	die();
}
function rename_getrelated($page)
{
	$related = array();
	$pages = get_existpages();
	$pattern = '/(?:^|\/)'.preg_quote(strip_bracket($page),'/').'(?:\/|$)/';
	foreach ($pages as $name)
	{
		if ($name == $page)
		{
			continue; 
		}
		if (preg_match($pattern,$name))
		{
			$related[] = $name;
		}
	}
	return $related;
}
function rename_getselecttag($page)
{
	global $whatsnew;
	
	$pages =array();
	foreach (get_existpages() as $_page)
	{
		if ($_page == $whatsnew)
		{
			continue; 
		}
		$_page = strip_bracket($_page);
		$selected = ($_page == $page) ? ' selected' : '';
		$s_page = htmlspecialchars($_page);
		$pages[$_page] = "<option value=\"$s_page\"$selected>$s_page</option>";
	}
	ksort($pages);
	$list = join("\n ",$pages);
	return <<<EOD
<select name="refer">
 <option value=""></option>
 $list
</select>
EOD;
}
?>