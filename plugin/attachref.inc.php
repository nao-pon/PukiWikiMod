<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: attachref.inc.php,v 1.8 2005/02/23 15:00:53 nao-pon Exp $
// ORG: attachref.inc.php,v0.5 2003/07/31 14:15:29 sha Exp $
//

/*
*プラグイン attachref
 その場に添付する。attach & ref

*Usage
 &attachref;
 &attachref(file[,<ref options>][,button]);

*パラメータ
-file: attachすると自動的に追加される。
-<ref options>: &ref;用の引数。
-button: [attach]のようなリンクでなく、<form></form>のボタンにする。

*動作
(1)&attachref;を追加すると、[attach]ボタンが表示される。
(2)[attach]ボタンを押すと、アップロードフォームが現われて、指定したファイル
  を添付できる。
(3)添付されたファイルは&ref(...);で参照したように貼り付けられる。
(4)そのファイルを削除すると、"file not found"と[attach]ボタンが表示される。
(5)(4)のときに、そのファイルが追加されると再び復活する。

*/

// max file size for upload on script of PukiWiki(default 1MB)
define('MAX_FILESIZE',1000000);

// upload dir(must set end of /) attach.inc.phpと合わせる
define('ATTACHREF_UPLOAD_DIR','./attach/');


function plugin_attachref_init()
{
	$messages = array(
		'_attachref_messages' => array(
			'min_pixel'     => '10',
			'btn_submit'    => '添付',
			'msg_title'     => '$1 へ指定ファイルを添付して参照を設定しました。',
			'msg_title2'    => '$1 へ指定URLへの参照を設定しました。',
			'msg_title_collided' => '$1 へ指定ファイルを添付して参照を設定しましたが【更新の衝突】が起きました。',
			'msg_collided'  => 'あなたがファイルを添付している間に、他の人が同じページを更新してしまったようです。<br />
ファイルが違う位置に挿入されているかもしれません。<br />',
			'msg_from_pc'=> '1. お手元のパソコンから',
			'msg_from_url'=> '2. インターネットURLから',
			'msg_from_painter'=> 'お絵かきツールを使って手書きする',
			'msg_make_thumb'=> 'サムネイルを作成(画像の場合)',
			'msg_max_rate' => '縮小率',
			'msg_max_width' => '最大幅',
			'msg_max_height'=> '最大高',
			'msg_thumb_note'=> 'サムネイルを作成する場合は縮小率(%)または大きさ(px[ピクセル])を指定してください。<br />大きさは、どちらか一方でも構いません。<br />最小値は 10 です。',
			'msg_comment_h' => 'コメントを付加',
			'msg_comment'   => 'コメント',
			'msg_attach_file'   => '添付ファイルを指定',
			'msg_attach_point'   => ' (添付ポイント No.$1)',
			'err_nothing_file'   => '有効なファイルが指定されていません。',
		),
	);
	set_plugin_messages($messages);
	require_once(PLUGIN_DIR."ref.inc.php");
}
function plugin_attachref_inline()
{
	global $script,$vars,$digest;
	global $_attachref_messages;
	static $numbers = array();
	
	if (!array_key_exists($vars['page'],$numbers))
	{
		$numbers[$vars['page']] = 0;
	}
	$attachref_no = $numbers[$vars['page']]++;
	
	//戻り値
	$ret = '';
	$dispattach = 1;
	$button = 0;
	$btn_text = $_attachref_messages['btn_submit'];

	$args = func_get_args();
	if ( $args[count($args)-1] == '' ){
	    array_pop($args);
	}
	if ( $args[count($args)-1] === 'button' ){
	    $button = 1;
	    array_pop($args);
	}
	if ( preg_match("/^btn(:.+)/",$args[count($args)-1],$tmp))
	{
	    $btn_text = htmlspecialchars($tmp[1]);
	    if (strtolower(substr($btn_text,-5)) == ":auth")
	    {
	    	$btn_text = substr($btn_text,0,strlen($btn_text)-5);
	    	if (is_freeze($vars['page']))
	    		$btn_text = ":";
	    	else
	    		if (!$btn_text) $btn_text = ":".$_attachref_messages['btn_submit'];
	    }
	    $btn_text = substr($btn_text,1);
	    array_pop($args);
	}
	if (func_num_args() and $args[0]!='' )
	{
		//ref用パラメータ変換
		//添付ファイル名を取得
		//$args = func_get_args();
		$name = array_shift($args);

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
			$dispattach = 1;
			array_unshift($args,"");
		} else {
			$ret = $rets['_body'];
			$dispattach = 0;
		}

	}
	if ( $dispattach && $btn_text) {
		//XSS脆弱性問題 - 外部から来た変数をエスケープ
		$f_page = htmlspecialchars($vars['page']);
		$args = str_replace(",","\x08",$args);
		$s_args = trim(join(",", $args));
		if ( $button ){
			$s_args .= ",button";
			$f_args = htmlspecialchars($s_args);
			$ret = <<<EOD
  <form action="$script" method="post">
  <div>
  $ret
  <input type="hidden" name="attachref_no" value="$attachref_no" />
  <input type="hidden" name="attachref_opt" value="$f_args" />
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="plugin" value="attachref" />
  <input type="hidden" name="refer" value="$f_page" />
  <input type="submit" value="{$btn_text}" />
  </div>
  </form>
EOD;
		} else {
			$f_args = urlencode(htmlspecialchars($s_args));
			$f_page = urlencode($f_page);
			$ret = <<<EOD
  $ret<a href="$script?plugin=attachref&attachref_no=$attachref_no&attachref_opt=$f_args&refer=$f_page&digest=$digest">[{$btn_text}]</a>
EOD;
		}
	}
	return $ret;
}
function plugin_attachref_action()
{
	global $script,$vars,$post;
	global $_attachref_messages,$_attach_messages;
	global $html_transitional;
	
	include_once(XOOPS_WIKI_PATH."/plugin/attach.inc.php");
	
	$check = (!ATTACH_UPLOAD_EDITER_ONLY) ? 
	check_readable($vars['refer']) : check_editable($vars['refer']);
	if (!$check) return array('result'=>FALSE,'msg'=>$_attach_messages['err_noparm']);


	//戻り値を初期化
	$retval['msg'] = $_attachref_messages['msg_title'];
	$retval['body'] = '';
	
	if (is_uploaded_file($_FILES['attach_file']['tmp_name'])
		and array_key_exists('refer',$vars)
		and is_page($vars['refer']))
	{
		$file = $_FILES['attach_file'];
		$attachname = basename(str_replace("\\","/",$file['name']));
		$filename = preg_replace('/\..+$/','', $attachname,1);

		//すでに存在した場合、 ファイル名に'_0','_1',...を付けて回避(姑息)
		$count = '_0';
		while (file_exists(ATTACHREF_UPLOAD_DIR.encode($vars['refer']).'_'.encode($attachname)))
		{
			$attachname = preg_replace('/^[^\.]+/',$filename.$count++,$file['name']);
		}
		
		$file['name'] = $attachname;
		
		if (!exist_plugin('attach') or !function_exists('attach_upload'))
		{
			return array('msg'=>'attach.inc.php not found or not correct version.');
		}
		
		$copyright = (isset($post['copyright']))? TRUE : FALSE ;
		$pass = (isset($post['pass']))? md5($post['pass']) : NULL;
		
		$retval = attach_upload($file,$vars['refer'],$pass,$copyright);
		if ($retval['result'] == TRUE)
		{
			$retval = attachref_insert_ref("#".get_pgid_by_name($vars['refer'])."/".$file['name']);
		}
	}
	else
	{
		if (!empty($vars['url']) && is_url($vars['url']))
		{
			// ページ編集権限がない場合は拡張子をチェック
			//if ($GLOBALS['pukiwiki_allow_extensions'] && !is_editable($vars['refer'])
			//	&& !preg_match("/\.(".join("|",$GLOBALS['pukiwiki_allow_extensions']).")$/",$vars['url']))
			//{
			//	$retval = array('result'=>FALSE,'msg'=>str_replace('$1',preg_replace('/.*\.([^.]*)$/i',"$1",$vars['url']),$_attach_messages['err_extension']));
			//}
			//else
				$retval = attachref_insert_ref($vars['url']);
		}
		else
		{
			if (isset($vars['attachref_opt_org'])) $vars['attachref_opt'] = $vars['attachref_opt_org'];
			$retval = attachref_showform();
			// XHTML 1.0 Transitional
			$html_transitional = TRUE;
		}
	}
	return $retval;
}

function attachref_insert_ref($filename)
{
	global $script,$vars,$now,$do_backup;
	global $_attachref_messages;
	
	if (is_url($filename))
		$ret['msg'] = $_attachref_messages['msg_title2'];
	else
		$ret['msg'] = $_attachref_messages['msg_title'];
	
	$args = split(",", $vars['attachref_opt']);
	if ( count($args) ){
		array_shift($args);
		array_unshift($args,$filename);
		$s_args = join(",", $args);
	} else {
		$s_args = $filename;
	}
	//パラメーター挿入
	if (isset($vars['comment'])) {
		$vars['comment'] = htmlspecialchars($vars['comment']);
		$vars['comment'] = str_replace("|","&#x7c;",$vars['comment']);
		$s_args .= ",\"t:".$vars['comment']."\"";
	}
	$_size = 0;
	if (isset($vars['rate']))
	{
		$vars['rate'] = (int)$vars['rate'];
		if ($vars['rate'])
		{
			$vars['rate'] = max($vars['rate'],$_attachref_messages['min_pixel']);
			$vars['rate'] = min($vars['rate'],100);
			$s_args .= ",".$vars['rate']."%";
			$_size = 1;
		}
	}
	
	if (!$_size && isset($vars['mw'])) {
		$vars['mw'] = (int)($vars['mw']);
		if ($vars['mw'])
		{
			$s_args .= ",mw:".$vars['mw'];
		}
	}
	if (!$_size && isset($vars['mh'])) {
		$vars['mh'] = (int)($vars['mh']);
		if ($vars['mh'])
		{
			$s_args .= ",mh:".$vars['mh'];
		}
	}

	$msg = "&attachref($s_args);";
	
	$refer = $vars['refer'];
	$digest = $vars['digest'];
	$postdata_old = get_source($refer);
	$thedigest = md5(join('',$postdata_old));

	$postdata = '';
	$attachref_no = 0; //'#attachref'の出現回数
	$skipflag = 0;
	foreach ($postdata_old as $line)
	{
		if ( $skipflag || substr($line,0,1) === ' '){
			$postdata .= $line;
			continue;
		}
		$ct = preg_match_all('/&attachref(\(((?:(?!\)[;{]).)*)\))?;/',$line, $out);
		if ( $ct ){
			for($i=0; $i < $ct; $i++){
				if ($attachref_no == $vars['attachref_no'] ){
					$line = preg_replace('/&attachref(\(((?:(?!\)[;{]).)*)\))?;/',$msg,$line,1);
					$skipflag = 1;
					break;
				} else {
					$line = preg_replace('/&attachref(\(((?:(?!\)[;{]).)*)\))?;/','&___attachref$1___;',$line,1);
				}
				$attachref_no++;
			}
			$line = preg_replace('/&___attachref(\(((?:(?!\)___[;{]).)*)\))?___;/','&attachref$1;',$line);
		}
		$postdata .= $line;
		
	}
	
	// 更新の衝突を検出
	if ( $thedigest != $digest )
	{
		$ret['msg'] = $_attachref_messages['msg_title_collided'];
		$ret['body'] = $_attachref_messages['msg_collided'];
	}
	$mail_body = "Attached File: ".$filename."\n";
	page_write($vars['refer'],$postdata,NULL,"","","","","","",array('plugin'=>'attachref','mode'=>'none','text'=>$mail_body));
	
	return $ret;
}
//アップロードフォームを表示
function attachref_showform()
{
	global $vars;
	global $_attach_messages;
	
	$vars['page'] = $vars['refer'];
	$body = ini_get('file_uploads') ? attachref_form($vars['page']) : 'file_uploads disabled.';
	
	return array('msg'=>$_attach_messages['msg_upload'],'body'=>$body);
}
//アップロードフォーム
function attachref_form($page)
{
	global $script,$vars;
	global $_attach_messages,$_attachref_messages;
	global $X_uid;
	
	$s_page = htmlspecialchars($page);

	$f_digest = array_key_exists('digest',$vars) ? $vars['digest'] : '';
	$f_no = (array_key_exists('attachref_no',$vars) and is_numeric($vars['attachref_no'])) ?
		$vars['attachref_no'] + 0 : 0;


	if (!(bool)ini_get('file_uploads'))
	{
		return "";
	}
	
	$maxsize = MAX_FILESIZE;
	$msg_maxsize = sprintf($_attach_messages['msg_maxsize'],number_format($maxsize/1000)."KB");

	$allow_extensions = '';
	if ($GLOBALS['pukiwiki_allow_extensions'] && !is_editable($page))
	{
		$allow_extensions = str_replace('$1',join(", ",$GLOBALS['pukiwiki_allow_extensions']),$_attach_messages['msg_extensions'])."<br />";
	}

	$pass = '';
	if (ATTACH_PASSWORD_REQUIRE && !ATTACH_UPLOAD_ADMIN_ONLY && !$X_uid)
	{
		$title = $_attach_messages[ATTACH_UPLOAD_ADMIN_ONLY ? 'msg_adminpass' : 'msg_password'];
		$pass = '<br />'.$title.': <input type="password" name="pass" size="8" />';
	}
	
	$s_args_fix = $s_args = htmlspecialchars($vars['attachref_opt']);
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
	
	$painter='';
	if (exist_plugin('painter'))
	{
		$picw = WIKI_PAINTER_DEF_WIDTH;
		$pich = WIKI_PAINTER_DEF_HEIGHT;
		
		$painter='
<hr />
<h3>'.$_attachref_messages['msg_from_painter'].'</h3>
<a href="'.$script.'?plugin=painter&amp;pmode=upload&amp;refer='.encode($page).'&amp;attachref_no='.$f_no.'&amp;digest='.$f_digest.'&amp;attachref_opt='.rawurlencode($s_args_fix).'">'.$_attach_messages['msg_search_updata'].'</a><br />
<form action="'.$script.'" method="POST">
'.$_attach_messages['msg_paint_tool'].':<select name="tools">
<option value="normal">'.$_attach_messages['msg_shi'].'</option>
<option value="pro">'.$_attach_messages['msg_shipro'].'</option>
</select>
'.$_attach_messages['msg_width'].'<input type=text name=picw value='.$picw.' size=3> x '.$_attach_messages['msg_height'].'<input type=text name=pich value='.$pich.' size=3>
'.$_attach_messages['msg_max'].'('.WIKI_PAINTER_MAX_WIDTH_UPLOAD.' x '.WIKI_PAINTER_MAX_HEIGHT_UPLOAD.')
<input type=submit value="'.$_attach_messages['msg_do_paint'].'" />
<input type=checkbox value="true" name="anime" checked="true" />'.$_attach_messages['msg_save_movie'].'<br />
<br />'.$_attach_messages['msg_adv_setting'].'<br />
'.$_attach_messages['msg_init_image'].': <input type=text size=20 name="image_canvas" />
<input type="checkbox" name="fitimage" value="1" checked="true" />
'.$_attach_messages['msg_fit_size'].'
<input type="hidden" name="pmode" value="paint" />
<input type="hidden" name="plugin" value="painter" />
<input type="hidden" name="refer" value="'.$s_page.'" />
<input type="hidden" name="retmode" value="upload" />
<input type="hidden" name="attachref_no" value="'.$f_no.'" />
<input type="hidden" name="digest" value="'.$f_digest.'" />
<input type="hidden" name="attachref_opt" value="'.$s_args_fix.'" />
</form><hr />';
	}
	
	$title = "<h2>".str_replace('$1',make_pagelink($page),$_attach_messages['msg_upload']).str_replace('$1',$f_no,$_attachref_messages['msg_attach_point'])."</h2>";
	return <<<EOD
$title
<form enctype="multipart/form-data" action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attachref" />
  <input type="hidden" name="pcmd" value="post" />
  <input type="hidden" name="attachref_no" value="$f_no" />
  <input type="hidden" name="attachref_opt" value="{$s_args}" />
  <input type="hidden" name="attachref_opt_org" value="{$s_args_fix}" />
  <input type="hidden" name="digest" value="$f_digest" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
  <h3>{$_attachref_messages['msg_attach_file']}</h3>
  <input type="checkbox" name="copyright" value="1" /> &uarr; {$_attach_messages['msg_copyright']}
  <h5>{$_attachref_messages['msg_from_pc']}</h5>
  <span class="small">
   $msg_maxsize
  </span><br />
  $allow_extensions
  {$_attach_messages['msg_file']}: <input type="file" name="attach_file" size="40" /><br />
  <h5>{$_attachref_messages['msg_from_url']}</h5>
  URL: <input type="text" name="url" size="60" /><hr />
  <h4>{$_attachref_messages['msg_comment_h']}</h4>
  {$_attachref_messages['msg_comment']}: <input type="text" name="comment" size="60" value="{$comment}" /><hr />
  <h4>{$_attachref_messages['msg_make_thumb']}</h4>
  <p>{$_attachref_messages['msg_thumb_note']}</p>
  {$_attachref_messages['msg_max_rate']}: <input type="text" name="rate" size="3" value="{$rate}" />%&nbsp;&nbsp;
  {$_attachref_messages['msg_max_width']}: <input type="text" name="mw" size="4" value="{$mw}" />px&nbsp;<b>x</b>&nbsp;
  {$_attachref_messages['msg_max_height']}: <input type="text" name="mh" size="4" value="{$mh}" />px<hr />
  $pass
  <input type="submit" class="upload_btn" value="{$_attach_messages['btn_upload']}" />
 </div>
</form>
$painter

EOD;
}
?>