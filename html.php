<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: html.php,v 1.52 2005/03/16 12:49:47 nao-pon Exp $
/////////////////////////////////////////////////

// ��ʸ��ڡ���̾�������
function catbodyall($page,$title="",$pg="")
{
	if($title === "") $title = strip_bracket($page);
	if($pg === "") $pg = make_search($page);

	$body = join("",get_source($page));
	$body = convert_html($body);

	header_lastmod($vars["page"]);
	catbody($title,$pg,$body);
	die();
}

// ��ʸ�����
function catbody($title,$page,$body)
{
	global $script,$vars,$arg,$do_backup,$modifier,$modifierlink,$defaultpage,$whatsnew,$hr;
	global $date_format,$weeklabels,$time_format,$related_link,$_msg_trackback;
	global $HTTP_SERVER_VARS,$cantedit;
	global $longtaketime;
	global $foot_explain, $note_hr, $_msg_word, $search_word_color,$use_static_url;
	
	global $xoopsModule, $xoopsUser, $modifier, $hide_navi, $anon_writable, $wiki_writable, $wiki_allow_newpage;
	global $X_admin,$X_uname,$noattach,$noheader,$trackback,$xoopsTpl,$pgid,$use_xoops_comments;
	
	global $_msg_pagecomment,$_msg_trackback,$_msg_pings;
	
	
	//̾�����ִ�
	if (empty($vars['xoops_block']))
		$body = str_replace(WIKI_NAME_DEF,$X_uname,$body);
	
	//form�ִ�
	$body = preg_replace("/(<form[^>]+)(>)/is","$1 onsubmit=\"return pukiwiki_check(this);\"$2",$body);
	
	// ɽ����Υڡ���̾
	$_page = $vars["page"];
	$_rpage = rawurlencode($_page);
	
	// �ڡ�����¸�ߤ��뤫
	$is_page = is_page($_page);
	
	//�ڡ���ID
	$pid = get_pgid_by_name($_page);
	
	// �̾�Υڡ���ɽ���⡼��?
	$is_read = ($is_page && arg_check("read") && empty($vars["preview"]));
	
	// �ڡ������Խ�����ʤ���
	$_freeze = is_freeze($_page);
	
	$link_page = get_url_by_id($id);
	if ($use_static_url)
	{
		$con_str = "?";
	}
	else
	{
		$con_str = "&amp;";
 	}
	
	$link_top = XOOPS_WIKI_URL."/";
 	$link_add = "$script?cmd=add&amp;page=".$_rpage;
 	$link_edit = "$script?cmd=edit&amp;page=".$_rpage;
 	$link_diff = "$script?cmd=diff&amp;page=".$_rpage;
	$link_list = "$script?cmd=list";
	$link_filelist = "$script?cmd=filelist";
	$link_search = "$script?cmd=search";
	$link_whatsnew = "$script?$whatsnew";
 	$link_backup = "$script?cmd=backup&amp;page=".$_rpage;
	$link_help = "$script?cmd=help";
	$link_source = "$script?plugin=source&amp;page=".$_rpage;
	$link_new = "$script?plugin=newpage&amp;refer=".$_rpage;
	$link_copy = "$script?plugin=template&refer=".$_rpage;
	$link_rename = "$script?plugin=rename&refer=".$_rpage;
	$link_attach = "$script?plugin=attach&amp;pcmd=upload&amp;page=".$_rpage;
	$link_attachlist = "$script?plugin=attach&amp;pcmd=list&amp;page=".$_rpage;


	if($is_read && empty($vars['xoops_block']))
	{
		$fmt = @filemtime(get_filename(encode($_page)));
		
		$tb_url = tb_get_my_tb_url($pid);
		
		$comments_tag = ($use_xoops_comments)? " [ ".get_pagecomment_count($pgid,'#page_comments',$_msg_pagecomment.'($1)')." ]" : "";
		$tb_count = $_msg_trackback."(".tb_count($vars['page']).")";
		$tb_tag = ($trackback)? " [ <a href=\"".$tb_url.$con_str."__mode=view\" name=\"tb_body\">{$tb_count}</a> ]" : "";
		
		$sended_ping_tag = ($trackback)? "[ <a href=\"".$tb_url.$con_str."__mode=view#sended_ping\">$_msg_pings(".tb_count($vars['page'],".ping").")</a> ]" : "";
		
		if (strip_bracket($_page) != $defaultpage) {
			require_once(PLUGIN_DIR.'where.inc.php');
			$where = do_plugin_inline("where");
		}
		else
			$where = "";
		
		require_once(PLUGIN_DIR.'counter.inc.php');
		$counter = do_plugin_convert("counter");
		
		if(!$noattach && file_exists(PLUGIN_DIR."attach.inc.php") && $is_read)
		{
			require_once(PLUGIN_DIR."attach.inc.php");
			$attaches = attach_filelist();
		}
		
		$trackback_body = ($trackback)? tb_get_tb_body($_page,TRUE) : "";
		if ($trackback_body)
		{
			$trackback_body = <<<EOT
	<div class="outer">
	  <div class="head"><a name="tb_body"></a>{$_msg_trackback}{$tb_tag}</div>
	  <div class="tburl">{$_msg_trackback} URL: {$tb_url}</div>
	  <div class="blog">
	   {$trackback_body}
	  </div>
	</div>
	<hr />
EOT;
			$tb_tag = " [ <a href=\"#tb_body\">{$tb_count}</a> ]";
		}
	}
	
	if ($is_page)
	{
		global $no_name;
		global $defvalue_gids,$defvalue_aids;
		
		//$pg_auther_name=get_pg_auther_name($_page);
		$pginfo = get_pg_info_db($_page);
		$user = new XoopsUser();
		$pg_auther_name= $user->getUnameFromId($pginfo['uid']);
		$last_editer = $user->getUnameFromId($pginfo['lastediter']);
		
		//�Խ���
		$allows = get_pg_allow_editer($_page);
		$allow_groups = ($allows['group'])? explode(",",$allows['group']) : explode(",",$defvalue_gids,",");
		$allow_users = ($allows['user'])? explode(",",$allows['user']) : explode(",",$defvalue_aids.",");
		
		$groups = X_get_group_list();
		$allow_edit_groups = array();
		foreach($allow_groups as $_gid)
		{
			if ($_gid) $allow_edit_groups[] = $groups[$_gid];
		}
		$allow_edit_groups = join(', ',$allow_edit_groups);
		
		$allow_editers = array();
		if (!in_array(2,$allow_groups))
		{
			$allow_users = array_merge(array($pginfo['uid']),$allow_users);
			foreach($allow_users as $_uid)
			{
				if ($_uid) $allow_editers[] = "<a href=\"".XOOPS_URL."/userinfo.php?uid=".$_uid."\">".$user->getUnameFromId($_uid)."</a>";
			}
		}
		$allow_editers = join(', ',$allow_editers);
		
		if ($allow_edit_groups && $allow_editers) $allow_edit_groups .= " :: ";
		if (!$allow_edit_groups && !$allow_editers) $allow_edit_groups = $groups[1];
		
		unset($user);
	}

	if($is_read && $related_link)
	{
		$related = make_related($_page);
	}
	
	//ñ�측��
	if ($search_word_color and array_key_exists('word',$vars))
	{
		$search_word = '';
		$words = array_flip(array_splice(preg_split('/\s+/',$vars['word'],-1,PREG_SPLIT_NO_EMPTY),0,10));
		$keys = array();
		foreach ($words as $word=>$id)
		{
			$keys[$word] = strlen($word);
		}
		arsort($keys,SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys));
		$id = 0;
		foreach ($keys as $key=>$pattern)
		{
			$s_key = htmlspecialchars($key);
			$search_word .= " <strong class=\"word$id\">$s_key</strong>";
			$pattern = ($s_key{0} == '&') ?
				"/(<[^>]*>)|($pattern)/" :
				"/(<[^>]*>|&(?:#[0-9]+|#x[0-9a-f]+|[0-9a-zA-Z]+);)|($pattern)/";
			$body = preg_replace_callback($pattern,
				create_function('$arr',
					'return $arr[1] ? $arr[1] : "<strong class=\"word'.$id.'\">{$arr[2]}</strong>";'),$body);
			$id++;
		}
		$body = "<div class=\"small\">$_msg_word$search_word</div>$hr\n$body";
	}
	
	if ($foot_explain)
		$body .= "\n$note_hr\n".join("\n",$foot_explain);
	
	$taketime = sprintf("%01.03f",getmicrotime() - MUTIME);
	
	// XOOPS �ƥ�ץ졼��
	$use_xoops_tpl = 0;
	if (is_object($xoopsTpl))
	{
		$use_xoops_tpl = 1;
		
		$xoopsTpl->assign('modifierlink',$modifierlink);
		$xoopsTpl->assign('modifier',$modifier);
		$xoopsTpl->assign('xoops_wiki_copyright',_XOOPS_WIKI_COPYRIGHT);
		$xoopsTpl->assign('s_copyright',S_COPYRIGHT);
		$xoopsTpl->assign('php_version',PHP_VERSION);
		$xoopsTpl->assign('taketime',$taketime);
		
		$xoopsTpl->assign('is_read', $is_read);
		
		$xoopsTpl->assign('trackback_body', $trackback_body);
		$trackback_body = "";
	}
	
	if(!file_exists(SKIN_FILE)||!is_readable(SKIN_FILE))
	  die_message(SKIN_FILE."(skin file) is not found.");
	require(SKIN_FILE);
}

// �ڡ����͡���μ���
function get_page_name(){
	global $non_list,$whatsnew;
	
	$tmpnames = array();
	$retval = array();
	$files = get_existpages();	// �������¤�̵�뤷�Ƽ�������� get_existpages(true) �����
	foreach($files as $page) {
		if(preg_match("/$non_list/",$page)) continue;
		if($page == $whatsnew) continue;
		$tmpnames[strip_bracket($page)] = (function_exists('mb_strlen'))? mb_strlen(strip_bracket($page)) : strlen(strip_bracket($page));
	}
	arsort ($tmpnames);
	reset ($tmpnames);
	return array_keys($tmpnames);
}

// �Խ��ե������ɽ��
function edit_form($postdata,$page,$add=0,$allow_groups=NULL,$allow_users=NULL,$freeze_check="")
{
	global $script,$rows,$cols,$hr,$vars,$function_freeze,$autolink;
	global $_btn_addtop,$_btn_preview,$_btn_update,$_btn_freeze,$_msg_help,$_btn_notchangetimestamp,$_btn_enter_enable,$_btn_autobracket_enable,$_btn_freeze_enable,$_btn_auther_id;
	global $whatsnew,$_btn_template,$_btn_load,$non_list,$load_template_func;
	global $X_admin,$X_uid,$freeze_tag,$wiki_writable;
	global $unvisible_tag,$_btn_unvisible_enable,$_btn_v_allow_memo,$read_auth,$pagereading_enable;
	
	$b_preview = array_key_exists('preview',$vars); // �ץ�ӥ塼�� TRUE
	
	// �Ƽ���������
	$refer = "";
	$reading_tag = "";
	
	if (!$b_preview)
	{
		// �ڡ����Խ���
		
		$digest = md5(@join("",get_source($page)));
		
		unset ($create_uid);
		if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$postdata,$arg))
		{
			$create_uid = $arg[1];
			$freeze_check = "checked ";
		}

		// �ڡ���������
		delete_page_info($postdata);

		//�ڡ��������Ԥ�����
		if (is_page($page))
			$author_uid = get_pg_auther($page);
		else
			$author_uid = $X_uid;
		
		$create_uid = (isset($create_uid))? $create_uid : $X_uid ;
		
		$add_top_enable = "";
		$enter_enable = " checked";
		$auto_bra_enable = ($autolink)? "" : " checked";
		$notimestamp_enable = "";
		$v_gids = $v_aids = NULL;
		$paraedit_tag = "";

		if ($pagereading_enable && (($X_uid && $X_uid == $author_uid) || $X_admin))
		{
			// �ڡ���̾�ɤ�BOX
			$reading_tag = "<hr /><div>�ڡ����ɤ�: ".'<input type="text" name="f_page_reading" size="60" value="'.get_reading($page).'" /><br />';
			$reading_tag .= '&nbsp;&nbsp;&nbsp;<input type="checkbox" id="c_page_reading" name="c_page_reading" value=" checked" /><label for="c_page_reading">�ڡ����ɤߤ򹹿����� (̤���ϤǼ�ư����)</label></div>';

		}
	}
	else
	{
		// �ץ�ӥ塼��
		$digest = $vars["digest"];
		$add_top_enable = $vars["add_top"];
		$create_uid = $vars["f_create_uid"];
		if (!is_page($page))
			$author_uid = $X_uid;
		else
			$author_uid = ($X_admin)? $vars["f_author_uid"] : get_pg_auther($page);
		$freeze_check = $vars["freeze"]? " checked" : "";
		$enter_enable = $vars["enter_enable"]? " checked" : "";
		$auto_bra_enable = $vars["auto_bra_enable"]? " checked" : "";
		$notimestamp_enable = $vars["notimestamp"]? " checked" : "";
		$v_gids = $vars["v_gids"];
		$v_aids = $vars["v_aids"];
		
		//paraedit
		$paraedit_tag  = "   <input type=\"hidden\" name=\"msg_before\" value=\"".htmlspecialchars($vars["msg_before"])."\">\n";
		$paraedit_tag .= "   <input type=\"hidden\" name=\"msg_after\"  value=\"".htmlspecialchars($vars["msg_after"])."\">\n";
		
		if ($pagereading_enable && (($X_uid && $X_uid == $author_uid) || $X_admin))
		{
			// �ڡ���̾�ɤ�BOX
			$reading_tag = "<hr /><div>�ڡ����ɤ�: ".'<input type="text" name="f_page_reading" size="60" value="'.$vars["f_page_reading"].'" /><br />';
			$reading_tag .= '&nbsp;&nbsp;&nbsp;<input type="checkbox" id="c_page_reading" name="c_page_reading" value=" checked"'.$vars["c_page_reading"].' /><label for="c_page_reading">�ڡ����ɤߤ򹹿����� (̤���ϤǼ�ư����)</label></div>';
		}
	}

	if($add)
	{
		$addtag = '<input type="hidden" name="add" value="true" />';
		$add_top = '<input type="checkbox" id="add_top" name="add_top" value="true"'.$add_top_enable.' /><label for="add_top"><span class="small">'.$_btn_addtop.'</span></label>';
	}

	if($vars["help"] == "true")
		$help = $hr.catrule();
	else
 		$help = "<br />\n<ul><li><a href=\"$script?cmd=edit&amp;help=true&amp;page=".rawurlencode($page)."\">$_msg_help</a></ul></li>\n";
	//echo sprintf("%01.03f",getmicrotime() - MUTIME)."<br />";
	$allow_edit_tag = $freeze_tag = $unvisible_tag = '';
	if($function_freeze){
		if (($X_uid && $X_uid == $author_uid) || $X_admin) {
			if ($wiki_writable === 2){
				$enable_user = _MD_PUKIWIKI_ADMIN;
			} elseif($wiki_writable === 1){
				$enable_user = _MD_PUKIWIKI_REGIST;
			} else {
				$enable_user = _MD_PUKIWIKI_ALL;
			}
			$freeze_tag = '<input type="hidden" name="f_create_uid" value="'.htmlspecialchars($create_uid).'" /><input type="checkbox" id="freeze" name="freeze" value="true" '.$freeze_check.'/><label for="freeze"><span class="small">'.sprintf($_btn_freeze_enable,$enable_user).'</span></label>';
			$allow_edit_tag = allow_edit_form($allow_groups,$allow_users);
		}
	}
	//echo sprintf("%01.03f",getmicrotime() - MUTIME)."<br />";
	// �������¥ե�����
	if ($read_auth)
	{
		if (($X_uid && $X_uid == $author_uid) || $X_admin)
		{
			if (!$b_preview)
			{
				$auth_viewer = get_pg_allow_viewer($page,false);
				$unvisible_check = $auth_viewer['owner']? "checked" : "";
			}
			else
				$unvisible_check = !empty($vars['unvisible'])? "checked" : "";
			$unvisible_tag = ($function_freeze)? '' : '<input type="hidden" name="f_create_uid" value="'.htmlspecialchars($create_uid).'" />';
			$unvisible_tag .= '<input type="checkbox" id="unvisible" name="unvisible" value="true" '.$unvisible_check.'/><label for="unvisible"><span class="small">'.sprintf($_btn_unvisible_enable).'</span></label>';
			$allow_view_tag = allow_view_form($v_gids,$v_aids);
		}
	}
	//echo sprintf("%01.03f",getmicrotime() - MUTIME)."<br />";

	// �ڡ����������ѹ�BOX
	$auther_tag = ($X_admin)?
		'&nbsp:&nbsp;[ '.$_btn_auther_id.'<input type="text" name="f_author_uid" size="3" value="'.htmlspecialchars($author_uid).'" /> ]'
		:'';
	
	// �����ॹ�����
	$timestamp_tag = ($X_admin || (($X_uid == $author_uid) && $X_uid))?
		'<input type="checkbox" id="notimestamp" name="notimestamp" value="true"'.$notimestamp_enable.' /><label for="notimestamp"><span style="small">'.$_btn_notchangetimestamp.'</span></label>'
		:'';
	
	if($load_template_func && !$b_preview && empty($vars['id']) && !is_page($page))
	{
		$vals = array();

		//��쳬�ؤΤ߼�������
		$files = get_existpages_db(false,"",0,"",false,true);
		//$files = get_existpages();//���٤�ɽ��������Ϥ���
		foreach($files as $pg_org) {
			if($pg_org == $whatsnew) continue;
			if(preg_match("/$non_list/",$pg_org)) continue;
			$name = strip_bracket($pg_org);
			$s_name = htmlspecialchars($name);
			$s_org = htmlspecialchars($pg_org);
			$vals[$name] = "    <option value=\"$s_org\">$s_name</option>";
		}
		@ksort($vals);
		
		$template = "   <select name=\"template_page\">\n"
			   ."    <option value=\"\">-- $_btn_template --</option>\n"
			   .join("\n",$vals)
			   ."   </select>\n"
			   ."   <input type=\"submit\" name=\"template\" value=\"$_btn_load\" accesskey=\"r\" /><br />\n";
		$page_s = strip_bracket($page);
		if($vars["refer"] && $page_s{0} != ":") $refer = $vars["refer"]."\n\n";
	}
	
	return '
<form enctype="multipart/form-data" action="'.$script.'" method="post">
'.$addtag.'
<table cellspacing="3" cellpadding="0" border="0" width="100%">
 <tr>
  <td>
'.file_attache_form().'
  </td>
  <td align="right">
'.$template.'
  </td>
 </tr>
 <tr><td colspan=2>
'.fontset_js_tag().'
   <input type="checkbox" id="enter_enable" name="enter_enable" value="true"'.$enter_enable.' /><label for="enter_enable"><span class="small">'.$_btn_enter_enable.'</span></label>
   <input type="checkbox" id="auto_bra_enable" name="auto_bra_enable" value="true"'.$auto_bra_enable.' /><label for="auto_bra_enable"><span class="small">'.$_btn_autobracket_enable.'</span></label>
 </td></tr>
 <tr>
  <td align="left" colspan=2>
   <input type="hidden" name="encode_hint" value="��" />
   <input type="hidden" name="write" value="1" />
   <input type="hidden" name="page" value="'.htmlspecialchars($page).'" />
   <input type="hidden" name="digest" value="'.htmlspecialchars($digest).'" />
'.$paraedit_tag.'
   <textarea name="msg" rows="'.$rows.'" cols="'.$cols.'" wrap="virtual">
'.htmlspecialchars($refer.$postdata).'</textarea>
  </td>
 </tr>
 <tr>
  <td colspan=2>
   <input type="submit" name="preview" value="'.$_btn_preview.'" accesskey="p" />
   <input type="submit" value="'.$_btn_update.'" accesskey="s" />
   '.$add_top.'
   '.$timestamp_tag.'
   '.$auther_tag.'
  </td>
 </tr>
</table>
'.$reading_tag.'
'.$allow_edit_tag.$allow_view_tag.'
</form>
' . $help;
}

// ��Ϣ����ڡ���
function make_related($page,$tag='')
{
	global $script,$vars,$related,$rule_related_str,$related_str,$non_list;
	global $_ul_left_margin, $_ul_margin, $_list_pad_str;
	$page = strip_bracket($page);
	$links = links_get_related($page);
	
	if ($tag) {
		//ksort($links);
		krsort($links);
	}
	else {
		arsort($links);
	}
	$_links = array();
	foreach ($links as $page=>$lastmod)
	{
		if (preg_match("/$non_list/",$page))
		//if (preg_match("/$non_list/",$page))
		{
			continue;
		}
		$r_page = rawurlencode($page);
		$s_page = htmlspecialchars($page);
		$passage = get_passage(($lastmod));

		//�ڡ���̾���ֿ�����-�פ����ξ��ϡ�*(**)�Ԥ����
		if (preg_match("/^(.*\/)?[0-9\-]+$/",$s_page))
			$alias = get_heading($page);
		else
		{
			$alias = (preg_match("/^.*\/([^\/]+)$/",$s_page,$match))? $match[1] : $s_page;
		}
		$_links[] = $tag ?
			make_pagelink($page) :
			make_pagelink($page,$alias)."<small>$passage</small>";
	}
	
	if (count($_links) == 0)
	{
		return '';
	}
	
	if ($tag == 'p') // ��Ƭ����
	{
		$margin = $_ul_left_margin + $_ul_margin;
		$style = sprintf($_list_pad_str,1,$margin,$margin);
		$retval =  "\n<ul$style>\n<li>".join($rule_related_str,$_links)."</li>\n</ul>\n";
	}
	else if ($tag)
	{
		$retval = join($rule_related_str,$_links);
	}
	else
	{
		$retval = join($related_str,$_links);
	}
	return $retval;
}

// �桼������롼��(���������ִ�����)
function user_rules_str($str)
{
	global $str_rules,$fixed_heading_anchor;

	$arystr = split("\n",$str);
	
	$pre = 0;
	
	// ���顼�͡��������ɽ��
	$colors_reg = "aqua|navy|black|olive|blue|purple|fuchsia|red|gray|silver|green|teal|lime|white|maroon|yellow|transparent";
	// ����񼰻���Ҥ�����ɽ��
	$table_reg = "(?:FC:(?:#?[0-9abcdef]{6}?|".$colors_reg."|0)|(?:SC|BC):(?:(?:#?[0-9abcdef]{6}?|".$colors_reg."|0)|\([^),]*(?:,once|,1)?\))|(?:LEFT|CENTER|RIGHT)?:(?:TOP|MIDDLE|BOTTOM)?)?";
	
	foreach($arystr as $str)
	{
		// ���ա�����ʤ��ִ�����
		if(substr($str,0,1) != " ")
		{
			foreach($str_rules as $rule => $replace)
			{
				$str = preg_replace("/$rule/",$replace,$str);
			}
		}
		
		if ($str == "<<<") $pre ++;
		if ($pre && $str == ">>>") $pre --;
		
		// ���Ф��˸�ͭID����Ϳ����
		if ($fixed_heading_anchor and !$pre)
		{
			preg_match('/^(\|)?(.*?)(\|h?|->)?$/', $str, $matches);
			$matches[1] = (!empty($matches[1]))? $matches[1] : "";
			$matches[3] = (!empty($matches[3]))? $matches[3] : "";
			if ($matches[1] || $matches[3])
			{
				// ɽ��ν���
				$_str_a = array();
				foreach(explode("|",$matches[2]) as $_str)
				{
					if (preg_match('/^('.$table_reg.'\*{1,6}(.(?!\[#[A-Za-z][\w-]+\]))+?)(->)?$/i', $_str, $_arg))
					{
						// ��ͭID����������
						// ������ʱѻ�(1ʸ��)+md5�ϥå���Υ��������ʬʸ����(7ʸ��)
						$anchor = chr(mt_rand(ord('a'), ord('z'))).
							substr(md5(uniqid(substr($_arg[1], 0, 100), 1)), mt_rand(0, 24), 7);
						$_str = rtrim($_arg[1])." [#$anchor]".$_arg[4];
					}
					$_str_a[] = $_str;
				}
				$str = $matches[1].join("|",$_str_a).$matches[3];
			}
			else
			{
				if (preg_match('/^(\*{1,3}(.(?!\[#[A-Za-z][\w-]+\]))+)$/', $str, $matches))
				{
					// ��ͭID����������
					// ������ʱѻ�(1ʸ��)+md5�ϥå���Υ��������ʬʸ����(7ʸ��)
					$anchor = chr(mt_rand(ord('a'), ord('z'))).
						substr(md5(uniqid(substr($matches[2], 0, 100), 1)), mt_rand(0, 24), 7);
					$str = rtrim($matches[1])." [#$anchor]";
				}
			}
		}
		$retvars[] = $str;
	}
	return join("\n",$retvars);
}

// PukiWiki 1.4 �ߴ���
function make_str_rules($str)
{
	return user_rules_str($str);
}
// �桼������롼��(���������ִ���������С���)
function make_line_rules($str)
{
	global $line_rules;
	static $pattern,$replace;
	
	if (!isset($pattern))
	{
		$pattern = array_map(create_function('$a','return "/$a/";'),array_keys($line_rules));
		$replace = array_values($line_rules);
		unset($line_rules);
	}
	return preg_replace($pattern,$replace,$str);
}

// HTML�����������
function strip_htmltag($str)
{
	//$str = preg_replace("/<a[^>]+>\?<\/a>/","",$str);
	return preg_replace("/<[^>]+>/","",$str);
}

// �ڡ���̾����ڡ���̾�򸡺������󥯤����
function make_search($page)
{
	global $script,$WikiName;

	$name = strip_bracket($page);
	$url = rawurlencode($name);

	//WikiWikiWeb like...
	//if(preg_match("/^$WikiName$/",$page))
	//	$name = preg_replace("/([A-Z][a-z]+)/","$1 ",$name);

 	return "<a href=\"$script?cmd=search&amp;word=$url\">".htmlspecialchars($name)."</a> ";
}

// ���Ф������� (����HTML���������)
function make_heading(&$str,$strip=TRUE)
{
	global $NotePattern;
	
	// ���Ф��θ�ͭID������
	$id = '';
	if (preg_match('/^(\*{0,3})(.*?)\[#([A-Za-z][\w-]+)\](.*?)$/m',$str,$matches))
	{
		$str = $matches[2].$matches[4];
		$id = $matches[3];
	}
	else
	{
		$str = preg_replace('/^\*{0,3}/','',$str);
	}
	if ($strip)
	{
		$str = strip_htmltag(make_link(preg_replace($NotePattern,'',$str)));
	} 
	
	return $id; 
}

// �ơ��֥�������Ѥ�Ϣ��
function table_inc_add ($arytable)
{
	//+-�ǰϤ�����ϡ�Ʊ���������ơ��֥������ҤˤǤ��롣
	$td_level = 0 ;
	$lines_tmp = array();
	$td_tmp = "";
	foreach($arytable as $td){
		if (preg_match("/^\}([^|]*)$/",$td,$reg)) {
			$td_level += 1;
			if ($td_level == 1) $td = $reg[1];
		}
		if (preg_match("/^([^|]*)\{$/",$td,$reg)) {
			$td_level -= 1;
			if ($td_level == 0) $td = $reg[1];
		}
		if ($td_level) {
			if ($td_level == 1){
				//ɽ��Ǥ��뤫��Ƚ��
				if (preg_match("/^.*___td_br___$/",$td) || preg_match("/^___td_br___.*$/",$td)) {
					$rep_str = "\n";
				} else {
					$rep_str = "->\n";
				}
				$td = preg_replace("/___td_br___([ #\-+*]|(___td_br___)+)/e","str_replace('___td_br___','$rep_str','$0')",$td);
				$td_tmp .= str_replace("~___td_br___","~$rep_str",$td)."|";//ok
				
			} else {
				$td_tmp .= str_replace("___td_br___","->\n",$td)."|";
			}
		} else {
			$td_tmp .= $td;//ok
			$lines_tmp[] = $td_tmp;
			$td_tmp = "";
		}
	}
	return $lines_tmp;
}
//�Խ����¥ե�����
function allow_edit_form($allow_groups=NULL,$allow_users=NULL) {
	//global $xoopsUser;
	global $wiki_writable,$X_uid,$vars;
	global $_btn_allow_memo,$_btn_allow_header,$_btn_allow_group,$_btn_allow_user,$_btn_allow_memo_t,$_btn_allow_deny,$freeze_tag;
	global $defvalue_gids,$defvalue_aids;

	//�ڡ������Խ����¤�����
	if (is_null($allow_groups) || is_null($allow_users)) $allows = get_pg_allow_editer($vars['page']);
	if (is_null($allow_groups)) $allow_groups = ($allows['group'])? explode(",",$allows['group']) : explode(",",$defvalue_gids,",");
	if (is_null($allow_users)) $allow_users = ($allows['user'])? explode(",",$allows['user']) : explode(",",$defvalue_aids.",");
	//�����Ȥ�����ԲĤ�����ξ��֥����ȡץ��롼�פΥ�å�������ɽ�����ʤ�
	//$_btn_allow_guest = ($wiki_writable === 0)? $_btn_allow_guest : "";
	
	$ret = "<hr>";
	$ret .= "<table class='style_table'><tr><th colspan='3'>$freeze_tag</th></tr>";
	$ret .= "<tr><th class='style_th'>$_btn_allow_group</th><th class='style_th'>$_btn_allow_user</th><th class='style_th'>$_btn_allow_memo_t</th></tr>";
	$ret .= "<tr><td class='style_td'>";

	if ($wiki_writable !== 2){
		$groups = X_get_group_list();
		$mygroups = X_get_groups();
	}

	// ���롼�פ�̾���򥵥��Ȥ�����˽񤭴���
	//$_btn_allow_memo = str_replace("_GUEST_ALLOW_",$_btn_allow_guest,$_btn_allow_memo);
	$_btn_allow_memo = str_replace("_LOGDINUSER_",$groups[2],$_btn_allow_memo);
	//$_btn_allow_memo = str_replace("_GUREST_",$groups[3],$_btn_allow_memo);

	// ���롼�װ���ɽ��
	$ret .= "<select  size='10' name='gids[]' id='gids[]' multiple='multiple'>";
	if (!is_array($allow_groups)){
		$sel = " selected";
	} else {
		if (in_array("0",$allow_groups))
		{
			$sel = " selected";
			$allow_groups = array();
		}
		else
			$sel = "";
	}
	$ret .= "<option value='0'$sel>$_btn_allow_deny</option>";
	foreach ($groups as $gid => $gname){
		if ($gid !== 1 && $gid !== 3 && in_array($gid,$mygroups)){
			$sel = (in_array($gid,$allow_groups))? " selected" : "";
			$ret .= "<option value='".$gid."'".$sel.">$gname</option>";
		}
	}
	$ret .= "</select></td>";
	$ret .= "<td class='style_td'>";
	
	if ($wiki_writable !== 2){
		$allusers = X_get_users();
		//asort($allusers);
	}

	// �桼������ɽ��
	$ret .= "<select  size='10' name='aids[]' id='aids[]' multiple='multiple'>";
	if (!is_array($allow_users)){
		$sel = " selected";
	} else {
		if (in_array("0",$allow_users))
		{
			$sel = " selected";
			$allow_users = array();
		}
		else
			$sel = "";
	}
	$ret .= "<option value='0' $sel>$_btn_allow_deny</option>";
	foreach ($allusers as $uid => $uname){
			$sel = (in_array($uid,$allow_users))? " selected" : "";
			if ($uid != $X_uid) $ret .= "<option value='".$uid."'$sel>$uname</option>";
	}
	$ret .= "</select></td><td class='style_td'>".$_btn_allow_memo."</td></tr></table>";
	
	return $ret;

}

//�������¥ե�����
function allow_view_form($allow_groups=NULL,$allow_users=NULL) {
	//global $xoopsUser;
	global $wiki_writable,$X_uid,$vars;
	global $_btn_allow_header,$_btn_allow_group,$_btn_allow_user,$_btn_allow_deny;
	global $unvisible_tag,$_btn_unvisible_enable,$_btn_allow_memo_t,$_btn_v_allow_memo;
	//global $defvalue_gids,$defvalue_aids;

	//�ڡ����α������¤�����
	if (is_null($allow_groups) || is_null($allow_users)) $allows = get_pg_allow_viewer(strip_bracket($vars['page']),true);
	if (is_null($allow_groups)) $allow_groups = explode(",",$allows['group']);
	if (is_null($allow_users)) $allow_users = explode(",",$allows['user']);
	//�����Ȥ�����ԲĤ�����ξ��֥����ȡץ��롼�פΥ�å�������ɽ�����ʤ�
	//$_btn_allow_guest = ($wiki_writable === 0)? $_btn_allow_guest : "";
	
	$ret = "<hr>";
	$ret .= "<table class='style_table'><tr><th colspan='3'>$unvisible_tag</th></tr>";
	$ret .= "<tr><th class='style_th'>$_btn_allow_group</th><th class='style_th'>$_btn_allow_user</th><th class='style_th'>$_btn_allow_memo_t</th></tr>";
	$ret .= "<tr><td class='style_td'>";

	//if ($wiki_writable !== 2){
		$groups = X_get_group_list();
		$mygroups = X_get_groups();
	//}

	// ���롼�פ�̾���򥵥��Ȥ�����˽񤭴���
	//$_btn_allow_memo = str_replace("_GUEST_ALLOW_",$_btn_allow_guest,$_btn_allow_memo);
	$_btn_v_allow_memo = str_replace("_LOGDINUSER_",$groups[2],$_btn_v_allow_memo);
	$_btn_v_allow_memo = str_replace("_GUREST_",$groups[3],$_btn_v_allow_memo);

	// ���롼�װ���ɽ��
	$ret .= "<select  size='10' name='v_gids[]' id='v_gids[]' multiple='multiple'>";
	if (!is_array($allow_groups)){
		$sel = " selected";
	} else {
		if (in_array("0",$allow_groups))
		{
			$sel = " selected";
			$allow_groups = array();
		}
		else
			$sel = "";
	}
	$ret .= "<option value='0'$sel>$_btn_allow_deny</option>";
	foreach ($groups as $gid => $gname){
		//if ($gid !== 1 && $gid !== 3 && in_array($gid,$mygroups)){
		if ($gid !== 1 && in_array($gid,$mygroups) || $gid == 3){
			$sel = (in_array($gid,$allow_groups))? " selected" : "";
			$ret .= "<option value='".$gid."'".$sel.">$gname</option>";
		}
	}
	$ret .= "</select></td>";
	$ret .= "<td class='style_td'>";
	
	//if ($wiki_writable !== 2){
		$allusers = X_get_users();
		//asort($allusers);
	//}

	// �桼������ɽ��
	$ret .= "<select  size='10' name='v_aids[]' id='v_aids[]' multiple='multiple'>";
	if (!is_array($allow_users)){
		$sel = " selected";
	} else {
		if (in_array("0",$allow_users))
		{
			$sel = " selected";
			$allow_users = array();
		}
		else
			$sel = "";
	}
	$ret .= "<option value='0' $sel>$_btn_allow_deny</option>";
	foreach ($allusers as $uid => $uname){
			$sel = (in_array($uid,$allow_users) || in_array("all",$allow_users))? " selected" : "";
			//if ($uid != $X_uid) $ret .= "<option value='".$uid."'$sel>$uname</option>";
			$ret .= "<option value='".$uid."'$sel>$uname</option>";
	}
	$ret .= "</select></td><td class='style_td'>".$_btn_v_allow_memo."</td></tr></table>";
	
	return $ret;

}

// ź�եե����륢�åץ��ɥե�����
function file_attache_form() {
	global $_msg_attach_filelist,$max_size,$_msg_maxsize,$_msg_attachfile,$vars,$_attach_messages;

	$max_size = number_format(MAX_FILESIZE/1000);
	$max_size.= "KB";

	$ret.= "<input type=\"hidden\" name=\"refer\" value=\"".htmlspecialchars($vars["page"])."\">\n";
	$ret.= "<input type=\"hidden\" name=\"max_file_size\" value=\"".MAX_FILESIZE."\" />\n";
	$ret.= "<b>".$_msg_attachfile."</b>:<input type=\"file\" name=\"attach_file\" />";
	$ret .= "<span class=\"small\"><input type=\"checkbox\" id=\"copyright\" name=\"copyright\" value=\"1\" /><label for=\"copyright\">{$_attach_messages['msg_copyright_s']}</label>|";
	$ret.= "Max[$max_size]</span>\n";

	return $ret;
}

// �ե���Ȼ���JavaScript
function fontset_js_tag()
{
	return <<<EOD
<script type="text/javascript">
<!--
	pukiwiki_show_fontset_img();
-->
</script>
EOD;
}

?>