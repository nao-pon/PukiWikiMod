<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: pginfo.inc.php,v 1.1 2003/10/31 12:22:59 nao-pon Exp $
//

// ��å���������
function plugin_pginfo_init()
{
	$messages = array(
		'_links_messages'=>array(
			'title_update'  => '�ڡ�������DB����',
			'msg_adminpass' => '�����ԥѥ����',
			'btn_submit'    => '�¹�',
			'msg_done'      => '�ڡ�������DB�ι�������λ���ޤ�����',
			'msg_usage'     => "
* ��������

:�ڡ�������DB�򹹿�:���ƤΥڡ����򥹥���󤷡��ڡ�������DB�������ľ���ޤ���

* ���
�¹ԤˤϿ�ʬ��������⤢��ޤ����¹ԥܥ���򲡤������ȡ����Ф餯���Ԥ�����������

* �¹�
[�¹�]�ܥ���� ''1��Τ�'' ����å����Ƥ���������~
���β��˼¹ԥܥ���ɽ������Ƥ��ʤ����ϡ������Ը��¤ǥ����󤷤ƺ�ɽ�����Ƥ���������
"
		)
	);
	set_plugin_messages($messages);
}

function plugin_pginfo_action()
{
	global $script,$post,$vars,$adminpass,$foot_explain;
	global $_links_messages,$X_admin;
	
	if (empty($vars['action']) or !$X_admin)
	{
		$body = convert_html($_links_messages['msg_usage']);
	if ($X_admin)
	{
		$body .= <<<EOD
<form method="POST" action="$script">
 <div>
  <input type="hidden" name="plugin" value="pginfo" />
  <input type="hidden" name="action" value="update" />
  <input type="submit" value="{$_links_messages['btn_submit']}" />
 </div>
</form>
EOD;
	}
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$body
		);
	}
	else if ($vars['action'] == 'update')
	{
		error_reporting(E_ALL);
		pginfo_db_init();
		
		// ������ˤ���
		$foot_explain = array();
		return array(
			'msg'=>$_links_messages['title_update'],
			'body'=>$_links_messages['msg_done']
		);
	}
	
	return array(
		'msg'=>$_links_messages['title_update'],
		'body'=>$_links_messages['err_invalid']
	);
}

// �ڡ�������ǡ����١��������
function pginfo_db_init()
{
	global $xoopsDB,$whatsnew;
	if ($dir = @opendir(DATA_DIR))
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_pginfo");
		$result=$xoopsDB->queryF($query);
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_count");
		$result=$xoopsDB->queryF($query);
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".txt")===FALSE) continue;
			
			$name=$aids=$gids=$vaids=$vgids= "";
			$buildtime=$editedtime=$lastediter=$uid=$freeze=$unvisible = 0;
			
			$page = decode(trim(preg_replace("/\.txt$/"," ",$file)));

			if ($page === $whatsnew)
			{
				@unlink($file);
				continue;
			}
			
			$name = addslashes(strip_bracket($page));
			$buildtime = filectime(DATA_DIR.$file);
			$editedtime = filemtime(DATA_DIR.$file);
			
			$checkpostdata = join("",get_source($page,3));
			//echo $page."<hr />";
			// �Խ�����
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
			$query = "insert into ".$xoopsDB->prefix("pukiwikimod_pginfo")." (name,buildtime,editedtime,aids,gids,vaids,vgids,lastediter,uid,freeze,unvisible) values('$name',$buildtime,$editedtime,'$aids','$gids','$vaids','$vgids',$lastediter,$uid,$freeze,$unvisible);";
			$result=$xoopsDB->queryF($query);
			//echo $query."<hr>";
		}
		closedir($dir);
	}
	// ������Ⱦ���
	if ($dir = @opendir(COUNTER_DIR))
	{
		$query = "DELETE FROM ".$xoopsDB->prefix("pukiwikimod_count");
		$result=$xoopsDB->queryF($query);
		while($file = readdir($dir))
		{
			if($file == ".." || $file == "." || strstr($file,".count")===FALSE) continue;
			//echo $file."<hr/>";
			
			$name=$today=$ip="";
			$count=$today_count=$yesterday_count=0;
			
			$page = decode(trim(preg_replace("/\.count$/"," ",$file)));
			if ($page == $whatsnew) continue;
			
			$array = file(COUNTER_DIR.$file);
			$name = addslashes(strip_bracket($page));
			$count = rtrim($array[0]);
			$today = rtrim($array[1]);
			$today_count = rtrim($array[2]);
			$yesterday_count = rtrim($array[3]);
			$ip = rtrim($array[4]);
			
			$query = "insert into ".$xoopsDB->prefix("pukiwikimod_count")." (name,count,today,today_count,yesterday_count,ip) values('$name',$count,'$today',$today_count,$yesterday_count,'$ip');";
			$result=$xoopsDB->queryF($query);

		}
		closedir($dir);
	}
	return ;
}
?>
