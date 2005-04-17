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
// $Id: fusen.inc.php,v 1.1 2005/04/17 12:56:47 nao-pon Exp $
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


function plugin_fusen_convert() {
	global $script,$vars;
	global $now_inculde_convert;
	global $X_uid,$X_admin;
	
	static $loaded = false;
	
	//�ɤ߹��ߥ����å�
	if ($now_inculde_convert)
	{
		return "<p>".make_pagelink($vars['page'],strip_bracket($vars['page'])."����䵤�ɽ��")."</p>";
	}
	if ($loaded)
	{
		return "<p>#fusen �ϰ�Ĥ����֤��ޤ���</p>";
	}
	
	// �����
	$loaded = true;
	$refer = $vars['page'];
	$jsfile = FUSEN_SCRIPT_FILE;
	$border_normal = FUSEN_STYLE_BORDER_NORMAL;
	$border_lock = FUSEN_STYLE_BORDER_LOCK;
	$border_del = FUSEN_STYLE_BORDER_DEL;
	$fusen_data = plugin_fusen_data($refer);
	$name = WIKI_NAME_DEF;
	$jname = plugin_fusen_jsencode($name);
	
	// �ѥ�᡼��
	$refresh = 0;
	$height = 0;
	foreach(func_get_args() as $prm)
	{
		if (preg_match("/^r(efresh)?:([\d]+)/",$prm,$arg))
			$refresh =($arg[2])? max($arg[2],10) * 1000 : 0;
		if (preg_match("/^h(eight)?:([\d]+)/",$prm,$arg))
			$height = min($arg[2],1000);
	}
	
	$wiki_helper = fontset_js_tag();
	

	$refresh_str = '��ư����:<select name="fusen_menu_interval" id="fusen_menu_interval" size="1" onchange="fusen_setInterval(this.value);window.focus();">';
	$refresh_str .= '<option value="0">�ʤ�';
	foreach(array(10,20,30,60) as $sec)
	{
		$selected = (is_null($selected) && $sec <= $refresh)? ' selected="true"' : '';
		$msec = $sec * 1000;
		$refresh_str .= '<option value="'.$msec.'"'.$selected.'>'.$sec.'��';
	}
	$refresh_str .= '</select>';
	
	$html = plugin_fusen_gethtml($fusen_data);
	
	$fusen_url = str_replace(XOOPS_WIKI_PATH,'_XOOPS_WIKI_HOST_'.XOOPS_WIKI_URL,P_CACHE_DIR . "fusen_" .encode($vars['page']) . ".utxt");
	
	$X_ucd = WIKI_UCD_DEF;
	
	return <<<EOD
<script type="text/javascript" src="$jsfile" charset="euc-jp"></script>
<div id="fusen_top_menu" class="fusen_top_menu" style="visibility: hidden;">
<form action="" onsubmit="return false;">
  <p>
    ��䵵�ǽ
    [<a href="JavaScript:fusen_new()" title="��������䵤���">����</a>]
    [<a href="JavaScript:fusen_dustbox()" title="����Ȣ��ɽ��/��ɽ��">����Ȣ</a>]
    [<a href="JavaScript:fusen_transparent()" title="��䵤��Ʃ����/��Ʃ����">Ʃ��</a>]
    [<a href="JavaScript:fusen_init(1)" title="�ǿ��ξ��֤˹���">����</a>]
    [<a href="JavaScript:fusen_show('fusen_help')" title="�Ȥ���">�إ��</a>]&nbsp;
    ������<input type="text" onkeyup="JavaScript:fusen_grep(this.value)" />
    <small>{$refresh_str}</small>
  </p>
</form>
</div>
<noscript>
<p><strong>JavaScript̤ư��</strong>: ��䵤��Խ��Ǥ��ޤ��󡣤ޤ�����䵤�ɽ�����֤�����Ƥ����礬����ޤ���</p>
</noscript>

<div id="fusen_help" style="position: absolute; font-size: 11px; left: 90px; top: 80px; padding: 4px; background-color: white; border: black 2px solid; visibility: hidden; z-index: 4; filter:alpha(opacity=90); -moz-opacity:0.9;">
  [<a href="javascript:fusen_hide('fusen_help')">��</a>]
  <ul>
    <li>���֥륯��å��ǿ�������䵤�����Ǥ��ޤ���</li>
    <li>�񤭹���ȡ���䵤�ɽ������ޤ���</li>
    <li>��䵤ϥɥ�å����ư��֤��ư�Ǥ��ޤ���</li>
    <li>��䵤���֥륯��å�����ȡ�������䵤��Խ��Ǥ��ޤ���</li>
    <li>"lock"�򲡤��ȡ��Խ�����ư��ػߤ��ޤ���lock������䵤�"unlock"�Ǹ����᤻�ޤ���</li>
    <li>"del"�򲡤��ȡ���䵤򥴥�Ȣ�ذ�ư���ޤ�������Ȣ����䵤�"recover"�Ǹ����᤻�ޤ���<br />
        ����Ȣ����䵤�"del"�򲡤��ȡ���䵤����˺�����ޤ���</li>
  </ul>
  <dl>
    <dt>[����]</dt>
    <dd>��������䵤��Խ����̤�ɽ�����ޤ���</dd>
    <dt>[����Ȣ]</dt>
    <dd>����Ȣ�������줿��䵤�ɽ�����ޤ���</dd>
    <dt>[�إ��]</dt>
    <dd>���������񤭤�ɽ�����ޤ���</dd>
    <dt>����</dt>
    <dd>���Ϥ���������ɤ������䵤Τ�ɽ�����ޤ���</dd>
  </dl>
</div>
<div id="fusen_area" style="height:{$height}px; width:100%;">
$html
</div>
<script type="text/javascript">
var fusenBorderObj = {"normal":"{$border_normal}", "lock":"{$border_lock}", "del":"{$border_del}"};
var fusenJsonUrl = "{$fusen_url}";
var fusenInterval = {$refresh};
var fusenX_admin = {$X_admin};
var fusenX_uid = {$X_uid};
var fusenX_ucd = "{$X_ucd}";
</script>
<div id="fusen_editbox" class="fusen_editbox">
  [<a href="javascript:fusen_editbox_hide()" title="�Ĥ���">��</a>] (�ɥ�å����ư�ư�Ǥ��ޤ�)
  <form id="edit_frm" method="post" action="" style="padding:0; margin:0" onsubmit="fusen_save(); return false;">
    <p style="margin:0">
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
      $wiki_helper<br />
      <textarea name="body" id="edit_body" cols="50" rows="10" style="width:auto;"></textarea><br />
      <input type="submit" value="�񤭹���" />
      <input type="hidden" name="id" id="edit_id"/>
      <input type="hidden" name="z" id="edit_z" value="1" />
      <input type="hidden" name="l" id="edit_l" />
      <input type="hidden" name="t" id="edit_t" />
      <input type="hidden" name="w" id="edit_w" />
      <input type="hidden" name="h" id="edit_h" />
      <input type="hidden" name="fix" id="edit_fix" value="0"/>
      <input type="hidden" name="bx" id="edit_bx" />
      <input type="hidden" name="by" id="edit_by" />
      <input type="hidden" name="pass" id="edit_pass" value="" />
      <input type="hidden" name="mode" id="edit_mode" value="edit" />
      <input type="hidden" name="plugin" value="fusen" />
      <input type="hidden" name="refer" value="{$refer}" />
      <input type="hidden" name="page" value="{$refer}" />
    </p>
  </form>
</div>

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
	if (!in_array($post['mode'],array('set','del','lock','unlock','recover','edit')))
	{
		ob_clean();
		exit;
	}
	
	$id = preg_replace('/id/', '', $post['id']);
	
	$auth = false;
	
	if ($id && array_key_exists($id,$dat))
	{
		if ($X_admin) $auth = true;
		else if ($dat[$id]['uid'] && $dat[$id]['uid'] == $X_uid) $auth = true;
		else if ($dat[$id]['ucd'] && $dat[$id]['ucd'] == PUKIWIKI_UCD) $auth = true;
	}
	else
	{
		$auth = true;
	}
	
	// ID����,�ǡ�������
	switch ($post['mode']) {
		case 'set':
		case 'del':
		case 'lock':
		case 'unlock':
		case 'recover';
			if (!array_key_exists($id,$dat)) die_message('The data is not accumulated just.'."($id)");
			// �ڡ���HTML����å������
			delete_page_html($refer,"html");
			//�͹���
			switch ($post['mode']) {
				case 'set':
					if (!$dat[$id]['lk']) $auth = true;
					$dat[$id]['x'] = (preg_match('/^\d+$/', $post['l']) ? $post['l'] : '');
					$dat[$id]['y'] = (preg_match('/^\d+$/', $post['t']) ? $post['t'] : '');
					$dat[$id]['bx'] = (preg_match('/^\d+$/', $post['l']) ? $post['bx'] : 0);
					$dat[$id]['by'] = (preg_match('/^\d+$/', $post['t']) ? $post['by'] : 0);
					$dat[$id]['w'] = (preg_match('/^\d+$/', $post['w']) ? $post['w'] : 0);
					$dat[$id]['h'] = (preg_match('/^\d+$/', $post['h']) ? $post['h'] : 0);
					$dat[$id]['fix'] = (!empty($post['fix'])) ? 1 : 0;
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
						$dat[$id]['ln'] = '';
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
				$name = $post['name'];
				// ̾���򥯥å�������¸
				setcookie("pukiwiki_un", $name, time()+86400*365);//1ǯ��
			}
			else
			{
				if (!$dat[$id]['lk']) $auth = true;
				if (!array_key_exists($id,$dat)) die_message('The data is not accumulated just.'."($id)");
				$mt = $dat[$id]['mt'];
				$uid = $dat[$id]['uid'];
				$ucd = $dat[$id]['ucd'];
				$name = $dat[$id]['name'];
			}
			
			$txt = str_replace("\r","",$post['body']);
			$txt = preg_replace('/^#fusen/m', '&#35;fusen', $txt);
			$txt = user_rules_str(auto_br($txt));
			$txt = rtrim($txt)."\n";
			
			$et = date("ymdHis");
			$fix = (!empty($post['fix']))? 1 : 0;
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
				'txt' => rtrim($txt),
				'name' => $name,
				'mt' => $mt,
				'et' => $et,
				'uid' => $uid,
				'ucd' => $ucd,
				'fix' => $fix,
				'w' => $w,
				'h' => $h,
			);
			
			ksort($dat);
			
			// plane_text DB ������ؼ�
			need_update_plaindb($refer);
			// �ڡ���HTML����å������
			delete_page_html($refer,"html");
			
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
	global $X_uid,$X_admin;
	global $vars,$post,$get;
	global $nowikiname,$related_link,$content_id;
	
	$fname = encode($page) . '_' . encode(FUSEN_ATTACH_FILENAME);
	if (!file_exists(UPLOAD_DIR . $fname)) return array();
	$data = file(UPLOAD_DIR . $fname);
	if (!$data || trim(array_shift($data)) != FUSEN_ATTACH_FILENAME) return array();
	
	$data = unserialize(join('',$data));
	
	if (!$convert) return $data;
	
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
	
	return $data;
}

//PHP���֥������Ȥ�JSON���Ѵ�
function plugin_fusen_getjson($fusen_data) {

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


		// JSON�ι���
		if ($json != '{') $json .= ",\n  ";
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
		$json .= '"uid":' . ($dat['uid']? $dat['uid'] : 0 ) . ',';
		$json .= '"ucd":"' . ($dat['ucd']? $dat['ucd'] : "" ) . '",';
		$json .= '"fix":' . (empty($dat['fix'])? 0 : 1 ) . ',';
		$json .= '"w":' . ($dat['w']? $dat['w'] : 0 ) . ',';
		$json .= '"h":' . ($dat['h']? $dat['h'] : 0 ) . '';
		if (isset($dat['ln']) && $dat['ln']) $json .= ',"ln":' . preg_replace('/^id/', '', $dat['ln']) ;
		if (isset($dat['lk']) && $dat['lk']) $json .= ',"lk":' . $dat['lk'];
		if (isset($dat['del']) && $dat['del']) $json .= ',"del":' . $dat['del'] ;
		$json .= '}';
	}
	$json .= '}';
	return $json;
}

//JSON�������󥳡���
function plugin_fusen_jsencode($str) {
	$str = preg_replace('/(\x22|\x2F|\x5C)/', '\\\$1', $str);
	$str = preg_replace('/\x08/', '\b', $str);
	$str = preg_replace('/\x09/', '\t', $str);
	$str = preg_replace('/\x0A/', '\n', $str);
	$str = preg_replace('/\x0C/', '\f', $str);
	$str = preg_replace('/\x0D/', '\r', $str);
	return $str;
}

//PHP���֥������Ȥ�HTML���Ѵ�
function plugin_fusen_gethtml($fusen_data)
{
	global $vars;
	//JSON�ե�����񤭹���
	plugin_fusen_putjson($fusen_data,$vars['page']);
	
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
			$fix_style .= "height:{$dat['h']}px;";
		}

		
		$ret .= "<div class=\"fusen_body\" style=\"left:{$dat['x']}px; top:{$dat['y']}px; color:{$dat['tc']}; background-color:{$dat['bg']}; border:{$border};{$del}{$fix_style}\">\n";
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
	$json = mb_convert_encoding($json, $to, SOURCE_ENCODING);
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
	global $X_uid,$X_admin;
	global $vars,$post,$get;
	global $nowikiname,$related_link,$content_id;

	// �����Х��ѿ�����
	$_page = $vars['page'];
	$_X_uid = $X_uid;
	$_X_admin = $X_admin;
	$_content_id = $content_id;
	$_related_link = $related_link;
	$_nowikiname = $nowikiname;
	
	$X_admin = $X_uid = 0;	//��˥����Ȱ���
	$vars['page'] = $post['page'] = $get['page'] = $page; // ���ڡ���̾
	$content_id = 1;	// areaedit ��󥯤ʤ��޻�
	$nowikiname = 1;	// ̤����WikiName��?����޻�
	$related_link = 0;	// ��Ϣ����ڡ�����ꥹ�ȥ��åפ��ʤ�
	
	$pcon = new pukiwiki_converter();
	$pcon->string = $str;
	$str = $pcon->convert();
	//$str = convert_html($str);
	
	// �����Х��ѿ��ᤷ
	$content_id = $_content_id;
	$related_link = $_related_link;
	$nowikiname = $_nowikiname;
	$X_uid = $_X_uid;
	$X_admin = $_X_admin;
	$vars['page'] = $post['page'] = $get['page'] = $_page;
	
	// ������󥯤ξ�� class="ext" ���ղ�
	$str = preg_replace("/(<a[^>]+?)(href=(\"|')?(?!https?:\/\/".$_SERVER["HTTP_HOST"].")http)/","$1class=\"ext\" $2",$str);
	
	return $str;
}
?>