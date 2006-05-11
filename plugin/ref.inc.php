<?php
// $Id: ref.inc.php,v 1.28 2006/05/11 08:24:52 nao-pon Exp $
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
-t:�����ȥ�
 �����Υ��åץƥ����Ȥ����

*/

// GD�ΥС�����󥻥å�(ɬ���Ķ��˹�碌��)
//if (!defined('_WIKI_GD_VERSION')) define('_WIKI_GD_VERSION',1); // Ver 1
if (!defined('_WIKI_GD_VERSION')) define('_WIKI_GD_VERSION',2); // Ver 2

// upload dir(must set end of /)
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR','./attach/');

// file icon image
if (!defined('REF_FILE_ICON')) define('REF_FILE_ICON','<img src="./image/file.gif" alt="file" width="20" height="20" />');

// default alignment
if (!defined('REF_DEFAULT_ALIGN')) define('REF_DEFAULT_ALIGN','left'); // 'left','center','right'

// force wrap on default
if (!defined('REF_WRAP_TABLE')) define('REF_WRAP_TABLE',FALSE); // TRUE,FALSE


function plugin_ref_action()
{
	global $X_admin,$vars;
	if (!$X_admin || $vars['pmode'] != "move_thumb") return(array("redirect" => XOOPS_WIKI_URL."/"));
	$counter = 0;
	if ($dir = @opendir(UPLOAD_DIR))
	{
		$page_pattern = '(?:[0-9A-F]{2})+';
		$age_pattern = '(?:\.([0-9]+))?';
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";
		
		$count = 0;
		while($file = readdir($dir))
		{
			$matches = array();
			if (!preg_match($pattern,$file,$matches))
			{
				continue;
			}
			$page = decode($matches[1]);
			$name = decode($matches[2]);
			$age = array_key_exists(3,$matches) ? $matches[3] : 0;
			
			if (preg_match("/^\d\d?%/",$name))
			{
				//echo "$name<br>";
				@unlink(UPLOAD_DIR."s/".$file);
				@rename(UPLOAD_DIR.$file,UPLOAD_DIR."s/".$file);
				@unlink(UPLOAD_DIR.$file.".log");
				$count++;
			}
		}
		closedir($dir);
	}
	return array('msg'=>"Moved thumb files.","body"=>"$count files ware moved.");

}

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
	$params = array('_args'=>array(),'nocache'=>FALSE,'_size'=>FALSE,'_w'=>0,'_h'=>0);
	
	foreach($args as $val){
		if ($val == nocache) {
			$params['nocache'] = TRUE;
			continue;
		}
		$params['_args'][] = $val;
	}

	$rets = plugin_ref_body($name,$args,$params);
	if ($rets['_error']) {
		$ret = $rets['_error'];
	} else {
		$ret = $rets['_body'];
	}
	unset($name,$args,$params,$rets);

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
	//array_walk($args, 'ref_check_arg', &$params);
	//�ʤ��� $args �Υ��С�����¿���� array_walk �Ǥ�PHP������뤳�Ȥ�����
	foreach($args as $key=>$val)
	{
		ref_check_arg($val, $key, $params);
	}

	$rets = plugin_ref_body($name,$args,$params);
	if ($rets['_error']) {
		$ret = $rets['_error'];
	} else {
		$ret = $rets['_body'];
	}

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
	unset($name,$args,$params,$rets);

	return $ret;
}

//-----------------------------------------------------------------------------
// �������ɤ���
function is_picture($text,$page) {
	global $vars;
	//����å��������å�
	if (is_url($text))
	{
		$parse = parse_url($text);
		$name = $parse['host']."_".basename($parse['path']);
		$filename = UPLOAD_DIR.encode($page)."_".encode($name);
		if (is_readable($filename))
			$text = $filename;
	}

	$size = @getimagesize($text);
	if ($size[2] > 0 && $size[2] < 4) {
		return true;
	} else {
		return false;
	}
}
// Flash���ɤ���
function plugin_ref_is_flash($text) {
	$filename = preg_replace("/.*\//","",$text);
	$filename = decode(preg_replace("/.*_/","",$text));
	return preg_match("/.*\.swf/i",$filename);
}
// div�����
function wrap_div($text, $align, $around) {
	if ($around) {
		$style = ($align == 'right') ? 'float:right' : 'float:left';
	} else {
		$style = "text-align:$align";
	}
	return "<div style=\"$style\"><div class=\"img_margin\">$text</div></div>\n";
	//return "<div style=\"$style\">$text</div>\n";
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
		//$params['_done'] = TRUE;
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
		$l_url = $url = $ext = $info = htmlspecialchars($name);
		$icon = $size = '';
		$page = $vars['page'];
		$match = array();
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
		//���Хѥ�����ե�ѥ�������
		$matches = array();
		if (preg_match('/^(.+)\/([^\/]+)$/',$name,$matches))
		{
			if ($matches[1] == '.' or $matches[1] == '..')
			{
				$matches[1] .= '/';
			}
			$page = add_bracket(get_fullname($matches[1],$page));
			$name = $matches[2];
		}
		/*
		if (!is_page($page)) { 
			$rets['_error'] = 'page not found.';
			return $rets;
		}
		*/
		$ext = $name;
		$file = UPLOAD_DIR.encode($page).'_'.encode($name);
		if (!is_file($file)) {
			if (!is_page($page))
			{ 
				$rets['_error'] = 'page not found.';
				return $rets;
			}
			else
			{
				$rets['_error'] = 'not found.';
				return $rets;
			}
		}
		$l_url = $script.'?plugin=attach&amp;openfile='.rawurlencode($name).'&amp;refer='.rawurlencode($page);
		$fsize = sprintf('%01.1f',round(filesize($file)/1000,1)).'KB';

		$is_picture = is_picture($file,$page);
		$is_flash = ($is_picture)? false : plugin_ref_is_flash($file);

		if ($is_picture) {
			$url = $file;
			$size = getimagesize($file);
			$org_w = $size[0];
			$org_h = $size[1];
		} else {
			$lastmod = date('Y/m/d H:i:s',filemtime($file));
			$info = "$lastmod $fsize";
		}
	}

	//�����ȥ�����
	if (!isset($title) or $title == '') { $title = $ext; }
	$title = htmlspecialchars($title);

	// �ե��������Ƚ��
	if (!isset($is_picture)) $is_picture = is_picture($url,$page);
	if ($is_picture) { // ����
		$info = "";
		$width=$height=0;
		$class = " class=\"img_margin\"";

		//URL�ξ�祭��å���Ƚ��
		if (is_url($url)){
			$parse = parse_url($url);
			$name = $parse['host']."_".basename($parse['path']);
			$filename = encode($page)."_".encode($name);
			if (!$params['nocache']){
				//����å��夹��
				$size = plugin_ref_cache_image_fetch($filename, $url, $name);
				$l_url = $script.'?plugin=attach&amp;openfile='.rawurlencode($name).'&amp;refer='.rawurlencode($page);
				$fsize = sprintf('%01.1f',round(filesize($url)/1000,1)).'KB';
			} else {
				//����å��夷�ʤ�
				$size = @getimagesize($url);
				$l_url = $url;
				$fsize = '?KB';
			}
			$org_w = (!empty($size[0]))? $size[0] : 0;
			$org_h = (!empty($size[1]))? $size[1] : 0;
		}
		foreach ($params['_args'] as $arg){
			$m = array();
			if (preg_match("/^(m)?w:([0-9]+)$/i",$arg,$m)){
				$params['_size'] = TRUE;
				$params['_w'] = $m[2];
				$max_flg = $m[1];
			}
			if (preg_match("/^(m)?h:([0-9]+)$/i",$arg,$m)){
				$params['_size'] = TRUE;
				$params['_h'] = $m[2];
				$max_flg = $m[1];
			}
			if (preg_match("/^([0-9.]+)%$/i",$arg,$m)){
				$params['_%'] = $m[1];
			}
			if (preg_match("/^t:(.*)$/i",$arg,$m)){
				$m[1] = htmlspecialchars(str_replace("&amp;quot;","",$m[1]));
				if ($m[1]) $title = $m[1]."&#13;&#10;".$title;
			}
		}
		// ���ꤵ�줿����������Ѥ���
		if ($params['_size']) {
			if ($params['_w'] > 0 && $params['_h'] > 0 && !$max_flg){
				$width = $params['_w'];
				$height = $params['_h'];
			} else {
				$_w = $params['_w'] ? $org_w / $params['_w'] : 0;
				$_h = $params['_h'] ? $org_h / $params['_h'] : 0;
				$zoom = max($_w,$_h);
				if ($zoom) {
					if (!$max_flg || ($zoom >= 1 && $max_flg)){
						$width = floor($org_w / $zoom);
						$height = floor($org_h / $zoom);
					}
				}
			}
		}
		if ($params['_%']) {
			$width = floor($org_w * $params['_%'] / 100);
			$height = floor($org_h * $params['_%'] / 100);
		}
		if ($org_w && $width && $org_h && $height){
			$zoom = floor(max($width/$org_w,$height/$org_h)*100);
		}
		$title .= "&#13;&#10;SIZE:{$org_w}x{$org_h}($fsize)";
		//EXIF DATA
		$exif_data = get_exif_data($file);
		if ($exif_data){
			$title .= "&#13;&#10;".$exif_data['title'];
			foreach($exif_data as $key => $value){
				if ($key != "title") $title .= "&#13;&#10;$key: $value";
			}
		}
		// &amp;���Ѵ�
		$title = str_replace("&amp;","&",$title);
		//IE�ʳ��ϲ���ʸ���򥹥ڡ������Ѵ�
		if ( !strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) $title = str_replace("&#13;&#10;"," ",$title);
		
		if (!$params['nocache'] && $width && $height) {
			$s_file = UPLOAD_DIR."s/".encode($page).'_'.encode($zoom."%".$name);
			if (!file_exists($s_file) && ($zoom < 90) && (!$params['nocache'])) {
				//����ͥ������
				$url = plugin_ref_make_thumb($url,$s_file,$width,$height,$org_w,$org_h);
			} else {
				if (file_exists($s_file)) {
					//����ͥ��뤬����Х���ͥ���򻲾�
					$url = $s_file;
				}
			}
			$info = "width=\"$width\" height=\"$height\" ";
			$ret .= "<a href=\"$l_url\" title=\"$title\"><img src=\"".XOOPS_WIKI_URL."/$url\" alt=\"$title\" title=\"$title\" $info /></a>";
		} else {
			if ($org_w and $org_h) $info = "width=\"$org_w\" height=\"$org_h\" ";
			if (!$params['nocache'])
			{
				$url = preg_replace("#^\./#","",$url);
				$ret .= "<img src=\"".XOOPS_WIKI_URL."/$url\" alt=\"$title\" title=\"$title\" $info />";
			}
			else
				$ret .= "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info />";
		}
	} else if ($is_flash) { //	Flash�ե�����
		//�����
		$params['_qp']  =
		$params['_q']   =
		$params['_pp']  =
		$params['_p']   =
		$params['_lp']  =
		$params['_l']   =
		$params['_w']   =
		$params['_h']   =
		$params['_a']   =
		$params['_bp']  =
		$params['_b']   =
		$params['_scp'] =
		$params['_sc']  =
		$params['_sap'] =
		$params['_sa']  =
		$params['_mp']  =
		$params['_m']   =
		$params['_wmp'] = "";
		
		foreach ($params['_args'] as $arg){
			$m = array();
			if (preg_match("/^q(?:uality)?:((auto)?(high|low|best|medium))$/i",$arg,$m)){
				$params['_qp'] = "<param name=\"quality\" value=\"{$m[1]}\">";
				$params['_q']  = " quality=\"{$m[1]}\"";
			}
			if (preg_match("/^p(?:lay)?:(true|false)$/i",$arg,$m)){
				$params['_pp'] = "<param name=\"play\" value=\"{$m[1]}\">";
				$params['_p']  = " play=\"{$m[1]}\"";
			}
			if (preg_match("/^l(?:oop)?:(true|false)$/i",$arg,$m)){
				$params['_lp'] = "<param name=\"loop\" value=\"{$m[1]}\">";
				$params['_l']  = " loop=\"{$m[1]}\"";
			}
			if (preg_match("/^w(?:idth)?:([0-9]+)$/i",$arg,$m)){
				$params['_w'] = " width=".$m[1];
			}
			if (preg_match("/^h(?:eight)?:([0-9]+)$/i",$arg,$m)){
				$params['_h'] = " height=".$m[1];
			}
			if (preg_match("/^a(?:lign)?:(l|r|t|b)$/i",$arg,$m)){
				$params['_a'] = " align=\"{$m[1]}\"";
			}
			if (preg_match("/^b(?:gcolor)?:#?([abcdef\d]{6,6})$/i",$arg,$m)){
				$params['_bp'] = "<param name=\"bgcolor\" value=\"{$m[1]}\">";
				$params['_b']  = " bgcolor=\"#{$m[1]}\"";
			}
			if (preg_match("/^sc(?:ale)?:(showall|noborder|exactfit|noscale)$/i",$arg,$m)){
				$params['_scp'] = "<param name=\"scale\" value=\"{$m[1]}\">";
				$params['_sc']  = " scale=\"{$m[1]}\"";
			}
			if (preg_match("/^sa(?:lign)?:(l|r|t|b|tl|tr|bl|br)$/i",$arg,$m)){
				$params['_sap'] = "<param name=\"salign\" value=\"{$m[1]}\">";
				$params['_sa']  = " salign=\"{$m[1]}\"";
			}
			if (preg_match("/^m(?:enu)?:(true|false)$/i",$arg,$m)){
				$params['_mp'] = "<param name=\"menu\" value=\"{$m[1]}\">";
				$params['_m']  = " menu=\"{$m[1]}\"";
			}
			if (preg_match("/^wm(?:ode)?:(window|opaque|transparent)$/i",$arg,$m)){
				$params['_wmp'] = "<param name=\"wmode\" value=\"{$m[1]}\">";
			}
		}
		$f_file = XOOPS_WIKI_URL.substr($file,1);
		$ret .= <<<_HTML_
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,79,0"{$params['_w']}{$params['_h']}{$params['_a']}>
<param name="movie" value="{$f_file}">
{$params['_qp']}{$params['_lp']}{$params['_pp']}{$params['_scp']}{$params['_sap']}{$params['_mp']}{$params['_wmp']}
<embed src="{$f_file}" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/jp/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"{$params['_w']}{$params['_h']}{$params['_a']}{$params['_p']}{$params['_l']}{$params['_q']}{$params['_b']}{$params['_sc']}{$params['_sa']}{$params['_m']}>
</embed>
</object>
_HTML_;

	} else { // �̾�ե�����
		foreach ($params['_args'] as $arg){
			$m = array();
			if (preg_match("/^t:(.*)$/i",$arg,$m)){
				$m[1] = htmlspecialchars(str_replace("&amp;quot;","",$m[1]));
				if ($m[1]) $info = $m[1]."&#13;&#10;".$info;
			}
		}
		// &amp;���Ѵ�
		$info = str_replace("&amp;","&",$info);
		//IE�ʳ��ϲ���ʸ���򥹥ڡ������Ѵ�
		if ( !strstr($_SERVER["HTTP_USER_AGENT"], "MSIE")) $info = str_replace("&#13;&#10;"," ",$info);

		$ret .= "<a href=\"$l_url\" title=\"$info\">$icon$title</a>";
	}
	$rets[_body] = $ret;
	return $rets;
}

// ��������å��夬���뤫Ĵ�٤�
function plugin_ref_cache_image_fetch($filename, &$url, $name)
{
	$filename = UPLOAD_DIR.$filename;
	if (!is_readable($filename))
	{
		$dat = http_request($url);
		if ($dat['rc'] == 200 && $dat['data'])
		{
			plugin_ref_cache_image_save($dat['data'], $filename, $name);
			$url = $filename;
		}
	}
	else
	{
		$url = $filename;
	}
	$size = @getimagesize($url);
	return $size;
}
// ��������å������¸
function plugin_ref_cache_image_save($data, $filename, $name)
{
	global $vars;
	$fp = fopen($filename.".tmp", "wb");
	fwrite($fp, $data);
	fclose($fp);
	
	if (!exist_plugin('attach') or !function_exists('attach_upload'))
	{
		exit ('attach.inc.php not found or not correct version.');
	}
	
	$GLOBALS['pukiwiki_allow_extensions'] = "";
	do_upload($vars['page'],$name,$filename.".tmp",TRUE,NULL,TRUE);
	
	return $filename;
}
// ����ͥ�����������
function plugin_ref_make_thumb($url,$s_file,$width,$height,$org_w,$org_h)
{
	return HypCommonFunc::make_thumb($url,$s_file,$width,$height);
}
?>