<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: ping.php,v 1.3 2004/05/13 14:10:39 nao-pon Exp $
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

$page = strip_bracket(mb_convert_encoding(trim($arg),SOURCE_ENCODING,"AUTO"));

$vars["page"] = add_bracket($page);
$get["page"] = $post["page"] = $vars["page"];

$filename = CACHE_DIR.encode($page).".tbf";

if (file_exists($filename))
{
	unlink($filename);
	
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
}

header("Content-Type: image/gif");
readfile('image/transparent.gif');

exit;
?>