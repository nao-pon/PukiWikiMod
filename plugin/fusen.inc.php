<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// fusen.inc.php
// ��䵥ץ饰����
// ohguma@rnc-com.co.jp
//
// v 1.0 2005/03/12 ����
// v 1.1 2005/03/16 FUSEN_SCRIPT_FILE��DATA_HOME�ɲ�,
//                  ��䵺���������õ�,
//                  �����Ǥ�#fusen���,
//                  ������form����������Զ����.
// v 1.2 2005/03/16 XSS�к�,�����ǧ�ɲ�
// v 1.3 2005/03/17 XHTML1.1�б�?
//                  �ط�Ʃ����������������
// v 1.4 2005/03/18 ������ǽ�ɲ�,
//                  ��䵹�������ź�ո��ե�����κǽ��������򹹿�
// v 1.5 2005/03/18 ������ǽ����(convert_html���ɽ�����ƤǸ���),
//                  ��䵹�������RecentChanges��ȿ��,
//                  XSS�к��ν���
// v 1.6 2005/03/28 �����ɲû���ID��Ϳ�����꽤��
// v 1.7 2005/04/02 HELP����,���ϲ����ѹ�
//                  ��䵥ǡ����ݻ���ˡ�ѹ�
// v 1.8 2005/04/03 ��䵤�0��ˤʤä��ݤΥХ�����
//                  AJAX�б�(auto set, �ꥢ�륿���๹���б�)
//

/////////////////////////////////////////////////
// PukiWikiMod - XOOPS's PukiWiki module.
//
// fusen.inc.php for PukiWikiMod by nao-pon
// http://hypweb.net
// $Id: fusen.inc.php,v 1.11 2005/11/07 06:24:56 nao-pon Exp $
// 

// fusen.js��PATH
define('FUSEN_SCRIPT_FILE', './skin/fusen.js');

// ��䵥ǡ�����ź�եե�����̾
define('FUSEN_ATTACH_FILENAME','fusen.dat');

// ���������������
// �̾�ʬ
define('FUSEN_STYLE_BORDER_NORMAL', '#000000 1px solid');
// ��å�ʬ
//define('FUSEN_STYLE_BORDER_LOCK', '#000000 3px double');
define('FUSEN_STYLE_BORDER_LOCK', '#836FFF 1px solid');
// ���ʬ
define('FUSEN_STYLE_BORDER_DEL', '#333333 1px dotted');
// ����ʬ
define('FUSEN_STYLE_BORDER_SELECT', 'red 1px solid');


function plugin_fusen_convert() {
	global $script,$vars;
	global $now_inculde_convert;
	global $X_uid,$X_admin;
	global $pwm_plugin_flg;
	
	static $loaded = false;
	
	// �ѥ�᡼��
	$off = $from_skin = $refresh = 0;
	$background = $height = '';
	foreach(func_get_args() as $prm)
	{
		if (preg_match("/^r(efresh)?:([\d]+)/",$prm,$arg))
			$refresh =($arg[2])? $arg[2] : 0;
		if (preg_match("/^h(eight)?:([\d]+)/",$prm,$arg))
			$height = min($arg[2],10000);
		if ($prm == 'FROM_SKIN')
			$from_skin = 1;
		if (strtolower($prm) == 'off')
			$off = 1;
	}
	
	//�ɤ߹��ߥ����å�
	if ($now_inculde_convert)
	{
		if ($off) return '';
		return "<p>".make_pagelink($vars['page'],strip_bracket($vars['page'])."����䵤�ɽ��")."</p>";
	}
	if ($loaded)
	{
		return '';
	}
	
	// $pwm_plugin_flg ���å�
	$pwm_plugin_flg['fusen']['convert'] = true;
	
	if ($off) return '';
	
	// �����
	$loaded = true;
	$refer = $vars['page'];
	$jsfile = FUSEN_SCRIPT_FILE;
	$border_normal = FUSEN_STYLE_BORDER_NORMAL;
	$border_lock = FUSEN_STYLE_BORDER_LOCK;
	$border_del = FUSEN_STYLE_BORDER_DEL;
	$border_select = FUSEN_STYLE_BORDER_SELECT;
	$fusen_data = plugin_fusen_data($refer);
	$name = WIKI_NAME_DEF;
	$jname = plugin_fusen_jsencode($name);
	
	if ($height)
	{
		$height = "height:{$height}px;";
	}
	else
	{
		$background = 'background:transparent none;';
	}
	
	$wiki_helper = '<div class="wiki_helper">'.fontset_js_tag().'</div>';
	
	$selected = 0;
	$refresh_str = '��ư����:<select name="fusen_menu_interval" id="fusen_menu_interval" size="1" onchange="fusen_setInterval(this.value);window.focus();">';
	$refresh_str .= '<option value="0">�ʤ�';
	foreach(array(10,20,30,60) as $sec)
	{
		if (!$selected && $refresh && $sec >= $refresh)
		{
			$select = ' selected="true"';
			$selected = $sec;
		}
		else
			$select = '';
		$msec = $sec * 1000;
		$refresh_str .= '<option value="'.$msec.'"'.$select.'>'.$sec.'��';
	}
	$refresh_str .= '</select>';
	$refresh = $selected * 1000;
	
	$html = plugin_fusen_gethtml($fusen_data);
	//if (!$html) $html = '<p></p>';
	
	$fusen_post = '_XOOPS_WIKI_HOST_' . XOOPS_WIKI_URL . "/index.php";
	$fusen_url = str_replace(XOOPS_WIKI_PATH,'_XOOPS_WIKI_HOST_'.XOOPS_WIKI_URL,P_CACHE_DIR . "fusen_" .encode($vars['page']) . ".utxt");
	$X_ucd = WIKI_UCD_DEF;
	$js_refer = plugin_fusen_jsencode($refer);
	$auth = ($X_admin || ($X_uid && get_pg_auther($refer) == $X_uid))? 1 : 0;
	
	$burn = ($auth)? "(<a href=\"JavaScript:fusen_burn()\" title=\"����Ȣ����ˤ���\">����</a>)" : "";
	
	return <<<EOD
<script type="text/javascript" src="$jsfile" charset="euc-jp"></script>
<script type="text/javascript">
<!--
var fusenBorderObj = {"normal":"{$border_normal}", "lock":"{$border_lock}", "del":"{$border_del}", "select":"{$border_select}"};
var fusenJsonUrl = "{$fusen_url}";
var fusenPostUrl = "{$fusen_post}";
var fusenInterval = {$refresh};
var fusenX_admin = {$auth};
var fusenX_uid = {$X_uid};
var fusenX_ucd = "{$X_ucd}";
var fusenFromSkin = {$from_skin};
//-->
</script>
<fieldset class="fusen_fieldset">
<legend>��䵵�ǽ(wema) ��˥塼&nbsp;</legend>
<div id="fusen_top_menu" class="fusen_top_menu" style="visibility: hidden;">
<form action="" onsubmit="return false;" style="padding:0px;margin:0px;">
  <img src="./image/fusen.gif" width="20" height="20" alt="��䵵�ǽ" title="��䵵�ǽ" />
  [<a href="JavaScript:fusen_new()" title="��������䵤�Ž��">����</a>]
  [<a href="JavaScript:fusen_dustbox()" title="����Ȣ��ɽ��/��ɽ��">����Ȣ</a>{$burn}]
  [<a href="JavaScript:fusen_transparent()" title="��䵤��Ʃ����/��Ʃ����">Ʃ��</a>]
  [<a href="JavaScript:fusen_init(1)" title="�ǿ��ξ��֤˹���">����</a>]
  [<a href="JavaScript:fusen_show('fusen_list')" title="���Υڡ�������䵥ꥹ��">�ꥹ��</a>]
  [<a href="JavaScript:fusen_show('fusen_help')" title="�Ȥ���">�إ��</a>]&nbsp;
  ��䵸���:<input type="text" onkeyup="JavaScript:fusen_grep(this.value)" />
  {$refresh_str}
</form>
</div>
<noscript>
<div class="fusen_top_menu"><strong>JavaScript̤ư��</strong>: ��䵤��Խ��Ǥ��ޤ��󡣤ޤ�����䵤�ɽ�����֤�����Ƥ����礬����ޤ���</div>
</noscript>
</fieldset>
<div id="fusen_editbox" class="fusen_editbox">
  <div class="fusen_editbox_title">��䵤��Խ�</div>
  <form id="edit_frm" method="post" action="" style="padding:0px; margin:0px" onsubmit="fusen_save(); return false;">
      ʸ������<select id="edit_tc" name="tc" size="1">
        <option id="tc000000" value="#000000" style="color: #000000" selected>����</option>
        <option id="tc999999" value="#999999" style="color: #999999">����</option>
        <option id="tcff0000" value="#ff0000" style="color: #ff0000">����</option>
        <option id="tc00ff00" value="#00ff00" style="color: #00ff00">����</option>
        <option id="tc0000ff" value="#0000ff" style="color: #0000ff">����</option>
      </select>
      �طʿ���<select id="edit_bg" name="bg" size="1">
        <option id="bgffffff" value="#ffffff" style="background-color: #ffffff" selected>��</option>
        <option id="bgffaaaa" value="#ffaaaa" style="background-color: #ffaaaa">����</option>
        <option id="bgaaffaa" value="#aaffaa" style="background-color: #aaffaa">����</option>
        <option id="bgaaaaff" value="#aaaaff" style="background-color: #aaaaff">����</option>
        <option id="bgffffaa" value="#ffffaa" style="background-color: #ffffaa">����</option>
        <option id="bgransparent" value="transparent">Ʃ��</option>
      </select><br />
      ��̾��:<input type="text" name="name" id="edit_name" value="{$name}"/>&nbsp;
      ����³id��<input type="text" name="ln" id="edit_ln" size="4" /><br />
      $wiki_helper
      <textarea name="body" id="edit_body" cols="50" rows="10" style="width:auto;"></textarea><br />
      <input type="submit" value="�񤭹���" />
      <input type="button" value="�Ĥ���" onclick="fusen_editbox_hide();" />
      <input type="hidden" name="id" id="edit_id"/>
      <input type="hidden" name="z" id="edit_z" value="1" />
      <input type="hidden" name="l" id="edit_l" />
      <input type="hidden" name="t" id="edit_t" />
      <input type="hidden" name="w" id="edit_w" value="0" />
      <input type="hidden" name="h" id="edit_h" value="0" />
      <input type="hidden" name="fix" id="edit_fix" value="0" />
      <input type="hidden" name="bx" id="edit_bx" value="0" />
      <input type="hidden" name="by" id="edit_by" value="0" />
      <input type="hidden" name="pass" id="edit_pass" value="" />
      <input type="hidden" name="mode" id="edit_mode" value="edit" />
      <input type="hidden" name="plugin" value="fusen" />
      <input type="hidden" name="refer" value="{$refer}" />
      <input type="hidden" name="page" value="{$refer}" />
  </form>
  <div class="fusen_editbox_footer">
  <form action="" onsubmit="return false;" style="width:auto;padding:0px;margin:0px;">
    [<a href="JavaScript:fusen_dustbox()" title="����Ȣ��ɽ��/��ɽ��">����Ȣ</a>]
    [<a href="JavaScript:fusen_transparent()" title="��䵤��Ʃ����/��Ʃ����">Ʃ��</a>]
    [<a href="JavaScript:fusen_show('fusen_list')" title="���Υڡ�������䵥ꥹ��">�ꥹ��</a>]
    [<a href="JavaScript:fusen_show('fusen_help')" title="�Ȥ���">�إ��</a>]&nbsp;
    ��䵸���:<input type="text" size="20" onkeyup="JavaScript:fusen_grep(this.value)" />
  </form>
  </div>
</div>
<div id="fusen_help" class="fusen_help"></div>
<div id="fusen_list" class="fusen_list"></div>
<div id="fusen_area" style="{$height}{$background}">$html</div>
EOD;
}


function plugin_fusen_action() {
	global $post,$adminpass,$vars;
	global $X_admin,$X_uid,$X_uname;

	error_reporting(E_ALL);
	
	// �����
	//if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	$refer = $post['page'] = $vars['page'] = $post['refer'];
	
	// ����С��Ȥ��ʤ��ǥե������ɤ߹���
	$dat = plugin_fusen_data($refer,false);
	
	// ���곰�Υ⡼��
	if (!in_array($post['mode'],array('set','del','lock','unlock','recover','edit','burn')))
	{
		ob_clean();
		exit;
	}
	
	$id = preg_replace('/id/', '', $post['id']);
	
	$auth = false;
	
	if ($id && array_key_exists($id,$dat))
	{
		if ($X_admin || ($X_uid && get_pg_auther($refer) == $X_uid)) $auth = true;
		else if ($dat[$id]['uid'] && $dat[$id]['uid'] == $X_uid) $auth = true;
		else if (!$dat[$id]['uid'] && $dat[$id]['ucd'] && $dat[$id]['ucd'] == PUKIWIKI_UCD) $auth = true;
	}
	else
	{
		// ���⡼��
		if ($post['mode'] == "burn")
		{
			if ($X_admin || ($X_uid && get_pg_auther($refer) == $X_uid)) $auth = true;
		}
		else
		{
			$auth = true;
		}
	}
	
	// ID����,�ǡ�������
	switch ($post['mode'])
	{
		case 'set':
		case 'del':
		case 'lock':
		case 'unlock':
		case 'recover':
			if (!array_key_exists($id,$dat)) die_message('The data is not accumulated just.'."($id)");
		case 'burn':
			// �ڡ���HTML����å������
			delete_page_html($refer,"html");
			// touch
			if ($id) $dat[$id]['tt'] = time();
			//�͹���
			switch ($post['mode'])
			{
				case 'set':
					if (!$dat[$id]['lk']) $auth = true;
					$dat[$id]['x'] = (preg_match('/^\d+$/', $post['l']) ? $post['l'] : '');
					$dat[$id]['y'] = (preg_match('/^\d+$/', $post['t']) ? $post['t'] : '');
					$dat[$id]['bx'] = (preg_match('/^\d+$/', $post['l']) ? $post['bx'] : 0);
					$dat[$id]['by'] = (preg_match('/^\d+$/', $post['t']) ? $post['by'] : 0);
					$dat[$id]['w'] = (preg_match('/^\d+$/', $post['w']) ? $post['w'] : 0);
					$dat[$id]['h'] = (preg_match('/^\d+$/', $post['h']) ? $post['h'] : 0);
					$dat[$id]['fix'] = (!empty($post['fix'])) ? (int)$post['fix'] : 0;
					$dat[$id]['z'] = (preg_match('/^\d+$/', $post['z']) ? $post['z'] : '');
					break;
				case 'lock':
					$dat[$id]['lk'] = true;
					break;
				case 'unlock':
					$dat[$id]['lk'] = false;
					break;
				case 'del':
					if (empty($dat[$id]['del']))
					{
						$dat[$id]['del'] = true;
						$dat[$id]['lk'] = false;
						//$dat[$id]['ln'] = '';
					}
					else
					{
						unset($dat[$id]);
						// plane_text DB �򹹿�
						need_update_plaindb($refer);
					}
					foreach($dat as $k=>$v)
					{
						if ($dat[$k]['ln'] == 'id'.$id) $dat[$k]['ln'] = '';
					}
					break;
				case 'recover':
					$dat[$id]['del'] = false;
					break;
				case 'burn':
					$burned = false;
					foreach($dat as $k=>$v)
					{
						if (!empty($dat[$k]['del']))
						{
							unset($dat[$k]);
							$burned = true;
						}
					}
					if ($burned) need_update_plaindb($refer);
					break;
			}
			break;
		case 'edit':
			if ($id == '')
			{
				krsort($dat);
				$id = array_shift(array_keys($dat)) + 1;
				$mt = date("ymdHis");
				$uid = $X_uid;
				$ucd = PUKIWIKI_UCD;
				// ̾���򥯥å�������¸
				$name = $post['name'];
				setcookie("pukiwiki_un", $name, time()+86400*365);//1ǯ��
				make_user_link($name);
			}
			else
			{
				//if (!$dat[$id]['lk']) $auth = true;
				if (!array_key_exists($id,$dat)) die_message('The data is not accumulated just.'."($id)");
				$mt = $dat[$id]['mt'];
				$uid = $dat[$id]['uid'];
				$ucd = $dat[$id]['ucd'];
				$name = $dat[$id]['name'];
			}
			if ($auth)
			{
				$txt = str_replace(array("\r\n","\r"),"\n",$post['body']);
				$txt = preg_replace('/^#fusen/m', '&#35;fusen', $txt);
				$txt = user_rules_str(auto_br($txt));
				$txt = rtrim($txt);
				
				$et = date("ymdHis");
				$fix = (!empty($post['fix']))? (int)$post['fix'] : 0;
				$w = (preg_match('/^\d+$/', $post['w']))? $post['w'] : 0;
				$h = (preg_match('/^\d+$/', $post['h']))? $post['h'] : 0;
				
				$dat[$id] = array(
					'ln' => (preg_match('/^(id)?(\d+)$/', $post['ln'], $ma) ? $ma[2] : ''),
					'x' => (preg_match('/^\d+$/', $post['l']) ? $post['l'] : 100),
					'y' => (preg_match('/^\d+$/', $post['t']) ? $post['t'] : 100),
					'bx' => (preg_match('/^\d+$/', $post['bx']) ? $post['bx'] : 0),
					'by' => (preg_match('/^\d+$/', $post['by']) ? $post['by'] : 0),
					'z' => 1,
					'tc' => (preg_match('/^#[\dA-F]{6}$/i', $post['tc']) ? $post['tc'] : '#000000'),
					'bg' => (preg_match('/^(#[\dA-F]{6}|transparent)$/i', $post['bg']) ? $post['bg'] : '#ffffff'),
					'lk' => false,
					'txt' => $txt,
					'name' => $name,
					'mt' => $mt,
					'et' => $et,
					'tt' => time(),
					'uid' => $uid,
					'ucd' => $ucd,
					'fix' => $fix,
					'w' => $w,
					'h' => $h,
				);
				
				ksort($dat);
				
				// NULL�Х��Ⱥ��
				$dat = input_filter($dat);
				
				// plane_text DB ������ؼ�
				need_update_plaindb($refer);
				
				// �ɲåǡ����ե�������¸
				$_pgid = get_pgid_by_name($refer);
				push_page_changes($_pgid,"[���:$id]\n\n".$txt);
				
				// �ڡ���HTML����å����RSS����å������
				delete_page_html($refer);
			}
			break;
		default:
			die_message('Illegitimate parameter was used.');
	}

	if ($auth) {
		//�񤭹���
		if (!exist_plugin('attach') or !function_exists('attach_upload'))
		{
			exit ('attach.inc.php not found or not correct version.');
		}
		if (count($dat) < 1) $dat = array();
		
		$fname = UPLOAD_DIR . encode($refer) . '_' . encode(FUSEN_ATTACH_FILENAME);
		if ($fp = fopen($fname.".tmp", "wb"))
		{
			flock($fp, LOCK_EX);
			fputs($fp, FUSEN_ATTACH_FILENAME . "\n");
			fputs($fp, serialize($dat));
			fclose($fp);
			$GLOBALS['pukiwiki_allow_extensions'] = "";
			if ($post['mode'] == 'edit')
			{
				// �Խ����ϥ����ॹ����פ򹹿�����
				$ret = do_upload($refer, FUSEN_ATTACH_FILENAME, $fname.".tmp");
			}
			else
			{
				// ����¾�ϥ����ॹ����פ򹹿����ʤ�
				$ret = do_upload($refer, FUSEN_ATTACH_FILENAME, $fname.".tmp",FALSE,NULL,TRUE);
			}
		}
		
		// ����å����˴�
		@unlink(P_CACHE_DIR.encode($refer).".fusen");
		clearstatcache();
		
		// ����С��Ȥ��ƺ��ɤ߹���
		$dat = plugin_fusen_data($refer);
		// JSON�ե�����񤭹���
		plugin_fusen_putjson($dat,$refer);
	}
	ob_clean();
	exit;
}

//ź�եե������ɤ߹���
function plugin_fusen_data($page,$convert=true)
{
	$fname = encode($page) . '_' . encode(FUSEN_ATTACH_FILENAME);
	if (!file_exists(UPLOAD_DIR . $fname)) return array();
	$data = file(UPLOAD_DIR . $fname);
	if (!$data || trim(array_shift($data)) != FUSEN_ATTACH_FILENAME) return array();
	
	$data = unserialize(join('',$data));
	
	if (!$convert) return $data;
	
	// ����å�������å�
	$cfile = P_CACHE_DIR.encode($page).".fusen";
	if (file_exists($cfile) && filemtime($cfile) > time() - 1800) return unserialize(join('',file($cfile)));
	
	// ��礷�ƥ���С��Ȥ���
	$str = '';
	foreach ($data as $k => $dat)
	{
		$str .= "###fusen_data_convert###{$k}\n\n".$dat['txt']."\n\n";
	}
	
	fusen_convert_html($str,$page);
	
	$str = trim(str_replace("\r","",$str));
	
	$str_ary = preg_split("/###fusen_data_convert###/",$str);
	array_shift($str_ary);
	foreach ($str_ary as $str)
	{
		list($id,$dat) = explode("\n",$str,2);
		$data[$id]['disp'] = trim($dat);
	}
	
	if($fp = fopen($cfile, "wb"))
	{
		flock($fp, LOCK_EX);
		fputs($fp, serialize($data));
		fclose($fp);
	}

	return $data;
}

//PHP���֥������Ȥ�JSON���Ѵ�
function plugin_fusen_getjson($fusen_data)
{
	global $X_admin,$X_uid;
	// �����ȤȤ��ƽ���
	$_X_uid = $X_uid;
	$_X_admin = $X_admin;
	$X_uid = $X_admin = 0;
	
	// ��䵡����ǡ�������
	$json = '{';
	foreach ($fusen_data as $k => $dat) {
		//����ֹ椬�����Ǥʤ��������Ф���
		if (!preg_match('/\d+/', $k)) continue;
		$id = 'id' . $k;

		//#fusen�ץ饰����Υͥ��ȶػ�
		$dat['txt'] = preg_replace('/^#fusen/m', '&#35;fusen', $dat['txt']);

		// XSS�к�(��䵥ǡ�����ľ�ܲ����󤵤����֤�����)
		if (!preg_match('/^\d+$/', $dat['x'])) $dat['x'] = 100 + $k;
		if (!preg_match('/^\d+$/', $dat['y'])) $dat['y'] = 100 + $k;
		if (!preg_match('/^\d+$/', $dat['bx'])) $dat['bx'] = 0;
		if (!preg_match('/^\d+$/', $dat['by'])) $dat['by'] = 0;
		if (!preg_match('/^\d+$/', $dat['z'])) $dat['z'] = 1;
		if (!preg_match('/^#[\dA-F]{6}$/i', $dat['tc'])) $dat['tc'] = '#000000';
		if (!preg_match('/^(#[\dA-F]{6}|transparent)$/i', $dat['bg'])) $dat['bg'] = '#ffffff';
		if (!preg_match('/^(id)?\d+$/', $dat['ln'])) $dat['ln'] = '';
		if (!preg_match('/^\d+$/', $dat['mt'])) $dat['mt'] = 0;
		if (!preg_match('/^\d+$/', $dat['et'])) $dat['et'] = 0;
		
		// ~\n -> \n
		$dat['txt'] = preg_replace("/~$/m","",$dat['txt']);
		
		// ����ʸ��������
		$dat['disp'] = str_replace(array("\r","\n","\t"),'',$dat['disp']);

		// JSON�ι���
		if ($json != '{') $json .= ",\n";
		$json .=  $k . ':{';
		$json .= '"x":' . $dat['x'] . ',';
		$json .= '"y":' . $dat['y'] . ',';
		$json .= '"bx":' . $dat['bx'] . ',';
		$json .= '"by":' . $dat['by'] . ',';
		$json .= '"z":' . $dat['z'] . ',';
		$json .= '"tc":"' . $dat['tc'] . '",';
		$json .= '"bg":"' . $dat['bg'] . '",';
		$json .= '"disp":"' .plugin_fusen_jsencode($dat['disp']) . '",';
		$json .= '"txt":"' . plugin_fusen_jsencode(htmlspecialchars($dat['txt'])) . '",';
		$json .= '"name":"' . plugin_fusen_jsencode(make_link($dat['name'])) . '",';
		$json .= '"mt":"' . ($dat['mt']? $dat['mt'] : "" ) . '",';
		$json .= '"et":"' . ($dat['et']? $dat['et'] : "" ) . '",';
		$json .= '"tt":' . ($dat['tt']? $dat['tt'] : 0 ) . ',';
		$json .= '"uid":' . ($dat['uid']? $dat['uid'] : 0 ) . ',';
		$json .= '"ucd":"' . ($dat['ucd']? $dat['ucd'] : "" ) . '",';
		$json .= '"fix":' . (empty($dat['fix'])? 0 : (int)$dat['fix'] ) . ',';
		$json .= '"w":' . ($dat['w']? $dat['w'] : 0 ) . ',';
		$json .= '"h":' . ($dat['h']? $dat['h'] : 0 ) . '';
		if (isset($dat['ln']) && $dat['ln']) $json .= ',"ln":' . preg_replace('/^id/', '', $dat['ln']) ;
		if (isset($dat['lk']) && $dat['lk']) $json .= ',"lk":' . $dat['lk'];
		if (isset($dat['del']) && $dat['del']) $json .= ',"del":' . $dat['del'] ;
		$json .= '}';
	}
	$json .= '}';
	
	//����������ᤷ
	$X_uid = $_X_uid;
	$X_admin = $_X_admin;
	
	return $json;
}

//JSON�������󥳡���
function plugin_fusen_jsencode($str) {
	$str = preg_replace('/(\x22|\x2F|\x5C)/', '\\\$1', $str);
	$str = str_replace(array("\x00","\x08","\x09","\x0A","\x0C","\x0D"), array('','\b','\t','\n','\f','\r'), $str);
	return $str;
}

//PHP���֥������Ȥ�HTML���Ѵ�
function plugin_fusen_gethtml($fusen_data)
{
	global $vars;
	
	//JSON�ե�����񤭹���
	plugin_fusen_putjson($fusen_data,$vars['page']);
	
	if (!$fusen_data) return '';
	
	// ��䵡����ǡ�������
	$ret = '';
	foreach ($fusen_data as $k => $dat) {
		//����ֹ椬�����Ǥʤ��������Ф���
		if (!preg_match('/\d+/', $k)) continue;
		$id = 'id' . $k;

		//#fusen�ץ饰����Υͥ��ȶػ�
		$dat['txt'] = preg_replace('/^#fusen/m', '&#35;fusen', $dat['txt']);

		// XSS�к�(��䵥ǡ�����ľ�ܲ����󤵤����֤�����)
		if (!preg_match('/^\d+$/', $dat['x'])) $dat['x'] = 100 + $k;
		if (!preg_match('/^\d+$/', $dat['y'])) $dat['y'] = 100 + $k;
		if (!preg_match('/^\d+$/', $dat['bx'])) $dat['bx'] = 0;
		if (!preg_match('/^\d+$/', $dat['by'])) $dat['by'] = 0;
		if (!preg_match('/^\d+$/', $dat['z'])) $dat['z'] = 1;
		if (!preg_match('/^#[\dA-F]{6}$/i', $dat['tc'])) $dat['tc'] = '#000000';
		if (!preg_match('/^(#[\dA-F]{6}|transparent)$/i', $dat['bg'])) $dat['bg'] = '#ffffff';
		if (!preg_match('/^(id)?\d+$/', $dat['ln'])) $dat['ln'] = '';

		// HTML�ι���
		
		if ($dat['lk']) $border = FUSEN_STYLE_BORDER_LOCK;
		else if (!empty($dat['del'])) $border = FUSEN_STYLE_BORDER_DEL;
		else $border = FUSEN_STYLE_BORDER_NORMAL;
		
		$del = (empty($dat['del']))? "" : " visibility: hidden;";
		$date = ($dat['et'])? " : ".substr($dat['et'],0,2)."/".substr($dat['et'],2,2)."/".substr($dat['et'],4,2)." ".substr($dat['et'],6,2).":".substr($dat['et'],8,2) : "";
		
		// Fix?
		$fix_style = "";
		if ($dat['fix'])
		{
			$fix_style .= "overflow:hidden;";
			$fix_style .= "white-space:normal;";
			$fix_style .= "width:{$dat['w']}px;";
			$fix_style .= ($dat['fix'] == 1)? "height:{$dat['h']}px;" : "height:auto;";
		}

		
		$ret .= "<div class=\"fusen_body_trans\" style=\"left:{$dat['x']}px; top:{$dat['y']}px; color:{$dat['tc']}; background-color:{$dat['bg']}; border:{$border};{$del}{$fix_style}\">\n";
		$ret .= "<div class=\"fusen_menu\">id.{$k}: </div>\n";
		$ret .= "<div class=\"fusen_info\">".make_link($dat['name']).$date."</div>\n";
		$ret .= "<div class=\"fusen_contents\">{$dat['disp']}</div>\n";
		$ret .= "</div>\n";
	}
	return $ret;
}

//JSON����å���ե�����񤭹���
function plugin_fusen_putjson($dat,$page)
{
	$fname = P_CACHE_DIR . "fusen_". encode($page) . ".utxt";
	$json = plugin_fusen_getjson($dat);
	$to = "UTF-8";
	$json = str_replace("\0","",mb_convert_encoding($json, $to, SOURCE_ENCODING));
	
	// �ѹ������å�
	$old = @join('',@file($fname));
	if ($json == $old) return;
	
	$fp = false;
	$count = 0;
	while(!$fp && ++$count < 6)
	{
		if($fp = fopen($fname, "wb"))
		{
			flock($fp, LOCK_EX);
			fputs($fp, $json);
			fclose($fp);
		}
		else
		{
			sleep(1);
		}
	}
}

function fusen_convert_html(&$str,$page)
{
	global $X_uid,$X_admin,$pgid;
	global $vars,$post,$get;
	global $related_link,$content_id;

	// �����Х��ѿ�����
	$_page = $vars['page'];
	$_cmd = $vars['cmd'];
	$_X_uid = $X_uid;
	$_X_admin = $X_admin;
	$_content_id = $content_id;
	$_related_link = $related_link;
	$_pgid = $pgid;
	
	$X_admin = $X_uid = 0;	//��˥����Ȱ���
	$vars['page'] = $post['page'] = $get['page'] = $page; // ���ڡ���̾
	$content_id = 1;	// areaedit ��󥯤ʤ��޻�
	$related_link = 0;	// ��Ϣ����ڡ�����ꥹ�ȥ��åפ��ʤ�
	$vars['cmd'] = "read"; //�����⡼�ɤǥ���С���
	$pgid = get_pgid_by_name($vars["page"]); //�ڡ���ID
	
	$pcon = new pukiwiki_converter();
	$pcon->string = $str;
	$str = $pcon->convert();
	//$str = convert_html($str);
	
	// �����Х��ѿ��ᤷ
	$content_id = $_content_id;
	$related_link = $_related_link;
	$X_uid = $_X_uid;
	$X_admin = $_X_admin;
	$vars['page'] = $post['page'] = $get['page'] = $_page;
	$vars['cmd'] = $_cmd;
	$pgid = $_pgid;
	
	// ������󥯤ξ�� class="ext" ���ղ�
	$str = preg_replace("/(<a[^>]+?)(href=(\"|')?(?!https?:\/\/".$_SERVER["HTTP_HOST"].")http)/","$1class=\"ext\" $2",$str);
	
	return $str;
}
?>