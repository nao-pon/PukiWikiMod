<?php
//�ƥ�ݥ��ǥ��쥯�ȥ�
define('WIKI_PAINTER_TEMP_DIR', './plugin_data/painter/tmp/');
//�ץ饰����Ǥκ���������
define('WIKI_PAINTER_MAX_WIDTH_PLUGIN',320);
define('WIKI_PAINTER_MAX_HEIGHT_PLUGIN',320);
//�ڡ���ź�դǤκ���������
define('WIKI_PAINTER_MAX_WIDTH_UPLOAD',1024);
define('WIKI_PAINTER_MAX_HEIGHT_UPLOAD',1024);
//�ǥե���Ȥ�������
define('WIKI_PAINTER_DEF_WIDTH',320);
define('WIKI_PAINTER_DEF_HEIGHT',320);

function plugin_painter_action()
{
	global $vars;
	$pmode = (isset($vars['pmode']))? $vars['pmode'] : "";
	switch($pmode)
	{
		case "paint":
			return plugin_painter_paint();
			break;
		case "upload":
			return plugin_painter_save(true);
			break;
		case "save":
			return plugin_painter_save();
			break;
		case "write":
			return plugin_painter_write();
			break;
		default:
	}
}

function plugin_painter_convert()
{
	global $script,$vars;
	static $load = array();
	
	$pgid = get_pgid_by_name($vars['page']);
	if (isset($load[$pgid]))
		return "<p>'#painter' �ϡ�1�ڡ���������֤Ǥ���Τϡ�1�ĤޤǤǤ���</p>";
	else
		$load[$pgid] = TRUE;
	
	list ($picw,$pich,$defmode,$template) = func_get_args();
	$picw = ($picw)? (int)$picw : WIKI_PAINTER_MAX_WIDTH_PLUGIN ;
	$pich = ($pich)? (int)$pich : WIKI_PAINTER_MAX_HEIGHT_PLUGIN ;
	$picw = ($picw < 20)? 20 : $picw;
	$pich = ($pich < 20)? 20 : $pich;
	$picw = min($picw,WIKI_PAINTER_MAX_WIDTH_PLUGIN);
	$pich = min($pich,WIKI_PAINTER_MAX_HEIGHT_PLUGIN);
	
	
	
	$template_tag = '';
	$templates = array();
	if ($template)
	{
		foreach (explode(" ",$template) as $name)
		{
			$page = $vars['page'];
			$_name = $name;
			//���Хѥ�����ե�ѥ�������
			if (preg_match('/^(.+)\/([^\/]+)$/',$name,$matches))
			{
				if ($matches[1] == '.' or $matches[1] == '..')
				{
					$matches[1] .= '/';
				}
				$page = add_bracket(get_fullname($matches[1],$page));
				$name = $matches[2];
			}
			$file = UPLOAD_DIR.encode($page).'_'.encode($name);
			if (is_file($file))
				$templates[] = $_name;
		}
		if ($templates)
		{
			$template_tag = '�ƥ�ץ졼��:&nbsp;<select name="image_canvas">';
			$template_tag .= '<option val="">���Ѥ��ʤ�</option>';
			foreach($templates as $val)
			{
				$template_tag .= '<option val="'.$val.'">'.$val.'</option>';
			}
			$template_tag .= '</select>';
			$template_tag .= '<input type="hidden" name="fitimage" value="ture" />';
		}
	}
	$template_tag .= '&nbsp;<input type=checkbox id="_p_anime_'.$pgid.'" value="true" name="anime" /><label for="_p_anime_'.$pgid.'">ư�赭Ͽ&nbsp;(�����ڥ��󥿡� & Pro �Τ�)</label><br />';
	
	$def_select = array('','','');
	switch ($defmode)
	{
		case "BBS�ڥ��󥿡�":
			$def_select[1] = " selected=\"true\"";
			break;
		case "�����ڥ��󥿡�":
			$def_select[2] = " selected=\"true\"";
			break;
		case "�����ڥ��󥿡�Pro":
			$def_select[3] = " selected=\"true\"";
			break;
	}
	$out='
<form action="'.$script.'" method=POST>
'.$template_tag.'
���������ġ���:<select name="tools">
<option value="bp"'.$def_select[1].'>BBS�ڥ��󥿡�</option>
<option value="normal"'.$def_select[2].'>�����ڥ��󥿡�</option>
<option value="pro"'.$def_select[3].'>�����ڥ��󥿡�Pro</option>
</select>
��<input type=text name=picw value='.$picw.' size=3>�߽�<input type=text name=pich value='.$pich.' size=3>
����('.WIKI_PAINTER_MAX_WIDTH_PLUGIN.' x '.WIKI_PAINTER_MAX_HEIGHT_PLUGIN.')
<input type=submit value="������������" />
<input type=hidden name="pmode" value="paint" />
<input type=hidden name="plugin" value="painter" />
<input type=hidden name="refer" value="'.$vars['page'].'" />
<input type=hidden name="retmode" value="plugin" />
</form><br />
<a href="'.$script.'?plugin=painter&amp;pmode=save&amp;refer='.encode($vars['page']).'">�������åץ��ɺѤߥǡ�����õ��(�����ڥ��󥿡�)����</a>
<hr />
';
	return "<p>".$out."</p>";
}

function plugin_painter_write()
{
	global $post,$vars;
	
	if (!exist_plugin('paint'))
		return array('msg'=>'paint.inc.php not found or not correct version.');
	
	$upload = (!empty($post['upload']))? TRUE : FALSE ;
	
	//�ڡ����˽񤭹���
	$filename = $post['filename'];
	$file = $post['file'];

	$page = $post['page'] = $vars['page'] = $post['refer'] = $vars['refer'];
	
	if (!empty($post['delete']))
	{
		// �������
		$tgtfile = preg_replace("/[^\.]+$/","",$file);
		@unlink(WIKI_PAINTER_TEMP_DIR.$file);
		@unlink(WIKI_PAINTER_TEMP_DIR.$tgtfile."dat");
		@unlink(WIKI_PAINTER_TEMP_DIR.$tgtfile."spch");
		$retval = array("msg" => "���������ǡ����������ޤ�����", "body" => "");
	}
	else
	{
		// ��Ͽ����
		
		// �ե�����̾�ִ�
		if ($filename)
			$attachname = preg_replace('/^[^\.]+/',$filename,$file);
		else
			$attachname = $file;
			
		$attachname = str_replace(array(",",");","){"),array("��",")��",")��"),$attachname);
		
		//���Ǥ�¸�ߤ�����硢 �ե�����̾��'_0','_1',...���դ��Ʋ���(��©)
		$count = '_0';
		while (file_exists(PAINT_UPLOAD_DIR.encode($vars['refer']).'_'.encode($attachname)))
		{
			$attachname = preg_replace('/^[^\.]+/',$filename.$count++,$file);
		}
		// spch �ե�����
		$spchname = preg_replace('/[^\.]+$/','spch',$attachname);
		
		if (!exist_plugin('attach') or !function_exists('attach_upload'))
		{
			return array('msg'=>'attach.inc.php not found or not correct version.');
		}
		
		$retval = do_upload($page,$attachname,WIKI_PAINTER_TEMP_DIR.$file);
		if ($retval['result'] == TRUE)
		{
			
			$tgtfile = preg_replace("/[^\.]+$/","",$file);
			if (file_exists(WIKI_PAINTER_TEMP_DIR.$tgtfile."spch"))
			{
				do_upload($page,$spchname,WIKI_PAINTER_TEMP_DIR.$tgtfile."spch");
				$vars['spch'] = $spchname;
			}
			@unlink(WIKI_PAINTER_TEMP_DIR.$tgtfile."dat");
			
			clearstatcache();
			if ($upload)
			{
				if (isset($vars['attachref_no']) && exist_plugin('attachref'))
				{
					do_plugin_init('attachref');
					$retval = attachref_insert_ref(strip_bracket($page)."/".$attachname);
				}
				else
					$retval = array("msg" => "���������ǡ����򥢥åץ��ɤ��ޤ�����", "body" => "");
			}
			else
				$retval = paint_insert_ref($attachname);
		}
		else
			$retval['msg'] = '���Υǡ���( '.htmlspecialchars($file).' )�ν����ϴ�λ���Ƥ��ޤ���';
	}
	
	if (!plugin_painter_getfiles(encode($vars['refer'])) || isset($vars['attachref_no']))
		return $retval;
	else
	{
		if ($upload)
			$pmode = "upload";
		else
			$pmode = "save";
		
		$returl = $script."?plugin=painter&amp;pmode=".$pmode."&amp;refer=".encode($vars['refer']);
		redirect_header($returl,1,$retval['msg']."<br /><br />���Υ��åץ��ɺѤߥե�����ν����򤷤ޤ���");
		exit();
	}
}

function plugin_painter_save($upload=false)
{
	global $vars;
	$files = plugin_painter_getfiles($vars['refer']);
	$page = $vars['refer'] = decode($vars['refer']);
	
	if (!$files)
	{
		if ($upload)
		{
			if (isset($vars['attachref_no']))
			{
				$add_prm = '';
				if (isset($vars['attachref_no'])) $add_prm .= "&amp;attachref_no=".rawurlencode($vars['attachref_no']);
				if (isset($vars['digest'])) $add_prm .= "&amp;digest=".rawurlencode($vars['digest']);
				if (isset($vars['attachref_opt'])) $add_prm .= "&amp;attachref_opt=".rawurlencode($vars['attachref_opt']);
				$url = $script."?plugin=attachref&amp;refer=".rawurlencode($page).$add_prm;
			}
			else
				$url = $script."?plugin=attach&amp;pcmd=upload&amp;page=".rawurlencode($page);
		}
		else
			$url = get_url_by_name($page);
		redirect_header($url,1,"���åץ��ɺѤߥե�����Ϥ���ޤ���");
		exit();
	}
	return plugin_painter_add($files,$upload);
}

function plugin_painter_add($files,$upload=false)
{
	global $script,$vars,$X_uname;
	
	$p_num = (empty($vars['p_num']))? 1 : (int)$vars['p_num'];
	$p_count = count($files);
	if ($p_count < $p_num) $p_num = $p_count;
	//$file = $files[$p_num-1];
	$file = $files[$p_num-1]['file'];
	$refer = $vars['refer'];
	$pmode = ($upload)? "upload" : "save";
	
	$add_prm = '';
	if (isset($vars['attachref_no'])) $add_prm .= "&amp;attachref_no=".rawurlencode($vars['attachref_no']);
	if (isset($vars['digest'])) $add_prm .= "&amp;digest=".rawurlencode($vars['digest']);
	if (isset($vars['attachref_opt'])) $add_prm .= "&amp;attachref_opt=".rawurlencode($vars['attachref_opt']);
	
	$i = 0;
	foreach($files as $temp)
	{
		$i++;
		if ($i == $p_num)
			$links[] = "<strong>[No.".$i."] <small>".date('n/j H:i',filemtime(WIKI_PAINTER_TEMP_DIR.$temp['file']))."</small></strong>";
		else
			$links[] = "<a href=\"".$script."?plugin=painter&amp;pmode={$pmode}&amp;refer=".encode($refer)."&amp;p_num={$i}{$add_prm}\">[No.".$i."] <small>".date('n/j H:i',filemtime(WIKI_PAINTER_TEMP_DIR.$temp['file']))."</small></a>";
	}
	
	$navi = "���åץ��ɺѤߥե�����(".count($files)."��): ".join(" | ",$links)."<hr />";
	$img = '<img src="'.WIKI_PAINTER_TEMP_DIR.$file.'" /><hr />';
	$fontset_js_tag = fontset_js_tag();
	if ($upload)
	{
		$exttag = '<input type="hidden" name="upload" value="1" />';
		
		if (isset($vars['attachref_no']) && exist_plugin('attachref'))
		{
			global $_attachref_messages;
			do_plugin_init('attachref');
			
			$s_args = htmlspecialchars($vars['attachref_opt']);
			$comment = "";
			if (preg_match("/t:([^,]*)/i",$s_args,$m_args)){
				$comment = $m_args[1];
				$comment = str_replace("\x08",",",$comment);
				$s_args = preg_replace("/(,)?t:([^,]*)/i","",$s_args);
			}
			$rate = "";
			if (preg_match("/([\d]+)%/",$s_args,$m_args)){
				$rate = $m_args[1];
				$s_args = preg_replace("/(,)?([\d]+)%/","",$s_args);
			}
			$mw = "";
			if (preg_match("/mw:([\d]+)/",$s_args,$m_args)){
				$mw = $m_args[1];
				$s_args = preg_replace("/(,)?mw:([\d]+)/","",$s_args);
			}
			$mh = "";
			if (preg_match("/mh:([\d]+)/",$s_args,$m_args)){
				$mh = $m_args[1];
				$s_args = preg_replace("/(,)?mh:([\d]+)/","",$s_args);
			}
			
			$exttag .="<hr />
<h4>{$_attachref_messages['msg_comment_h']}</h4>
{$_attachref_messages['msg_comment']}: <input type='text' name='comment' size='60' value='{$comment}' /><hr />
<h4>{$_attachref_messages['msg_make_thumb']}</h4>
<p>{$_attachref_messages['msg_thumb_note']}</p>
{$_attachref_messages['msg_max_rate']}: <input type='text' name='rate' size='3' value='{$rate}' />%&nbsp;&nbsp;
{$_attachref_messages['msg_max_width']}: <input type='text' name='mw' size='4' value='{$mw}' />px&nbsp;<b>x</b>&nbsp;
{$_attachref_messages['msg_max_height']}: <input type='text' name='mh' size='4' value='{$mh}' />px<hr />
";
			$exttag .= '<input type="hidden" name="attachref_no" value="'.htmlspecialchars($vars['attachref_no']).'" />';
			$exttag .= '<input type="hidden" name="digest" value="'.htmlspecialchars($vars['digest']).'" />';
			$exttag .= '<input type="hidden" name="attachref_opt" value="'.$s_args.'" />';
		}
	}
	else
	{
		$exttag ='<input type="hidden" name="upload" value="0" />
��̾��: <input type="text" name="yourname" size="20" value="'.$X_uname.'" /><br />
�����ȥ�: <input type="text" name="title" size="60" /><br />
��å�����:'.$fontset_js_tag.'<br />
<textarea name="msg" cols="80" rows="10"></textarea><br />
<input type="checkbox" id="add_comment" name="add_comment" value="1" /><label for="add_comment">�����Ȥ�����դ���</label>';
	}
	$title = "<h2>".str_replace('$1',make_pagelink($refer),'$1�ؤ��������ǡ�������Ͽ')."</h2>";
	$body =<<<EOD
<form action="$script" method="POST">
<input type="hidden" name="plugin" value="painter" />
<input type="hidden" name="pmode" value="write" />
<input type="hidden" name="file" value="$file" />
<input type="hidden" name="refer" value="$refer" />
�ե�����̾: <input type="text" name="filename" size="20" />(��ά��)<br />
$exttag
<input type="submit" value=" �� Ͽ ">
&nbsp;&nbsp;&nbsp;
<input type="submit" name="delete" value=" �� �� " style="color:red;">
</form>
EOD;
	$body = $title.$navi.$img.$body;
	return array(
		'msg'  => '$1�ؤ��������ǡ�������Ͽ',
		'body' => $body
	);
}

function plugin_painter_getfiles($refer)
{
	global $X_uid,$X_admin;
	
	//�ƥ�ݥ������ꥹ�Ⱥ���
	
	$tmplist = array();
	$handle = @opendir(WIKI_PAINTER_TEMP_DIR);
	while ($file = readdir($handle))
	{
		if(!is_dir($file) && preg_match("/\.(dat)$/i",$file))
		{
			$fp = fopen(WIKI_PAINTER_TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,$page,$uid) = explode("\t", rtrim($userdata));
			$file_name = eregi_replace("\.(dat)$","",$file);
			if(@file_exists(WIKI_PAINTER_TEMP_DIR.$file_name.$imgext) && $page==$refer) //����������Хꥹ�Ȥ��ɲ�
				$tmplist[] = $ucode."\t".$uip."\t".$file_name.$imgext."\t".$page."\t".$uid;
		}
	}
	closedir($handle);
	$tmp = array();
	if(count($tmplist)!=0){
		//user-code�ǥ����å�
		foreach($tmplist as $tmpimg)
		{
			list($ucode,$uip,$file,$page,$uid) = explode("\t", $tmpimg);
			if($ucode == $usercode || ($X_uid && ($uid == $X_uid)) || $X_admin)
			{
				$page = decode($page);
				$tmp[] = compact('page','file');
			}
		}
		//user-code��hit���ʤ����IP�Ǻƥ����å�
		if(count($tmp)==0)
		{
			$userip = getenv("HTTP_CLIENT_IP");
			if(!$userip) $userip = getenv("HTTP_X_FORWARDED_FOR");
			if(!$userip) $userip = getenv("REMOTE_ADDR");
			foreach($tmplist as $tmpimg)
			{
				list($ucode,$uip,$file,$page,$uid) = explode("\t", $tmpimg);
				if(!IP_CHECK || $uip == $userip)
				{
					$page = decode($page);
					$tmp[] = compact('page','file');
				}
			}
		}
	}
	return $tmp;
}

function plugin_painter_paint()
{
	global $vars,$script,$X_uid;
	
	$tools = (isset($vars['tools']))? $vars['tools'] : "normal";
	$picw = (isset($vars['picw']))? $vars['picw'] : WIKI_PAINTER_DEF_WIDTH;
	$pich = (isset($vars['pich']))? $vars['pich'] : WIKI_PAINTER_DEF_HEIGHT;
	
	//���ϲ����ե�����ν���
	$page = $vars['refer'];
	$name = (isset($vars['image_canvas']))? rtrim($vars['image_canvas']) : "";
	//���Хѥ�����ե�ѥ�������
	if (preg_match('/^(.+)\/([^\/]+)$/',$name,$matches))
	{
		if ($matches[1] == '.' or $matches[1] == '..')
		{
			$matches[1] .= '/';
		}
		$page = add_bracket(get_fullname($matches[1],$page));
		$name = $matches[2];
	}
	$file = UPLOAD_DIR.encode($page).'_'.encode($name);
	$image_canvas = '';
	if (is_file($file))
	{
		$image_canvas = '<param name="image_canvas" value="'.$file.'">'."\n";
		//�����Х����������ɤ߹��ߥ��᡼���ˤ��碌��
		if (!empty($vars['fitimage']))
		{
			$imgsize = getimagesize($file);
			$picw = $imgsize[0];
			$pich = $imgsize[1];
		}
	}
	
	$picw = ((int)$picw < 20)? 20 : (int)$picw;
	$pich = ((int)$pich < 20)? 20 : (int)$pich;
	if (isset($vars['retmode']) && $vars['retmode']=="plugin")
	{
		$maxw = WIKI_PAINTER_MAX_WIDTH_PLUGIN;
		$maxh = WIKI_PAINTER_MAX_HEIGHT_PLUGIN;
	}
	else
	{
		$maxw = WIKI_PAINTER_MAX_WIDTH_UPLOAD;
		$maxh = WIKI_PAINTER_MAX_HEIGHT_UPLOAD;
	}
	$picw = min($picw,$maxw);
	$pich = min($pich,$maxh);
	
	if ($tools == "bp")
	{
		if (exist_plugin('paint'))
		{
			$vars['paint_no'] = 0;
			$vars['digest'] = "";
			$vars['plugin'] = "paint";
			$vars['width'] = $picw;
			$vars['height'] = $pich;
			plugin_paint_init();
			return plugin_paint_action();
		}
		else
			$tools = "normal";

	}
	if ($tools != "pro") $tools = "normal";
	
	$anime = (!empty($vars['anime']))? '<param name="thumbnail_type" value="animation">' : '' ;
	$anime_sign = ($anime)? '�����襢�˥ᵭϿ���' : '' ;
	$refer = encode($vars['refer']);
	
	$add_prm = '';
	if (isset($vars['attachref_no'])) $add_prm .= "&amp;attachref_no=".rawurlencode($vars['attachref_no']);
	if (isset($vars['digest'])) $add_prm .= "&amp;digest=".rawurlencode($vars['digest']);
	if (isset($vars['attachref_opt'])) $add_prm .= "&amp;attachref_opt=".rawurlencode($vars['attachref_opt']);

	$retmode = (isset($vars['retmode']))? $vars['retmode'] : '' ;
	if ($retmode == 'plugin')
		$returl = $script."?plugin=painter&amp;pmode=save&amp;refer=".$refer;
	else
		$returl = $script."?plugin=painter&amp;pmode=upload&amp;refer=".$refer.$add_prm;
	
	$title = strip_bracket($vars['refer'])."�ؤ�������";
	$back_to = " | <a href='".get_url_by_name($vars['refer'])."'>".strip_bracket($vars['refer'])."�ؤ�ɤ�</a>";
	$out ='<html><head><title>'.$title.'</title>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=EUC_JP">
<META name="ROBOTS" content="NOINDEX,NOFOLLOW">
<STYLE TYPE="text/css">
<!--
body,tr,td,th { font-size:11pt }
a:hover { color:#000088;background-color:#44DDDD;TEXT-DECORATION:NONE }
a:link  { color:#2222FF;background-color:#CCFFFF; }
a:vlink { color:#2222FF; }
span { font-size:24pt }
big { font-size:14pt }
small { font-size:9pt }
-->
</STYLE>
<script language="JavaScript" charset="Shift_JIS" src="./plugin_data/painter/palette.js" type="text/javascript"></script>
</head>
<body bgcolor=#F0F8FF text=#000088 link=#2222FF vlink=#2222FF alink=#000088 onload="SetTimeCount()">
<center>
<!--���������⡼�� ����-->
<NOSCRIPT><H3>JavaScript��ͭ���Ǥʤ����������ư���פ��ޤ���</H3></NOSCRIPT>
<TABLE><TR><TD>
<applet code="c.ShiPainter.class" archive="plugin_data/painter/spainter1073.jar,plugin_data/painter/res/'.$tools.'.zip" name="paintbbs" width="650" height="470" MAYSCRIPT>
<param name="dir_resource" value="plugin_data/painter/res/">
<param name="tt.zip" value="plugin_data/painter/res/tt.zip">
<param name="res.zip" value="plugin_data/painter/res/res_'.$tools.'.zip">
<param name="tools" value="'.$tools.'">
<param name="layer_count" value="3">
<param name="quality" value="1">
<param name="image_width" value="'.$picw.'">
<param name="image_height" value="'.$pich.'">
<param name="image_jpeg" value="true">
<param name="image_size" value="60">
'.$image_canvas.'<param name="compress_level" value="15">
<param name="undo" value="90">
<param name="undo_in_mg" value="45">
<param name="url_save" value="'.XOOPS_WIKI_URL.'/plugin_data/painter/picpost.php">
<param name="url_exit" value="'.$returl.'">
<param name="send_header" value="usercode='.PUKIWIKI_UCD.'&refer='.$refer.'&uid='.$X_uid.'">
<param name="poo" value="false">
<param name="send_advance" value="true">
'.$anime.'<param name="thumbnail_width" value="100%">
<param name="thumbnail_height" value="100%">
<param name="tool_advance" value="true">
<param name="security_post" value="false">
</applet>
</TD><TD ALIGN="CENTER" VALIGN="top"><BR>
<TABLE BGCOLOR="black" CELLPADDING="1" CELLSPACING="0" width=100%><TR><FORM name="Palette">
<TD><TABLE BGCOLOR="white" CELLPADDING="3" CELLSPACING="0" width=100%><TR>
<TD><FONT SIZE=2><font face="Impact, Arial Black" color="black" size="2">PALETTE</font> <INPUT TYPE=button VALUE="�����¸" OnClick="PaletteSave()"><BR>
<select name="select" size="13" onChange="setPalette()">
<option>�����¸�ѥ�å�</option>
<option>ȩ����</option>
<option>�ַ�</option>
<option>��������</option>
<option>�з�</option>
<option>�ķ�</option>
<option>���</option>
<option>���Ԥ�</option>
<option>��ʪ</option>
<option>�ѥ��ƥ�</option>
<option>�𸶤�����</option>
<option>˨��������</option>
<option>��Υ���</option>
</select><BR>
<INPUT TYPE=button VALUE="����" OnClick="PaletteNew()">
<INPUT TYPE=button VALUE="�ѹ�" OnClick="PaletteRenew()">
<INPUT TYPE=button VALUE="���" OnClick="PaletteDel()"><BR>
<INPUT TYPE=button VALUE="����" OnClick="P_Effect(10)">
<INPUT TYPE=button VALUE="����" OnClick="P_Effect(-10)">
<INPUT TYPE=button VALUE="ȿž" OnClick="P_Effect(255)">
<HR SIZE=1><font face="Impact, Arial Black" color="black" size="2">MATRIX</font>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="m_m">
<option value=0>����</option>
<option value=1>����</option>
<option value=2>�ɲ�</option>
</SELECT><BR>
<INPUT name="m_g" TYPE=button VALUE="����" OnClick="PaletteMatrixGet()">
<INPUT name="m_s" TYPE=button VALUE="���å�" OnClick="PalleteMatrixSet()">
<INPUT name="m_h" TYPE=button VALUE=" ? " OnClick="PalleteMatrixHelp()"><BR>
<TEXTAREA rows="1" name="setr" cols="13" onmouseover="this.select()"></TEXTAREA><BR>
</TD></TR></TABLE></TD></FORM></TR></TABLE>
<BR>
<TABLE BGCOLOR="black" CELLPADDING=1 CELLSPACING=0><TR><TD><TABLE BGCOLOR="white" CELLPADDING=3 CELLSPACING=0 width=100%><TR><FORM name=grad>
<TD><FONT SIZE=2><font face="Impact, Arial Black" color="black" size=2>GRADATION</font>&nbsp;<INPUT TYPE=checkbox name=view OnClick="showHideLayer()"><INPUT TYPE=button VALUE=" OK " OnClick="ChengeGrad()"><BR>
<SELECT name=p_st onChange="GetPalette()">
<option>1</option>
<option>2</option>
<option>3</option>
<option>4</option>
<option>5</option>
<option>6</option>
<option>7</option>
<option>8</option>
<option>9</option>
<option>10</option>
<option>11</option>
<option>12</option>
<option>13</option>
<option>14</option>
</SELECT><input type=text name=pst size=8 onKeyPress="Chenge_()" onChange="Chenge_()"><BR>
<SELECT name=p_ed onChange="GetPalette()">
<option>1</option>
<option>2</option>
<option>3</option>
<option>4</option>
<option>5</option>
<option>6</option>
<option>7</option>
<option>8</option>
<option>9</option>
<option>10</option>
<option>11</option>
<option selected>12</option>
<option>13</option>
<option>14</option>
</SELECT><input type=text name=ped size=8 onKeyPress="Chenge_()" onChange="Chenge_()"><div id=psft style="position:absolute;width:100px;height:30px;z-index:1;left:5px;top:10px;"></div>
</TD></TR></TABLE></TD></FORM></TR></TABLE>
<center><a href="http://wondercatstudio.com/" target="_blank" title="WonderCatStudio"><small>DynamicPalette<BR>(C)�Τ�ͥ�</small></a></center>
</TD></TR></TABLE>
<script language="javascript">
<!--
if(DynamicColor) PaletteListSetColor();
//-->
</script>
<form name="watch">�������
<input size="20" name="count">&nbsp;'.$anime_sign.$back_to.'
</form>
</center></body></html>
';
	
	echo $out;
	exit;
}

?>