<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: showspch.inc.php,v 1.3 2005/03/09 12:18:27 nao-pon Exp $

function plugin_showspch_convert()
{
	list($name,$text) = func_get_args();
	return "<div>".plugin_showspch_tag($name,$text)."</div>";
}
function plugin_showspch_inline()
{
	list($name,$text) = func_get_args();
	return plugin_showspch_tag($name,$text);
}
function plugin_showspch_tag($name,$text="")
{
	global $vars,$script;
	
	$name = preg_replace("/\.[^\.]+$/","",$name);
	
	$text = ($text)? htmlspecialchars(str_replace('$1',$name,$text)) :"動画再生";
	
	$name .= ".spch";
	
	$page = $vars['page'];
	//相対パスからフルパスを得る
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
	if (!is_file($file)) {
		if (!is_page($page))
		{ 
			return 'page not found.';
		}
		else
		{
			return 'not found.';
		}
	}
	
	return '<a href="'.$script.'?plugin=showspch&amp;page='.rawurlencode($page).'&amp;file='.rawurlencode($name).'#viewer">'.$text.'</a>';
	
}

function plugin_showspch_action()
{
	global $vars,$noattach;
	
	$noattach = 1;
	
	$file = UPLOAD_DIR.encode($vars['page']).'_'.encode($vars['file']);
	if (!is_file($file)) {
		if (!is_page($page))
		{ 
			return array('msg'=>'page not found.');
		}
		else
		{
			return array('msg'=>'not found.');
		}
	}
	
	//spch情報読み込み
	$data = array();
	$fp = fopen ($file, "rb");
	while (!feof ($fp) || $_line)
	{
		$_line = trim(fgets($fp, 4096));
		list($prm,$val) = explode("=",$_line);
		$data[$prm] = $val;
	}
	fclose($fp);
	$width = $data['image_width'] + 10;
	$height = $data['image_height'] + 36;
	
	
	$page = convert_html("**[[".$vars['page']."]]\n***".str_replace(".spch","",$vars['file']));
	//$image = make_link("&ref(".$vars['file'].");");
	$ret['msg'] = $vars['file']."を再生";
	$ret['body'] = '<p><a name="viewer"></a>'.$page.'</p>';
	//$ret['body'] .= '<p>'.$image.'</p>';
	$ret['body'] .= '<div style="text-align:center;">
<applet
 name="pch"
 code="pch2.PCHViewer.class"
 codebase="./";
 archive="./plugin_data/painter/PCHViewer.jar"
 width="'.$width.'"
 height="'.$height.'"
>

<param name="run" value="true">
<param name="pch_file" value="'.$file.'">

<param name="buffer_progress" value="false">
<param name="buffer_canvas" value="false">

<param name="res.zip" value="./plugin_data/painter/res/res_normal.zip">
<param name="tt.zip" value="./plugin_data/painter/res/tt.zip"
<param name=tt_size value=31>
</applet>
</div>';
	
	//ページ名を破棄
	$vars['page'] = "";
	return $ret;
}
?>