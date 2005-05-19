<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
//  $Id: attach.inc.php,v 1.34 2005/05/19 23:49:48 nao-pon Exp $
//  ORG: attach.inc.php,v 1.31 2003/07/27 14:15:29 arino Exp $
//

/*
 プラグイン attach

 changed by Y.MASUI <masui@hisec.co.jp> http://masui.net/pukiwiki/
 modified by PANDA <panda@arino.jp> http://home.arino.jp/
*/

if (file_exists(PLUGIN_DATA_DIR."attach/config.php"))
{
	//ファイルがあれば読み込み
	include(PLUGIN_DATA_DIR."attach/config.php");
}
else
{
	// 管理者だけが添付ファイルをアップロードできるようにする
	define('ATTACH_UPLOAD_ADMIN_ONLY',FALSE); // FALSE or TRUE

	// ページ編集権限がある人のみ添付ファイルをアップロードできるようにする
	define('ATTACH_UPLOAD_EDITER_ONLY',FALSE); // FALSE or TRUE

	// ページ編集権限がない場合にアップロードできる拡張子(カンマ区切り)
	// ATTACH_UPLOAD_EDITER_ONLY = FALSE のときに使用
	define('ATTACH_UPLOAD_EXTENSION','jpg, jpeg, gif, png, txt, spch, zip, lzh, tar, taz, tgz, gz, z');

	// 管理者とページ作成者だけが添付ファイルを削除できるようにする
	define('ATTACH_DELETE_ADMIN_ONLY',FALSE); // FALSE or TRUE

	// 管理者とページ作成者が添付ファイルを削除するときは、バックアップを作らない
	define('ATTACH_DELETE_ADMIN_NOBACKUP',TRUE); // FALSE or TRUE 

	// ゲストユーザーのアップロード/削除時にパスワードを要求する
	// (ADMIN_ONLYが優先 TRUE を強く奨励)
	define('ATTACH_PASSWORD_REQUIRE',TRUE); // FALSE or TRUE

	// ファイルのアクセス権 
	define('ATTACH_FILE_MODE',0644); 
	//define('ATTACH_FILE_MODE',0604); // for XREA.COM 

	// open, delete, upload 時にリファラをチェックする
	// 0:チェックしない, 1:未定義は許可, 2:未定義も不許可
	// 未設定 = URL直打ち, ノートンなどでリファラを遮断 など。
	define('ATTACH_REFCHECK',1);

	// file icon image
	if (!defined('FILE_ICON'))
	{
		define('FILE_ICON','<img src="./image/file.png" width="20" height="20" alt="file" style="border-width:0px" />');
	}

	// mime-typeを記述したページ
	define('ATTACH_CONFIG_PAGE_MIME','plugin/attach/mime-type');

	// 詳細情報・ファイル一覧(イメージモード)で使用する ref プラグインの追加オプション
	define('ATTACH_CONFIG_REF_OPTION',',mw:160,mh:120');

	// tar
	define('TAR_HDR_LEN',512);			// ヘッダの大きさ
	define('TAR_BLK_LEN',512);			// 単位ブロック長さ
	define('TAR_HDR_NAME_OFFSET',0);	// ファイル名のオフセット
	define('TAR_HDR_NAME_LEN',100);		// ファイル名の最大長さ
	define('TAR_HDR_SIZE_OFFSET',124);	// サイズへのオフセット
	define('TAR_HDR_SIZE_LEN',12);		// サイズの長さ
	define('TAR_HDR_TYPE_OFFSET',156);	// ファイルタイプへのオフセット
	define('TAR_HDR_TYPE_LEN',1);		// ファイルタイプの長さ
}

// 添付可能な拡張子を配列化
if (!ATTACH_UPLOAD_ADMIN_ONLY && !ATTACH_UPLOAD_EDITER_ONLY && ATTACH_UPLOAD_EXTENSION)
{
	$GLOBALS['pukiwiki_allow_extensions'] = explode(",",str_replace(" ","",ATTACH_UPLOAD_EXTENSION));
}
else
{
	$GLOBALS['pukiwiki_allow_extensions'] = array();
}

//-------- convert
function plugin_attach_convert()
{
	global $vars;
	
	if (!ini_get('file_uploads'))
	{
		return 'file_uploads disabled';
	}
	
	$nolist = $noform = FALSE;
	
	if (func_num_args() > 0)
	{
		foreach (func_get_args() as $arg)
		{
			$arg = strtolower($arg);
			$nolist |= ($arg == 'nolist');
			$noform |= ($arg == 'noform');
		}
	}
	$ret = '';
	if (!$nolist)
	{
		$obj = &new AttachPages($vars['page']);
		$ret .= $obj->toString($vars['page'],FALSE);
	}
	if (!$noform)
	{
		$ret .= attach_form($vars['page']);
	}
	
	return $ret;
}

//-------- action
function plugin_attach_action()
{
	global $vars,$post,$_attach_messages;
	
	
	// backward compatible
	if (array_key_exists('openfile',$vars))
	{
		$vars['pcmd'] = 'open';
		$vars['file'] = $vars['openfile'];
	}
	if (array_key_exists('delfile',$vars))
	{
		$vars['pcmd'] = 'delete';
		$vars['file'] = $vars['delfile'];
	}
	if (empty($vars['refer'])) $vars['refer'] = $vars['page'];
	
	$age = array_key_exists('age',$vars) ? $vars['age'] : 0;
	$pcmd = array_key_exists('pcmd',$vars) ? $vars['pcmd'] : '';
	
	// リファラチェック
	if (ATTACH_REFCHECK)
	{
		if (($vars['pcmd'] == 'open' || $vars['pcmd'] == 'delete' || $vars['pcmd'] == 'upload' || !$pcmd)
		 && !pukiwiki_refcheck(ATTACH_REFCHECK-1))
		{
			//redirect_header(XOOPS_WIKI_URL,0,"Access denied!");
			//echo "Access Denied!";
			@readfile("./image/accdeny.gif");
			exit;
		}
	}
	
	// Authentication
	if (array_key_exists('refer',$vars) and is_pagename($vars['refer']))
	{
		$read_cmds = array('info','open','list','imglist');
		$check = (ATTACH_UPLOAD_EDITER_ONLY && !in_array($pcmd,$read_cmds)) ? 
		check_editable($vars['refer']) : check_readable($vars['refer']);
		if (!$check) return array('result'=>FALSE,'msg'=>$_attach_messages['err_noparm']);
	}
	
	// Upload
	if (array_key_exists('attach_file',$_FILES))
	{
		$pass = array_key_exists('pass',$vars) ? md5($vars['pass']) : NULL;
		$copyright = (isset($post['copyright']))? TRUE : FALSE;
		return attach_upload($_FILES['attach_file'],$vars['refer'],$pass,$copyright);
	}
	
	$pass = array_key_exists('pass',$vars) ? $vars['pass'] : NULL;
	switch ($pcmd)
	{
		case 'info':    return attach_info();
		case 'delete':  return attach_delete($pass);
		case 'open':    return attach_open();
		case 'list':    return attach_list();
		case 'imglist':return attach_list('imglist');
		case 'freeze':  return attach_freeze(TRUE,$pass);
		case 'unfreeze':return attach_freeze(FALSE,$pass);
		case 'upload':  return attach_showform();
		case 'copyright':  return attach_copyright($pass);
	}
	if ($vars['page'] == '' or !is_page($vars['page']))
	{
		return attach_list();
	}
	
	return attach_showform();
}
//-------- call from skin
function attach_filelist($isbn=false)
{
	global $vars,$_attach_messages;
	
	$obj = &new AttachPages($vars['page'],0,$isbn,20);
	if ($obj->err === 1) return '<span style="color:red;font-size:150%;font-weight:bold;">DB ERROR!: Please initialize an attach file database on an administrator screen.</span>';

	if (!array_key_exists($vars['page'],$obj->pages))
	{
		return '';
	}
	$_tmp = $obj->toString($vars['page'],TRUE);
	if ($_tmp) $_tmp = $_attach_messages['msg_file'].': '.$_tmp."\n";
	return $_tmp;
}
//-------- 実体
//ファイルアップロード
function attach_upload($file,$page,$pass=NULL,$copyright=FALSE)
{
// $pass=NULL : パスワードが指定されていない
// $pass=TRUE : アップロード許可
	global $adminpass,$_attach_messages,$post,$X_admin;
	
	if ($file['tmp_name'] == '' or !is_uploaded_file($file['tmp_name']) or !$file['size'])
	{
		return array('result'=>FALSE);
	}
	if ($file['size'] > MAX_FILESIZE)
	{
		return array('result'=>FALSE,'msg'=>$_attach_messages['err_exceed']);
	}
	if (!is_pagename($page) or ($pass !== TRUE and ATTACH_UPLOAD_EDITER_ONLY and !is_editable($page)))
	{
		return array('result'=>FALSE,'msg'=>$_attach_messages['err_noparm']);
	}
	if (ATTACH_UPLOAD_ADMIN_ONLY and $pass !== TRUE and !$X_admin)
	{
		return array('result'=>FALSE,'msg'=>$_attach_messages['err_adminpass']);
	}
	//$copyright = (isset($post['copyright']))? TRUE : FALSE;
	
	if ( strcasecmp(substr($file['name'],-4),".tar") == 0 && $post['untar_mode'] == "on" ) {
		// UploadされたTarアーカイブを展開添付する

		// Tarファイル展開
		$etars = untar( $file['tmp_name'], UPLOAD_DIR);

		// 展開されたファイルを全てアップロードファイルとして追加
		foreach ( $etars as $efile ) {
			$res = do_upload( $page,
				mb_convert_encoding($efile['extname'], SOURCE_ENCODING,"auto"),
				$efile['tmpname'],$copyright,$pass);
			if ( ! $res['result'] ) {
				unlink( $efile['tmpname']);
			}
		}

		// 最後の返り値でreturn
		return $res;
	} else {
		// 通常の単一ファイル添付処理
		return do_upload($page,$file['name'],$file['tmp_name'],$copyright,$pass);
	}
}

function do_upload($page,$fname,$tmpname,$copyright=FALSE,$pass=NULL,$notouch=FALSE)
{
	global $_attach_messages,$X_uid,$X_admin;
	
	$_action = "insert";
	// style.css
	$pginfo = get_pg_info_db($page);
	if ($fname == "style.css" && ($X_admin || ($X_uid && $pginfo["uid"] == $X_uid)))
	{
		if ( is_uploaded_file($tmpname) )
		{
			$_pagecss_file = CACHE_DIR.encode(strip_bracket($page)).".css";
			if (file_exists($_pagecss_file)) unlink($_pagecss_file);
			if (move_uploaded_file($tmpname,$_pagecss_file))
			{
				chmod($_pagecss_file,ATTACH_FILE_MODE);
				// 空のファイルの場合はファイル削除
				if (!trim(join('',file($_pagecss_file))))
				{
					unlink($_pagecss_file);
					return array('result'=>TRUE,'msg'=>$_attach_messages['msg_unset_css']);
				}
				else
				{
					// 外部ファイルの参照を禁止するための書き換え
					$_data = join('',file($_pagecss_file));
					$_data = preg_replace("#(ht|f)tps?://#","",$_data);
					if ($fp = fopen($_pagecss_file,"wb"))
					{
						fputs($fp,$_data);
						fclose($fp);
					}
					
					return array('result'=>TRUE,'msg'=>$_attach_messages['msg_set_css']);
				}
			}
			else
				return array('result'=>FALSE,'msg'=>$_attach_messages['err_exists']);
			
		}
	}
	
	// ページ編集権限がない場合は拡張子をチェック
	if ($GLOBALS['pukiwiki_allow_extensions'] && !is_editable($page)
		 && !preg_match("/\.(".join("|",$GLOBALS['pukiwiki_allow_extensions']).")$/i",$fname))
	{
		unlink($tmpname);
		return array('result'=>FALSE,'msg'=>str_replace('$1',preg_replace('/.*\.([^.]*)$/',"$1",$fname),$_attach_messages['err_extension']));
	}
	
	$obj = &new AttachFile($page,$fname);
	
	if ( is_uploaded_file($tmpname) ) {
		if ($obj->exist)
		{
			return array('result'=>FALSE,'msg'=>$_attach_messages['err_exists']);
		}
		
		if (move_uploaded_file($tmpname,$obj->filename))
		{
			chmod($obj->filename,ATTACH_FILE_MODE);
		}
		else
			return array('result'=>FALSE,'msg'=>$_attach_messages['err_exists']);
	} else {
		if (file_exists($obj->filename))
		{
			unlink($obj->filename);
			$_action = "update";
		}
		if (rename($tmpname,$obj->filename))
		{
			chmod($obj->filename,ATTACH_FILE_MODE);
		}
		else
			return array('result'=>FALSE,'msg'=>$_attach_messages['err_exists']);
	}
	
	if (!$notouch && is_page($page))
	{
		touch(get_filename(encode($page)));
		touch_db($page);
	}
	
	$obj->getstatus();
	$obj->status['pass'] = ($pass !== TRUE and $pass !== NULL) ? $pass : '';
	$obj->status['copyright'] = $copyright;
	$obj->status['owner'] = $X_uid;
	$obj->action = $_action;
	$obj->putstatus();

	return array('result'=>TRUE,'msg'=>$_attach_messages['msg_uploaded']);
}
//詳細フォームを表示
function attach_info($err='')
{
	global $vars,$_attach_messages;
	
	foreach (array('refer','file','age') as $var)
	{
		$$var = array_key_exists($var,$vars) ? $vars[$var] : '';
	}
	
	$obj = &new AttachFile($refer,$file,$age);
	return $obj->getstatus() ? $obj->info($err) : array('msg'=>$_attach_messages['err_notfound']);
}
//削除
function attach_delete($pass)
{
	global $vars,$_attach_messages;
	
	foreach (array('refer','file','age','pass') as $var)
	{
		$$var = array_key_exists($var,$vars) ? $vars[$var] : '';
	}
	
	if (ATTACH_UPLOAD_EDITER_ONLY and !is_editable($refer))
	{
		return array('msg'=>$_attach_messages['err_noparm']);
	}
	
	$obj = &new AttachFile($refer,$file,$age);
	return $obj->getstatus() ? $obj->delete($pass) : array('msg'=>$_attach_messages['err_notfound']);
}
//凍結
function attach_freeze($freeze,$pass)
{
	global $vars,$_attach_messages;
	
	foreach (array('refer','file','age','pass') as $var)
	{
		$$var = array_key_exists($var,$vars) ? $vars[$var] : '';
	}
	
	if (ATTACH_UPLOAD_EDITER_ONLY and !is_editable($refer))
	{
		return array('msg'=>$_attach_messages['err_noparm']);
	}
	
	$obj = &new AttachFile($refer,$file,$age);
	return $obj->getstatus() ? $obj->freeze($freeze,$pass) : array('msg'=>$_attach_messages['err_notfound']);
}
//著作権設定
function attach_copyright($pass)
{
	global $vars,$_attach_messages;
	foreach (array('refer','file','age','pass','copyright') as $var)
	{
		$$var = array_key_exists($var,$vars) ? $vars[$var] : '';
	}
	
	if (ATTACH_UPLOAD_EDITER_ONLY and !is_editable($refer))
	{
		return array('msg'=>$_attach_messages['err_noparm']);
	}
	
	$copyright = ($copyright)? TRUE : FALSE;
	$obj = &new AttachFile($refer,$file,$age);
	return $obj->getstatus() ? $obj->copyright($copyright,$pass) : array('msg'=>$_attach_messages['err_notfound']);
}
//ダウンロード
function attach_open()
{
	global $vars,$_attach_messages;
	
	foreach (array('refer','file','age') as $var)
	{
		$$var = array_key_exists($var,$vars) ? $vars[$var] : '';
	}
	
	$obj = &new AttachFile($refer,$file,$age);
	
	return $obj->getstatus() ? $obj->open() : array('msg'=>$_attach_messages['err_notfound']);
}
//一覧取得
function attach_list($mode="")
{
	global $vars,$noattach;
	global $_attach_messages;
	global $X_admin,$X_uid;
	
	$refer = array_key_exists('refer',$vars) ? $vars['refer'] : '';
	
	$noattach = 1;
	
	$msg = $_attach_messages[$refer == '' ? 'msg_listall' : 'msg_listpage'];
	
	$max = ($refer)? 50 : 20;
	$max = (isset($vars['max']))? (int)$vars['max'] : $max;
	$max = min(50,$max);
	$start = (isset($vars['start']))? (int)$vars['start'] : 0;
	$start = max(0,$start);
	$f_order = (isset($vars['order']))? $vars['order'] : "";
	$mode = ($mode == "imglist")? $mode : "";

	$obj = &new AttachPages($refer,NULL,TRUE,$max,$start,FALSE,$f_order,$mode);
	if ($obj->err === 1) return array('msg'=>'DB ERROR!','body'=>'Please initialize an attach file database on an administrator screen.');
	
	
	$body = ($refer == '' or array_key_exists($refer,$obj->pages)) ?
		$obj->toString($refer,FALSE) :
		"<p>".make_pagelink($refer)."</p>\n".$_attach_messages['err_noexist'];
	return array('msg'=>$msg,'body'=>$body);
}
//アップロードフォームを表示
function attach_showform()
{
	global $vars;
	global $_attach_messages;
	
	$vars['refer'] = $vars['page'];
	$body = ini_get('file_uploads') ? attach_form($vars['page']) : 'file_uploads disabled.';
	
	return array('msg'=>$_attach_messages['msg_upload'],'body'=>$body);
}

//-------- サービス
//mime-typeの決定
function attach_mime_content_type($filename)
{
	$type = 'application/octet-stream'; //default
	
	if (!file_exists($filename))
	{
		return $type;
	}
	$size = @getimagesize($filename);
	if (is_array($size))
	{
		switch ($size[2])
		{
			case 1:
				return 'image/gif';
			case 2:
				return 'image/jpeg';
			case 3:
				return 'image/png';
			case 4:
				return 'application/x-shockwave-flash';
		}
	}
	
	if (!preg_match('/_((?:[0-9A-F]{2})+)(?:\.\d+)?$/',$filename,$matches))
	{
		return $type;
	}
	$filename = decode($matches[1]);
	
	// mime-type一覧表を取得
	$config = new Config(ATTACH_CONFIG_PAGE_MIME);
	$table = $config->read() ? $config->get('mime-type') : array();
	unset($config); // メモリ節約
	
	foreach ($table as $row)
	{
		$_type = trim($row[0]);
		$exts = preg_split('/\s+|,/',trim($row[1]),-1,PREG_SPLIT_NO_EMPTY);
		
		foreach ($exts as $ext)
		{
			if (preg_match("/\.$ext$/i",$filename))
			{
				return $_type;
			}
		}
	}
	
	return $type;
}
//アップロードフォーム
function attach_form($page)
{
	global $script,$vars;
	global $_attach_messages,$X_admin,$X_uid;
	static $load = array();
	
	exist_plugin('attach');
	
	if (isset($load[$page]))
		$load[$page]++;
	else
		$load[$page] = 0;
	$pgid = get_pgid_by_name($page);
	
	$r_page = rawurlencode($page);
	$s_page = htmlspecialchars($page);
	$header = "<h3>".str_replace('$1',make_pagelink($page),$_attach_messages['msg_upload'])."</h3>";
	$navi = <<<EOD
  $header
  <span class="small">
   [<a href="$script?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$_attach_messages['msg_list']}</a>]
   [<a href="$script?plugin=attach&amp;pcmd=list">{$_attach_messages['msg_listall']}</a>]
  </span><br />
EOD;

	if (!(bool)ini_get('file_uploads'))
	{
		return $navi;
	}
	
	if (exist_plugin('painter'))
	{
		$picw = WIKI_PAINTER_DEF_WIDTH;
		$pich = WIKI_PAINTER_DEF_HEIGHT;
		//$picw = min($picw,WIKI_PAINTER_MAX_WIDTH_UPLOAD);
		//$pich = min($pich,WIKI_PAINTER_MAX_HEIGHT_UPLOAD);
		
		$painter='
<hr />
<a href="'.$script.'?plugin=painter&amp;pmode=upload&amp;refer='.encode($page).'">'.$_attach_messages['msg_search_updata'].'</a><br />
<form action="'.$script.'" method=POST>
<label for="_p_attach_tools_'.$pgid.'_'.$load[$page].'">'.$_attach_messages['msg_paint_tool'].'</label>:<select id="_p_attach_tools_'.$pgid.'_'.$load[$page].'" name="tools">
<option value="normal">'.$_attach_messages['msg_shi'].'</option>
<option value="pro">'.$_attach_messages['msg_shipro'].'</option>
</select>
'.$_attach_messages['msg_width'].'<input type=text name=picw value='.$picw.' size=3> x '.$_attach_messages['msg_height'].'<input type=text name=pich value='.$pich.' size=3>
'.$_attach_messages['msg_max'].'('.WIKI_PAINTER_MAX_WIDTH_UPLOAD.' x '.WIKI_PAINTER_MAX_HEIGHT_UPLOAD.')
<input type=submit value="'.$_attach_messages['msg_do_paint'].'" />
<input type=checkbox id="_p_attach_anime_'.$pgid.'_'.$load[$page].'" value="true" name="anime" />
<label for="_p_attach_anime_'.$pgid.'_'.$load[$page].'">'.$_attach_messages['msg_save_movie'].'</label><br />
<br />'.$_attach_messages['msg_adv_setting'].'<br />
<label for="_p_attach_image_canvas_'.$pgid.'_'.$load[$page].'">'.$_attach_messages['msg_init_image'].'</label>: <input type="text" size="20" id="_p_attach_image_canvas_'.$pgid.'_'.$load[$page].'" name="image_canvas" />
<input type="checkbox" id="_p_attach_fitimage_'.$pgid.'_'.$load[$page].'" name="fitimage" value="1" checked="true" />
<label for="_p_attach_fitimage_'.$pgid.'_'.$load[$page].'">'.$_attach_messages['msg_fit_size'].'</label>
<input type=hidden name="pmode" value="paint" />
<input type=hidden name="plugin" value="painter" />
<input type=hidden name="refer" value="'.$page.'" />
<input type=hidden name="retmode" value="upload" />
</form>';
	}
	$maxsize = MAX_FILESIZE;
	$msg_maxsize = sprintf($_attach_messages['msg_maxsize'],number_format($maxsize/1000)."KB");

	//$uid = get_pg_auther($this->page);
	$pass = '';
	//if (ATTACH_PASSWORD_REQUIRE && !ATTACH_UPLOAD_ADMIN_ONLY && ((!$X_admin && $X_uid !== $uid) || $X_uid == 0))
	if (ATTACH_PASSWORD_REQUIRE && !ATTACH_UPLOAD_ADMIN_ONLY && !$X_uid)
	{
		$title = $_attach_messages[ATTACH_UPLOAD_ADMIN_ONLY ? 'msg_adminpass' : 'msg_password'];
		$pass = '<br />'.$title.': <input type="password" name="pass" size="8" />';
	}
	
	$allow_extensions = '';
	$antar_tag = "(<label for=\"_p_attach_untar_mode_{$pgid}_{$load[$page]}\">{$_attach_messages['msg_untar']}</label>:<input type=\"checkbox\" id=\"_p_attach_untar_mode_{$pgid}_{$load[$page]}\" name=\"untar_mode\">)";
	if ($GLOBALS['pukiwiki_allow_extensions'] && !is_editable($page))
	{
		$allow_extensions = str_replace('$1',join(", ",$GLOBALS['pukiwiki_allow_extensions']),$_attach_messages['msg_extensions'])."<br />";
		$antar_tag = "";
	}
	
	$filelist = "<hr />".attach_filelist();
	
	return <<<EOD
<form enctype="multipart/form-data" action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="pcmd" value="post" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="max_file_size" value="$maxsize" />
  $navi
  <span class="small">
   $msg_maxsize
  </span><br />
  $allow_extensions
  <label for="_p_attach_attach_fil_{$pgid}_{$load[$page]}">{$_attach_messages['msg_file']}</label>: <input type="file" id="_p_attach_attach_fil_{$pgid}_{$load[$page]}" name="attach_file" />
  $pass
  <input type="submit" class="upload_btn" value="{$_attach_messages['btn_upload']}" />
  $antar_tag<br />
  <input type="checkbox" id="_p_attach_copyright_{$pgid}_{$load[$page]}" name="copyright" value="1" /> <label for="_p_attach_copyright_{$pgid}_{$load[$page]}">{$_attach_messages['msg_copyright']}</label>

 </div>
</form>
$painter
$filelist
EOD;
}
//-------- クラス
//ファイル
class AttachFile
{
	var $page,$file,$age,$basename,$filename,$logname,$copyright;
	var $time = 0;
	var $size = 0;
	var $pgid = 0;
	var $time_str = '';
	var $size_str = '';
	var $owner_str = '';
	var $status = array('count'=>array(0),'age'=>'','pass'=>'','freeze'=>FALSE,'copyright'=>FALSE,'owner'=>0);
	var $action = 'update';
	
	function AttachFile($page,$file,$age=0,$pgid=0)
	{
		$this->page = $page;
		$this->pgid = ($pgid)? $pgid : get_pgid_by_name($page);
		$this->file = basename(str_replace("\\","/",$file));
		$this->age = is_numeric($age) ? $age : 0;
		
		$this->basename = UPLOAD_DIR.encode($page).'_'.encode($this->file);
		$this->filename = $this->basename . ($age ? '.'.$age : '');
		$this->logname = $this->basename.'.log';
		$this->exist = file_exists($this->filename);
		$this->time = $this->exist ? filemtime($this->filename) - LOCALZONE : 0;
		$this->md5hash = $this->exist ? md5_file($this->filename) : '';
	}
	// ファイル情報取得
	function getstatus()
	{
		if (!$this->exist)
		{
			return FALSE;
		}
		// ログファイル取得
		if (file_exists($this->logname))
		{
			$data = file($this->logname);
			foreach ($this->status as $key=>$value)
			{
				$this->status[$key] = chop(array_shift($data));
			}
			$this->status['count'] = explode(',',$this->status['count']);
		}
		$this->time_str = get_date('Y/m/d H:i:s',$this->time);
		$this->size = filesize($this->filename);
		$this->size_str = sprintf('%01.1f',round($this->size)/1000,1).'KB';
		$this->type = attach_mime_content_type($this->filename);
		$this->owner_str = get_pg_auther_name($this->status['owner'],TRUE);
		make_user_link(&$this->owner_str);
		$this->owner_str = make_link($this->owner_str);
		
		return TRUE;
	}
	//ステータス保存
	function putstatus()
	{
		$this->update_db();
		$this->status['count'] = join(',',$this->status['count']);
		$fp = fopen($this->logname,'wb')
			or die_message('cannot write '.$this->logname);
		flock($fp,LOCK_EX);
		foreach ($this->status as $key=>$value)
		{
			fwrite($fp,$value."\n");
		}
		flock($fp,LOCK_UN);
		fclose($fp);
	}
	// attach DB 更新
	function update_db()
	{
		if ($this->action == "insert")
		{
			$this->size = filesize($this->filename);
			$this->type = attach_mime_content_type($this->filename);
			$this->time = filemtime($this->filename) - LOCALZONE;
		}
		$data['pgid'] = $this->pgid;
		$data['name'] = $this->file;
		$data['mtime'] = $this->time;
		$data['size'] = $this->size;
		$data['type'] = $this->type;
		$data['status'] = $this->status;

		attach_db_write($data,$this->action);
		
	}
	// 日付の比較関数
	function datecomp($a,$b)
	{
		return ($a->time == $b->time) ? 0 : (($a->time > $b->time) ? -1 : 1);
	}
	function toString($showicon,$showinfo,$mode)
	{
		global $script,$date_format,$time_format,$weeklabels;
		global $_attach_messages;
		
		$this->getstatus();
		$param  = '&amp;file='.rawurlencode($this->file).'&amp;refer='.rawurlencode($this->page).
			($this->age ? '&amp;age='.$this->age : '');
		$title = $this->time_str.' '.$this->size_str;
		$label = ($showicon ? FILE_ICON : '').htmlspecialchars($this->file);
		if ($this->age)
		{
			if ($mode == "imglist")
				$label = 'backup No.'.$this->age;
			else
				$label .= ' (backup No.'.$this->age.')';
		}
		
		$info = $count = '';
		if ($showinfo)
		{
			$_title = str_replace('$1',rawurlencode($this->file),$_attach_messages['msg_info']);
			if ($mode == "imglist")
				$info = "[ [[{$_attach_messages['btn_info']}:".XOOPS_WIKI_HOST."{$script}?plugin=attach&pcmd=info".str_replace("&amp;","&",$param)."]] ]";
			else
				$info = "\n<span class=\"small\">[<a href=\"$script?plugin=attach&amp;pcmd=info$param\" title=\"$_title\">{$_attach_messages['btn_info']}</a>]</span>";
			$count = ($showicon and !empty($this->status['count'][$this->age])) ?
				sprintf($_attach_messages['msg_count'],$this->status['count'][$this->age]) : '';
		}
		if ($mode == "imglist")
		{
			if ($this->age)
				return "&size(12){".$label.$info."};";
			else
				return "&size(12){&ref(\"".strip_bracket($this->page)."/".$this->file."\"".ATTACH_CONFIG_REF_OPTION.");~\n".$info."};";
		}
		else
			return "<a href=\"$script?plugin=attach&amp;pcmd=open$param\" title=\"$title\">$label</a>$count$info";
	}
	// 情報表示
	function info($err)
	{
		global $script,$_attach_messages,$X_admin,$X_uid;
		
		$r_page = rawurlencode($this->page);
		$s_page = htmlspecialchars($this->page);
		$s_file = htmlspecialchars($this->file);
		$s_err = ($err == '') ? '' : '<p style="font-weight:bold">'.$_attach_messages[$err].'</p>';
		$ref = "";
		
		//$uid = get_pg_auther($this->page);
		$pass = '';
		//if (ATTACH_PASSWORD_REQUIRE && !ATTACH_UPLOAD_ADMIN_ONLY && ((!$X_admin && $X_uid !== $uid) || $X_uid == 0))
		if (ATTACH_PASSWORD_REQUIRE && !ATTACH_UPLOAD_ADMIN_ONLY && !$X_uid)
		{
			$title = $_attach_messages[ATTACH_UPLOAD_ADMIN_ONLY ? 'msg_adminpass' : 'msg_password'];
			$pass = $title.': <input type="password" name="pass" size="8" />';
		}
		
		if ($this->age)
		{
			$msg_freezed = '';
			$msg_delete  = '<input type="radio" id="pcmd_d" name="pcmd" value="delete" /><label for="pcmd_d">'.$_attach_messages['msg_delete'].'</label>';
			$msg_delete .= $_attach_messages['msg_require'];
			$msg_delete .= '<br />';
			$msg_freeze  = '';
		}
		else
		{
			$ref .= "<dd><hr /></dd><dd>".convert_html("&ref(\"".strip_bracket($this->page)."/".$this->file."\"".ATTACH_CONFIG_REF_OPTION.");")."</dd>\n";
			if ($this->status['freeze'])
			{
				$msg_freezed = "<dd>{$_attach_messages['msg_isfreeze']}</dd>";
				$msg_delete = '';
				$msg_freeze  = '<input type="radio" id="pcmd_u" name="pcmd" value="unfreeze" /><label for="pcmd_u">'.$_attach_messages['msg_unfreeze'].'</label>';
				$msg_freeze .= $_attach_messages['msg_require'].'<br />';
			}
			else
			{
				$msg_freezed = '';
				$msg_delete = '<input type="radio" id="pcmd_d" name="pcmd" value="delete" /><label for="pcmd_d">'.$_attach_messages['msg_delete'].'</label>';
				if (ATTACH_DELETE_ADMIN_ONLY or $this->age)
				{
					$msg_delete .= $_attach_messages['msg_require'];
				}
				$msg_delete .= '<br />';
				$msg_freeze  = '<input type="radio" id="pcmd_f" name="pcmd" value="freeze" /><label for="pcmd_f">'.$_attach_messages['msg_freeze'].'</label>';
				$msg_freeze .= "{$_attach_messages['msg_require']}<br />";
			}
		}
		$info = $this->toString(TRUE,FALSE);
		$copyright = ($this->status['copyright'])? ' checked=TRUE' : '';
		
		$retval = array('msg'=>sprintf($_attach_messages['msg_info'],htmlspecialchars($this->file)));
		$page_link = make_pagelink($s_page);
		//EXIF DATA
		$exif_data = get_exif_data($this->filename);
		if ($exif_data){
			$exif_tags = "<hr>".$exif_data['title'];
			foreach($exif_data as $key => $value){
				if ($key != "title") $exif_tags .= "<br />$key: $value";
			}
		}
		$v_filename = ($this->status['copyright'])? "" : "<dd>{$_attach_messages['msg_filename']}:{$this->filename}</dd>";
		$v_md5hash  = ($this->status['copyright'])? "" : "<dd>{$_attach_messages['msg_md5hash']}:{$this->md5hash}</dd>";
		$retval['body'] = <<< EOD
<p class="small">
 [<a href="$script?plugin=attach&amp;pcmd=list&amp;refer=$r_page">{$_attach_messages['msg_list']}</a>]
 [<a href="$script?plugin=attach&amp;pcmd=list">{$_attach_messages['msg_listall']}</a>]
</p>
<dl style="word-break: break-all;">
 <dt>$info</dt>
 <dd>{$_attach_messages['msg_page']}:$page_link</dd>
 {$v_filename}
 {$v_md5hash}
 <dd>{$_attach_messages['msg_filesize']}:{$this->size_str} ({$this->size} bytes)</dd>
 <dd>Content-type:{$this->type}</dd>
 <dd>{$_attach_messages['msg_date']}:{$this->time_str}</dd>
 <dd>{$_attach_messages['msg_dlcount']}:{$this->status['count'][$this->age]}</dd>
 <dd>{$_attach_messages['msg_owner']}:{$this->owner_str}</dd>
 $ref
 $exif_tags
 $msg_freezed
</dl>
<hr />
$s_err
EOD;
		$uid = get_pg_auther($this->page);
		//if (check_editable($this->page,false,false) && ((!ATTACH_DELETE_ADMIN_ONLY && $X_uid == $this->status['owner']) || (($X_admin || $X_uid == $uid) && $X_uid != 0))){
		if ($X_admin || ( $X_uid == $this->status['owner']) || (!ATTACH_DELETE_ADMIN_ONLY && !$this->status['owner'])){
			$retval['body'] .= <<< EOD
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="file" value="$s_file" />
  <input type="hidden" name="age" value="{$this->age}" />
  $msg_delete
  $msg_freeze
  $pass
  <input type="submit" value="{$_attach_messages['btn_submit']}" />
 </div>
</form>
<hr />
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="attach" />
  <input type="hidden" name="refer" value="$s_page" />
  <input type="hidden" name="file" value="$s_file" />
  <input type="hidden" name="age" value="{$this->age}" />
  <input type="hidden" name="pcmd" value="copyright" />
  <input type="checkbox" id="copyright" name="copyright" value="1"{$copyright} /> <label for="copyright">{$_attach_messages['msg_copyright']}</label>
  $pass
  <input type="submit" value="{$_attach_messages['btn_submit']}" />
 </div>
</form>
EOD;
		}
		return $retval;
	}
	function delete($pass)
	{
		global $adminpass,$_attach_messages,$vars,$X_admin,$X_uid,$script;
				
		if ($this->status['freeze'])
		{
			return attach_info('msg_isfreeze');
		}
		
		$uid = get_pg_auther($vars['page']);
		$admin = FALSE;
		if ((!$X_admin && $X_uid !== $uid && $X_uid != $this->status['owner']) || $X_uid == 0)
		// 管理者とページ作成者とファイル所有者以外
		{
			if ((ATTACH_PASSWORD_REQUIRE and md5($pass) != $this->status['pass']) || $this->status['owner'])
				return attach_info('err_password');
			
			if (ATTACH_DELETE_ADMIN_ONLY or $this->age)
				return attach_info('err_adminpass');
		}
		else
			$admin = TRUE;

		//バックアップ
		if ($this->age or 
			($admin and ATTACH_DELETE_ADMIN_NOBACKUP))
		{
			@unlink($this->filename);
			$this->del_thumb_files();
			attach_db_write(array('pgid'=>$this->pgid,'name'=>$this->file),"delete");
		}
		else
		{
			do
			{
				$age = ++$this->status['age'];
			}
			while (file_exists($this->basename.'.'.$age));
			
			if (!rename($this->basename,$this->basename.'.'.$age))
			{
				// 削除失敗 why?
				return array('msg'=>$_attach_messages['err_delete']);
			}

			$this->del_thumb_files();
			
			$this->status['count'][$age] = $this->status['count'][0];
			$this->status['count'][0] = 0;
			$this->putstatus();
		}
		if (is_page($this->page))
		{
			touch(get_filename(encode($this->page)));
			touch_db($page);
		}
		
		return array('msg'=>$_attach_messages['msg_deleted'],'redirect'=>$script."?plugin=attach&pcmd=upload&page=".rawurlencode($this->page));
	}
	function freeze($freeze,$pass)
	{
		global $adminpass,$vars,$X_admin,$X_uid,$_attach_messages,$script;
		
		$uid = get_pg_auther($vars['page']);
		if ((!$X_admin && $X_uid !== $uid && $X_uid != $this->status['owner']) || $X_uid == 0)
		// 管理者とページ作成者とファイル所有者以外
		{
			if ((ATTACH_PASSWORD_REQUIRE and md5($pass) != $this->status['pass']) || $this->status['owner'])
				return attach_info('err_password');
		}
		$this->getstatus();
		$this->status['freeze'] = $freeze;
		$this->putstatus();
		
		$param  = '&file='.rawurlencode($this->file).'&refer='.rawurlencode($this->page).
			($this->age ? '&age='.$this->age : '');
		$redirect = "$script?plugin=attach&pcmd=info$param";
		
		return array('msg'=>$_attach_messages[$freeze ? 'msg_freezed' : 'msg_unfreezed'],'redirect'=>$redirect);
	}
	function copyright($copyright,$pass)
	{
		global $adminpass,$vars,$X_admin,$X_uid,$_attach_messages,$script;
		
		$uid = get_pg_auther($vars['page']);
		if ((!$X_admin && $X_uid !== $uid && $X_uid != $this->status['owner']) || $X_uid == 0)
		// 管理者とページ作成者とファイル所有者以外
		{
			if ((ATTACH_PASSWORD_REQUIRE and md5($pass) != $this->status['pass']) || $this->status['owner'])
				return attach_info('err_password');
		}
		
		$this->getstatus();
		$this->status['copyright'] = $copyright;
		$this->putstatus();
		
		$param  = '&file='.rawurlencode($this->file).'&refer='.rawurlencode($this->page).
			($this->age ? '&age='.$this->age : '');
		$redirect = "$script?plugin=attach&pcmd=info$param";
		
		return array('msg'=>$_attach_messages[$copyright ? 'msg_copyrighted' : 'msg_uncopyrighted'],'redirect'=>$redirect);
	}
	function open()
	{
		global $X_admin,$X_uid;
		$this->getstatus();
		$uid = get_pg_auther($vars['page']);
		if ((!X_admin && $X_uid !== $uid && $X_uid != $this->status['owner']) || $X_uid == 0)
		{
			if ($this->status['copyright'])
				return attach_info('err_copyright');
		}
		$this->status['count'][$this->age]++;
		$this->putstatus();
		
		// for japanese (???)
		$filename = htmlspecialchars(mb_convert_encoding($this->file,'SJIS','auto'));
		
		ini_set('default_charset','');
		mb_http_output('pass');
		
		// 画像以外はダウンロード扱いにする(XSS対策)
		$_i_size = getimagesize($this->filename);
		if ($_i_size[2])
		{
			header('Content-Disposition: inline; filename="'.$filename.'"');
		}
		else
		{
			header('Content-Disposition: attachment; filename="'.$filename.'"');
		}
		
		header('Content-Length: '.$this->size);
		header('Content-Type: '.$this->type);
		@readfile($this->filename);
		exit;
	}
	
	function del_thumb_files(){
		// 該当ファイルのサムネイルを削除
		$dir = opendir(UPLOAD_DIR."s/")
			or die('directory '.UPLOAD_DIR.'s/ is not exist or not readable.');
		
		/*
		$page_pattern = ($this->page == '') ? '(?:[0-9A-F]{2})+' : preg_quote(encode($this->page),'/');
		$age_pattern = ($age === NULL) ?
			'(?:\.([0-9]+))?' : ($age ?  "\.($age)" : '');
		$pattern = "/^({$page_pattern})_((?:[0-9A-F]{2})+){$age_pattern}$/";
		
		while ($file = readdir($dir))
		{
			if (!preg_match($pattern,$file,$matches))
			{
				continue;
			}
			$_page = decode($matches[1]);
			$_file = decode($matches[2]);
			if (preg_match("/^\d\d?%".preg_quote($this->file)."/",$_file)){
				@unlink(UPLOAD_DIR.$file);
			}
		}
		closedir($dir);
		*/
		for ($i = 1; $i < 100; $i++)
		{
			$file = encode($this->page).'_'.encode($i."%").encode($this->file);
			if (file_exists(UPLOAD_DIR."s/".$file))
			{
				unlink(UPLOAD_DIR."s/".$file);
			}
		}
	}
}

// ファイルコンテナ
class AttachFiles
{
	var $page;
	var $pgid;
	var $files = array();
	var $count = 0;
	var $max = 50;
	var $start = 0;
	var $order = "";
	
	function AttachFiles($page)
	{
		$this->page = $page;
	}
	function add($file,$age)
	{
		$this->files[$file][$age] = &new AttachFile($this->page,$file,$age,$this->pgid);
	}
	// ファイル一覧を取得
	function toString($flat,$fromall=FALSE,$mode="")
	{
		global $_title_cannotread,$script;
		
		if (!check_readable($this->page,FALSE,FALSE))
		{
			return str_replace('$1',make_pagelink($this->page),$_title_cannotread);
		}
		if ($flat)
		{
			return $this->to_flat();
		}
		$ret = '';
		$files = array_keys($this->files);
		$navi = "";
		$pcmd = ($mode == "imglist")? "imglist" : "list";
		$pcmd2 = ($mode == "imglist")? "list" : "imglist";
		if (!$fromall)
		{
			$url = $script."?plugin=attach&amp;pcmd={$pcmd}&amp;refer=".rawurlencode($this->page)."&amp;order=".$this->order."&amp;start=";
			$url2 = $script."?plugin=attach&amp;pcmd={$pcmd}&amp;refer=".rawurlencode($this->page)."&amp;start=";
			$url3 = $script."?plugin=attach&amp;pcmd={$pcmd2}&amp;refer=".rawurlencode($this->page)."&amp;order=".$this->order."&amp;start=".$this->start;
			$sort_time = ($this->order == "name")? " [ <a href=\"{$url2}0&amp;order=time\">Sort by time</a> |" : " [ <b>Sort by time</b> |";
			$sort_name = ($this->order == "name")? " <b>Sort by name</b> ] " : " <a href=\"{$url2}0&amp;order=name\">Sort by name</a> ] ";
			$mode_tag = ($mode == "imglist")? "[ <a href=\"$url3\">List view<a> ]":"[ <a href=\"$url3\">Image view</a> ]";
			
			if ($this->max < $this->count)
			{
				$_start = $this->start + 1;
				$_end = $this->start + $this->max;
				$_end = min($_end,$this->count);
				$now = $this->start / $this->max + 1;
				$total = ceil($this->count / $this->max);
				$navi = array();
				for ($i=1;$i <= $total;$i++)
				{
					if ($now == $i)
						$navi[] = "<b>$i</b>";
					else
						$navi[] = "<a href=\"".$url.($i - 1) * $this->max."\">$i</a>";
				}
				$navi = join(' | ',$navi);
				
				$prev = max(0,$now - 1);
				$next = $now;
				$prev = ($prev)? "<a href=\"".$url.($prev - 1) * $this->max."\" title=\"Prev\"> <img src=\"./image/prev.png\" width=\"6\" height=\"12\" alt=\"Prev\"> </a>|" : "";
				$next = ($next < $total)? "|<a href=\"".$url.$next * $this->max."\" title=\"Next\"> <img src=\"./image/next.png\" width=\"6\" height=\"12\" alt=\"Next\"> </a>" : "";
				
				$navi = "<div class=\"wiki_page_navi\">| $navi |<br />[{$prev} $_start - $_end / ".$this->count." files {$next}]<br />{$sort_time}{$sort_name}{$mode_tag}</div>";
			}
			else
			{
				$navi = "<div class=\"wiki_page_navi\">{$sort_time}{$sort_name}{$mode_tag}</div>";
			}
		}
		$col = 1;
		foreach ($files as $file)
		{
			$_files = array();
			foreach (array_keys($this->files[$file]) as $age)
			{
				$_files[$age] = $this->files[$file][$age]->toString(FALSE,TRUE,$mode);
			}
			if (!array_key_exists(0,$_files))
			{
				$_files[0] = htmlspecialchars($file);
			}
			ksort($_files);
			$_file = $_files[0];
			unset($_files[0]);
			if ($mode == "imglist")
			{
				$ret .= "|$_file";
				if (count($_files))
				{
					$ret .= "~\n".join("~\n-",$_files);
				}
				$mod = $col % 4;
				if ($mod === 0)
				{
					$ret .= "|\n";
					$col = 0;
				}
				$col++;
			}
			else
			{
				$ret .= " <li>$_file\n";
				if (count($_files))
				{
					$ret .= "<ul>\n<li>".join("</li>\n<li>",$_files)."</li>\n</ul>\n";
				}
				$ret .= " </li>\n";
			}
		}
		
		if ($mode == "imglist")
		{
			if ($mod) $ret .= str_repeat("|>",4-$mod)."|\n";
			//if ($mod) $ret .= "|\n";
			$ret = "|TCENTER:704px CENTER:MIDDLE:176px|CENTER:MIDDLE:176px|CENTER:MIDDLE:176px|CENTER:MIDDLE:176px|c\n".$ret;
		 	$ret = convert_html($ret);
		}
		
		$showall = ($fromall && $this->max < $this->count)? " [ <a href=\"{$script}?plugin=attach&amp;pcmd={$pcmd}&amp;refer=".rawurlencode($this->page)."\">Show All</a> ]" : "";
		$allpages = ($fromall)? "" : " [ <a href=\"?plugin=attach&amp;pcmd={$pcmd}\" />All Pages</a> ]";
		return $navi.($navi? "<hr />":"")."<div class=\"wiki_filelist_page\">".make_pagelink($this->page)."<small> (".$this->count." file".(($this->count==1)?"":"s").")".$showall.$allpages."</small></div>\n<ul>\n$ret</ul>".($navi? "<hr />":"")."$navi\n";
	}
	// ファイル一覧を取得(inline)
	function to_flat()
	{
		global $script;
		$ret = '';
		$files = array();
		foreach (array_keys($this->files) as $file)
		{
			if (array_key_exists(0,$this->files[$file]))
			{
				$files[$file] = &$this->files[$file][0];
			}
		}
		uasort($files,array('AttachFile','datecomp'));
		//if ($max) $files = array_slice($files,$start,$max);
		
		foreach (array_keys($files) as $file)
		{
			$ret .= $files[$file]->toString(TRUE,TRUE).' ';
		}
		$more = $this->count - $this->max;
		$more = ($this->count > $this->max)? "... more ".$more." files. [ <a href=\"{$script}?plugin=attach&amp;pcmd=list&amp;refer=".rawurlencode($this->page)."\">Show All</a> ]" : "";
		return $ret.$more;
	}
}
// ページコンテナ
class AttachPages
{
	var $pages = array();
	var $start = 0;
	var $max = 50;
	var $mode = "";
	var $err = 0;
	
	function AttachPages($page='',$age=NULL,$isbn=true,$max=50,$start=0,$fromall=FALSE,$f_order="time",$mode="")
	{
		global $xoopsDB,$X_admin,$X_uid;
		$this->mode = $mode;
		if ($page)
		{
			// 閲覧権限チェック
			if (!$fromall && !check_readable($page,false,false)) return;
			
			$this->pages[$page] = &new AttachFiles($page);
			
			$pgid = get_pgid_by_name($page);
			$this->pages[$page]->pgid = $pgid;
			
			// WHERE句
			$where = array();
			$where[] = "`pgid` = {$pgid}";
			if (!$isbn) $where[] = "`mode` != '1'";
			if (!is_null($age)) $where[] = "`age` = $age";
			//if ($mode == "imglist") $where[] = "`type` LIKE 'image%' AND `age` = 0";
			//if ($mode == "imglist") $where[] = "`age` = 0";
			$where = " WHERE ".join(' AND ',$where);
			
			// このページの添付ファイル数取得
			$query = "SELECT count(*) as count FROM `".$xoopsDB->prefix(pukiwikimod_attach)."`{$where};";
			if (!$result = $xoopsDB->query($query))
				{
					$this->err = 1;
					return;
				}
			$_row = mysql_fetch_row($result);
			if (!$_row[0]) return;
			
			$this->pages[$page]->count = $_row[0];
			$this->pages[$page]->max = $max;
			$this->pages[$page]->start = $start;
			$this->pages[$page]->order = $f_order;
			
			// ファイル情報取得
			$order = ($f_order == "name")? " ORDER BY name ASC" : " ORDER BY mtime DESC";
			$limit = " LIMIT {$start},{$max}";
			$query = "SELECT name,age FROM `".$xoopsDB->prefix(pukiwikimod_attach)."`{$where}{$order}{$limit};";
			$result = $xoopsDB->query($query);
			while($_row = mysql_fetch_row($result))
			{
				$_file = $_row[0];
				$_age = $_row[1];
				$this->pages[$page]->add($_file,$_age);
			}
		}
		else
		{
			// WHERE句
			if ($X_admin)
				$where = "";
			else
			{
				$where = "";
				if ($X_uid) $where .= " (p.uid = $X_uid) OR";
				$where .= " (p.vaids LIKE '%all%') OR (p.vgids LIKE '%&3&%')";
				if ($X_uid) $where .= " OR (p.vaids LIKE '%&{$X_uid}&%')";
				foreach(X_get_groups() as $gid)
				{
					$where .= " OR (p.vgids LIKE '%&{$gid}&%')";
				}
				$where = " WHERE".$where;
			}
			
			// 添付ファイルのあるページ数カウント
			$query = "SELECT p.id FROM ".$xoopsDB->prefix(pukiwikimod_pginfo)." p INNER JOIN ".$xoopsDB->prefix(pukiwikimod_attach)." a ON p.id=a.pgid{$where} GROUP BY a.pgid;";
			$result = $xoopsDB->query($query);
			
			$this->count = 0;
			while($_row = mysql_fetch_row($result))
			{
				$this->count++;
			}
			$this->max = $max;
			$this->start = $start;
			$this->order = $f_order;
			
			// ページ情報取得
			$order = ($f_order == "name")? " ORDER BY p.name ASC" : " ORDER BY p.editedtime DESC";
			$limit = " LIMIT $start,$max";
			
			$query = "SELECT p.name,p.editedtime FROM ".$xoopsDB->prefix(pukiwikimod_pginfo)." p INNER JOIN ".$xoopsDB->prefix(pukiwikimod_attach)." a ON p.id=a.pgid{$where} GROUP BY a.pgid{$order}{$limit};";
			if (!$result = $xoopsDB->query($query)) echo "QUERY ERROR : ".$query;
			
			
			while($_row = mysql_fetch_row($result))
			{
				$this->AttachPages(add_bracket($_row[0]),$age,$isbn,20,0,TRUE,$f_order,$mode);
			}
		}
	}
	function toString($page='',$flat=FALSE)
	{
		global $script;
		if ($page != '')
		{
			if (!array_key_exists($page,$this->pages))
			{
				return '';
			}
			return $this->pages[$page]->toString($flat,FALSE,$this->mode);
		}
		$pcmd = ($this->mode == "imglist")? "imglist" : "list";
		$pcmd2 = ($this->mode == "imglist")? "list" : "imglist";
		$url = $script."?plugin=attach&amp;pcmd={$pcmd}&amp;order=".$this->order."&amp;start=";
		$url2 = $script."?plugin=attach&amp;pcmd={$pcmd}&amp;start=";
		$url3 = $script."?plugin=attach&amp;pcmd={$pcmd2}&amp;order=".$this->order."&amp;start=".$this->start;
		$sort_time = ($this->order == "name")? " [ <a href=\"{$url2}0&amp;order=time\">Sort by time</a> |" : " [ <b>Sort by time</b> |";
		$sort_name = ($this->order == "name")? " <b>Sort by name</b> ] " : " <a href=\"{$url2}0&amp;order=name\">Sort by name</a> ] ";
		$mode_tag = ($this->mode == "imglist")? "[ <a href=\"$url3\">List view<a> ]":"[ <a href=\"$url3\">Image view</a> ]";
		
		$_start = $this->start + 1;
		$_end = $this->start + $this->max;
		$_end = min($_end,$this->count);
		$now = $this->start / $this->max + 1;
		$total = ceil($this->count / $this->max);
		$navi = array();
		
		for ($i=1;$i <= $total;$i++)
		{
			if ($now == $i)
				$navi[] = "<b>$i</b>";
			else
				$navi[] = "<a href=\"".$url.($i - 1) * $this->max."\">$i</a>";
		}
		$navi = join(' | ',$navi);
		$prev = max(0,$now - 1);
		$next = $now;
		$prev = ($prev)? "<a href=\"".$url.($prev - 1) * $this->max."\" title=\"Prev\"> <img src=\"./image/prev.png\" width=\"6\" height=\"12\" alt=\"Prev\"> </a>|" : "";
		$next = ($next < $total)? "|<a href=\"".$url.$next * $this->max."\" title=\"Next\"> <img src=\"./image/next.png\" width=\"6\" height=\"12\" alt=\"Next\"> </a>" : "";
		$navi = "<div class=\"wiki_page_navi\">| $navi |<br />[{$prev} $_start - $_end / ".$this->count." pages {$next}]<br />{$sort_time}{$sort_name}{$mode_tag}</div>";
		
		$ret = "";
		$pages = array_keys($this->pages);
		//sort($pages);
		foreach ($pages as $page)
		{
			//$ret .= '<li>'.$this->pages[$page]->toString($flat)."</li>\n";
			$ret .= $this->pages[$page]->toString($flat,TRUE,$this->mode)."\n";
		}
		//return "\n<ul>\n".$ret."</ul>\n";
		return "\n$navi".($navi? "<hr />":"")."\n$ret\n".($navi? "<hr />":"")."$navi\n";
		
	}
}

// $tname: tarファイルネーム
// $odir : 展開先ディレクトリ
// 返り値: 特に無し。大したチェックはせず、やるだけやって後は野となれ山となれ
function untar( $tname, $odir)
{
	if (!( $fp = fopen( $tname, "rb") ) ) {
		return;
	}

	unset($files);
	$cnt = 0;
	while ( strlen($buff=fread( $fp,TAR_HDR_LEN)) == TAR_HDR_LEN ) {
		for ( $i=TAR_HDR_NAME_OFFSET,$name="";
			$buff[$i] != "\0" && $i<TAR_HDR_NAME_OFFSET+TAR_HDR_NAME_LEN;
			$i++) {
			$name .= $buff[$i];
		}
		$name = basename(trim($name)); //ディレクトリお構い無し

		for ( $i=TAR_HDR_SIZE_OFFSET,$size="";
				$i<TAR_HDR_SIZE_OFFSET+TAR_HDR_SIZE_LEN; $i++ ) {
			$size .= $buff[$i];
		}
		list($size) = sscanf("0".trim($size),"%i"); // サイズは8進数

		// データブロックは512byteでパディングされている
		$pdsz =  ((int)(($size+(TAR_BLK_LEN-1))/TAR_BLK_LEN))*TAR_BLK_LEN;

		// 通常のファイルしか相手にしない
		$type = $buff[TAR_HDR_TYPE_OFFSET];

		if ( $name && $type == 0 ) {
			$buff = fread( $fp, $pdsz);
			$tname = tempnam( $odir, "tar" );
			$fpw = fopen( $tname , "wb");
			fwrite( $fpw, $buff, $size );
			fclose( $fpw);
			$files[$cnt  ]['tmpname'] = $tname;
			$files[$cnt++]['extname'] = $name;
		}
	}
	fclose( $fp);

	return $files;	
}
?>