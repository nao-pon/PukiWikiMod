<?php
// $Id: pcomment.inc.php,v 1.12 2004/01/24 14:46:11 nao-pon Exp $
/*
Last-Update:2002-09-12 rev.15

*�ץ饰���� pcomment
���ꤷ���ڡ����˥����Ȥ�����

*Usage
 #pcomment([�ڡ���̾][,ɽ�����륳���ȿ�][,���ץ����])

*�ѥ�᡼��
-�ڡ���̾~
 ��Ƥ��줿�����Ȥ�Ͽ����ڡ�����̾��
-ɽ�����륳���ȿ�~
 ���Υ����Ȥ򲿷�ɽ�����뤫(0������)

*���ץ����
-above OR down~
 �����Ȥ�ե�����ɤ�����ɽ��(��������������)
-below OR up~
 �����Ȥ�ե�����ɤθ��ɽ��(��������������)
-reply~
 2��٥�ޤǤΥ����Ȥ˥�ץ饤��Ĥ���radio�ܥ����ɽ��
-btn:[String]~
 �ܥ����ɽ������ƥ�����
-size:[Integer]~
 �����ȥƥ����ȥܥå����Υ�����
-cname:[String]~
 �����ȥڡ�������Ƭ��̾
*/
// ɽ�����륳���ȿ��Υǥե����
define('PCMT_NUM_COMMENTS',10);
//
// �����Ȥ�̾���ƥ����ȥ��ꥢ�Υ�����
define('PCMT_COLS_NAME',15);
//
// �����ȤΥƥ����ȥ��ꥢ�Υ�����
define('PCMT_COLS_COMMENT',70);
//
// ����������� 1:���� 0:��Ƭ
define('PCMT_INSERT_INS',1);
//
//�����Ȥ������ե����ޥå�
define('PCMT_FORMAT_NAME','[[%s]]');
define('PCMT_FORMAT_MSG','%s');
define('PCMT_FORMAT_DATE','&new{%s};');
// \x08�ϡ���Ƥ��줿ʸ������˸���ʤ�ʸ���Ǥ���Фʤ�Ǥ⤤����
define("PCMT_FORMAT","\x08MSG\x08 -- \x08NAME\x08 \x08DATE\x08");
/////////////////////////////////////////////////
// areaedit��������ͤˤ���(Yes:1, No:0)
define('PCMT_AREAEDIT_ENABLE',1);
// areaedit������ɲå��ץ����
define('PCMT_AREAEDIT_OPTION',",preview:5");

function plugin_pcomment_init() {
	
	if (LANG == "ja") {
		$_plugin_pcmt_messages = array(
			'_pcmt_btn_name' => '��̾��: ',
			'_pcmt_btn_comment' => '�����Ȥ�����',
			'_pcmt_msg_comment' => '������: ',
			'_pcmt_msg_recent' => '�ǿ���%d���ɽ�����Ƥ��ޤ���',
			'_pcmt_msg_all' => '�����ȥڡ����򻲾�',
			'_pcmt_msg_edit' => '�Խ�',
			'_pcmt_msg_none' => '�����ȤϤ���ޤ���',
			'_title_pcmt_collided' => '$1 �ǡڹ����ξ��ۤ͡������ޤ���',
			'_msg_pcmt_collided' => '���ʤ������Υڡ������Խ����Ƥ���֤ˡ�¾�οͤ�Ʊ���ڡ����򹹿����Ƥ��ޤä��褦�Ǥ���<br />
			�����Ȥ��ɲä��ޤ��������㤦���֤���������Ƥ��뤫�⤷��ޤ���<br />',
		);
	} else {
		$_plugin_pcmt_messages = array(
			'_pcmt_btn_name' => 'Name:',
			'_pcmt_btn_comment' => 'Post comment',
			'_pcmt_msg_comment' => 'Comment: ',
			'_pcmt_msg_recent' => 'The newest %d comments',
			'_pcmt_msg_all' => 'See comment page',
			'_pcmt_msg_edit' => 'Edit',
			'_pcmt_msg_none' => 'No comments yet',
			'_title_pcmt_collided' => 'Conflicts found in $1',
			'_msg_pcmt_collided' => 'Other user has updated the page you are editing.<br />
			Your comment was added anyway but may be at wrong line.<br />',
		);
	}
  set_plugin_messages($_plugin_pcmt_messages);
}
function plugin_pcomment_action() {
	global $post;

	$retval = '';
	if($post['msg']) { $retval = pcmt_insert(); }
	return $retval;
}

function plugin_pcomment_convert() {
	global $script,$vars,$BracketName,$WikiName,$digest;
	global $_pcmt_btn_name, $_pcmt_btn_comment, $_pcmt_msg_comment, $_pcmt_msg_all, $_pcmt_msg_edit, $_pcmt_msg_recent;

	//�����
	$ret = '';

	//�ѥ�᡼���Ѵ�
	$args = func_get_args();
	array_walk($args, 'pcmt_check_arg', &$params);

	$all_option = (is_array($args))? implode(" ",$args) : $args;
	//�ܥ���ƥ����Ȼ��ꥪ�ץ����
	$btn_text = $_pcmt_btn_comment;
	if (preg_match("/(?: |^)btn:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$btn_text = htmlspecialchars($arg[1]);
	}
	//�����ȥƥ����ȥܥå������������ꥪ�ץ����
	$comment_size = PCMT_COLS_COMMENT;
	if (preg_match("/(?: |^)size:([0-9]+)(?: |$)/i",trim($all_option),$arg)){
		if (PCMT_COLS_COMMENT > $arg[1] && ($arg[1])) $comment_size = $arg[1];
	}
	//�����ȥڡ���̾���ꥪ�ץ����
	$comment_pg_name = PCMT_PAGE;
	if (preg_match("/(?: |^)cname:([^ ]+)(?: |$)/i",trim($all_option),$arg)){
		$comment_pg_name = "[[".trim($arg[1])."]]";
		if (preg_match("/^(($BracketName)|($WikiName))$/",$comment_pg_name)){
			$comment_pg_name = "[[".htmlspecialchars(trim($arg[1]))."/%s]]";
		}
	}
	//areaedit����
	$areaedit = "";
	if (preg_match("/(?: |^)areaedit(?: |$)/i",trim($all_option),$arg)){
		$areaedit = "<input type=\"hidden\" name=\"areaedit\" value=\"1\" />\n";
	}

	unset($args);

	//ʸ��������
	list($page, $count) = $params['arg'];
	//if ($page == '') { $page = sprintf(PCMT_PAGE,strip_bracket($vars['page'])); }
	if ($page == '') { $page = sprintf($comment_pg_name,strip_bracket($vars['page'])); }

	$_page = get_fullname($page,$vars['page']);
	if (!preg_match("/^$BracketName$/",$_page))
		return 'invalid page name.';
	
	// ��������
	if (!check_readable($_page,false,false))
		return str_replace('$1',strip_bracket($_page),_MD_PUKIWIKI_NO_VISIBLE);;

	if ($count == 0 and $count !== '0') { $count = PCMT_NUM_COMMENTS; }

	//���������
	$dir = PCMT_INSERT_INS;
	if ($params['above'] || $params['up']) { $dir = 1; }
	if ($params['below'] || $params['down']) { $dir = 0; } //ξ�����ꤵ�줿�鲼�� (^^;
	
	//�����Ȥ����
	$data = @file(get_filename(encode($_page)));
	
	//�ڡ���̾�ʤɤ������ؤ�
	$now_page = $vars['page'];
	$now_digest = $digest;
	if (is_page($_page)) $vars['page'] = $_page;
	list($comments, $digest) = pcmt_get_comments($data,$count,$dir,$params['reply']);

	// xoops //
	global $xoopsUser;
	if ($xoopsUser){
		$x_name = $xoopsUser->uname();
	} else {
		$x_name = "";
	}
	// ---- //

	//�ե������ɽ��
	if($params['noname']) {
		$title = $_pcmt_msg_comment;
		$name = '';
	} else {
		$title = $_pcmt_btn_name;
		$name = '<input type="text" name="name" size="'.PCMT_COLS_NAME.'" value="'.$x_name.'" />';
	}

	$radio = $params['reply'] ? '<input class="pcmt" type="radio" name="reply" value="0" checked />' : '';
	$comment = '<input type="text" name="msg" size="'.$comment_size.'" />';

	//XSS�ȼ������� - ���������褿�ѿ��򥨥�������
	$f_page = htmlspecialchars($page);
	$f_refer = htmlspecialchars($vars['page']);
	$f_nodate = htmlspecialchars($params['nodate']);

	$form = <<<EOD
  <div>
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="plugin" value="pcomment" />
  <input type="hidden" name="refer" value="$f_refer" />
  <input type="hidden" name="page" value="$f_page" />
  <input type="hidden" name="nodate" value="$f_nodate" />
  <input type="hidden" name="dir" value="$dir" />
  $areaedit $radio $title $name $comment
  <input type="submit" value="$btn_text" />
  </div>
EOD;
	$link = $_page;
	if (!is_page($_page)) {
		$recent = $_pcmt_msg_none;
		$link = make_pagelink($link);
	} else {
		if ($_pcmt_msg_all != '')
			$link = make_pagelink($link,$_pcmt_msg_all);
		$recent = '';
		if ($count > 0) { $recent = sprintf($_pcmt_msg_recent,$count); }
		$edit_tag =  (is_freeze($_page,false))? "" : " | <a href=\"$script?cmd=edit&amp;page=".rawurlencode($_page)."\">$_pcmt_msg_edit</a>";
	}
	//$link = make_pagelink($link);
	
	//���򤷤��ѿ����᤹
	$vars['page'] = $now_page;
	$digest = $now_digest;

	return $dir ?
		"<div><p>$recent $link$edit_tag</p>\n<form action=\"$script\" method=\"post\">$comments$form</form></div>" :
		"<div><form action=\"$script\" method=\"post\">$form$comments</form>\n<p>$recent $link</p></div>";
}

function pcmt_insert($page) {
	global $post,$vars,$script,$now,$do_backup,$BracketName;
	global $_title_updated,$no_name,$X_uid;

	$page = $post['page'];
	if (!preg_match("/^$BracketName$/",$page))
		return array('msg'=>'invalid page name.','body'=>'cannot add comment.','collided'=>TRUE);

	$ret['msg'] = $_title_updated;

	//ʸ���������
	$msg = user_rules_str($post['msg']);
	//ɽ��Ǥ���ѤǤ���褦��|�򥨥������� nao-pon
	$msg = str_replace('|','&#124;',$msg);
	
	//�����ȥե����ޥåȤ�Ŭ��
	$msg = sprintf(PCMT_FORMAT_MSG, rtrim($post['msg']));
	$name = ($post['name'] == '') ? $no_name : $post['name'];
	if (WIKI_USER_DIR)
		make_user_link($name);
	else
		$name = sprintf(PCMT_FORMAT_NAME, $name);
	$date = ($post['nodate'] == '1') ? '' : sprintf(PCMT_FORMAT_DATE, $now);
	if ($date != '' or $name != '') { 
		$msg = str_replace("\x08MSG\x08", $msg,  PCMT_FORMAT);
		$msg = str_replace("\x08NAME\x08",$name, $msg);
		$msg = str_replace("\x08DATE\x08",$date, $msg);
		//$msg = str_replace("\x08NEW\x08",$mnew, $msg);
	}
	if ($post['reply'] or !is_page($page)) {
		$msg = preg_replace('/^\-+/','',$msg);
	}
	
	$msg = rtrim($msg);
	//areaedit����
	if (PCMT_AREAEDIT_ENABLE || !empty($post['areaedit'])) $msg = "&areaedit(uid:".$X_uid.PCMT_AREAEDIT_OPTION."){".$msg."};";
	
	if (!is_page($page)) {
		//$new = PCMT_CATEGORY.' '.htmlspecialchars($post['refer'])."\n\n-$msg\n";
		$new = '***'.htmlspecialchars($post['refer'])."�Υ����Ȱ���\n".PCMT_CATEGORY."\n\n-$msg\n";
	} else {
		//�ڡ������ɤ߽Ф�
		$data = file(get_filename(encode($page)));
		$old = join('',$data);

		$reply = $post['reply'];
		// �����ξ��ͤ򸡽�
		if (md5($old) != $post['digest']) {
			$ret['msg'] = $_title_paint_collided;
			$ret['body'] = $_msg_paint_collided;
			$reply = 0; //��ץ饤�Ǥʤ�����
		}

		// �ڡ���������Ĵ��
		if (substr($data[count($data) - 1],-1,1) != "\n") { $data[] = "\n"; }

		//����������
		$level = 1;
		if ($post['dir'] == '1') {
			$pos = count($data) - 1;
			$step = -1;
		} else {
			$pos = -1;
			foreach ($data as $line) {
				if (preg_match('/^\-/',$line)) break;
				$pos++;
			}
			$step = 1;
		}
		//��ץ饤��Υ����Ȥ򸡺�
		if ($reply > 0) {
			while ($pos >= 0 and $pos < count($data)) {
				if (preg_match('/^(\-{1,2})(?!\-)/',$data[$pos], $matches) and --$reply == 0) {
					$level = strlen($matches[1]) + 1; //���������٥�
					break;
				}
				$pos += $step;
			}
			while (++$pos < count($data)) {
				if (preg_match('/^(\-{1,2})(?!\-)/',$data[$pos], $matches)) {
					if (strlen($matches[1]) < $level) { break; }
				}
			}
		} else {
			$pos++;
		}
		//��Ƭʸ��
		$head = str_repeat('-',$level);
		//�����Ȥ�����
		array_splice($data,$pos,0,"$head$msg\n");
		$new = join('',$data);
	}

	//�ƥڡ����Υե����륿���๹��
	touch(DATA_DIR.encode($post['refer']).".txt");
	put_lastmodified();
	//�ƥڡ�����DB����
	pginfo_db_write($post['refer'],"update");

	if (!is_page($page))
	{
		//�ڡ�����������
		$aids = $gids = $freeze = "";
		//�Խ����·Ѿ������åȤ���Ƥ����̥ڡ��������������
		$up_freezed = get_pg_allow_editer($post['refer']);
		$page_info = "";
		//�ڡ�������Υ��å�
		if ($up_freezed['uid'] !== "")
		{
			//�Խ����·Ѿ�����
			$freeze = 1;
			$uid = $up_freezed['uid'];
			$aids = preg_replace("/(($|,)$uid,|,$)/","",$up_freezed['user']);
			$gids = preg_replace("/,$/","",$up_freezed['group']);
			$page_info = "#freeze\tuid:{$uid}\taid:{$aids}\tgid:{$gids}\n// author:{$uid}\n";
		}
		else
		{
			$page_info = "// author:".get_pg_auther($post['refer'])."\n";
		}
		$new = $page_info.$new;
		// �ڡ�������
		page_write($page, $new, NULL,$aids,$gids,"","",$freeze,"",array('plugin'=>'pcomment','mode'=>'add'));
	}
	else
	{
		// �����ȥե�����ν񤭹��� ��4����:�ǽ��������ʤ�=true
		page_write($page, $new, true,"","","","","","",array('plugin'=>'pcomment','mode'=>'add'));
	}

	$vars['page'] = $post['page'] = $post['refer'];

	return $ret;
}
//���ץ�������Ϥ���
function pcmt_check_arg($val, $key, &$params) {
	$valid_args = array('noname','nodate','below','above','reply','mail','up','down');
	foreach ($valid_args as $valid) {
		if (strpos($valid, strtolower($val)) === 0) {
			$params[$valid] = 1;
			return;
		}
	}
	$params['arg'][] = $val;
}
function pcmt_get_comments($data,$count,$dir,$reply) {
	if (!is_array($data)) { return array('',0); }

	$digest = md5(join('',$data));

	//�����Ȥ���ꤵ�줿��������ڤ���
	if ($dir) { $data = array_reverse($data); }
	$num = $cnt = 0;
	$cmts = array();
	foreach ($data as $line) {
		if ($count > 0 and $dir and $cnt == $count) { break; }
		if (preg_match('/^(\-{1,2})(?!\-)(.*)$/', $line, $matches)) {
			if ($count > 0 and strlen($matches[1]) == 1 and ++$cnt > $count) { break; }
			if ($reply) {
				++$num;
				$cmts[] = "$matches[1]\x01$num\x02$matches[2]\n";
			} else {
				$cmts[] = $line;
			}
		} else {
			$cmts[] = $line;
		}
	}
	$data = $cmts;
	if ($dir) { $data = array_reverse($data); }
	unset($cmts);

	//�����Ȥ�����Υǡ������������
	while (count($data) > 0 and substr($data[0],0,1) != '-') { array_shift($data); }

	//html�Ѵ�
	$comments = convert_html(join('', $data));
	unset($data);

	//�����Ȥ˥饸���ܥ���ΰ���Ĥ���
	if ($reply) {
		//$comments = preg_replace("/\x01(\d+)\x02/",'<input class="wiki_radio" type="radio" name="reply" value="$1" />', $comments);
		$comments = preg_replace("/<li>\x01(\d+)\x02/",'<li class="pcmt"><input class="pcmt" type="radio" name="reply" value="$1" />', $comments);

	}
	return array($comments,$digest);
}
?>
