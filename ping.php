<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ping.php,v 1.9 2006/02/22 12:52:09 nao-pon Exp $
/////////////////////////////////////////////////

//XOOPS�ݒ�ǂݍ���
include("../../mainfile.php");

// �v���O�����t�@�C���ǂݍ���
require("func.php");
require("file.php");
require("plugin.php");
require("template.php");
require("convert_html.php");
require("html.php");
require("backup.php");
require("rss.php");
require('make_link.php');
require('config.php');
require('link.php');
require('proxy.php');
require('db_func.php');
require('trackback.php');

require("init.php");
/////////////////////////////
$h_excerpt = "";

$page = strip_bracket($get['p']);
$vars["page"] = add_bracket($page);
$get["page"] = $post["page"] = $vars["page"];

$is_rsstop = (empty($get['t']))? 0 : 1;

if ($is_rsstop)
	$up_page = strip_bracket($vars["page"]);
else
{
	if (strpos($vars["page"],"/") === FALSE)
		$up_page = "";
	else
		$up_page = preg_replace("/(.+)\/[^\/]+/","$1",strip_bracket($vars["page"]));
}
$up_page = ($up_page && is_page($up_page))? "&p=".get_pgid_by_name($up_page) : "";

$rss_url = XOOPS_URL.'/modules/pukiwiki/index.php?cmd=rss10&content=s'.$up_page;

$filename = CACHE_DIR.encode($page).".tbf";

if (file_exists($filename))
{
	//��ɃQ�X�g���[�h
	$X_admin = $X_uid = 0;
	
	// ���݂��Ȃ��y�[�W or �Q�X�g�{�������Ȃ�
	if (!is_page(add_bracket($page)) || !check_readable($page,FALSE,FALSE))
	{
		unlink($filename); // ����t�@�C�����폜
		exit;
	}
	
	sleep(3); // �y�[�W���\�������܂ł�����Ƒ҂�
	
	// ���s���Ԃ𒷂߂ɐݒ�
	@set_time_limit(120);
	
	//sleep(mt_rand(40,60)); //�K���ɒx��������
	
	//�L���b�V���쐬 (�y�[�W)
	convert_html($page,false,true);
	
	//�L���b�V���쐬 (RSS)
	http_request($rss_url);
	
	sleep(2);
	
	//�\�[�X���擾
	$data = get_source($page);
	
	//�������Ȃ��v���O�C�����폜
	if ($notb_plugin)
	{
		// �O�̂��� quote
		$notb_plugin = preg_quote($notb_plugin,"/");
		// ���K�\���`����
		$notb_plugin = str_replace(",","|",$notb_plugin);
		
		// �Y���v���O�C�����폜
		$data = preg_replace("/#($notb_plugin)(\(((?!#[^(]+\()(?!\),).)*\))?/","",$data);
	}

	$data = join("",$data);
	//delete_page_info($data);
	$data = convert_html($data);

	tb_send($page,$data);
	
	unlink($filename); // ����t�@�C�����폜
}

exit;
?>