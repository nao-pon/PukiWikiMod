<?php
// $Id: ref.inc.php,v 1.6 2003/07/16 13:51:45 nao-pon Exp $
/*
Last-Update:2002-10-29 rev.33

*�ץ饰���� ref
�ڡ�����ź�դ��줿�ե������Ÿ������

*Usage
 #ref(filename[,Page][[,{Left|Center|Right}]|[,{Wrap|Nowrap}]|[,Around]]{}[,comments])

*�ѥ�᡼��
-filename~
 ź�եե�����̾�����뤤��URL
-Page~
 WikiName�ޤ���BracketName����ꤹ��ȡ����Υڡ�����ź�եե�����򻲾Ȥ���
-Left|Center|Right~
 ���ΰ��ֹ�碌
-Wrap|Nowrap~
 �ơ��֥륿���ǰϤ�/�Ϥޤʤ�
-Around~
 �ƥ����Ȥβ�����
-nocache~
 URL�����ե�����(�����ե�����)�򥭥�å��夷�ʤ�
-w:�ԥ������
-h:�ԥ������
-����%
 �����ե�����Υ��������ꡣ
 w: h: �ɤ��餫�λ���ǽĲ�����Ψ���ݤäƥꥵ������
 %����ǡ�����Υѡ�����Ȥ�ɽ����

*/

// upload dir(must set end of /)
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR','./attach/');

// file icon image
if (!defined('REF_FILE_ICON')) define('REF_FILE_ICON','<img src="./image/file.gif" alt="file" width="20" height="20" />');

// default alignment
if (!defined('REF_DEFAULT_ALIGN')) define('REF_DEFAULT_ALIGN','left'); // 'left','center','right'

// force wrap on default
if (!defined('REF_WRAP_TABLE')) define('REF_WRAP_TABLE',FALSE); // TRUE,FALSE

function plugin_ref_inline() {

	global $script,$vars;
	global $WikiName, $BracketName;

	//�����
	$ret = '';

	//���顼�����å�
	if (!func_num_args()) return 'no argument(s).';

	//ź�եե�����̾�����
	$args = func_get_args();
	$name = array_shift($args);

	//�ѥ�᡼���Ѵ�
	$params = array('left'=>FALSE,'center'=>FALSE,'right'=>FALSE,'wrap'=>FALSE,'nowrap'=>FALSE,'around'=>FALSE,'_args'=>array(),'_done'=>FALSE,'nocache'=>FALSE,'_size'=>FALSE,'_w'=>0,'_h'=>0);
	array_walk($args, 'ref_check_arg', &$params);

	$ret = plugin_ref_body($name,$args,$params);
	
	return $ret;
}

function plugin_ref_convert() {

	global $script,$vars;
	global $WikiName, $BracketName;

	//�����
	$ret = '';

	//���顼�����å�
	if (!func_num_args()) return 'no argument(s).';

	//ź�եե�����̾�����
	$args = func_get_args();
	$name = array_shift($args);

	//�ѥ�᡼���Ѵ�
	$params = array('left'=>FALSE,'center'=>FALSE,'right'=>FALSE,'wrap'=>FALSE,'nowrap'=>FALSE,'around'=>FALSE,'_args'=>array(),'_done'=>FALSE,'nocache'=>FALSE,'_size'=>FALSE,'_w'=>0,'_h'=>0);
	array_walk($args, 'ref_check_arg', &$params);
	//if (count($params['_args']) > 0) { $title = join(',', $params['_args']); }

	$ret = plugin_ref_body($name,$args,$params);

	//���饤�����Ƚ��
	if ($params['right'])
		$align = 'right';
	else if ($params['left'])
		$align = 'left';
	else if ($params['center'])
		$align = 'center';
	else
		$align = REF_DEFAULT_ALIGN;

	if ((REF_WRAP_TABLE and !$params['nowrap']) or $params['wrap']) {
		$ret = wrap_table($ret, $align, $params['around']);
	}
	$ret = wrap_div($ret, $align, $params['around']);

	return $ret;
}

//-----------------------------------------------------------------------------
// �������ɤ���
function is_picture($text) {
	return preg_match('/(\.gif|\.png|\.jpeg|\.jpg)$/i', $text);
}
// div�����
function wrap_div($text, $align, $around) {
	if ($around) {
		$style = ($align == 'right') ? 'float:right' : 'float:left';
	} else {
		$style = "text-align:$align";
	}
	return "<div class=\"img_margin\" style=\"$style\">$text</div>\n";
}
// �Ȥ����
// margin:auto Moz1=x(wrap,around�������ʤ�),op6=oNN6=x(wrap,around�������ʤ�)IE6=x(wrap,around�������ʤ�)
// margin:0px Moz1=x(wrap�Ǵ󤻤������ʤ�),op6=x(wrap�Ǵ󤻤������ʤ�),nn6=x(wrap�Ǵ󤻤������ʤ�),IE6=o
function wrap_table($text, $align, $around) {
	$margin = ($around ? '0px' : 'auto');
	$margin_align = ($align == 'center') ? '' : ";margin-$align:0px";
	return "<table class=\"style_table\" style=\"margin:$margin$margin_align\">\n<tr><td class=\"style_td\">\n$text\n</td></tr>\n</table>\n";
}
//���ץ�������Ϥ���
function ref_check_arg($val, $_key, &$params) {
	if ($val == '') { $params['_done'] = TRUE; return; }
	if (!$params['_done']) {
		foreach (array_keys($params) as $key) {
			if (strpos($key, strtolower($val)) === 0) {
				$params[$key] = TRUE;
				return;
			}
		}
		$params['_done'] = TRUE;
	}
	$params['_args'][] = $val;
}

// BodyMake
function plugin_ref_body($name,$args,$params){
	
// $name���Ȥ˰ʲ����ѿ�������
// $url : URL
// $title :�����ȥ�
// $ext : ��ĥ��Ƚ����ʸ����
// $icon : ���������img����
// $size : �����ե�����ΤȤ�������
// $info : �����ե�����ʳ��Υե�����ξ���
//  ź�եե�����ΤȤ� : �ե�����κǽ��������ȥ�����
//  URL�ΤȤ� : URL���Τ��

	global $script,$vars;
	global $WikiName, $BracketName;

	if (is_url($name)) { //URL
		$url = $ext = $info = htmlspecialchars($name);
		$icon = $size = '';
		if (preg_match('/([^\/]+)$/', $name, $match)) { $ext = $match[1]; }
	} else { //ź�եե�����
		$icon = REF_FILE_ICON;
		if (!is_dir(UPLOAD_DIR)) return 'no UPLOAD_DIR.';
		//�ڡ�������Υ����å�
		$page = $vars['page'];
		if (count($args) > 0) {
			$_page = get_fullname($args[0],$vars['page']);
			if (is_page($_page)) {
				$page = $_page;
				array_shift($args);
			}
		}
		if (!is_page($page)) { return 'page not found.'; }

		$ext = $name;
		$file = UPLOAD_DIR.encode($page).'_'.encode($name);
		if (!is_file($file)) { return 'not found.'; }

		if (is_picture($ext)) {
			$url = $file;
			$size = getimagesize($file);
			$org_w = $width = $size[0];
			$org_h = $height = $size[1];
		} else {
			$url = $script.'?plugin=attach&amp;openfile='.rawurlencode($name).'&amp;refer='.rawurlencode($page);
			$lastmod = date('Y/m/d H:i:s',filemtime($file));
			$size = sprintf('%01.1f',round(filesize($file)/1000,1)).'KB';
			$info = "$lastmod $size";
		}
	}

	//�����ȥ�����
	if (!isset($title) or $title == '') { $title = $ext; }
	$title = htmlspecialchars($title);

	// �ե��������Ƚ��
	if (is_picture($ext)) { // ����
		$info = "";
		$width=$height=0;
		//URL�ξ�祭��å���Ƚ��
		if ((is_url($url)) && (!$params['nocache'])){
			$parse = parse_url($url);
			$tmpname = $parse['host']."_".basename($parse['path']);
			$filename = $dir.encode($vars['page'])."_".encode($tmpname);
			$img_arg = plugin_ref_cache_image_fetch($filename, $url, UPLOAD_DIR);
			$url = $img_arg[0];
			$size = $img_arg[1];
			$org_w = $size[0];
			$org_h = $size[1];
		}
		foreach ($params['_args'] as $arg){
			if (preg_match("/^w:([0-9]+)$/i",$arg,$m)){
				$params['_size'] = TRUE;
				$params['_w'] = $m[1];
			}
			if (preg_match("/^h:([0-9]+)$/i",$arg,$m)){
				$params['_size'] = TRUE;
				$params['_h'] = $m[1];
			}
			if (preg_match("/^([0-9.]+)%$/i",$arg,$m)){
				$params['_%'] = $m[1];
			}
		}
		// ���ꤵ�줿����������Ѥ���
		if ($params['_size']) {
			if ($params['_w'] > 0 && $params['_h'] > 0){
				$width = $params['_w'];
				$height = $params['_h'];
			} else {
				$_w = $params['_w'] ? $org_w / $params['_w'] : 0;
				$_h = $params['_h'] ? $org_h / $params['_h'] : 0;
				$zoom = max($_w,$_h);
				if ($zoom) {
					$width = (int)($org_w / $zoom);
					$height = (int)($org_h / $zoom);
				}
			}
		}
		if ($params['_%']) {
			$width = (int)($org_w * $params['_%'] / 100);
			$height = (int)($org_h * $params['_%'] / 100);
		}
		if ($width and $height) {
			$info = "width=\"$width\" height=\"$height\" ";
			$title .= " SIZE:{$org_w}x{$org_h}";
			$ret .= "<a href=\"$url\" title=\"$title\"><img src=\"$url\" alt=\"$title\" title=\"$title\" $info/></a>\n";
		} else {
			if ($org_w and $org_h) $info = "width=\"$org_w\" height=\"$org_h\" ";
			$ret .= "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info/>";
		}
	} else { // �̾�ե�����
		$ret .= "<a href=\"$url\" title=\"$info\">$icon$title</a>\n";
	}
	return $ret;
}

// ��������å��夬���뤫Ĵ�٤�
function plugin_ref_cache_image_fetch($filename, $target, $dir) {
	global $vars;
	
  $filename = $dir.$filename;

	if (!is_readable($filename)) {
		$file = fopen($target, "rb"); // ���֤� size ������ꤳ���餬����Ū��������®��
		if (! $file) {
			fclose($file);
			$url = NOIMAGE;
		} else {
			$data = fread($file, 1000000); 
			fclose ($file);
			$size = @getimagesize($target); // ���ä��顢size ��������̾��1���֤뤬ǰ�Τ���0�ξ���(reimy)
			if ($size[0] <= 1)
				$url = NOIMAGE;
			else
				$url = $filename;
		}
		plugin_ref_cache_image_save($data, $filename);
	}
	$size = @getimagesize($filename);
	
	return array($filename,$size);
}
// ��������å������¸
function plugin_ref_cache_image_save($data, $filename) {
	$fp = fopen($filename, "wb");
	fwrite($fp, $data);
	fclose($fp);
	return $filename;
}
?>
