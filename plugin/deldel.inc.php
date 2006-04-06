<?php
	error_reporting(E_ALL);
/**
 * $Id: deldel.inc.php,v 1.3 2006/04/06 13:32:16 nao-pon Exp $
 * ORG: deldel.inc.php 161 2005-06-28 12:58:13Z okkez $
 *
 * 色んなものを一括削除するプラグイン
 * @version 1.60
 * @lisence PukiWiki本体と同じGPL2
 *
 */
require_once(PLUGIN_DIR.'attach.inc.php');
//require_once(PLUGIN_DIR.'backup.inc.php');

/**
 * プラグインのメッセージを初期化する
 * @todo いずれPlus!版と統合。
 */
function plugin_deldel_init() {
	$messages = array(
		_deldel_messages => array(
			'title_deldel'		   => '複数ページ一括削除プラグイン',
			'title_list'		   => 'ページの一覧',
			'title_backuplist'	   => 'バックアップの一覧',
			'title_attachlist'	   => '添付ファイルの一覧',
			'title_difflist'	   => '差分ファイルの一覧',
			'title_cachelist'	   => '元ページの存在しないキャッシュファイルを削除しました',
			'title_counterlist'	   => 'カウントファイルの一覧',
			'title_refererlist'	   => '送信済みPingファイル一覧',
			'title_delete_page'	   => 'ページを一括削除しました',
			'title_delete_backup'  => 'バックアップを一括削除しました',
			'title_delete_attach'  => '添付ファイルを一括削除しました',
			'title_delete_diff'	   => '差分ファイルを一括削除しました',
			'title_delete_counter' => 'カウントファイルを一括削除しました',
			'title_delete_referer' => '送信済みPingを一括削除しました',
			'title_delete_error'   => 'エラー',
			'title_select_list'	   => '選択された一覧',
			'msg_error'			   => '削除するページを選択して下さい。',
			'msg_body_start'	   => '操作したいデータを選んで、検索ボタンを押して下さい。',
			'msg_check'			   => '削除したいものにチェックを入れ、確認ボタンを押して下さい。',
			'msg_auth'			   => 'これらのファイルを削除してよければ、削除ボタンを押して下さい。',
			'msg_backup'		   => '複数のバックアップファイルを一括削除しました。',
			'msg_dirlist'		   => 'ディレクトリをまとめてリストアップ',
			'msg_selectlist'	   => 'ページ名から検索してリストアップ',
			'msg_page'			   => '複数のページを一括削除しました。',
			'msg_together_flag'	   => '同時に、該当ページのバックアップ・差分・カウンター・添付ファイル・送信済みPing も削除する。',
			'msg_together_confirm' => '同時に、該当ページのバックアップ・差分・カウンター・添付ファイル・送信済みPing も削除するように設定されています。',
			'msg_together'		   => '同時に該当ページのバックアップ・差分・カウンター・添付ファイル・送信済みPing も削除しました。',
			'msg_auth_error'	   => '管理者権限がありません。管理者としてログインしてください。',
			'msg_delete_error'	   => '削除しようとしたファイルはもう既にないか、何らかの理由で削除できませんでした。確認して下さい。',
			'msg_delete_success'   => '以上のファイルを削除しました。',
			'msg_fatal_error'	   => '何か変です！何が変かはわかりません。',
			'msg_back_word'		   => '戻る',
			'msg_regexp_label'	   => '削除ページ名のパターン(正規表現)：',
			'msg_regexp_error'	   => 'このパターンを含むページはありませんでした。',
			'btn_exec'			   => '削除',
			'btn_search'		   => '検索',
			'btn_research'		   => '再検索',
			'btn_concern'		   => '確認'
			)
		);
	set_plugin_messages($messages);
}

/**
 * plugin_deldel_action
 * 色んなものを一括削除する
 *
 * @access	  private
 * @param	  String	NULL		ページ名
 *
 * @return	  Array		ページタイトルと内容。
 */
function plugin_deldel_action() {

	global $_attach_messages,$_deldel_messages;
	global $vars,$script,$X_admin,$xoopsDB;

	//変数の初期化
	$mode = isset($vars['mode']) ? $vars['mode'] : NULL;
	$status = array(0 => $_deldel_messages['title_delete_error'],
					1 => $_deldel_messages['btn_delete']);
	$is_cascade = (empty($vars['cascade'])) ? false : true;
	$vars['s_regxp'] = (empty($vars['regexp']))? "" : htmlspecialchars($vars['regexp']);
	$body = '';
	
	if(!isset($mode) || !$X_admin)
	{
		if (!$X_admin)
		{
			$body .= "<p style=\"color:red;font-size:120%;font-weight:bold;\"><img src=\"image/alert.gif\" width=\"15\" height=\"15\" alt=\"alert\" /> ".$_deldel_messages['msg_auth_error']."</p>";
		}
		$mes = "_deldel_messages";
		//最初のページ
		$body .= "<h2>".$_deldel_messages['msg_selectlist']."</h2>";
		$body .= "<form method='post' action=\"$script?cmd=deldel\">";
		$body .= "<input type=\"hidden\" name=\"dir\" value=\"DATA\"/>\n";
		$body .= "<input type=\"hidden\" name=\"mode\" value=\"select\"/>\n";
		$body .= "{${$mes}['msg_regexp_label']}<input type='text' name='regexp' value='{$vars['s_regxp']}' />\n";
		$body .= "<input type=\"submit\" value=\"{$_deldel_messages['btn_search']}\" />";
		$body .= "<p>{$_deldel_messages['msg_body_start']}</p>";
		$body .= "</form>";
		
		$body .= "<h2>".$_deldel_messages['msg_dirlist']."</h2>";
		$body .= "<form method='post' action=\"$script?cmd=deldel\">";
		$body .= '<select name="dir" size="1">';
		$body .= '<option value="DATA">wiki</option>';
		$body .= '<option value="BACKUP">backup</option>';
		//$body .= '<option value="UPLOAD">attach</option>';
		$body .= '<option value="DIFF">diff</option>';
		//$body .= '<option value="CACHE">cache</option>';
		$body .= '<option value="REFERER">ping</option>';
		$body .= '<option value="COUNTER">counter</option></select>';
		$body .= "<input type=\"hidden\" name=\"mode\" value=\"select\"/>\n";
		$body .= "<input type=\"submit\" value=\"{$_deldel_messages['btn_search']}\" />";
		$body .= "<p>{$_deldel_messages['msg_body_start']}</p>";
		$body .= "</form>";

		return array('msg'=>$_deldel_messages['title_deldel'],'body'=>$body);
	}elseif(isset($mode) && $mode === 'select'){
		if($X_admin) {
			//認証が通ったらそれぞれページ名やファイル名の一覧を表示する
			$vars['pass'] = '';//認証が終わったのでパスを消去
			if(array_key_exists('regexp',$vars) && $vars['regexp'] != ''){
				//$pattern = $vars['regexp'];
				$pattern = "#".str_replace(array('#','"'),array('\#','\"'),$vars['regexp'])."#i";
				foreach ( get_existpages(true,"",0,"",false,false,true,true) as $file => $page ) {
					//if (mb_eregi($pattern, $page)) {
					if (preg_match($pattern, $page)) {
						$target[$file] = $page;
					}
				}
				if(is_null($target)){
					$error_msg = "<p>{$_deldel_messages['msg_regexp_error']}</p>\n";
					$error_msg .= "<p>". htmlspecialchars($vars['regexp']) ."</p>";
					$error_msg .= make_body($vars['cmd'], DATA_DIR, true);
					$error_msg .= "<p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
					return array('msg'=>$_deldel_messages['title_delete_error'] ,'body'=>$error_msg);
				}
				$body .= make_body($vars['cmd'], DATA_DIR, false ,$target);
				return array('msg'=>$_deldel_messages['title_list'],'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="DATA") {
				//ページ
				$body .= make_body($vars['cmd'], DATA_DIR);
				return array('msg'=>$_deldel_messages['title_list'],'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="BACKUP"){
				//バックアップ
				$body .= make_body($vars['cmd'], BACKUP_DIR);
				return array('msg'=>$_deldel_messages['title_backuplist'],'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="UPLOAD"){
				//添付ファイル
				$body .= "\n<form method=\"post\" action=\"$script?cmd=deldel\"><div>";
				$retval = attach_list2();
				$body .= $retval['body'];
				$body .= "<input type=\"hidden\" name=\"mode\" value=\"confirm\"/>\n<input type=\"hidden\" name=\"dir\" value=\"{$vars['dir']}\"/>\n";
				$body .= "<input type=\"submit\" value=\"{$_deldel_messages['btn_concern']}\"/></div>\n</form>";
				$body .= $_deldel_messages['msg_check'];
				return array('msg'=>$_deldel_messages['title_attachlist'],'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="DIFF") {
				//diff
				$body .= make_body($vars['cmd'], DIFF_DIR);
				return array('msg'=>$_deldel_messages['title_difflist'], 'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="CACHE") {
				//cache
				$body .= "<ul>\n<li>rel\n<ul>";
				$deleted_caches = sweap_cache();
				foreach($deleted_caches['rel'] as $key => $value) {
					$body .= '<li>'. $value. '<ul><li>'. $key. '</li></ul></li>'."\n";
				}
				$body .= "</ul></li></ul>\n";
				$body .= "<ul><li>ref\n<ul>";
				foreach($deleted_caches['ref'] as $key => $value) {
					$body .= '<li>'. $value. '<ul><li>'. $key. '</li></ul></li>'."\n";
				}
				$body .= '</ul></li></ul>';
				$body .= '<p>'. $_deldel_messages['msg_delete_success']. '</p>';
				return array('msg'=>$_deldel_messages['title_cachelist'], 'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="REFERER") {
				//リンク元referer
				$body .= make_body($vars['cmd'], TRACKBACK_DIR);
				return array('msg'=>$_deldel_messages['title_refererlist'], 'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="COUNTER") {
				//カウンター*.count
				$body .= make_body($vars['cmd'], COUNTER_DIR);
				return array('msg'=>$_deldel_messages['title_counterlist'], 'body'=>$body);
			}
		}elseif(isset($vars['pass']) && !pkwk_login($vars['pass'])){
			//認証エラー
			return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$_deldel_messages['msg_auth_error']);
		}
	}elseif(isset($mode) && $mode === 'confirm'){
		//確認画面+もう一回認証要求？
		if(array_key_exists('pages',$vars) and $vars['pages'] != ''){
			return make_confirm('deldel', $vars['dir'], $vars['pages'], $is_cascade);
		}else{
			//選択がなければエラーメッセージを表示する
			$error_msg = "<p>{$_deldel_messages['msg_error']}</p><p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
			return array('msg'=>$_deldel_messages['title_delete_error'] ,'body'=>$error_msg);
		}
	}elseif(isset($mode) && $mode === 'exec'){
		//削除
		if($X_admin) {
			switch($vars['dir']){
			  case 'DATA':
				$mes = 'page';
				global $autolink;
				$_autolink = $autolink;
				$autolink = 0;
				$count = count($vars['pages']);
				$i = 0;
				foreach($vars['pages'] as $page)
				{
					$i++;
					if ($i == $count) $autolink = $_autolink;
					$s_page = htmlspecialchars($page, ENT_QUOTES);
					$b_page = add_bracket($page);
					$pgid = get_pgid_by_name($page);
					$target = encode($b_page);
					
					if(file_exists(get_filename(encode($b_page)))){
						$flag[$s_page] = true;
						file_write(DATA_DIR, $b_page, '');
					}else{
						$flag[$s_page] = false;
					}
					if ($vars['cascade'] == 1) {
						// BACKUP
						$f_page = get_filename2('backup',$page);
						if(file_exists($f_page)){
							$flag[$s_page] = unlink($f_page);
						}
						// DIFF
						$f_page = get_filename2('diff',$page);
						if(file_exists($f_page)){
							$flag[$s_page] = unlink($f_page);
						}
						// COUNTER
						$f_page = get_filename2('counter',$page);
						if(file_exists($f_page)){
							$flag[$s_page] = unlink($f_page);
						}
						// REFERER
						$f_page = get_filename2('referer',$page);
						if(file_exists($f_page)){
							$flag[$s_page] = unlink($f_page);
						}
						// CACHE
						//sweap_cache();
						
						// 添付ファイルDB
						$del_files = attach_db_write(array('pgid'=>$pgid),'delete');
						
						$att = $thm = array();
						
						if (is_array($del_files) && $del_files)
						{
							foreach($del_files as $del_file)
							{
								$name = $target."_".encode($del_file);
								// 添付ファイル
								if (file_exists(UPLOAD_DIR.$name))
								{
									unlink(UPLOAD_DIR.$name);
									$att[] = $del_file." [$name]";
								}
								//サムネイル
								for ($i = 1; $i < 100; $i++)
								{
									$file = $target.'_'.encode($i."%").encode($del_file);
									if (file_exists(UPLOAD_DIR."s/".$file))
									{
										unlink(UPLOAD_DIR."s/".$file);
										$thm[] = $i.'%'.$del_file." [$name]";
									}
								}
							}
						}
					}
				}
				break;
			  case 'BACKUP':
				$mes = 'backup';
				foreach($vars['pages'] as $page){
					$s_page = htmlspecialchars($page, ENT_QUOTES);
					$f_page = get_filename2($mes,$page);
					//if(file_exists($f_page) && !is_freeze($s_page)){
					if(file_exists($f_page)){
						$flag[$s_page] = unlink($f_page);
					}else{
						$flag[$s_page] = false;
					}
				}
				break;
			  case 'UPLOAD':
				$mes = 'attach';
				$size = count($vars['file_a']);
				for($i=0;$i<$size;$i++){
					foreach (array('refer', 'file', 'age') as $var) {
						$vars[$var] = isset($vars[$var.'_a'][$i]) ? $vars[$var.'_a'][$i] : '';
					}
					$result = attach_delete();
					//それぞれのファイルについて成功|失敗のフラグを立てる
					switch($result['msg']){
					  case $_attach_messages['msg_deleted']:
						$flag["{$vars['refer']}/{$vars['file']}"] = true;
						break;
					  case $_attach_messages['err_notfound'] || $_attach_messages['err_noparm']:
						$flag["{$vars['refer']}/{$vars['file']}"] = false;
						break;
					  default:
						$flag["{$vars['refer']}/{$vars['file']}"] = false;
						break;
					}
				}
				break;
			  case 'DIFF' :
				$mes = 'diff';
				foreach($vars['pages'] as $page){
					$s_page = htmlspecialchars($page, ENT_QUOTES);
					$f_page = get_filename2($mes,$page);
					if(file_exists($f_page) && !is_freeze($s_page)){
						$flag[$s_page] = unlink($f_page);
					}else{
						$flag[$s_page] = false;
					}
				}
				break;
			  case 'REFERER':
				$mes = 'referer';
				foreach($vars['pages'] as $page){
					$s_page = htmlspecialchars($page, ENT_QUOTES);
					$f_page = get_filename2($mes,$page);
					if(file_exists($f_page) && !is_freeze($s_page)){
						$flag[$s_page] = unlink($f_page);
					}else{
						$flag[$s_page] = false;
					}
				}
				break;
			  case 'COUNTER':
				$mes = 'counter';
				foreach($vars['pages'] as $page){
					$s_page = htmlspecialchars($page, ENT_QUOTES);
					$f_page = get_filename2($mes,$page);
					if(file_exists($f_page)){
						$flag[$s_page] = unlink($f_page);
					}else{
						$flag[$s_page] = false;
					}
					//カウンターDB
					$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." WHERE `name` = '".addslashes($page)."' LIMIT 1;";
					$result=$xoopsDB->queryF($query);
				}
				break;
			}
			if(in_array(false,$flag)){
				//削除失敗したものが一つでもある
				foreach($flag as $key=>$value)
				{
					$body .= "$key =&gt; {$status[(($value)? 1 : 0)]}<br/>\n";
				}
				$body .= "<p>{$_deldel_messages['msg_delete_error']}</p>";
				$body .= "<p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
				return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$body);
			}else{
				//削除成功
				foreach($flag as $key=>$value){
					$body .= "$key<br/>\n";
				}
				$body .= "<p>{$_deldel_messages['msg_delete_success']}</p>";
				$body .= $is_cascade ? "<p>{$_deldel_messages['msg_together']}</p>" : "";
				$body .= "<p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
				return array('msg' => $_deldel_messages['title_delete_'.$mes] ,'body' => $body);
			}
		}
		elseif(isset($vars['pass']) && !pkwk_login($vars['pass'])){
			//認証エラー
			return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$_deldel_messages['msg_auth_error']);
		}
	}
}
/**
 * page_list2
 * ページ一覧の作成 page_list()の一部を変更
 *
 * @access public
 * @param  Array   $pages		 ページ名配列
 * @param  String  $cmd			 コマンド
 * @param  Boolean $withfilename ファイルネームを返す(true)返さない(false)
 *
 * @return String				 整形済みのページリスト
 */
function page_list2($pages, $cmd = 'read', $withfilename = FALSE, $checked=FALSE)
{
	global $script, $list_index;
	global $_msg_symbol, $_msg_other;
	global $pagereading_enable;

	// ソートキーを決定する。 ' ' < '[a-zA-Z]' < 'zz'という前提。
	$symbol = ' ';
	$other = 'zz';

	$retval = '';

	if($pagereading_enable) {
		mb_regex_encoding(SOURCE_ENCODING);
		$readings = get_readings($pages);
	}
	echo "Pages: ".count($pages);

	$list = $matches = array();
	foreach($pages as $file=>$page) {
	//foreach($pages as $page) {
		$r_page	 = rawurlencode($page);
		$s_page	 = htmlspecialchars($page, ENT_QUOTES);
		$passage = get_pg_passage($page);
		// 変更ココから by okkez
		$checked = ($checked)? " checked=\"true\"" : "";
		$freezed = is_freeze($page) ? '<span class="new1"> * </span>' : '';
		$exist_page = is_page($page) ? '' : '<span class="diff_added"> # </span>';
		$str = '   <li><input type="checkbox" name="pages[]" value="' . $s_page . '"'.$checked.' /><a href="' .
		$script . '?cmd=' . $cmd . '&amp;page=' . $r_page .
		'">' . $s_page . '</a>' . $passage . $freezed . $exist_page;
		// ココまで

		if ($withfilename) {
			$s_file = htmlspecialchars($file);
			$str .= "\n" . '	<ul><li>' . $s_file . '</li></ul>' .
			"\n" . '   ';
		}
		$str .= '</li>';

		// WARNING: Japanese code hard-wired
		if($pagereading_enable) {
			if(mb_ereg('^([A-Za-z])', mb_convert_kana($page, 'a'), $matches)) {
				$head = $matches[1];
			} elseif(mb_ereg('^([ァ-ヶ])', $readings[$page], $matches)) { // here
				$head = $matches[1];
			} elseif (mb_ereg('^[ -~]|[^ぁ-ん亜-熙]', $page)) { // and here
				$head = $symbol;
			} else {
				$head = $other;
			}
		} else {
			$head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] :
			(preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);
		}

		$list[$head][$page] = $str;
	}
	ksort($list);

	$cnt = 0;
	$arr_index = array();
	$retval .= '<ul>' . "\n";
	foreach ($list as $head=>$pages) {
		if ($head === $symbol) {
			$head = $_msg_symbol;
		} else if ($head === $other) {
			$head = $_msg_other;
		}

		if ($list_index) {
			++$cnt;
			$arr_index[] = '<a id="top_' . $cnt .
			'" href="#head_' . $cnt . '"><strong>' .
			$head . '</strong></a>';
			$retval .= ' <li><a id="head_' . $cnt . '" href="#top_' . $cnt .
			'"><strong>' . $head . '</strong></a>' . "\n" .
			'  <ul>' . "\n";
		}
		ksort($pages);
		$retval .= join("\n", $pages);
		if ($list_index)
		$retval .= "\n	</ul>\n </li>\n";
	}
	$retval .= '</ul>' . "\n";
	if ($list_index && $cnt > 0) {
		$top = array();
		while (! empty($arr_index))
		$top[] = join(' | ' . "\n", array_splice($arr_index, 0, 16)) . "\n";

		$retval = '<div id="top" style="text-align:center">' . "\n" .
		join('<br />', $top) . '</div>' . "\n" . $retval;
	}
	return $retval;
}

/**
 * make_body
 * DATA_DIR,BACKUP_DIR,DIFF_DIR,COUNTER_DIRの一覧を作る。
 *
 * @param  String  $cmd コマンド
 * @param  String  $dir DATA_DIR or BACKUP_DIR のどちらか一方。省略不可
 * @param  Boolean $retry リトライかどうか。TRUE:リトライ,FALSE:リトライではない。
 * @return String		一覧表示のbody部分を返す。
 */
function make_body($cmd, $dir, $retry=false, $pages=array())
{
	global $script, $_deldel_messages, $_freeze2_messages, $_unfreeze2_messages, $vars;
	
	$mes = "_{$cmd}_messages";
	if($dir === DATA_DIR) {
		$ext = '.txt';
	}elseif($dir === BACKUP_DIR) {
		$ext = (function_exists(gzfile))? ".gz" : ".txt";
	}elseif($dir === DIFF_DIR) {
		$ext = '.txt';
	}elseif($dir === TRACKBACK_DIR) {
		$ext = '.ping';
	}elseif($dir === COUNTER_DIR) {
		$ext = '.count';
	}
	$body .= "<form method='post' action=\"$script?cmd=$cmd\"><div>\n";
	if ($dir === DATA_DIR)
	{
		$body .= "<input type=\"hidden\" name=\"dir\" value=\"DATA\"/>\n";
		$body .= "<input type=\"hidden\" name=\"mode\" value=\"select\"/>\n";
		$body .= "{${$mes}['msg_regexp_label']}<input type='text' name='regexp' value='{$vars['s_regxp']}' />\n";
		$body .= "<input type=\"submit\" value=\"{${$mes}['btn_research']}\" /></div></form>\n";
	}
	if ($retry === false) {
		$body .= "<form method='post' action=\"$script?cmd=$cmd\"><div>\n";
		if ($pages)
		{
			$body .= page_list2($pages, 'read', FALSE, TRUE);
		}
		else if ($dir === DATA_DIR)
		{
			$body .= page_list2(get_existpages(true,"",0,"",false,false,true,true));
		}
		else if ($dir === TRACKBACK_DIR)
		{
			$dp = @opendir($dir)
				or die_message($dir. ' is not found or not readable.');
			while ($file = readdir($dp))
			{
				$matches = array();
				if (preg_match("/^([\d]+)\.ping$/",$file,$matches))
				{
					$aryret[$file] = get_pgname_by_id($matches[1]);
				}
			}
			closedir($dp);
			$body .= page_list2($aryret);
		}
		else
		{
			$body .= page_list2(get_existpages($dir, $ext, true));
		}
		if($dir === DATA_DIR) {
			$dir = 'DATA';
			$body .= ($cmd === 'deldel') ? "<input type=\"checkbox\" name=\"cascade\" value=\"1\"/><span>{$_deldel_messages['msg_together_flag']}</span><br />\n" : "";
		}elseif($dir === BACKUP_DIR) {
			$dir = 'BACKUP';
		}elseif($dir === DIFF_DIR) {
			$dir = 'DIFF';
		}elseif($dir === TRACKBACK_DIR) {
			$dir = 'REFERER';
		}elseif($dir === COUNTER_DIR) {
			$dir = 'COUNTER';
		}
		$body .= "<input type=\"hidden\" name=\"mode\" value=\"confirm\"/>\n";
		$body .= "<input type=\"hidden\" name=\"dir\" value=\"{$dir}\"/>\n";
		$body .= "<input type=\"submit\" value=\"{${$mes}['btn_concern']}\" /></div></form>\n";
		$body .= ${$mes}['msg_check'];
	}

	return $body;
}

/**
 * make_confirm
 * 確認画面を作る
 * globalで変数を引き回すのはあまりよくない気がしたので引数で渡してみた
 *
 * @access public
 * @param  String  $cmd	   コマンド [deldel|freeze2|unfreeze2]
 * @param  String  $dir	   $vars['dir']を使う
 * @param  String  $pages  $vars['pages']を使う
 *
 * @return Array   ページタイトルと内容
 */
function make_confirm($cmd, $dir, $pages, $is_cascade=false)
{
	global $_deldel_messages, $_freeze2_messages, $_unfreeze2_messages;
	
	$is_cascade = ($is_cascade)? "1" : "0";
	
	$i=0;
	$mes = "_{$cmd}_messages";
	$body .= "<form method=\"post\" action=\"$script?cmd=$cmd\">\n<ul>\n";
	switch($dir){
	  case 'DATA' :
	  case 'BACKUP' :
	  case 'DIFF' :
	  case 'REFERER' :
	  case 'COUNTER':
		foreach($pages as $page){
			$s_page = htmlspecialchars($page,ENT_QUOTES);
			$body .= "<li><input type=\"hidden\" name=\"pages[$i]\" value=\"$s_page\"/>$s_page<br/></li>\n";
			$i++;
		}
		break;
	  case 'UPLOAD' :
		foreach($pages as $page){
			$s_page = htmlspecialchars($page,ENT_QUOTES);
			$temp = split("=|&amp;",$s_page);
			$file = rawurldecode($temp[1]);
			$refer = rawurldecode($temp[3]);
			$age = isset($temp[5])? rawurldecode($temp[5]) : 0 ;
			$body .= "<li><input type=\"hidden\" name=\"pages[$i]\" value=\"$s_page\"/>$refer/$file";
			$body .= "<input type=\"hidden\" name=\"refer_a[$i]\" value=\"$refer\"/>";
			$body .= "<input type=\"hidden\" name=\"file_a[$i]\" value=\"$file\"/>";
			$body .= "<input type=\"hidden\" name=\"age_a[$i]\" value=\"$age\"/></li>\n";
			$i++;
		}
		break;
	  default :
		return array('msg' => ${$mes}['title_delete_error'],'body'=>${$mes}['msg_fatal_error']);
	}
	$body .= "</ul>\n<div>";
	$body .= '<input type="hidden" name="mode" value="exec"/><input type="hidden" name="dir" value="'.$dir.'"/>'."\n";
	//$body .= '<input type="hidden" name="cascade" value="'.$is_cascade.'" />'."\n";
	$body .= "<input type=\"checkbox\" name=\"cascade\" value=\"1\"".(($is_cascade)? "checked=\"true\"" : "")." /><span>{$_deldel_messages['msg_together_flag']}</span><br />\n";
	$body .= "<input type=\"submit\" value=\"{${$mes}['btn_exec']}\"/>\n</div></form>\n";
	$body .= "<p>{${$mes}['msg_auth']}</p>";
	//$body .= $is_cascade ? "<p>{$_deldel_messages['msg_together_confirm']}</p>" : "";
	return array('msg'=>${$mes}['title_select_list'],'body'=>$body);
}

/**
 * AttachFile2
 * AttachFileを継承したクラス
 * toStringメソッドをcheckboxと凍結フラグを表示するように変更
 * &amp;の位置を変更している
 */
class AttachFile2 extends AttachFile
{
	/**
	 * ページ名に対して色々なリンクを一つにまとめて返す。
	 *
	 * @param  hoge	 $showicon
	 * @param  hoge	 $showinfo
	 *
	 * @return String
	 */
	function toString($showicon, $showinfo)
	{
		global $script, $_attach_messages, $vars;

		$this->getstatus();
		$param	= 'file=' . rawurlencode($this->file) . '&amp;refer=' . rawurlencode($this->page) .
		($this->age ? '&amp;age=' . $this->age : '');
		$title = $this->time_str . ' ' . $this->size_str;
		$label = ($showicon ? PLUGIN_ATTACH_FILE_ICON : '') . htmlspecialchars($this->file);
		if ($this->age) {
			$label .= ' (backup No.' . $this->age . ')';
		}
		$info = $count = $retval = $freezed = '';
		if ($showinfo) {
			$_title = str_replace('$1', rawurlencode($this->file), $_attach_messages['msg_info']);
			$info = "\n<span class=\"small\">[<a href=\"$script?plugin=attach&amp;pcmd=info$param\" title=\"$_title\">{$_attach_messages['btn_info']}</a>]</span>\n";
			$count = ($showicon && ! empty($this->status['count'][$this->age])) ?
			sprintf($_attach_messages['msg_count'], $this->status['count'][$this->age]) : '';
		}
		$freezed = $this->status['freeze'] ? '<span class="new1"> * </span>' : '';
		$retval .= $vars['cmd'] === 'deldel' |
		$vars['cmd'] === 'freeze2' |
		$vars['cmd'] === 'unfreeze2' ?
		"<input type=\"checkbox\" name=\"page[]\" value=\"$param\"/>" : '';
		$retval .= "<a href=\"$script?plugin=attach&amp;pcmd=open&amp;$param\" title=\"$title\">$label</a>$count$info$freezed";
		return $retval;
	}
}
/**
 * AttachFiles2
 * AttachFilesを継承したクラス
 * AttachFile2を使うようにだけ修正
 */
class AttachFiles2 extends AttachFiles
{
	function add($file, $age)
	{
		$this->files[$file][$age] = & new AttachFile2($this->page, $file, $age);
	}

}
/**
 * AttachPages2
 * AttachPagesを継承したクラス
 * コンストラクタをちょこっと変更
 */
class AttachPages2 extends AttachPages
{
	function AttachPages2($page = '', $age = NULL)
	{

		$dir = opendir(UPLOAD_DIR) or
		die('directory ' . UPLOAD_DIR . ' is not exist or not readable.');

		$page_pattern = ($page == '') ? '(?:[0-9A-F]{2})+' : preg_quote(encode($page), '/');
		$age_pattern = ($age === NULL) ?
		'(?:\.([0-9]+))?' : ($age ?	 "\.($age)" : '');
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";

		$matches = array();
		while ($file = readdir($dir)) {
			if (! preg_match($pattern, $file, $matches))
			continue;

			$_page = decode($matches[1]);
			$_file = decode($matches[2]);
			$_age  = isset($matches[3]) ? $matches[3] : 0;
			if (! isset($this->pages[$_page])) {
				$this->pages[$_page] = & new AttachFiles2($_page);
			}
			$this->pages[$_page]->add($_file, $_age);
		}
		closedir($dir);
	}
}
/**
 * attach_list2
 * 添付ファイルの一覧取得 attach_list()をちょこっと改変
 *
 * @access private
 * @param  Void	   引数はなし
 *
 * @return Array   PukiWikiのプラグイン仕様に従ったもの
 *
 */
function attach_list2()
{
	global $vars, $_attach_messages;

	$refer = isset($vars['refer']) ? $vars['refer'] : '';

	$obj = & new AttachPages2($refer);

	$msg = $_attach_messages[($refer == '') ? 'msg_listall' : 'msg_listpage'];
	$body = ($refer == '' || isset($obj->pages[$refer])) ?
	$obj->toString($refer, FALSE) :
	$_attach_messages['err_noexist'];

	return array('msg'=>$msg, 'body'=>$body);
}

/**
 * get_filename2
 * Get physical file name of the page
 *
 * @param  String $dir	  ディレクトリ名 counter or diff
 * @param  String $page	  ページ名
 * @return String		  物理ファイル名
 */
function get_filename2($dir,$page)
{
	$pgid = get_pgid_by_name($page);
	$page = add_bracket($page);
	$page = encode($page);
	switch($dir){
	  case 'backup' :
		return BACKUP_DIR . $page . ((function_exists(gzfile))? ".gz" : ".txt") ;
	  case 'counter' :
		return COUNTER_DIR . $page . '.count' ;
	  case 'diff' :
		return DIFF_DIR . $page . '.txt' ;
	  case 'referer' :
		return TRACKBACK_DIR . $pgid . '.ping';
	}
}

/**
 * sweap_cache();
 * キャッシュのお掃除。元ファイルの存在しないキャッシュを問答無用で削除する。
 * @return Array 削除したファイル名=>削除したファイル名をデコードしたもの
 */
function sweap_cache()
{
	$rel = get_existpages(CACHE_DIR, '.rel');
	foreach($rel as $key => $value){
		if (is_page($value)){
			continue;
		}else{
			unlink(CACHE_DIR.$key);
			$delete_rel[$key] = $value;
		}
	}
	$ref = get_existpages(CACHE_DIR, '.ref');
	foreach($ref as $key => $value){
		if (is_page($value)){
			continue;
		}else{
			unlink(CACHE_DIR.$key);
			$delete_ref[$key] = $value;
		}
	}
	natcasesort($delete_rel);
	natcasesort($delete_ref);
	return array('rel' => $delete_rel,
				 'ref' => $delete_ref);
}
?>