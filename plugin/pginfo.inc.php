<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: pginfo.inc.php,v 1.12 2006/06/22 02:44:47 nao-pon Exp $
//

// ��å���������
function plugin_pginfo_init()
{
	$messages = array(
		'_links_messages'=>array(
			'title_update'  => '�ڡ�������DB����',
			'msg_adminpass' => '�����ԥѥ����',
			'msg_all' => 'DB�򤹤٤ƽ����&������',
			'msg_select' => '�ʲ��������򤷤ƽ����&������',
			'msg_hint' => '���Ƴ�����Ϥ��٤Ƥ˥����å���Ĥ��Ƽ¹Ԥ��Ƥ���������',
			'msg_init' => '�ڡ������ܾ���DB',
			'msg_count' => '�ڡ��������󥿡�����DB',
			'msg_noretitle' => '��¸�Υڡ����ϥ����ȥ������ݻ����롣',
			'msg_retitle' => '��¸�Υڡ����⥿���ȥ�����Ƽ������롣',
			'msg_plain_init' => '�����ѥƥ�����DB �� �ڡ����֥�󥯾���DB',
			'msg_plain_init_notall' => '�����ѥƥ�����DB�����Υڡ����Τ߽������롣',
			'msg_plain_init_all' => '���٤ƤΥڡ�����������롣(���֤��ݤ���ޤ���)',
			'msg_attach_init' => 'ź�եե��������DB',
			'msg_progress_report' => '��Ľ����:',
			'msg_now_doing' => '�����������С�¦�ǽ�����Ǥ���<br />���ο�Ľ���̤ˡ֤��٤Ƥν�������λ���ޤ������פ�ɽ�������ޤ�<br />���Υڡ����򳫤����ޤޤˤ����֤��Ƥ���������',
			'msg_next_do' => '<span style="color:blue;">�����С��μ¹Ի������¤ˤ����������Ǥ��ޤ�����<br />���ο�Ľ���̺ǲ����Ρ�³���ν�����¹ԡפ򥯥�å�����<br />����³��������ԤäƤ���������</span>',
			'btn_submit'    => '�¹�',
			'btn_next_do'    => '³���ν�����¹�',
			'msg_done'      => '���٤Ƥν�������λ���ޤ�����',
			'msg_usage'     => "
* ��������

:�ڡ�������DB�򹹿�:���ƤΥڡ����򥹥���󤷡��ڡ�������DB��ƹ��ۤ��ޤ���

* ���
�¹ԤˤϿ�ʬ��������⤢��ޤ����¹ԥܥ���򲡤������ȡ����Ф餯���Ԥ�����������

�ޤ������Υ����С���PHP�¹Ի��֤� &font(red,b){%1d}; �ä����ꤵ��Ƥ��ޤ���~
�����Τ��� &font(red,b){%2d}; �ø头�Ȥ˽������ö���Ǥ�����³���ν�����¹ԡץܥ����ɽ�����ޤ���~
���Υܥ���ɽ�����줿���ϡ�ɬ�����Υܥ���򥯥�å����ơ��Ǹ�ޤǽ�����ԤäƤ���������

* �¹�
[�¹�]�ܥ���� ''1��Τ�'' ����å����Ƥ���������~
���β��˼¹ԥܥ���ɽ������Ƥ��ʤ����ϡ������Ը��¤ǥ����󤷤ƺ�ɽ�����Ƥ���������

&font(red,b){*}; ���դ��Ƥ�����ܤϡ������˹Ԥä���������λ���Ƥ��ʤ����ܤǤ���
"
		)
	);
	set_plugin_messages($messages);
}

function plugin_pginfo_action()
{
	error_reporting(E_ERROR);
	
	global $script,$vars,$post,$adminpass,$foot_explain;
	global $_links_messages,$X_admin;
	
	@set_time_limit(120);
	
	if (empty($post['action']) or !$X_admin)
	{
		$_links_messages['msg_usage'] = str_replace(array("%1d","%2d"),array(ini_get('max_execution_time'),ini_get('max_execution_time') - 5),$_links_messages['msg_usage']);
		$body = convert_html($_links_messages['msg_usage']);
	if ($X_admin)
	{
		$not = array();
		foreach(array("i","c","p","a") as $type)
		{
			if (file_exists(CACHE_DIR."pginfo_".$type.".dat"))
			{
				$not[$type] = '<span style="color:red;font-weight:bold;"> *</span>';
			}
			else
			{
				$not[$type] = "";
			}
		}
		$body .= <<<__EOD__
<script>
<!--
var pwm_pginfo_doing = false;
var pwm_pginfo_timerID;
function pwm_pginfo_done()
{
	document.getElementById('pwm_pginfo_submit').disabled = false;
}
function pwm_pginfo_blink(mode)
{
	var timer;
	clearTimeout(pwm_pginfo_timerID);
	
	if (mode == 'stop')
	{
		pwm_pginfo_doing = false;
	}
	else
	{
		pwm_pginfo_doing = true;
	}
	
	if (!pwm_pginfo_doing || document.getElementById('pwm_pginfo_doing').style.visibility == "visible")
	{
		document.getElementById('pwm_pginfo_doing').style.visibility = "hidden";
		timer = 200;
	}
	else
	{
		document.getElementById('pwm_pginfo_doing').style.visibility = "visible";
		timer = 800;
	}
	
	if (mode == 'start') {pwm_pginfo_setmsg('pwm_pginfo_doing','{$_links_messages['msg_now_doing']}');}
	
	if (mode == 'continue')
	{
		pwm_pginfo_setmsg('pwm_pginfo_doing','{$_links_messages['msg_next_do']}');
		document.getElementById('pwm_pginfo_doing').style.visibility = "visible";
	}
	
	if (pwm_pginfo_doing && mode != 'continue')
	{
		pwm_pginfo_timerID = setTimeout("pwm_pginfo_blink()", timer);
	}
}
function pwm_pginfo_setmsg(id,msg)
{
	document.getElementById(id).innerHTML = msg;
}
-->
</script>
<form target="pukiwiki_pginfo_work" style= "margin:0px;" method="POST" action="$script">
 <div>
  <input type="hidden" name="plugin" value="pginfo" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="mode" value="select" />
  {$_links_messages['msg_hint']}
  <div style="margin-left:20px;">
  <input type="checkbox" name="init" value="on" checked="true" />{$_links_messages['msg_init']}{$not['i']}<br />
  &nbsp;&#9500;<input type="radio" name="title" value="" checked="true" />{$_links_messages['msg_noretitle']}<br />
  &nbsp;&#9495;<input type="radio" name="title" value="on" />{$_links_messages['msg_retitle']}<br />
  <input type="checkbox" name="count" value="on" checked="true" />{$_links_messages['msg_count']}{$not['c']}<br />
  <input type="checkbox" name="plain" value="on" checked="true" />{$_links_messages['msg_plain_init']}{$not['p']}<br />
  &nbsp;&#9500;<input type="radio" name="plain_all" value="" checked="true" />{$_links_messages['msg_plain_init_notall']}<br />
  &nbsp;&#9495;<input type="radio" name="plain_all" value="on" />{$_links_messages['msg_plain_init_all']}<br />
  <input type="checkbox" name="attach" value="on" checked="true" />{$_links_messages['msg_attach_init']}{$not['a']}<br />
 </div>
  <br />
  <input id="pwm_pginfo_submit" type="submit" value="{$_links_messages['btn_submit']}" onClick="pwm_pginfo_blink('start');return true;" />
 </div>
</form>
<div id="pwm_pginfo_doing" style="color:red;background-color:white;visibility:hidden;width:500px;text-align:center;">{$_links_messages['msg_now_doing']}</div>
<div>{$_links_messages['msg_progress_report']}</div>
<iframe src="" height="350" width="500" name="pukiwiki_pginfo_work"></iframe>
__EOD__;
	}
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$body
		);
	}
	else if ($post['action'] == 'update' && ($post['mode'] == 'all' || $post['mode'] == 'select'))
	{
		//error_reporting(E_ALL);
		$post['start_time'] = time();
		
		header ("Content-Type: text/html; charset="._CHARSET);
		// mod_gzip ��̵���ˤ��륪�ץ����(�ץ����С�¦����)
		header('X-NoGzip: 1');
		
		// ���Ϥ�Хåե���󥰤��ʤ�
		ob_end_clean();
		echo str_pad('',256);//for IE
		ob_implicit_flush(true);
		
		echo <<<__EOD__
<html>
<head>
</head>
<script>
var pukiwiki_root_url ="./";
</script>
<script type="text/javascript" src="skin/default.ja.js"></script>
<body>
__EOD__;
		
		$post['init'] = (!empty($post['init']))? "on" : "";
		$post['count'] = (!empty($post['count']))? "on" : "";
		$post['title'] = (!empty($post['title']))? "on" : "";
		$post['plain'] = (!empty($post['plain']))? "on" : "";
		$post['plain_all'] = (!empty($post['plain_all']))? "on" : "";
		$post['attach'] = (!empty($post['attach']))? "on" : "";
		
		if ($post['mode'] == 'all' || $post['init']) pginfo_db_init();
		if ($post['mode'] == 'all' || $post['count']) count_db_init();
		//if ($post['mode'] == 'all' || $post['title']) pginfo_db_retitle();
		if ($post['mode'] == 'all' || $post['plain']) plain_db_init();
		if ($post['mode'] == 'all' || $post['attach']) attach_db_init();
		
		//redirect_header("$script?plugin=pginfo",3,$_links_messages['msg_done']);
		echo $_links_messages['msg_done'];
		echo "<script>parent.pwm_pginfo_done();parent.pwm_pginfo_blink('stop');</script>";
		echo "</body></html>";
		exit();
	}
	
	return array(
		'msg'=>$_links_messages['title_update'],
		'body'=>$_links_messages['err_invalid']
	);
}

// �ڡ�������ǡ����١��������
function pginfo_db_init()
{
	global $xoopsDB,$whatsnew,$post;
	
	if ($dir = @opendir(DATA_DIR))
	{
		//name �ơ��֥��°���� BINARY �˥��å�(��������ʸ������ʸ������̤���)
		$query = 'ALTER TABLE `'.$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo").'` CHANGE `name` `name` VARCHAR( 255 ) BINARY NOT NULL ';
		$result=$xoopsDB->queryF($query);
		
		// �ڡ����������¤Υ���å���򥯥ꥢ��
		get_pg_allow_viewer("",false,true);
		
		// �����ѥե�����ǡ�����
		$work = CACHE_DIR."pginfo_i.dat";
		$domix = $dones = array();
		$done = 0;
		if (file_exists($work))
		{
			$dones = unserialize(join('',file($work)));
			if (!isset($dones[1])) $dones[1] = array();
			$docnt = count($dones[1]);
			$domix = array_merge($dones[0],$dones[1]);
			$done = count($domix);
		}
		if ($done)
		{
			echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo' Already converted {$docnt} pages.</b></div>";
		}
		
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo' Now converting... </b>( * = 10 Pages)<hr>";
		
		$fcounter = $counter = 0;
		
		$files = array();
		while($file = readdir($dir))
		{
			$files[] = $file;
		}
		
		foreach(array_diff($files,$domix) as $file)
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE)
			{
				$dones[0][] = $file;
				continue;
			}
			
			$name=$aids=$gids=$vaids=$vgids= "";
			$buildtime=$editedtime=$lastediter=$uid=$freeze=$unvisible = 0;
			
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));

			if ($page === $whatsnew)
			{
				$dones[0][] = $file;
				@unlink(DATA_DIR.$file);
				continue;
			}
			
			$name = strip_bracket($page);
			
			// id����
			$id = get_pgid_by_name($page);
			
			$name = addslashes($name);
			$buildtime = filectime(DATA_DIR.$file);
			$editedtime = filemtime(DATA_DIR.$file);
			if (!$buildtime || $buildtime > $editedtime) $buildtime = $editedtime;
			
			$checkpostdata = join("",get_source($page));
			if (!$checkpostdata)
			{
				@unlink(DATA_DIR.$file);
				continue;
			}
			
			//echo $page."<hr />";
			// �Խ�����
			$arg = array();
			if (preg_match("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
			{
				$freeze = 1;
				if (isset($arg[1])) $uid = $arg[1];
				if (isset($arg[2])) $aids = "&".str_replace(",","&",$arg[2])."&";
				if (isset($arg[3])) $gids = "&".str_replace(",","&",$arg[3])."&";
				$checkpostdata = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
			}
			else
			{
				$aids = "&all";
				$gids = "&3&";
				$freeze = 0;
			}
			
			// ��������
			if (preg_match("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/",$checkpostdata,$arg))
			{
				$unvisible = 1;
				$checkpostdata = preg_replace("/^#unvisible(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+|all))?(?:\tgid:([0-9,]+))?\n/","",$checkpostdata);
			}
			$info = get_pg_allow_viewer($page);
			if (!isset($uid)) $uid = $info['owner'];
			$vaids = "&".str_replace(",","&",$info['user']);
			$vgids = "&".str_replace(",","&",$info['group']);

			// �ڡ��������� ���ڡ����˵��ܤ�����Ф����ͤ����
			$author_uid = 0;
			if (preg_match("/^\/\/ author:([0-9]+)($|\n)/",$checkpostdata,$arg))
				if (!isset($uid)) $uid = $arg[1];
			
			if (!isset($uid) || $uid ==="") $uid = 0;
			$lastediter = $uid;
			
			// �����ȥ����
			$title = "";
			if (!$id || !empty($post['title']) || !get_heading($page, true))
			{
				$title = addslashes(str_replace(array('&lt;','&gt;','&amp;','&quot;','&#039;'),array('<','>','&','"',"'"),get_heading_init($page)));
			}
			
			if (!$id)
			{
				// ��������
				$query = "insert into ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." (`name`,`buildtime`,`editedtime`,`aids`,`gids`,`vaids`,`vgids`,`lastediter`,`uid`,`freeze`,`unvisible`,`update`) values('$name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible,1);";
			}
			else
			{
				// ���åץǡ���
				if ($title)
				{
					$title = ",`title`='$title'";
				}
				$value = "`name`='$name'"
				.",`buildtime`='$buildtime'"
				.",`editedtime`='$editedtime'"
				.",`lastediter`='$lastediter'"
				.$title
				.",`aids`='$aids'"
				.",`gids`='$gids'"
				.",`vaids`='$vaids'"
				.",`vgids`='$vgids'"
				.",`uid`='$uid'"
				.",`freeze`='$freeze'"
				.",`unvisible`='$unvisible'"
				.",`update`='1'";
				$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET $value WHERE id = '$id' LIMIT 1;";
			}
			$result=$xoopsDB->queryF($query);
			//echo $query."<hr>";
			
			$counter++;
			$dones[1][] = $file;
			if (($counter/10) == (floor($counter/10)))
			{
				echo "*";
				
			}
			if (($counter/100) == (floor($counter/100)))
			{
				echo " ( Done ".$counter." Pages !)<br />";
				
			}
			
			if ($post['start_time'] + (ini_get('max_execution_time') - 5) < time())
			{
				// �����ѥե�����ꥹ����¸
				if ($fp = fopen($work,"wb"))
				{
					fputs($fp,serialize($dones));
					fclose($fp);
				}
				closedir($dir);
				plugin_pginfo_next_do();
			}
		}
		closedir($dir);
		
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";
		
		// ���åץǡ��Ȥ��ʤ��ä��ڡ�������(�ƥ����ȥե����뤬�ʤ��ڡ���)�����Ѥ�(editedtime=0)�ˤ���
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET `editedtime` = '0' WHERE `update` = '0';";
		$result=$xoopsDB->queryF($query);
		
		// ���åץǡ��ȥե饰�ᤷ
		$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET `update` = '0';";
		$result=$xoopsDB->queryF($query);
		
		@unlink ($work);
	}
	$post['init'] = "";
}

// �ڡ��������󥿡��ǡ����١��������
function count_db_init()
{
	global $xoopsDB,$whatsnew,$post;
	
	// ������Ⱦ���
	if ($dir = @opendir(COUNTER_DIR))
	{
		// �����ѥե�����ꥹ�ȥǡ�����
		$work = CACHE_DIR."pginfo_c.dat";
		$domix = $dones = array();
		$done = 0;
		if (file_exists($work))
		{
			$dones = unserialize(join('',file($work)));
			if (!isset($dones[1])) $dones[1] = array();
			$docnt = count($dones[1]);
			$domix = array_merge($dones[0],$dones[1]);
			$done = count($domix);
		}
		if ($done)
		{
			echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_counter' Already converted {$docnt} pages.</b></div>";
		}
		else
		{
			$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count");
			$result=$xoopsDB->queryF($query);
		}
		
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_counter' Now converting... </b>( * = 10 Pages)<hr>";
		
		
		$counter = 0;
		
		$files = array();
		while($file = readdir($dir))
		{
			$files[] = $file;
		}
		
		foreach(array_diff($files,$domix) as $file)
		{
			if($file == ".." || $file == "." || strstr($file,".count")===FALSE)
			{
				$dones[0][] = $file;
				continue;
			}
			
			$name=$today=$ip="";
			$count=$today_count=$yesterday_count=0;
			
			$page = decode(trim(preg_replace("/\.count$/"," ",$file)));
			// ¸�ߤ��ʤ��ڡ���
			if ($page == $whatsnew || !file_exists(DATA_DIR.encode($page).".txt"))
			{
				@unlink(COUNTER_DIR.$file);
				$dones[0][] = $file;
				continue;
			}
			
			$array = file(COUNTER_DIR.$file);
			$name = addslashes(strip_bracket($page));
			$count = rtrim($array[0]);
			$today = rtrim($array[1]);
			$today_count = rtrim($array[2]);
			$yesterday_count = rtrim($array[3]);
			$ip = rtrim($array[4]);
			
			$query = "insert into ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_count")." (name,count,today,today_count,yesterday_count,ip) values('$name',$count,'$today',$today_count,$yesterday_count,'$ip');";
			$result=$xoopsDB->queryF($query);
			
			$counter++;
			if (($counter/10) == (floor($counter/10)))
			{
				echo "*";
				
			}
			if (($counter/100) == (floor($counter/100)))
			{
				echo " ( Done ".$counter." Pages !)<br />";
				
			}
			
			$dones[1][] = $file;
			
			if ($post['start_time'] + (ini_get('max_execution_time') - 5) < time())
			{
				// �����ѥե�����ꥹ����¸
				if ($fp = fopen($work,"wb"))
				{
					fputs($fp,serialize($dones));
					fclose($fp);
				}
				closedir($dir);
				plugin_pginfo_next_do();
			}
		}
		closedir($dir);
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";
		
		@unlink ($work);
	}
	$post['count'] = "";
}

/*
//title�ե�����ɺ�����
function pginfo_db_retitle()
{
	global $xoopsDB,$whatsnew,$post,$related;
	
	if ($dir = @opendir(DATA_DIR))
	{
		// �����ѥե�����ꥹ�ȥǡ�����
		$work = CACHE_DIR."pginfo_t.dat";
		$domix = $dones = array();
		$done = 0;
		if (file_exists($work))
		{
			$dones = unserialize(join('',file($work)));
			if (!isset($dones[1])) $dones[1] = array();
			$docnt = count($dones[1]);
			$domix = array_merge($dones[0],$dones[1]);
			$done = count($domix);
		}
		if ($done)
		{
			echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo(only title)' Already converted {$docnt} pages.</b></div>";
		}
		
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo(only title)' Now converting... </b>( * = 10 Pages)<hr>";
		
		
		$counter = 0;
		
		$files = array();
		while($file = readdir($dir))
		{
			$files[] = $file;
		}
		
		foreach(array_diff($files,$domix) as $file)
		{
			$fcounter ++;
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE)
			{
				$dones[0][] = $file;
				continue;
			}
			
			$related = array();
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			$post['page'] = $page;
			
			if($page === $whatsnew)
			{
				$dones[0][] = $file;
				continue;
			}
			$title = addslashes(str_replace(array('&lt;','&gt;','&amp;','&quot;','&#039;'),array('<','>','&','"',"'"),get_heading_init($page)));
			$name = addslashes(strip_bracket($page));
			$value = "title='$title'";
			$query = "UPDATE ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_pginfo")." SET $value WHERE name = '$name';";
			$result=$xoopsDB->queryF($query);
			
			$counter++;
			$dones[1][] = $file;
			if (($counter/10) == (floor($counter/10)))
			{
				echo "*";
				
			}
			if (($counter/100) == (floor($counter/100)))
			{
				echo " ( Done ".$counter." Pages !)<br />";
				
			}
			
			if ($post['start_time'] + (ini_get('max_execution_time') - 5) < time())
			{
				// �����ѥե�����ꥹ����¸
				if ($fp = fopen($work,"wb"))
				{
					fputs($fp,serialize($dones));
					fclose($fp);
				}
				closedir($dir);
				plugin_pginfo_next_do();
			}
		}
		closedir($dir);
		$post['page'] = "";
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";
		
		@unlink ($work);
	}
	$post['title'] = "";
}
*/

// ������ plain DB ������
function plain_db_init()
{
	global $xoopsDB,$whatsnew,$vars,$post,$get,$related,$comment_no;
	
	if ($dir = @opendir(DATA_DIR))
	{
		// �����ѥե�����ꥹ�ȥǡ�����
		$work = CACHE_DIR."pginfo_p.dat";
		$domix = $dones = array();
		$done = 0;
		if (file_exists($work))
		{
			$dones = unserialize(join('',file($work)));
			if (!isset($dones[1])) $dones[1] = array();
			$docnt = count($dones[1]);
			$domix = array_merge($dones[0],$dones[1]);
			$done = count($domix);
		}
		if ($done)
		{
			echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_plain' Already converted {$docnt} pages.</b></div>";
		}
		
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_plain' Now converting... </b>( * = 10 Pages)<hr>";
		
		
		$counter = 0;
		
		$files = array();
		while($file = readdir($dir))
		{
			$files[] = $file;
		}
		
		$vars['from_pginfo_init'] = true;
		
		foreach(array_diff($files,$domix) as $file)
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE)
			{
				$dones[0][] = $file;
				continue;
			}
			
			$related = array();
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));
			$vars['page']=$get['page']=$post['page'] = $page;
			$comment_no = 0;
			
			if($page === $whatsnew)
			{
				$dones[0][] = $file;
				continue;
			}
			
			$id = get_pgid_by_name($page);
			$query = "SELECT plain FROM `".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_plain")."` WHERE `pgid` = ".$id.";";
			$result = $xoopsDB->query($query);
			if (mysql_num_rows($result))
			{
				list($text) = mysql_fetch_row ( $result );
				if ($text && !$post['plain_all'])
				{
					$dones[0][] = $file;
					continue;
				}
				$mode = "update";
			}
			else
			{
				$mode = "insert";
			}
			
			if (plain_db_write($page,$mode))
			{
				$dones[1][] = $file;
				$counter++;
				if (($counter/10) == (floor($counter/10)))
				{
					echo "*";
					
				}
				if (($counter/100) == (floor($counter/100)))
				{
					echo " ( Done ".$counter." Pages !)<br />";
					
				}
			}
			else
			{
				$dones[0][] = $file;
			}
			
			if ($post['start_time'] + (ini_get('max_execution_time') - 5) < time())
			{
				// �����ѥե�����ꥹ����¸
				if ($fp = fopen($work,"wb"))
				{
					fputs($fp,serialize($dones));
					fclose($fp);
				}
				closedir($dir);
				plugin_pginfo_next_do();
			}
		}
		closedir($dir);
		$vars['page']=$get['page']=$post['page'] = "";
		$post['plain'] = "";
		echo " ( Done ".$counter." Pages !)<hr />";
		echo "</div>";
		
		@unlink ($work);
	}
}

// ź�եե����� DB ������
function attach_db_init()
{
	global $xoopsDB,$vars,$post,$get;
	
	if ($dir = @opendir(UPLOAD_DIR))
	{
		// �����ѥե�����ꥹ�ȥǡ�����
		$work = CACHE_DIR."pginfo_a.dat";
		$domix = $dones = array();
		$done = 0;
		if (file_exists($work))
		{
			$dones = unserialize(join('',file($work)));
			if (!isset($dones[1])) $dones[1] = array();
			$docnt = count($dones[1]);
			$domix = array_merge($dones[0],$dones[1]);
			$done = count($domix);
		}
		if ($done)
		{
			echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_attach' Already converted {$docnt} pages.</b></div>";
		}
		else
		{
			$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod".PUKIWIKI_DIR_NUM."_attach");
			$result=$xoopsDB->queryF($query);
		}
		echo "<div style=\"font-size:14px;\"><b>DB 'pukiwikimod".PUKIWIKI_DIR_NUM."_attach' Now converting... </b>( * = 10 Pages)<hr>";
		
		
		include_once(PLUGIN_DIR."attach.inc.php");
		
		$counter = 0;
		
		$page_pattern = '(?:[0-9A-F]{2})+';
		$age_pattern = '(?:\.([0-9]+))?';
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";
		
		$files = array();
		while($file = readdir($dir))
		{
			$files[] = $file;
		}
		
		foreach(array_diff($files,$domix) as $file)
		{
			$matches = array();
			if (!preg_match($pattern,$file,$matches))
			{
				$dones[0][] = $file;
				continue;
			}
			$page = decode($matches[1]);
			$name = decode($matches[2]);
			$age = array_key_exists(3,$matches) ? $matches[3] : 0;
			
			// ����ͥ���Ͻ���
			if (preg_match("/^\d\d?%/",$name))
			{
				$dones[0][] = $file;
				continue;
			}
			
			$obj = &new AttachFile($page,$name,$age);
			$obj->getstatus();
			
			$data['pgid'] = get_pgid_by_name($page);
			$data['name'] = $name;
			$data['mtime'] = $obj->time;
			$data['size'] = $obj->size;
			$data['type'] = $obj->type;
			$data['status'] = $obj->status;

			if (attach_db_write($data,"insert"))
			{
				$counter++;
				$dones[1][] = $file;
				if (($counter/10) == (floor($counter/10)))
				{
					echo "*";
					
				}
				if (($counter/100) == (floor($counter/100)))
				{
					echo " ( Done ".$counter." Files !)<br />";
					
				}
			}
			else
			{
				$dones[0][] = $file;
			}
			
			if ($post['start_time'] + (ini_get('max_execution_time') - 5) < time())
			{
				// �����ѥե�����ꥹ����¸
				if ($fp = fopen($work,"wb"))
				{
					fputs($fp,serialize($dones));
					fclose($fp);
				}
				closedir($dir);
				plugin_pginfo_next_do();
			}
		}
		closedir($dir);
		echo " ( Done ".$counter." Files !)<hr />";
		echo "</div>";
		
		@unlink ($work);
	}
}

function plugin_pginfo_next_do()
{
	global $script,$post,$_links_messages;
	
	$token = get_token_html();
	
	$html = <<<__EOD__
<form method="POST" action="{$script}" onsubmit="return pukiwiki_check(this);">
 <div>
  {$token}
  <input type="hidden" name="encode_hint" value="��" />
  <input type="hidden" name="plugin" value="pginfo" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="mode" value="select" />
  <input type="hidden" name="init" value="{$post['init']}" />
  <input type="hidden" name="title" value="{$post['title']}" />
  <input type="hidden" name="plain" value="{$post['plain']}" />
  <input type="hidden" name="plain_all" value="{$post['plain_all']}" />
  <input type="hidden" name="attach" value="{$post['attach']}" />
  <input type="submit" value="{$_links_messages['btn_next_do']}" onClick="parent.pwm_pginfo_blink('start');return true;" />
 </div>
</form>
<script>
<!--
parent.pwm_pginfo_blink('continue');
-->
</script>
</body></html>
__EOD__;
	echo $html;
	
	exit();
}

?>