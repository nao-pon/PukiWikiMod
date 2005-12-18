<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: addline.inc.php,v 1.5 2005/12/18 14:10:47 nao-pon Exp $
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
文字列が違う位置に挿入されているかもしれません。<br />',
			'title_noauth'  => '$1 の編集権限がありません',
			'msg_noauth'  => 'このページに内容を追加する権限がありません。<br />',
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
	
	// 編集権限チェック
	if (!empty($vars['auth']))
	{
		if (is_freeze($post['refer']))
		{
			$retvars['msg'] = $_addline_messages['title_noauth'];
			$retvars['body'] = $_addline_messages['msg_noauth'] . make_pagelink($post['refer']);
			return $retvars;
		}
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
		if (preg_match('/(^|\|((LEFT|RIGHT|CENTER):)?)#addline/',$line) and $addline_no++ == $post['addline_no'])
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
	$postdata = auto_br($postdata);
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
	$auth = 0;
	$configname = 'default';
	$btn_text = $_addline_messages['btn_submit'];
	if ( func_num_args() ){
		foreach ( func_get_args() as $opt ){
			if ( $opt === 'above' || $opt === 'up' )
			{
				$above = 1;
			}
			else if (preg_match("/btn(:.+)/i",$opt,$args))
			{
				$btn_text = htmlspecialchars($args[1]);
				if (strtolower(substr($btn_text,-5)) == ":auth")
				{
					$btn_text = substr($btn_text,0,strlen($btn_text)-5);
					$auth = 1;
					if (is_freeze($vars['page']))
						$btn_text = ":";
					else
						if (!$btn_text) $btn_text = ":". $_addline_messages['btn_submit'];
				}
				$btn_text = substr($btn_text,1);
			}
			else if ( $opt === 'below' || $opt === 'down' )
			{
				$above = 0;
			}
			else if ( $opt === 'auth' )
			{
				$auth = 1;
				if (is_freeze($vars['page'])) $btn_text = "";
			}
			else
			{
				$configname = $opt;
			}
		}
	}
	
	$s_page = htmlspecialchars($vars['page']);
	$string = "";
	if ($btn_text)
	{
		$string = <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="addline_no" value="$addline_no" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="plugin" value="addline" />
  <input type="hidden" name="above" value="$above" />
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="auth" value="$auth" />
  <input type="hidden" name="configname"  value="$configname" />
  <input type="submit" name="addline" value="$btn_text" />
 </div>
</form>
EOD;
	}
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