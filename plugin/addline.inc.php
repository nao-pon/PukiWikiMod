<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: addline.inc.php,v 1.2 2003/10/31 12:22:59 nao-pon Exp $
// ORG: addline.inc.php,v 0.2 2003/07/29 22:47:10 sha Exp $
//
/* 
*プラグイン addline
 その場に、固定の文字列を追加する。

*Usage
 #addline(設定名[,above|below])

*パラメータ
  設定:   「:config/plugin/addline/設定」の設定名を記載
  above|below: 上か下に追加する。

*設定ページの内容
 追加する文字列を記載する。複数行でもよい。
 例：
    |&attachref;|&attachref;|&attachref;|
    |あいう|えおか|きくけ|
*/

/////////////////////////////////////////////////
// コメントを挿入する位置 1:欄の前 0:欄の後
define('ADDLINE_INS','1');

function plugin_addline_init()
{
	$messages = array(
		'_addline_messages' => array(
			'btn_submit'    => 'add',
			'title_collided' => '$1 で【更新の衝突】が起きました',
			'msg_collided'  => 'あなたが文字列を追加している間に、他の人が同じページを更新してしまったようです。<br />
文字列が違う位置に挿入されているかもしれません。<br />'
		),
	);
	set_plugin_messages($messages);
}
function plugin_addline_action()
{
	global $script,$vars,$post,$now;
	global $_title_updated;
	global $_addline_messages;
	
	$postdata = '';
	$postdata_old  = get_source($post['refer']);
	$addline_no = 0;
	$addline_ins = ($post['above'] == '1');
	$configname = $post['configname'];


	$config = new Config('plugin/addline/'.$configname);
	if (!$config->read())
	{
		return "<p>config file '".htmlspecialchars($configname)."' is not exist.";
	}
	$config->config_name = $configname;
	$addline = join('', addline_get_source($config->page));
	$addline = rtrim($addline);

	foreach ($postdata_old as $line)
	{
		if (!$addline_ins)
		{
			$postdata .= $line;
		}
		if (preg_match('/(^|\|)#addline/',$line) and $addline_no++ == $post['addline_no'])
		{
			//$postdata = rtrim($postdata)."\n$addline\n";
			$postdata = rtrim($postdata)."\n$addline\n";
			/*
			if ($addline_ins)
			{
				$postdata .= "\n";
			}
			*/
		}
		if ($addline_ins)
		{
			$postdata .= $line;
		}
	}
	
	$title = $_title_updated;
	$body = '';
	if (md5(@join('',get_source($post['refer']))) != $post['digest'])
	{
		$title = $_addline_messages['title_collided'];
		$body  = $_addline_messages['msg_collided'] . make_pagelink($post['refer']);
	}
	
	page_write($post['refer'],$postdata);
	
	$retvars['msg'] = $title;
	$retvars['body'] = $body;
	
	$post['page'] = $vars['page'] = $post['refer'];
	
	return $retvars;
}
function plugin_addline_convert()
{
	global $script,$vars,$digest;
	global $_addline_messages;
	static $numbers = array();
	
	if (!array_key_exists($vars['page'],$numbers))
	{
		$numbers[$vars['page']] = 0;
	}
	$addline_no = $numbers[$vars['page']]++;
	
	$above = ADDLINE_INS;
	$configname = 'default';
	$btn_text = $_addline_messages['btn_submit'];
	if ( func_num_args() ){
		foreach ( func_get_args() as $opt ){
			if ( $opt === 'above' || $opt === 'up' ){
				$above = 1;
			}
			else if (preg_match("/btn:(.+)/i",$opt,$args)){
				$btn_text = htmlspecialchars($args[1]);
			}
			else if ( $opt === 'below' || $opt === 'down' ){
				$above = 0;
			}
			else {
			    $configname = $opt;
			}
		}
	}
	
	$s_page = htmlspecialchars($vars['page']);
	
	$string = <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="addline_no" value="$addline_no" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="plugin" value="addline" />
  <input type="hidden" name="above" value="$above" />
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="configname"  value="$configname" />
  <input type="submit" name="addline" value="$btn_text" />
 </div>
</form>
EOD;
	
	return $string;
}
function addline_get_source($page) // tracker_listから。
{
	$source = get_source($page);
	// 見出しの固有ID部を削除
	$source = preg_replace('/^(\*{1,6}.*)\[#[A-Za-z][\w-]+\](.*)$/m','$1$2',$source);
	// #freezeを削除
	$source = preg_replace("/^#freeze(?:\tuid:([0-9]+))?(?:\taid:([0-9,]+))?(?:\tgid:([0-9,]+))?\n/",'',$source);
	$source = preg_replace("/^\/\/ author:([0-9]+)\n/","",$source);
	return $source;
}
?>
