<?php
	error_reporting(E_ALL);
/**
 * $Id: deldel.inc.php,v 1.3 2006/04/06 13:32:16 nao-pon Exp $
 * ORG: deldel.inc.php 161 2005-06-28 12:58:13Z okkez $
 *
 * ����ʤ�Τ���������ץ饰����
 * @version 1.60
 * @lisence PukiWiki���Τ�Ʊ��GPL2
 *
 */
require_once(PLUGIN_DIR.'attach.inc.php');
//require_once(PLUGIN_DIR.'backup.inc.php');

/**
 * �ץ饰����Υ�å���������������
 * @todo ������Plus!�Ǥ����硣
 */
function plugin_deldel_init() {
	$messages = array(
		_deldel_messages => array(
			'title_deldel'		   => 'ʣ���ڡ���������ץ饰����',
			'title_list'		   => '�ڡ����ΰ���',
			'title_backuplist'	   => '�Хå����åפΰ���',
			'title_attachlist'	   => 'ź�եե�����ΰ���',
			'title_difflist'	   => '��ʬ�ե�����ΰ���',
			'title_cachelist'	   => '���ڡ�����¸�ߤ��ʤ�����å���ե�����������ޤ���',
			'title_counterlist'	   => '������ȥե�����ΰ���',
			'title_refererlist'	   => '�����Ѥ�Ping�ե��������',
			'title_delete_page'	   => '�ڡ������������ޤ���',
			'title_delete_backup'  => '�Хå����åפ��������ޤ���',
			'title_delete_attach'  => 'ź�եե�������������ޤ���',
			'title_delete_diff'	   => '��ʬ�ե�������������ޤ���',
			'title_delete_counter' => '������ȥե�������������ޤ���',
			'title_delete_referer' => '�����Ѥ�Ping���������ޤ���',
			'title_delete_error'   => '���顼',
			'title_select_list'	   => '���򤵤줿����',
			'msg_error'			   => '�������ڡ��������򤷤Ʋ�������',
			'msg_body_start'	   => '�������ǡ���������ǡ������ܥ���򲡤��Ʋ�������',
			'msg_check'			   => '�����������Τ˥����å������졢��ǧ�ܥ���򲡤��Ʋ�������',
			'msg_auth'			   => '�����Υե�����������Ƥ褱��С�����ܥ���򲡤��Ʋ�������',
			'msg_backup'		   => 'ʣ���ΥХå����åץե�������������ޤ�����',
			'msg_dirlist'		   => '�ǥ��쥯�ȥ��ޤȤ�ƥꥹ�ȥ��å�',
			'msg_selectlist'	   => '�ڡ���̾���鸡�����ƥꥹ�ȥ��å�',
			'msg_page'			   => 'ʣ���Υڡ������������ޤ�����',
			'msg_together_flag'	   => 'Ʊ���ˡ������ڡ����ΥХå����åס���ʬ�������󥿡���ź�եե����롦�����Ѥ�Ping �������롣',
			'msg_together_confirm' => 'Ʊ���ˡ������ڡ����ΥХå����åס���ʬ�������󥿡���ź�եե����롦�����Ѥ�Ping ��������褦�����ꤵ��Ƥ��ޤ���',
			'msg_together'		   => 'Ʊ���˳����ڡ����ΥХå����åס���ʬ�������󥿡���ź�եե����롦�����Ѥ�Ping �������ޤ�����',
			'msg_auth_error'	   => '�����Ը��¤�����ޤ��󡣴����ԤȤ��ƥ����󤷤Ƥ���������',
			'msg_delete_error'	   => '������褦�Ȥ����ե�����Ϥ⤦���ˤʤ��������餫����ͳ�Ǻ���Ǥ��ޤ���Ǥ�������ǧ���Ʋ�������',
			'msg_delete_success'   => '�ʾ�Υե�����������ޤ�����',
			'msg_fatal_error'	   => '�����ѤǤ��������Ѥ��Ϥ狼��ޤ���',
			'msg_back_word'		   => '���',
			'msg_regexp_label'	   => '����ڡ���̾�Υѥ�����(����ɽ��)��',
			'msg_regexp_error'	   => '���Υѥ������ޤ�ڡ����Ϥ���ޤ���Ǥ�����',
			'btn_exec'			   => '���',
			'btn_search'		   => '����',
			'btn_research'		   => '�Ƹ���',
			'btn_concern'		   => '��ǧ'
			)
		);
	set_plugin_messages($messages);
}

/**
 * plugin_deldel_action
 * ����ʤ�Τ���������
 *
 * @access	  private
 * @param	  String	NULL		�ڡ���̾
 *
 * @return	  Array		�ڡ��������ȥ�����ơ�
 */
function plugin_deldel_action() {

	global $_attach_messages,$_deldel_messages;
	global $vars,$script,$X_admin,$xoopsDB;

	//�ѿ��ν����
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
		//�ǽ�Υڡ���
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
			//ǧ�ڤ��̤ä��餽�줾��ڡ���̾��ե�����̾�ΰ�����ɽ������
			$vars['pass'] = '';//ǧ�ڤ�����ä��Τǥѥ���õ�
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
				//�ڡ���
				$body .= make_body($vars['cmd'], DATA_DIR);
				return array('msg'=>$_deldel_messages['title_list'],'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="BACKUP"){
				//�Хå����å�
				$body .= make_body($vars['cmd'], BACKUP_DIR);
				return array('msg'=>$_deldel_messages['title_backuplist'],'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="UPLOAD"){
				//ź�եե�����
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
				//��󥯸�referer
				$body .= make_body($vars['cmd'], TRACKBACK_DIR);
				return array('msg'=>$_deldel_messages['title_refererlist'], 'body'=>$body);
			}elseif(isset($vars['dir']) && $vars['dir']==="COUNTER") {
				//�����󥿡�*.count
				$body .= make_body($vars['cmd'], COUNTER_DIR);
				return array('msg'=>$_deldel_messages['title_counterlist'], 'body'=>$body);
			}
		}elseif(isset($vars['pass']) && !pkwk_login($vars['pass'])){
			//ǧ�ڥ��顼
			return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$_deldel_messages['msg_auth_error']);
		}
	}elseif(isset($mode) && $mode === 'confirm'){
		//��ǧ����+�⤦���ǧ���׵ᡩ
		if(array_key_exists('pages',$vars) and $vars['pages'] != ''){
			return make_confirm('deldel', $vars['dir'], $vars['pages'], $is_cascade);
		}else{
			//���򤬤ʤ���Х��顼��å�������ɽ������
			$error_msg = "<p>{$_deldel_messages['msg_error']}</p><p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
			return array('msg'=>$_deldel_messages['title_delete_error'] ,'body'=>$error_msg);
		}
	}elseif(isset($mode) && $mode === 'exec'){
		//���
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
						
						// ź�եե�����DB
						$del_files = attach_db_write(array('pgid'=>$pgid),'delete');
						
						$att = $thm = array();
						
						if (is_array($del_files) && $del_files)
						{
							foreach($del_files as $del_file)
							{
								$name = $target."_".encode($del_file);
								// ź�եե�����
								if (file_exists(UPLOAD_DIR.$name))
								{
									unlink(UPLOAD_DIR.$name);
									$att[] = $del_file." [$name]";
								}
								//����ͥ���
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
					//���줾��Υե�����ˤĤ�������|���ԤΥե饰��Ω�Ƥ�
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
					//�����󥿡�DB
					$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." WHERE `name` = '".addslashes($page)."' LIMIT 1;";
					$result=$xoopsDB->queryF($query);
				}
				break;
			}
			if(in_array(false,$flag)){
				//������Ԥ�����Τ���ĤǤ⤢��
				foreach($flag as $key=>$value)
				{
					$body .= "$key =&gt; {$status[(($value)? 1 : 0)]}<br/>\n";
				}
				$body .= "<p>{$_deldel_messages['msg_delete_error']}</p>";
				$body .= "<p><a href=\"$script?cmd=deldel\">".$_deldel_messages['msg_back_word']."</a></p>";
				return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$body);
			}else{
				//�������
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
			//ǧ�ڥ��顼
			return array('msg' => $_deldel_messages['title_delete_error'],'body'=>$_deldel_messages['msg_auth_error']);
		}
	}
}
/**
 * page_list2
 * �ڡ��������κ��� page_list()�ΰ������ѹ�
 *
 * @access public
 * @param  Array   $pages		 �ڡ���̾����
 * @param  String  $cmd			 ���ޥ��
 * @param  Boolean $withfilename �ե�����͡�����֤�(true)�֤��ʤ�(false)
 *
 * @return String				 �����ѤߤΥڡ����ꥹ��
 */
function page_list2($pages, $cmd = 'read', $withfilename = FALSE, $checked=FALSE)
{
	global $script, $list_index;
	global $_msg_symbol, $_msg_other;
	global $pagereading_enable;

	// �����ȥ�������ꤹ�롣 ' ' < '[a-zA-Z]' < 'zz'�Ȥ�������
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
		// �ѹ��������� by okkez
		$checked = ($checked)? " checked=\"true\"" : "";
		$freezed = is_freeze($page) ? '<span class="new1"> * </span>' : '';
		$exist_page = is_page($page) ? '' : '<span class="diff_added"> # </span>';
		$str = '   <li><input type="checkbox" name="pages[]" value="' . $s_page . '"'.$checked.' /><a href="' .
		$script . '?cmd=' . $cmd . '&amp;page=' . $r_page .
		'">' . $s_page . '</a>' . $passage . $freezed . $exist_page;
		// �����ޤ�

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
			} elseif(mb_ereg('^([��-��])', $readings[$page], $matches)) { // here
				$head = $matches[1];
			} elseif (mb_ereg('^[ -~]|[^��-��-��]', $page)) { // and here
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
 * DATA_DIR,BACKUP_DIR,DIFF_DIR,COUNTER_DIR�ΰ������롣
 *
 * @param  String  $cmd ���ޥ��
 * @param  String  $dir DATA_DIR or BACKUP_DIR �Τɤ��餫��������ά�Բ�
 * @param  Boolean $retry ��ȥ饤���ɤ�����TRUE:��ȥ饤,FALSE:��ȥ饤�ǤϤʤ���
 * @return String		����ɽ����body��ʬ���֤���
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
 * ��ǧ���̤���
 * global���ѿ�������󤹤ΤϤ��ޤ�褯�ʤ����������Τǰ������Ϥ��Ƥߤ�
 *
 * @access public
 * @param  String  $cmd	   ���ޥ�� [deldel|freeze2|unfreeze2]
 * @param  String  $dir	   $vars['dir']��Ȥ�
 * @param  String  $pages  $vars['pages']��Ȥ�
 *
 * @return Array   �ڡ��������ȥ������
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
 * AttachFile��Ѿ��������饹
 * toString�᥽�åɤ�checkbox�����ե饰��ɽ������褦���ѹ�
 * &amp;�ΰ��֤��ѹ����Ƥ���
 */
class AttachFile2 extends AttachFile
{
	/**
	 * �ڡ���̾���Ф��ƿ����ʥ�󥯤��ĤˤޤȤ���֤���
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
 * AttachFiles��Ѿ��������饹
 * AttachFile2��Ȥ��褦�ˤ�������
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
 * AttachPages��Ѿ��������饹
 * ���󥹥ȥ饯������礳�ä��ѹ�
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
 * ź�եե�����ΰ������� attach_list()����礳�äȲ���
 *
 * @access private
 * @param  Void	   �����Ϥʤ�
 *
 * @return Array   PukiWiki�Υץ饰������ͤ˽��ä����
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
 * @param  String $dir	  �ǥ��쥯�ȥ�̾ counter or diff
 * @param  String $page	  �ڡ���̾
 * @return String		  ʪ���ե�����̾
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
 * ����å���Τ��ݽ������ե������¸�ߤ��ʤ�����å��������̵�ѤǺ�����롣
 * @return Array ��������ե�����̾=>��������ե�����̾��ǥ����ɤ������
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