<?php
//テンポラリディレクトリ
define('WIKI_PAINTER_TEMP_DIR', './plugin_data/painter/tmp/');
//プラグインでの最大幅・高
define('WIKI_PAINTER_MAX_WIDTH_PLUGIN',320);
define('WIKI_PAINTER_MAX_HEIGHT_PLUGIN',320);
//ページ添付での最大幅・高
define('WIKI_PAINTER_MAX_WIDTH_UPLOAD',1024);
define('WIKI_PAINTER_MAX_HEIGHT_UPLOAD',1024);
//デフォルトの幅・高
define('WIKI_PAINTER_DEF_WIDTH',320);
define('WIKI_PAINTER_DEF_HEIGHT',320);

function plugin_painter_action()
{
	global $vars;
	$pmode = (isset($vars['pmode']))? $vars['pmode'] : "";
	switch($pmode)
	{
		case "paint":
			return plugin_painter_paint();
			break;
		case "upload":
			return plugin_painter_save(true);
			break;
		case "save":
			return plugin_painter_save();
			break;
		case "write":
			return plugin_painter_write();
			break;
		default:
	}
}

function plugin_painter_convert()
{
	global $script,$vars;
	list ($picw,$pich,$defmode,$template) = func_get_args();
	$picw = ($picw)? (int)$picw : WIKI_PAINTER_MAX_WIDTH_PLUGIN ;
	$pich = ($pich)? (int)$pich : WIKI_PAINTER_MAX_HEIGHT_PLUGIN ;
	$picw = ($picw < 20)? 20 : $picw;
	$pich = ($pich < 20)? 20 : $pich;
	$picw = min($picw,WIKI_PAINTER_MAX_WIDTH_PLUGIN);
	$pich = min($pich,WIKI_PAINTER_MAX_HEIGHT_PLUGIN);
	
	$templates = array();
	if ($template)
	{
		foreach (explode(" ",$template) as $name)
		{
			$page = $vars['page'];
			$_name = $name;
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
			if (is_file($file))
				$templates[] = $_name;
		}
		$template_tag = '';
		if ($templates)
		{
			$template_tag = 'テンプレート: <select name="image_canvas">';
			$template_tag .= '<option val="">使用しない</option>';
			foreach($templates as $val)
			{
				$template_tag .= '<option val="'.$val.'">'.$val.'</option>';
			}
			$template_tag .= '</select>(しぃペインター & Pro のみ)<br />';
			$template_tag .= '<input type="hidden" name="fitimage" value="ture" />';
		}
	}
	$def_select = array('','','');
	switch ($defmode)
	{
		case "BBSペインター":
			$def_select[1] = " selected=\"true\"";
			break;
		case "しぃペインター":
			$def_select[2] = " selected=\"true\"";
			break;
		case "しぃペインターPro":
			$def_select[3] = " selected=\"true\"";
			break;
	}
	$out='
<form action="'.$script.'" method=POST>
'.$template_tag.'
お絵かきツール:<select name="tools">
<option value="bp"'.$def_select[1].'>BBSペインター</option>
<option value="normal"'.$def_select[2].'>しぃペインター</option>
<option value="pro"'.$def_select[3].'>しぃペインターPro</option>
</select>
横<input type=text name=picw value='.$picw.' size=3>×縦<input type=text name=pich value='.$pich.' size=3>
最大('.WIKI_PAINTER_MAX_WIDTH_PLUGIN.' x '.WIKI_PAINTER_MAX_HEIGHT_PLUGIN.')
<input type=submit value="お絵かきする" />
<input type=checkbox value="true" name="anime" />動画記録
<input type=hidden name="pmode" value="paint" />
<input type=hidden name="plugin" value="painter" />
<input type=hidden name="refer" value="'.$vars['page'].'" />
<input type=hidden name="retmode" value="plugin" />
</form><br />
<a href="'.$script.'?plugin=painter&amp;pmode=save&amp;refer='.encode($vars['page']).'">☆　アップロード済みデータを探す(しぃペインター)　☆</a>
';
	return "<p>".$out."</p>";
}

function plugin_painter_write()
{
	global $post,$vars;
	
	if (!exist_plugin('paint'))
		return array('msg'=>'paint.inc.php not found or not correct version.');
	
	$upload = (!empty($post['upload']))? TRUE : FALSE ;
	
	//ページに書き込む
	$filename = $post['filename'];
	$file = $post['file'];

	$page = $post['page'] = $vars['page'] = $post['refer'] = $vars['refer'];
	
	//ファイル名置換
	if ($filename)
		$attachname = preg_replace('/^[^\.]+/',$filename,$file);
	else
		$attachname = $file;
	
	//すでに存在した場合、 ファイル名に'_0','_1',...を付けて回避(姑息)
	$count = '_0';
	while (file_exists(PAINT_UPLOAD_DIR.encode($vars['refer']).'_'.encode($attachname)))
	{
		$attachname = preg_replace('/^[^\.]+/',$filename.$count++,$file);
	}
	// spch ファイル
	$spchname = preg_replace('/[^\.]+$/','spch',$attachname);
	
	if (!exist_plugin('attach') or !function_exists('attach_upload'))
	{
		return array('msg'=>'attach.inc.php not found or not correct version.');
	}
	
	$retval = do_upload($page,$attachname,WIKI_PAINTER_TEMP_DIR.$file);
	if ($retval['result'] == TRUE)
	{
		
		$tgtfile = preg_replace("/[^\.]+$/","",$file);
		if (file_exists(WIKI_PAINTER_TEMP_DIR.$tgtfile."spch"))
		{
			do_upload($page,$spchname,WIKI_PAINTER_TEMP_DIR.$tgtfile."spch");
			$vars['spch'] = $spchname;
		}
		@unlink(WIKI_PAINTER_TEMP_DIR.$tgtfile."dat");
		
		clearstatcache();
		if ($upload)
			$retval = array("msg" => "お絵かきデータをアップロードしました。", "body" => "");
		else
			$retval = paint_insert_ref($attachname);
	}
	else
		$retval['msg'] = 'このデータ( '.htmlspecialchars($file).' )の処理は完了しています。';
	
	if (!plugin_painter_getfiles(encode($vars['refer'])))
		return $retval;
	else
	{
		if ($upload)
			$pmode = "upload";
		else
			$pmode = "save";
		
		$returl = $script."?plugin=painter&amp;pmode=".$pmode."&amp;refer=".encode($vars['refer']);
		redirect_header($returl,1,"次のアップロード済みファイルの処理をします。");
		exit();
	}
}

function plugin_painter_save($upload=false)
{
	global $vars;
	$files = plugin_painter_getfiles($vars['refer']);
	$page = $vars['refer'] = decode($vars['refer']);
	
	if (!$files)
	{
		if ($upload)
			$url = $script."?plugin=attach&amp;pcmd=upload&amp;page=".rawurlencode($page);
		else
			$url = $script."?".rawurlencode(strip_bracket($page));
		redirect_header($url,1,"アップロード済みファイルはありません。");
		exit();
	}
	return plugin_painter_add($files[0],$upload);
}

function plugin_painter_add($file,$upload=false)
{
	global $script,$vars,$X_uname;
	$file = $file['file'];
	$refer = $vars['refer'];
	//$vars['refer'] = $refer;
	$img = '<img src="'.WIKI_PAINTER_TEMP_DIR.$file.'" /><hr />';
	$fontset_js_tag = fontset_js_tag();
	if ($upload)
	{
		$exttag ='<input type="hidden" name="upload" value="1" />';
	}
	else
	{
		$exttag ='<input type="hidden" name="upload" value="0" />
お名前: <input type="text" name="yourname" size="20" value="'.$X_uname.'" /><br />
タイトル: <input type="text" name="title" size="60" /><br />
メッセージ:'.$fontset_js_tag.'<br />
<textarea name="msg" cols="80" rows="10"></textarea><br />
<input type="checkbox" name="add_comment" value="1" />コメントを受け付ける';
	}
	$body =<<<EOD
<form action="$script" method="POST">
<input type="hidden" name="plugin" value="painter" />
<input type="hidden" name="pmode" value="write" />
<input type="hidden" name="file" value="$file" />
<input type="hidden" name="refer" value="$refer" />
ファイル名: <input type="text" name="filename" size="20" />(省略可)<br />
$exttag
<input type="submit" value="送信">
</form>
EOD;
	$body = $img.$body;
	return array(
		'msg'  => '$1へお絵かきデータを登録',
		'body' => $body
	);
}

function plugin_painter_getfiles($refer)
{
	global $X_uid,$X_admin;
	
	//テンポラリ画像リスト作成
	
	$tmplist = array();
	$handle = @opendir(WIKI_PAINTER_TEMP_DIR);
	while ($file = readdir($handle))
	{
		if(!is_dir($file) && preg_match("/\.(dat)$/i",$file))
		{
			$fp = fopen(WIKI_PAINTER_TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,$page,$uid) = explode("\t", rtrim($userdata));
			$file_name = eregi_replace("\.(dat)$","",$file);
			if(@file_exists(WIKI_PAINTER_TEMP_DIR.$file_name.$imgext) && $page==$refer) //画像があればリストに追加
				$tmplist[] = $ucode."\t".$uip."\t".$file_name.$imgext."\t".$page."\t".$uid;
		}
	}
	closedir($handle);
	$tmp = array();
	if(count($tmplist)!=0){
		//user-codeでチェック
		foreach($tmplist as $tmpimg)
		{
			list($ucode,$uip,$file,$page,$uid) = explode("\t", $tmpimg);
			if($ucode == $usercode || ($X_uid && ($uid == $X_uid)) || $X_admin)
			{
				$page = decode($page);
				$tmp[] = compact('page','file');
			}
		}
		//user-codeでhitしなければIPで再チェック
		if(count($tmp)==0)
		{
			$userip = getenv("HTTP_CLIENT_IP");
			if(!$userip) $userip = getenv("HTTP_X_FORWARDED_FOR");
			if(!$userip) $userip = getenv("REMOTE_ADDR");
			foreach($tmplist as $tmpimg)
			{
				list($ucode,$uip,$file,$page,$uid) = explode("\t", $tmpimg);
				if(!IP_CHECK || $uip == $userip)
				{
					$page = decode($page);
					$tmp[] = compact('page','file');
				}
			}
		}
	}
	return $tmp;
}

function plugin_painter_paint()
{
	global $vars,$script,$X_uid;
	
	$tools = (isset($vars['tools']))? $vars['tools'] : "normal";
	$picw = (isset($vars['picw']))? $vars['picw'] : WIKI_PAINTER_DEF_WIDTH;
	$pich = (isset($vars['pich']))? $vars['pich'] : WIKI_PAINTER_DEF_HEIGHT;
	
	//下地画像ファイルの処理
	$page = $vars['refer'];
	$name = (isset($vars['image_canvas']))? rtrim($vars['image_canvas']) : "";
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
	$image_canvas = '';
	if (is_file($file))
	{
		$image_canvas = '<param name="image_canvas" value="'.$file.'">'."\n";
		//キャンバスサイズを読み込みイメージにあわせる
		if (!empty($vars['fitimage']))
		{
			$imgsize = getimagesize($file);
			$picw = $imgsize[0];
			$pich = $imgsize[1];
		}
	}
	
	$picw = ((int)$picw < 20)? 20 : (int)$picw;
	$pich = ((int)$pich < 20)? 20 : (int)$pich;
	if (isset($vars['retmode']) && $vars['retmode']=="plugin")
	{
		$maxw = WIKI_PAINTER_MAX_WIDTH_PLUGIN;
		$maxh = WIKI_PAINTER_MAX_HEIGHT_PLUGIN;
	}
	else
	{
		$maxw = WIKI_PAINTER_MAX_WIDTH_UPLOAD;
		$maxh = WIKI_PAINTER_MAX_HEIGHT_UPLOAD;
	}
	$picw = min($picw,$maxw);
	$pich = min($pich,$maxh);
	
	if ($tools == "bp")
	{
		if (exist_plugin('paint'))
		{
			$vars['paint_no'] = 0;
			$vars['digest'] = "";
			$vars['plugin'] = "paint";
			$vars['width'] = $picw;
			$vars['height'] = $pich;
			plugin_paint_init();
			return plugin_paint_action();
		}
		else
			$tools = "normal";

	}
	if ($tools != "pro") $tools = "normal";
	
	$anime = (!empty($vars['anime']))? '<param name="thumbnail_type" value="animation">' : '' ;
	$anime_sign = ($anime)? '★描画アニメ記録中★' : '' ;
	$refer = encode($vars['refer']);
	$retmode = (isset($vars['retmode']))? $vars['retmode'] : '' ;
	if ($retmode == 'plugin')
		$returl = $script."?plugin=painter&amp;pmode=save&amp;refer=".$refer;
	else
		$returl = $script."?plugin=painter&amp;pmode=upload&amp;refer=".$refer;
	
	$out ='
<html><head><title>お絵描き</title>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=EUC_JP">
<META name="ROBOTS" content="NOINDEX,NOFOLLOW">
<STYLE TYPE="text/css">
<!--
body,tr,td,th { font-size:11pt }
a:hover { color:#000088;background-color:#44DDDD;TEXT-DECORATION:NONE }
a:link  { color:#2222FF;background-color:#CCFFFF; }
a:vlink { color:#2222FF; }
span { font-size:24pt }
big { font-size:14pt }
small { font-size:9pt }
-->
</STYLE>
</head>
<body bgcolor=#F0F8FF text=#000088 link=#2222FF vlink=#2222FF alink=#000088>
<center>
<!--お絵かきモード 開始-->
<Script Language="JavaScript">
<!--
var DynamicColor = 1;	// パレットリストに色表示
var Palettes = new Array();
Palettes[0] = "#000000\n#FFFFFF\n#B47575\n#888888\n#FA9696\n#C096C0\n#FFB6FF\n#8080FF\n#25C7C9\n#E7E58D\n#E7962D\n#99CB7B\n#FCECE2\n#F9DDCF";

Palettes[1] = "#FFF0DC\n#52443C\n#FFE7D0\n#5E3920\n#FFD6C0\n#B06A54\n#FFCBB3\n#C07A64\n#FFC0A3\n#DEA197\n#FFB7A2\n#ECA385\n#000000\n#FFFFFF";

Palettes[2] = "#FFEEF7\n#FFE6E6\n#FFCAE4\n#FFC4C4\n#FF9DCE\n#FF7D7D\n#FF6AB5\n#FF5151\n#FF2894\n#FF0000\n#CF1874\n#BF0000\n#851B53\n#800000";

Palettes[3] = "#FFE3D7\n#FFFFDD\n#FFCBB3\n#FFFFA2\n#FFA275\n#FFFF00\n#FF8040\n#D9D900\n#FF5F11\n#AAAA00\n#DB4700\n#7D7D00\n#BD3000\n#606000";

Palettes[4] = "#C6FDD9\n#E8FACD\n#8EF09F\n#B9E97E\n#62D99D\n#9ADC65\n#1DB67C\n#65B933\n#1A8C5F\n#4F8729\n#136246\n#2B6824\n#0F3E2B\n#004000";

Palettes[5] = "#DFF4FF\n#C1FFFF\n#80C6FF\n#6DEEFC\n#60A8FF\n#44D0EE\n#1D56DC\n#209CCC\n#273D8F\n#2C769A\n#1C2260\n#295270\n#000040\n#003146";

Palettes[6] = "#E9D2FF\n#E1E1FF\n#DAB5FF\n#C1C1FF\n#CE9DFF\n#8080FF\n#B366FF\n#6262FF\n#9428FF\n#3D44C9\n#6900D2\n#33309E\n#3F007D\n#252D6B";

Palettes[7] = "#ECD3BD\n#F7E2BD\n#E4C098\n#DBC7AC\n#C8A07D\n#D9B571\n#896952\n#C09450\n#825444\n#AE7B3E\n#5E4435\n#8E5C2F\n#493830\n#5F492C";

Palettes[8] = "#FFEADD\n#DED8F5\n#FFCAAB\n#9C89C4\n#F19D71\n#CF434A\n#52443C\n#F09450\n#5BADFF\n#FDF666\n#0077D9\n#4AA683\n#000000\n#FFFFFF";

Palettes[9] = "#F6CD8A\n#FFF99D\n#89CA9D\n#C7E19E\n#8DCFF4\n#8CCCCA\n#9595C6\n#94AAD6\n#AE88B8\n#9681B7\n#F49F9B\n#F4A0BD\n#8C6636\n#FFFFFF";

Palettes[10] = "#C7E19E\n#D1E1FF\n#A8D59D\n#8DCFE0\n#7DC622\n#00A49E\n#528413\n#CBB99C\n#00B03B\n#766455\n#007524\n#5B3714\n#0F0F0F\n#FFFFFF";

Palettes[11] = "#FFFF80\n#F4C1D4\n#EE9C00\n#F4BDB0\n#C45914\n#ED6B9E\n#FEE7DB\n#E76568\n#FFC89D\n#BD3131\n#ECA385\n#AE687E\n#0F0F0F\n#FFFFFF";

Palettes[12] = "#FFFFFF\n#7F7F7F\n#EFEFEF\n#5F5F5F\n#DFDFDF\n#4F4F4F\n#CFCFCF\n#3F3F3F\n#BFBFBF\n#2F2F2F\n#AFAFAF\n#1F1F1F\n#0F0F0F\n#000000";

//-------------------------------------------------------------------
// OS&ブラウザ判定
var uAgent,aName,version,BROWSER,OS;
uAgent = navigator.userAgent.toUpperCase();
aName   = navigator.appName.toUpperCase();
if (uAgent.indexOf("MAC") >= 0)			{ OS="MAC" }
else if (uAgent.indexOf("WIN") >= 0)		{ OS="WIN" }

if (aName.indexOf("NETSCAPE") >= 0)		{ BROWSER="NN" }
else if (aName.indexOf("MICROSOFT") >= 0)	{ BROWSER="IE" }

if (aName.indexOf("NETSCAPE") >= 0){
	appVer  = navigator.appVersion;
	s = appVer.indexOf(" ",0);
	version = eval(parseInt(appVer.substring(0,s)));
	if (version >= 5) version++;
}else if (aName.indexOf("MICROSOFT") >= 0){
	appVer  = navigator.userAgent;
	s = appVer.indexOf("MSIE ",0) + 5;
	e = appVer.indexOf(";",s);
	version = eval(parseInt(appVer.substring(s,e)));
}

function setPalette(){
	d = document
	d.paintbbs.setColors(Palettes[d.Palette.select.selectedIndex])
}
function PaletteSave(){
	Palettes[0] = String(document.paintbbs.getColors())
}
var cutomP = 0;
function PaletteNew(){
	d = document
	p = String(d.paintbbs.getColors())
	s = d.Palette.select
	Palettes[s.length] = p
	cutomP++
	str = prompt("パレット名","パレット " + cutomP)
	if(str == null || str == ""){cutomP--;return}
	s.options[s.length] = new Option(str)
	if(s.length < 30) s.size = s.length
}
function PaletteRenew(){
	d = document
	Palettes[d.Palette.select.selectedIndex] = String(d.paintbbs.getColors())
}
function PaletteDel(){
	p = Palettes.length
	s = document.Palette.select
	i = s.selectedIndex
	if(i == -1)return
	
	flag = confirm("「"+s.options[i].text + "」を削除してよろしいですか？")
	if (!flag) return
	s.options[i] = null
	while(i<p){
		Palettes[i] = Palettes[i+1]
		i++
	}
	if(s.length < 30) s.size = s.length
}
function P_Effect(v){
	v=parseInt(v)
	x = 1
	if(v==255)x=-1
	d = document.paintbbs
	p=String(d.getColors()).split("\n")
	l = p.length
	var s = ""
	for(n=0; n<l;n++){
		R = v+(parseInt("0x" + p[n].substr(1,2))*x)
		G = v+(parseInt("0x" + p[n].substr(3,2))*x)
		B = v+(parseInt("0x" + p[n].substr(5,2))*x)
		if(R > 255){ R = 255}
		else if(R < 0){ R = 0}
		if(G > 255){ G = 255}
		else if(G < 0){ G = 0}
		if(B > 255){ B = 255}
		else if(B < 0){ B = 0}
		s += "#"+Hex(R)+Hex(G)+Hex(B)+"\n"
	}
	d.setColors(s)
}
function PaletteMatrixGet(){
	d = document.Palette
	p = Palettes.length
	s = d.select
	m = d.m_m.selectedIndex
	t = d.setr
	t.value = "!Palette\\n"+String(document.paintbbs.getColors())
	switch(m){
	case 0:case 2:default:
		n=0;c=0
		while(n<p){
			if(s.options[n] != null){ t.value = t.value + "\n!"+ s.options[n].text +"\n" + Palettes[n];c++}
			n++
		}
		alert ("パレット数："+c+"\nパレットマトリクスを取得しました");break
	case 1:
		alert("現在使用されているパレット情報を取得しました");break
	}
		t.value = t.value + "\n!Matrix"
}
function PalleteMatrixSet(){
	m = document.Palette.m_m.selectedIndex;
	str = "パレットマトリクスをセットします。"
	switch(m){
	case 0:default:
		flag = confirm(str+"\n現在の全パレット情報は失われますがよろしいですか？");break
	case 1:
		flag = confirm(str+"\n現在表示しているパレットと置き換えますがよろしいですか？");break;
	case 2:
		flag = confirm(str+"\n現在のパレット情報に追加しますがよろしいですか？");break
	}
		if (!flag) return
	
	PaletteSet()
	if(s.length < 30){ s.size = s.length}else{s.size=30};
	if(DynamicColor) PaletteListSetColor()
}
function PalleteMatrixHelp(){
	alert("★PALETTE MATRIX\nパレットマトリクスとはパレット情報を列挙したテキストを用いる事により\n自由なパレット設定を使用する事が出来ます。\n\n■マトリクスの取得\n1)「取得」ボタンよりパレットマトリクスを取得します。\n2)取得された情報が下のテキストエリアに出ます、これを全てコピーします。\n3)このマトリクス情報をテキストとしてファイルに保存しておくなりしましょう。\n\n■マトリクスのセット\n1）コピーしたマトリクスを下のテキストエリアに貼り付け(ペースト)します。\n2)ファイルに保存してある場合は、それをコピーし貼り付けます。\n3)「セット」ボタンを押せば保存されたパレットが使用できます。\n\n余分な情報があるとパレットが正しくセットされませんのでご注意下さい。");
}
function PaletteSet(){
	d = document.Palette
	se = d.setr.value;
	s = d.select;
	m = d.m_m.selectedIndex;
	l = se.length
	if(l<1){
		alert("マトリクス情報がありません。");return
	}
		n = 0;o = 0;e = 0
	switch(m){
	case 0:default:
		n = s.length
		while(n > 0){
			n--
			s.options[n] = null
		}
	case 2:
		i=s.options.length
		n = se.indexOf("!",0)+1
		if(n == 0)return
		while(n<l){
			e = se.indexOf("\n#",n)
			if(e == -1)return
			
			pn = se.substring(n,e-1)
			o = se.indexOf("!",e)
			if(o == -1)return
			pa = se.substring(e+1,o-2)
			if (pn != "Palette"){
			if(i >= 0)s.options[i] = new Option(pn)
			
			Palettes[i] = pa
			i++
			}else{document.paintbbs.setColors(pa)}
			
			n=o+1
		}
		break
	case 1:
		n = se.indexOf("!",0)+1
		if(n == 0)return
		e = se.indexOf("\n#",n)
		o = se.indexOf("!",e)
			if(e >= 0){
				pa = se.substring(e+1,o-2)
			}
		document.paintbbs.setColors(pa)
	}
}
function PaletteListSetColor(){
	var s = document.Palette.select;
	for(i = 1; s.options.length > i; i ++) {
		var c = Palettes[i].split("\n");
		s.options[i].style.background = c[4];
		s.options[i].style.color = GetBright(c[4]);
	}
}
function GetBright(c){
	r=parseInt("0x"+c.substr(1,2)),
	g=parseInt("0x"+c.substr(3,2)),
	b=parseInt("0x"+c.substr(5,2));
	c=(r>=g)?(r>=b)?r:b:(g>=b)?g:b;
	return c<128?"#FFFFFF":"#000000";
}
function Chenge_(){
	var st = document.grad.pst.value
	var ed = document.grad.ped.value
	
	if(isNaN(parseInt("0x" + st)))return
	if(isNaN(parseInt("0x" + ed)))return
	GradView("#"+st,"#"+ed);
}
function ChengeGrad(){
	var d =document
	st = d.grad.pst.value
	ed = d.grad.ped.value
	Chenge_()

	degi_R = parseInt("0x" + st.substr(0,2))
	degi_G = parseInt("0x" + st.substr(2,2))
	degi_B = parseInt("0x" + st.substr(4,2))
	R = parseInt((degi_R - parseInt("0x" + ed.substr(0,2)))/15)
	G = parseInt((degi_G - parseInt("0x" + ed.substr(2,2)))/15)
	B = parseInt((degi_B - parseInt("0x" + ed.substr(4,2)))/15)
	if(isNaN(R)) R = 1
	if(isNaN(G)) G = 1
	if(isNaN(B)) B = 1
	var p = new String()
	for(cnt=0,m1=degi_R,m2=degi_G,m3=degi_B; cnt<14; cnt++,m1-=R,m2-=G,m3-=B){
		if ((m1 > 255)||(m1 < 0)){ R *= -1;m1-=R}
		if ((m2 > 255)||(m2 < 0)){ G *= -1;m2-=G}
		if ((m3 > 255)||(m3 < 0)){ B *= -1;m2-=B}
		p += "#"+Hex(m1)+Hex(m2)+Hex(m3)+"\n"
	}
	d.paintbbs.setColors(p)
}
function Hex(n){
	n = parseInt(n);if (n < 0) n *=-1;
	var hex = new String()
	var m
	var k
	while(n > 16){
	m = n
	if (n >16){
		n = parseInt(n/16)
		m -= (n * 16)
	}
		k = Hex_(m)
		hex = k + hex
	}
		k = Hex_(n)
		hex = k + hex
	while(hex.length < 2){hex="0" + hex}
	return hex
}
function Hex_(n){
	if(! isNaN(n)){
		if(n == 10){n="A"}
		else if(n == 11){n="B"}
		else if(n == 12){n="C"}
		else if(n == 13){n="D"}
		else if(n == 14){n="E"}
		else if(n == 15){n="F"}
	}else{n=""}
	return n
}
function GetPalette(){
	d = document
	p = String(document.paintbbs.getColors());
	if(p == "null" || p == ""){return};
	ps = p.split("\n");
	st = d.grad.p_st.selectedIndex
	ed = d.grad.p_ed.selectedIndex
	d.grad.pst.value = ps[st].substr(1.6)
	d.grad.ped.value = ps[ed].substr(1.6)
	if(OS == "WIN" & (BROWSER == "IE" | (BROWSER == "NN" & version == "6"))) GradSelC()
	GradView(ps[st],ps[ed])
	if(DynamicColor) PaletteListSetColor()
}
function GradSelC(){
	if(! d.grad.view.checked)return
	d = document.grad
	for(n=0;ps.length>n;n++){
		d.p_st.options[n].style.background = ps[n];
		d.p_st.options[n].style.color = GetBright(ps[n]);
		d.p_ed.options[n].style.background = ps[n];
		d.p_ed.options[n].style.color = GetBright(ps[n]);
	}
}
function GradView(st,ed){
	d = document
	if (! d.grad.view.checked)return
	html = "<TABLE BGCOLOR=white cellspacing=0 cellpadding=0><TR><TD colspan=2><TT><font color=#FF6699><B>GRADATION</B></TT></TD></TR><TR><TD><TT><font color=#FF6699>START </TT></TD><TD><FONT COLOR="+st+" SIZE=4>■</FONT></TD></TR><TR><TD><font color=#FF6699><TT>END </TT></TD><TD><FONT COLOR="+ed+" SIZE=4>■</FONT></TD></TR></TABLE>";
	if(d.layers) {
		with(d.layers["psft"]){
			left = window.innerWidth - 120
			top = window.pageYOffset+5
			d = document
			d.open()
			d.write(html)
			d.close();
		}
	} else if(d.all){
		with(d.all("psft")){
			style.left = d.body.offsetWidth - 120
			style.top = d.body.scrollTop+5
			innerHTML = html
		}
	}
}
function showHideLayer() { //v3.0
	d = document
	var l
	if(d.layers) {
		l = d.layers["psft"]
	}else{
		l = d.all("psft").style
	}
	if (! d.grad.view.checked){
		l.visibility = "hidden"
	}
	if (d.grad.view.checked){
		l.visibility = "visible";GetPalette();
	}
}
//-->
</Script>
<NOSCRIPT><H3>JavaScriptが有効でないため正常に動作致しません</H3></NOSCRIPT>
<TABLE><TR><TD>
<applet code="c.ShiPainter.class" archive="./plugin_data/painter/spainter_all.jar" name="paintbbs" WIDTH="500" HEIGHT="470" MAYSCRIPT>
<param name=dir_resource value="./">
<param name=tt.zip value="tt_def.zip">
<param name=res.zip value="res.zip">
<param name=tools value="'.$tools.'">
<param name=layer_count value="3">
<param name=quality value="1">
<param name="image_width" value="'.$picw.'">
<param name="image_height" value="'.$pich.'">
<param name="image_bkcolor" value="#FFFFFF">
<param name="image_jpeg" value="true">
<param name="image_size" value="30">
'.$image_canvas.'<param name="compress_level" value="15">
<param name="undo" value="90">
<param name="undo_in_mg" value="45">
<param name="color_text" value="#333333">
<param name="color_bk" value="#FFFFFF">
<param name="color_bk2" value="#DDDDDD">
<param name="color_icon" value="#FFFFFF">
<param name="color_iconselect" value="#999999">
<param name="color_bar" value="#999999">
<param name="color_frame" value="#666666">
<param name="url_save" value="'.XOOPS_WIKI_URL.'/plugin_data/painter/picpost.php">
<param name="url_exit" value="'.$returl.'">
<param name="send_header" value="usercode='.PUKIWIKI_UCD.'&refer='.$refer.'&uid='.$X_uid.'">
<param name="poo" value="false">
<param name="send_advance" value="true">
'.$anime.'<param name="thumbnail_width" value="100%">
<param name="thumbnail_height" value="100%">
<param name="tool_advance" value="true">
<param name="tool_color_button" value="#FFFFFF">
<param name="tool_color_button2" value="#FFFFFF">
<param name="tool_color_text" value="#333333">
<param name="tool_color_bar" value="#FFFFFF">
<param name="tool_color_frame" value="#666666">
<param name="security_post" value="false">
</applet>
</TD><TD ALIGN="CENTER" VALIGN="top"><BR>
<TABLE BGCOLOR="black" CELLPADDING="1" CELLSPACING="0" width=100%><TR><FORM name="Palette">
<TD><TABLE BGCOLOR="white" CELLPADDING="3" CELLSPACING="0" width=100%><TR>
<TD><FONT SIZE=2><font face="Impact, Arial Black" color="black" size="2">PALETTE</font> <INPUT TYPE=button VALUE="一時保存" OnClick="PaletteSave()"><BR>
<select name="select" size="13" onChange="setPalette()">
<option>一時保存パレット</option>
<option>肌色系</option>
<option>赤系</option>
<option>黄・橙系</option>
<option>緑系</option>
<option>青系</option>
<option>紫系</option>
<option>せぴあ</option>
<option>人物</option>
<option>パステル</option>
<option>草原の大地</option>
<option>萌えサクラ</option>
<option>モノクロ</option>
</select><BR>
<INPUT TYPE=button VALUE="作成" OnClick="PaletteNew()">
<INPUT TYPE=button VALUE="変更" OnClick="PaletteRenew()">
<INPUT TYPE=button VALUE="削除" OnClick="PaletteDel()"><BR>
<INPUT TYPE=button VALUE="明＋" OnClick="P_Effect(10)">
<INPUT TYPE=button VALUE="明−" OnClick="P_Effect(-10)">
<INPUT TYPE=button VALUE="反転" OnClick="P_Effect(255)">
<HR SIZE=1><font face="Impact, Arial Black" color="black" size="2">MATRIX</font>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<SELECT name="m_m">
<option value=0>全体</option>
<option value=1>現在</option>
<option value=2>追加</option>
</SELECT><BR>
<INPUT name="m_g" TYPE=button VALUE="取得" OnClick="PaletteMatrixGet()">
<INPUT name="m_s" TYPE=button VALUE="セット" OnClick="PalleteMatrixSet()">
<INPUT name="m_h" TYPE=button VALUE=" ? " OnClick="PalleteMatrixHelp()"><BR>
<TEXTAREA rows="1" name="setr" cols="13" onmouseover="this.select()"></TEXTAREA><BR>
</TD></TR></TABLE></TD></FORM></TR></TABLE>
<BR>
<TABLE BGCOLOR="black" CELLPADDING=1 CELLSPACING=0><TR><TD><TABLE BGCOLOR="white" CELLPADDING=3 CELLSPACING=0 width=100%><TR><FORM name=grad>
<TD><FONT SIZE=2><font face="Impact, Arial Black" color="black" size=2>GRADATION</font>&nbsp;<INPUT TYPE=checkbox name=view OnClick="showHideLayer()"><INPUT TYPE=button VALUE=" OK " OnClick="ChengeGrad()"><BR>
<SELECT name=p_st onChange="GetPalette()">
<option>1</option>
<option>2</option>
<option>3</option>
<option>4</option>
<option>5</option>
<option>6</option>
<option>7</option>
<option>8</option>
<option>9</option>
<option>10</option>
<option>11</option>
<option>12</option>
<option>13</option>
<option>14</option>
</SELECT><input type=text name=pst size=8 onKeyPress="Chenge_()" onChange="Chenge_()"><BR>
<SELECT name=p_ed onChange="GetPalette()">
<option>1</option>
<option>2</option>
<option>3</option>
<option>4</option>
<option>5</option>
<option>6</option>
<option>7</option>
<option>8</option>
<option>9</option>
<option>10</option>
<option>11</option>
<option selected>12</option>
<option>13</option>
<option>14</option>
</SELECT><input type=text name=ped size=8 onKeyPress="Chenge_()" onChange="Chenge_()"><div id=psft style="position:absolute;width:100px;height:30px;z-index:1;left:5px;top:10px;"></div>
</TD></TR></TABLE></TD></FORM></TR></TABLE>
<center><a href="http://wondercatstudio.com/" target="_blank" title="WonderCatStudio"><small>DynamicPalette<BR>(C)のらネコ</small></a></center>
</TD></TR></TABLE>
<script language="javascript">
<!--
if(DynamicColor) PaletteListSetColor();
//-->
</script>
<form name="watch">描画時間
<input size="20" name="count">&nbsp;'.$anime_sign.'
</form>
<script language="javascript">
<!--
timerID = 10;
stime = new Date();
function SetTimeCount() {
	now = new Date();
	s = Math.floor((now.getTime() - stime.getTime())/1000);
	disp = \'\';
	if(s >= 86400){
		d = Math.floor(s/86400);
		disp += d+"日";
		s -= d*86400;
	}
	if(s >= 3600){
		h = Math.floor(s/3600);
		disp += h+"時間";
		s -= h*3600;
	}
	if(s >= 60){
		m = Math.floor(s/60);
		disp += m+"分";
		s -= m*60;
	}
	document.watch.count.value = disp+s+"秒";
	clearTimeout(timerID);
	timerID = setTimeout(\'SetTimeCount()\',250);
}
SetTimeCount();
//-->
</script>
</center></body></html>
';
	
	echo $out;
	exit;
}

?>