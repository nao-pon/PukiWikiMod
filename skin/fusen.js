/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// fusen.js
// 付箋プラグイン用JavaScript
// ohguma@rnc-com.co.jp
//
// v 1.0 2005/03/12 初版
// v 1.1 (欠番)
// v 1.2 2005/03/16 削除確認追加,Lock時のDrag廃止
// v 1.3 2005/03/17 XHTML1.1対応?
// v 1.4 2005/03/18 検索機能追加
// v 1.5 2005/03/18 検索機能修正(convert_html後の表示内容で検索)
// v 1.6 (欠番)
// v 1.7 2005/04/02 onload修正,関数名変更,付箋データ保持方法変更,線関係修正,DblClick対応
// v 1.8 2005/04/03 AJAX対応(auto set, リアルタイム更新)
//

/////////////////////////////////////////////////
// PukiWikiMod - XOOPS's PukiWiki module.
//
// fusen.js for PukiWikiMod by nao-pon
// http://hypweb.net
// $Id: fusen.js,v 1.12 2005/12/22 11:43:12 nao-pon Exp $
// 

var offsetX = 0;
var offsetY = 0;

// browser check
var GK = document.getElementById;         // Gecko or Opera or IE
var IE = (document.all && !window.opera); // IE

// mouse position
var mouseX = '';
var mouseY = '';

var fusenObj;
var fusenMovingObj = null;
var fusenMovingFlg = false;
var fusenResizeFlg = false;
var fusenDustboxFlg = false;
var fusenFullFlg = new Array();
var fusenShowFlg = new Array();
var fusenNowMovingOff = false;
var fusenDblClick = false;
var fusenBodyStyle = 'fusen_body_trans';
var fusenLastModified = '';
var fusenTimerID;		//Interval Timer ID
var fusenRetTimerID;	//リトライ用タイマー
var fusenFullTimerID = new Array();;
var fusenClickX = 0;
var fusenClickY = 0;
var fusenClickW = 0;
var fusenClickH = 0;
var fusenBusyFlg = false;
var fusenMinWidth = 8;
var fusenMinHeight = 8;
var fusenGetRetry = 0;
var fusenLoaded = false;
var fusenLines = new Array();

var fsen_msg_nowbusy = '只今サーバーと通信中です。';

function getElement(id) {
	return document.getElementById(id);
}

// Open window for object information.
function fusen_debugobj(objref) {
	var obj = null;
	var str = '';
	if (typeof(objref) == 'string') obj = getElement(objref);
	else obj = objref;
	if (obj) 
		for(i in obj)
			try {
				str += i + "=" + obj[i] + "\n";
			} catch (e) {
			}
	else str = objref;
	debugWin = window.open('', '');
	window.debugWin.document.write('<html>\n<body>\n<pre>\n' + str + '\n</pre>\n</body>\n</html>');
}

function fusen_setInterval(msec)
{
	fusenInterval = msec;
	fusen_set_timer();
}

function fusen_set_timer()
{
	if (fusenTimerID) clearTimeout(fusenTimerID);
	//window.status = fusenInterval;
	if (fusenInterval > 5000)
	{
		fusenTimerID = setInterval("fusen_init(0)", fusenInterval);
	}
}

function fusen_busy(busy)
{
	if (busy)
	{
		fusenBusyFlg = true;
	}
	else
	{
		fusenBusyFlg = false;
	}
	
	var set_cursor;
	var r_cursor;
	var w_cursor;
	var obj;
	
	r_cursor = (busy)? 'wait' : 'nw-resize';
	w_cursor = (busy)? 'wait' : 'w-resize';
	
	for(var id in fusenObj)
	{
		obj = getElement('fusen_id' + id);
		set_cursor = (fusenObj[id].lk)? 'auto' : 'move';
		set_cursor = (busy)? 'wait' : set_cursor;
		obj.style.cursor = set_cursor;
		if (busy)
		{
			obj.onmousedown = null;
		}
		else
		{
			fusen_set_onmousedown(obj,id);
		}

		getElement('fusen_id' + id + 'resize').style.cursor = r_cursor;
		getElement('fusen_id' + id + 'wresize').style.cursor = w_cursor;
		
	}
}

// Create HTTP request object.
function fusen_httprequest(){
	try {
		return new XMLHttpRequest();
	} catch(e) {
		var MSXML_XMLHTTP_PROGIDS = new Array(
			'MSXML2.XMLHTTP.5.0',
			'MSXML2.XMLHTTP.4.0',
			'MSXML2.XMLHTTP.3.0',
			'MSXML2.XMLHTTP',
			'Microsoft.XMLHTTP'
		);
		for (var i in MSXML_XMLHTTP_PROGIDS) {
			try {
				return new ActiveXObject(MSXML_XMLHTTP_PROGIDS[i]);
			} catch (e) {
			}
		}
	}
	throw 'Unable to create HTTP request object.';
}

// Post fusen data.
function fusen_postdata(mode) {
	var frm = getElement('edit_frm');
	var re = /input|textarea|select/i;
	var tag = '';
	var postdata = '';
	
	if (fusenTimerID) clearTimeout(fusenTimerID);
	
	var w_starus = (fusenInterval)? "付箋機能: 通信完了 [自動更新(" + (fusenInterval/1000) + "s) 待機中]" : "付箋機能: 通信完了 [自動更新 停止中]";
	window.status = "サーバーに接続中...";
	fusen_busy(1);
	
	for (var i = 0; i < frm.length; i++ ) {
		var child = frm[i];
		tag = String(child.tagName);
		if (tag.match(re)) {
			if (postdata!='') postdata += '&';
			postdata += encodeURIComponent(child.name) +
				'=' + encodeURIComponent(child.value);
		}
	}
	
	try {
		var xmlhttp = fusen_httprequest();
		//var url = location.href;
		//if (url.indexOf('?') > 0) url.substr(0, url.indexOf('?'));
		if (mode)
		{
			xmlhttp.onreadystatechange = readyStateChangeHandler;
		}
		xmlhttp.open('POST', fusenPostUrl, mode);
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded;');
		xmlhttp.send(postdata);
	} catch(e) {
		fusen_busy(0);
		xmlhttp = null;
		alert(e);
		throw 'Unable to post fusen data.';
	}
	if (!mode)
	{
		window.status = w_starus;
		fusen_busy(0);
		if(xmlhttp.status == 200 || xmlhttp.status == 0)
		{
			var ret = xmlhttp.responseText;
			xmlhttp = null;
			fusen_set_timer();
			return ret;
		}
		else
		{
			xmlhttp = null;
			fusenLastModified = '';
			alert ('データの送信に失敗しました。 付箋機能の「更新」をクリックして状態を確認してください。');
		}
	}
	function readyStateChangeHandler()
	{
		window.status = "サーバーと通信中...";
		if (xmlhttp.readyState == 4)
		{
			fusen_busy(0);
			window.status = w_starus;
			try
			{
				if (xmlhttp.status == 200)
				{
					fusen_set_timer();
				}
				else
				{
					fusenLastModified = '';
					alert('データの送信に失敗しました。 付箋機能の「更新」をクリックして状態を確認してください。');
				}
			}
			catch(e)
			{
				fusenLastModified = '';
				alert('データの送信に失敗しました。 付箋機能の「更新」をクリックして状態を確認してください。');
			}
			xmlhttp = null;
			return;
		}
	}
}

// Get fusen date.
function fusen_getdata(mod)
{
	fusen_busy(1);
	if (fusenTimerID) clearTimeout(fusenTimerID);
	
	var w_starus = (fusenInterval)? "付箋機能: 通信完了 [自動更新(" + (fusenInterval/1000) + "s) 待機中]" : "付箋機能: 通信完了 [自動更新 停止中]";
	window.status = "サーバーに接続中...";

	try
	{
		var dtNow = new Date;
		var xmlhttp = fusen_httprequest();
		var url = fusenJsonUrl+"?t="+dtNow.getHours()+dtNow.getMinutes()+dtNow.getSeconds();
		xmlhttp.onreadystatechange = readyStateChangeHandler;
		//xmlhttp.abort();
		xmlhttp.open(mod, url, true);
		xmlhttp.send(null);
	}
	catch(e)
	{
		fusen_busy(0);
		if (confirm('サーバーに接続できませんでした。再試行しますか？'))
		{
			if (fusenRetTimerID)  clearTimeout(fusenRetTimerID);
			fusenRetTimerID = setInterval("fusen_init(0)", 2000);
		}
		else
		{
			fusenInterval = 0;
		}
		xmlhttp = null;
		return;
	}
	

	function readyStateChangeHandler()
	{
		window.status = "サーバーと通信中...";
		var er = "";
		if (xmlhttp.readyState == 4)
		{
			window.status = w_starus;
			try
			{
				if (xmlhttp.status == 200)
				{
					fusen_busy(0);
					//alert(xmlhttp.getAllResponseHeaders());
					var lm = xmlhttp.getResponseHeader("Last-Modified");
					if (mod == 'HEAD')
					{
						//window.status = dtNow.getSeconds() + ' : ' + lm;
						xmlhttp = null;
						if (fusenLastModified == lm)
						{
							fusen_set_timer();
						}
						else
						{
							fusen_getdata('GET');
						}
						return;
					}
					fusenLastModified = lm;
					var txt = xmlhttp.responseText;
					try
					{
						var obj = getElement('fusen_area');
						if (fusenFromSkin)
						{
							var pobj = getElement('fusen_anchor');
						}
						else
						{
							var pobj = getElement('fusen_area');
						}
						var o_left = 0;
						var o_top = 0;
						
						fusenMovingObj = null;
						fusenMovingFlg = false;
						
						while (pobj != null) { 
							o_left += parseInt(pobj.offsetLeft); 
							o_top += parseInt(pobj.offsetTop); 
							pobj = pobj.offsetParent; 
						}
						getElement('edit_bx').value = o_left;
						getElement('edit_by').value = o_top;
						getElement('edit_pass').value = "";
						
						var frm = getElement('edit_frm');
						var re = /input|textarea|select/i;
						var tag = '';
						for (var i = 0; i < frm.length; i++ )
						{
							var child = frm[i];
							tag = String(child.tagName);
						}
						
						var edit_item = new Array();
						var change_status = false;
						
						if (fusenObj)
						{
							eval( 'fusenObj_N = ' + txt );
							
							// 新規 or 編集された付箋
							for (var id in fusenObj_N)
							{
								if (!fusenObj[id] || fusenObj[id].tt < fusenObj_N[id].tt)
								{
									change_status = true;
									edit_item[id] = true;
									if (fusenObj[id])
									{
										getElement('fusen_id' + id).id = 'fusen_id_remove' + id;
									}
									fusenObj[id] = fusenObj_N[id];
								}
							}
							// 削除された付箋
							for (var id in fusenObj)
							{
								if (!fusenObj_N[id])
								{
									change_status = true;
									fusen_removelines(id);
									obj.removeChild(getElement('fusen_id' + id));
									delete fusenObj[id];
								}
							}
						}
						else
						{
							eval( 'fusenObj = ' + txt );
							edit_item = fusenObj;
							while (obj.childNodes.length > 0) obj.removeChild(obj.firstChild);
						}
						
						if (edit_item)
						{
							// 接続線情報を破棄
							fusenLines = new Array();
							
							for(var id in edit_item)
							{
								// 編集権限
								fusenObj[id].auth = false;
								if (fusenX_admin) fusenObj[id].auth = true;
								else if (fusenX_uid && fusenX_uid == fusenObj[id].uid) fusenObj[id].auth = true;
								else if (!fusenObj[id].uid && fusenX_ucd && fusenX_ucd == fusenObj[id].ucd) fusenObj[id].auth = true;
								
								var cobj = fusen_create(id, fusenObj[id]);
								document.getElementById('fusen_area').appendChild(cobj);
								fusen_set_onmousedown(cobj,id)
								cobj.onmouseover = fusen_onmouseover;
								cobj.onmouseout = fusen_onmouseout;
								fusenFullFlg[id] = false;
								
								// サイズ再設定
								if (!fusenObj[id].fix)
								{
									fusen_size_init(cobj);
								}
								
								if (change_status && getElement('fusen_id_remove' + id))
								{
									obj.removeChild(getElement('fusen_id_remove' + id));
								}
								
								if (change_status) {fusen_setlines(id);}
							}
							
							if (fusenDustboxFlg)
							{
								fusenDustboxFlg = false;
								fusen_dustbox();
							}
							else
							{
								if (!change_status) {fusen_setlines();}
							}
							
							if (!fusenLoaded)
							{
								var jump_id = (!location.hash)? '' : location.hash.replace(/^#fusen([\d]+)$/,"$1");
								if (!(!jump_id))
								{
									fusen_select(jump_id,true);
									setInterval("fusen_select_clear()", 5000);
								}
								document.onmouseup = fusen_onmouseup;
								document.onmousemove = fusen_onmousemove;
								
								getElement("fusen_top_menu").style.visibility = 'visible';
								fusen_transparent();
							}
							change_status = true;
							fusenLoaded = true;
						}
						if (change_status) fusen_list_make();
						fusen_set_timer();
					}
					catch(e)
					{
						er = "無効なデータです。";
						fusenLastModified = '';
					}
				}
				else
				{
					er = "付箋通信ができませんでした。";
				}
			}
			catch(e)
			{
				er = "付箋通信ができませんでした。";
			}
			
			if (er)
			{
				if (fusenGetRetry++ >= 60/(fusenInterval/1000)) // 1分間は自動再試行する
				{
					fusenGetRetry = 0;
					fusen_busy(0)
					if (confirm(er + ' 再試行しますか？ 接続先: ' + url.replace(/^https?:\/\/([^\/]+).*$/,"$1")))
					{
						if (fusenRetTimerID)  clearTimeout(fusenRetTimerID);
						fusenRetTimerID = setInterval("fusen_init(0)", 1000);
					}
					else
					{
						fusenInterval = 0;
						window.status = "付箋機能: 通信完了 [自動更新 停止中]";
						getElement('fusen_menu_interval').selectedIndex = 0;
					}
				}
				else
				{
					fusen_set_timer();
				}
			}
			else
			{
				fusenGetRetry = 0;
			}
			er = '';
			xmlhttp = null;
			return;
		}
	}
}

// Get text in fusen.
function fusen_getchildtext(objref) {
	var obj;
	var output = '';
	if (typeof objref == 'string') obj = getElement(objref);
	else obj = objref;
	if (!obj) return '';
	var group = obj.childNodes;
	for (var i = 0; i < group.length; i++) {
		if (group[i].nodeType == 3) output += group[i].nodeValue.replace(/[\r\n]/,'');
		if (group[i].childNodes.length > 0) output += fusen_getchildtext(group[i]);
	}
	return output; 
}

function fusen_grep(pat) {
	fusenMovingObj = null;
	var re = new RegExp(pat, 'im');
	for(var id in fusenObj) {
		if (!fusenDustboxFlg && (fusenObj[id].del)) continue;
		if (fusenDustboxFlg && !(fusenObj[id].del)) continue;
		if (fusenObj[id].disp.match(re) || fusenObj[id].name.match(re)) {
			getElement('fusen_id' + id).style.visibility = "visible";
		} else {
			getElement('fusen_id' + id).style.visibility = "hidden";
		}
	}
}

// editbox control
function fusen_new(dblclick)
{
	if (!fusenLoaded) return;
	
	if (fusenDustboxFlg)
	{
		fusen_dustbox();
		if (dblclick) return;
	}
	
	if (fusenShowFlg['fusen_editbox']) {fusen_show('fusen_editbox');return;}
	
	fusenMovingObj = null;
	
	if (fusenTimerID) clearTimeout(fusenTimerID);
	
	getElement('edit_id').value = '';
	getElement('edit_ln').value = '';
	getElement('tc000000').selected = true;
	getElement('bgffffff').selected = true;
	getElement('edit_body').value = '';
	getElement('edit_name').style.visibility = "visible";
	getElement('edit_l').value = mouseX;
	getElement('edit_t').value = mouseY;
	getElement('edit_w').value = 0;
	getElement('edit_h').value = 0;
	getElement('edit_fix').value = 0;
	getElement('edit_mode').value = 'edit';
	fusen_show('fusen_editbox');
}

function fusen_editbox_hide() {
	fusenMovingObj = null;
	getElement('edit_name').style.visibility = "hidden";
	fusen_hide('fusen_editbox');
	fusen_set_timer();
}

function fusen_save()
{
	if (getElement('edit_mode').value == 'edit' && !getElement('edit_body').value)
	{
		alert('内容を記入してください。');
		return;
	}
	fusenDustboxFlg = false;
	fusen_postdata(false);
	fusen_init(1);
	fusen_hide('fusen_editbox');
}

function fusen_setpos(id,auto)
{
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	
	fusenMovingObj = null;
	
	var obj = getElement('fusen_id' + id);
	
	getElement('edit_id').value = id;
	getElement('edit_l').value = parseInt(obj.style.left.replace("px",""));
	getElement('edit_t').value = parseInt(obj.style.top.replace("px",""));
	if (auto)
	{
		getElement('edit_fix').value = 0;
		fusenObj[id].fix = 0;
		fusen_set_menu_html(getElement('fusen_id' + id + 'menu'),id,'');
		obj.style.overflow = 'visible';
		obj.style.whiteSpace = 'nowrap';
		obj.style.width = 'auto';
		obj.style.height = 'auto';
		fusen_size_init(obj);
		fusen_setlines(id);
	}
	else
	{
		getElement('edit_fix').value = fusenObj[id].fix;
	}
	if (fusenObj[id].fix)
	{
		getElement('edit_w').value = fusenObj[id].w = parseInt(obj.style.width.replace("px",""));
		getElement('edit_h').value = fusenObj[id].h = parseInt(obj.style.height.replace("px",""));
	}
	else
	{
		getElement('edit_w').value = fusenObj[id].w;
		getElement('edit_h').value = fusenObj[id].h;
	}
	
//	getElement('edit_z').value = getElement(id).style.zIndex;
	getElement('edit_mode').value = 'set';
	
	fusen_set_menu_html(getElement('fusen_id' + id + 'menu'),id,'');
	
	fusen_postdata(true);
}

function fusen_edit(id)
{
	if (fusenShowFlg['fusen_editbox']) {fusen_show('fusen_editbox');return;}

	if (fusenObj[id].lk) return;
	
	if (!fusenObj[id].auth) return;
	
	fusenMovingObj = null;

	if (fusenTimerID) clearTimeout(fusenTimerID);

	var obj = getElement('fusen_id' + id);
	var text_body = fusenObj[id].txt;
	
	text_body = text_body.replace(/&amp;/g,"&");
	text_body = text_body.replace(/&lt;/g,"<");
	text_body = text_body.replace(/&gt;/g,">");
	text_body = text_body.replace(/&quot;/g,"\"");
	
	getElement('edit_id').value = id;
	getElement('edit_l').value = parseInt(obj.style.left.replace("px",""));
	getElement('edit_t').value = parseInt(obj.style.top.replace("px",""));
	getElement('edit_ln').value = (fusenObj[id].ln) ? 'id' + fusenObj[id].ln : '';
	getElement('edit_name').style.visibility = "hidden";
	getElement('edit_body').value = text_body;
	getElement('edit_mode').value = 'edit';
	getElement('edit_w').value = fusenObj[id].w;
	getElement('edit_h').value = fusenObj[id].h;
	getElement('edit_fix').value = fusenObj[id].fix;

	var tcid = fusenObj[id].tc;
	if (!tcid) tcid = 'tc000000';
	else tcid = 'tc' + tcid.substr(1);
	var tcobj = getElement(tcid);
	if (!tcobj) getElement('tc000000').selected = true;
	else getElement(tcid).selected = true;

	var bgid = fusenObj[id].bg;
	if (!bgid) bgid = 'bgffffff';
	else bgid = 'bg' + bgid.substr(1);
	var bgobj = getElement(bgid);
	if (!bgobj) getElement('bg000000').selected = true;
	else getElement(bgid).selected = true;

	fusen_show('fusen_editbox');
}

function fusen_link(id) {
	fusenMovingObj = null;
	getElement('edit_l').value = parseInt(getElement('fusen_id'+id).style.left.replace("px",""));
	getElement('edit_t').value = parseInt(getElement('fusen_id'+id).style.top.replace("px","")) + getElement('fusen_id'+id).offsetHeight + 10;
	getElement('edit_w').value = 0;
	getElement('edit_h').value = 0;
	getElement('edit_fix').value = 0;
	getElement('edit_id').value = '';
	getElement('edit_ln').value = 'id' + id;
	getElement('edit_name').style.visibility = "visible";
	getElement('edit_body').value = '';
	getElement('edit_mode').value = 'edit';
	fusen_show('fusen_editbox');
}

function fusen_del(id)
{
	fusenMovingObj = null;
	var ok;
	var mode;
	
	if (fusenDustboxFlg)
	{
		ok = confirm('完全削除しますか？');
		mode = false;
	}
	else
	{
		ok = confirm('ゴミ箱へ入れますか？');
		mode = true;
	}
	
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	
	if (ok)
	{
		getElement('edit_id').value = id;
		getElement('edit_mode').value = 'del';
		//getElement('edit_ln').value = (fusenObj[id].ln) ? 'id' + fusenObj[id].ln : '';
		
		// 表示更新
		getElement('fusen_id' + id).style.visibility = "hidden";
		fusen_set_menu_html(getElement('fusen_id' + id + 'menu'),id,'del');
		getElement('fusen_id' + id).style.border = fusenBorderObj['del'];
		fusenObj[id].del = true;
		
		if (!fusenDustboxFlg)
		{
			//fusen_dustbox();
			fusen_list_make();
			fusen_removelines(id);
			fusen_setlines(id);
		}
		
		// サーバーデータ更新
		//fusen_postdata(mode);
		//if (!mode) 	fusen_init(1);
		fusen_postdata(true);
		if (!mode) 
		{
			getElement('fusen_area').removeChild(getElement('fusen_id' + id));
			delete fusenObj[id];
			fusen_list_make();
		}
	}
}

function fusen_recover(id)
{
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	
	fusenMovingObj = null;
	getElement('edit_id').value = id;
	getElement('edit_mode').value = 'recover';
	//getElement('edit_ln').value = (fusenObj[id].ln) ? 'id' + fusenObj[id].ln : '';
	
	// 表示更新
	fusen_set_menu_html(getElement('fusen_id' + id + 'menu'),id,'');
	getElement('fusen_id' + id).style.border = fusenBorderObj['normal'];
	getElement('fusen_id' + id).style.visibility = "visible";
	fusenObj[id].del = false;
	fusen_dustbox();
	fusen_list_make();
	
	// サーバーデータ更新
	fusen_postdata(true);
}

function fusen_burn()
{
	fusenMovingObj = null;
	var ok;
	//var mode = "burn";
	
	if (!fusenDustboxFlg) fusen_dustbox();
	
	ok = confirm('ゴミ箱を空にしますか？');
	if (!ok) return;
	
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	
	getElement('edit_id').value = "0";
	getElement('edit_mode').value = 'burn';

	// サーバーデータ更新
	fusen_postdata(false);
	fusen_init(1);
	alert ('ゴミ箱を空にしました。');
	fusen_dustbox();

}

function fusen_lock(id)
{
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	
	fusenMovingObj = null;
	getElement('edit_id').value = id;
	getElement('edit_mode').value = 'lock';
	fusenObj[id].lk = true;
	fusen_set_onmousedown(getElement('fusen_id' + id),id);
	
	// 表示更新
	getElement('fusen_id' + id).onmousedown = null;
	fusen_set_menu_html(getElement('fusen_id' + id + 'menu'),id,'lock');
	getElement('fusen_id' + id).style.border = fusenBorderObj['lock'];
	getElement('fusen_id' + id).style.cursor = 'auto';
	getElement('fusen_id' + id + 'resize').style.visibility = 'hidden';
	getElement('fusen_id' + id + 'wresize').style.visibility = 'hidden';
	fusen_show_full(id,'close');

	// サーバーデータ更新
	fusen_postdata(true);
}

function fusen_unlock(id)
{
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	
	fusenMovingObj = null;
	getElement('edit_id').value = id;
	getElement('edit_mode').value = 'unlock';
	fusenObj[id].lk = false;
	fusen_set_onmousedown(getElement('fusen_id' + id),id);
	
	// 表示更新
	fusen_set_menu_html(getElement('fusen_id' + id + 'menu'),id,'');
	getElement('fusen_id' + id).style.border = fusenBorderObj['normal'];
	getElement('fusen_id' + id).style.cursor = 'move';
	getElement('fusen_id' + id + 'resize').style.visibility = 'visible';
	getElement('fusen_id' + id + 'wresize').style.visibility = 'visible';
	fusen_show_full(id,'close');

	// サーバーデータ更新
	fusen_postdata(true);
}

function fusen_show(id)
{
	fusenShowFlg[id] = true;
	if (fusenTimerID) clearTimeout(fusenTimerID);
	//window.status = parseInt(getElement('edit_bx').value);
	//var left = Math.max(getWinXOffset() + 5,parseInt(getElement('edit_bx').value));
	//var top = Math.max(getWinYOffset() + 5,(mouseY - 150));
	if (id == 'fusen_editbox')
	{
		var top = getElement('edit_t').value;
		var left = getElement('edit_l').value;
	}
	else
	{
		var top = mouseY;
		var left = mouseX;
	}
	//window.status = left+":"+top;
	getElement(id).style.left = left + "px";
	getElement(id).style.top = top + "px";
	
	getElement(id).style.zIndex = (id == 'fusen_help')? 120 : 100;
	getElement(id).style.visibility = "visible";
	getElement(id).onmousedown = fusen_onmousedown;
	
	//if (id == 'fusen_editbox') getElement("edit_body").focus();
	if (id == 'fusen_editbox')
		setTimeout(function(){getElement("edit_body").focus();window.scrollTo(left,Math.max(0,top - 100));},10);
	else
		setTimeout(function(){window.scrollTo(left,Math.max(0,top - 100));},10);
	//fusen_winScroll(left,top);
	
	function getWinXOffset()
	{
		if (IE)
		{
			if(document.compatMode && document.compatMode=='CSS1Compat')
				return document.documentElement.scrollLeft;
			else
				return document.body.scrollLeft;
		}
		else if(window.scrollX) return window.scrollX; // Mozilla
		else if(window.pageXOffset) return window.pageXOffset; // Opera, NN4
		else return 0;
	}
	
	function getWinYOffset()
	{
		if (IE)
		{
			if(document.compatMode && document.compatMode=='CSS1Compat')
				return document.documentElement.scrollTop;
			else
				return document.body.scrollTop;
		}
		else if(window.scrollY) return window.scrollY; // Mozilla
		else if(window.pageYOffset) return window.pageYOffset; // Opera, NN4
		else return 0;
	}

}

function fusen_hide(id)
{
	if (id == 'fusen_list') fusen_select_clear();
	fusenShowFlg[id] = false;
	with(getElement(id).style)
	{
		visibility = "hidden";
		left = "0px";
		top = "0px";
	}
	document.onmouseup = fusen_onmouseup;
	document.onmousemove = fusen_onmousemove;
	fusenDblClick = false;
	fusenMovingObj = null;
	getElement("edit_body").blur();
	getElement("edit_ln").blur();
	getElement('edit_name').style.visibility = "hidden";
	fusen_set_timer();
}

function fusen_dustbox()
{
	/*
	if (fusenBusyFlg)
	{
		alert(fsen_msg_nowbusy);
		return;
	}
	*/
	
	fusenMovingObj = null;
	fusenDustboxFlg = !fusenDustboxFlg;
	for(var id in fusenObj) {
		var obj = getElement('fusen_id' + id);
		if (fusenObj[id].del) {
			if (fusenDustboxFlg) obj.style.visibility = 'visible';
			else obj.style.visibility = 'hidden';
		} else {
			if (fusenDustboxFlg) obj.style.visibility = 'hidden';
			else obj.style.visibility = 'visible';
		}
	}
	if (fusenDustboxFlg)
	{
		fusen_removelines();
		if (fusenTimerID) clearTimeout(fusenTimerID);
	}
	else
	{
		fusen_setlines();
		fusen_set_timer();
	}
}

function fusen_transparent()
{
	if (fusenBodyStyle != 'fusen_body')
	{
		fusenBodyStyle = 'fusen_body';
	}
	else
	{
		fusenBodyStyle = 'fusen_body_trans';
	}
	for (var i = 0; i < getElement('fusen_area').childNodes.length; i++ )
	{
		if (getElement('fusen_area').childNodes[i].id.indexOf('fusen_id') == 0)
			getElement('fusen_area').childNodes[i].className = fusenBodyStyle;
	}

}

function fusen_set_menu_html(tobj,id,mode)
{
	//tobj.innerHTML = '<a name="fusenid' + id + '"></a>id.' + id + ': ';
	tobj.innerHTML = 'id.' + id + ': ';
	if (mode == 'del')
	{
		if (fusenObj[id].auth)
		{
			tobj.innerHTML +=
				' <a href="javascript:fusen_recover(' + id + ')" title="ゴミ箱から戻す">recover</a>' +
				' <a href="javascript:fusen_del(' + id + ')" title="完全削除">del</a>';
		}
	}
	else if (mode == 'lock')
	{
		if (fusenObj[id].auth)
		{
			tobj.innerHTML +=
				' <a href="javascript:fusen_unlock(' + id + ')" title="ロック解除">unlock</a>';
		}
		tobj.innerHTML +=
			' <a href="javascript:fusen_link(' + id + ')" title="線を繋げて新規作成">line</a>';
	}
	else 
	{
		if (fusenObj[id].auth)
		{
			tobj.innerHTML +=
				' <a href="javascript:fusen_edit(' + id + ')" title="編集">edit</a>';
			tobj.innerHTML +=
				' <a href="javascript:fusen_lock(' + id + ')" title="ロック">lock</a>';
		}
		tobj.innerHTML +=
			' <a href="javascript:fusen_link(' + id + ')" title="線を繋げて新規作成">line</a>';
		if (fusenObj[id].auth)
		{	
			tobj.innerHTML +=
				' <a href="javascript:fusen_del(' + id + ')" title="ゴミ箱へ">del</a>';
		}
		if (fusenObj[id].fix)
		{
			tobj.innerHTML +=
				' <a href="javascript:fusen_setpos(' + id + ',1)" title="サイズ自動調整">auto</a>';
		}
	}
	return;
}

function fusen_create_menuobj(id, mode, obj) {
	var cobj = document.createElement("DIV");
	cobj.className = 'fusen_menu';
	cobj.id = 'fusen_id' + id + 'menu';
	fusen_set_menu_html(cobj,id,mode);
	return cobj;
}

function fusen_create_infoobj(id, obj) {
	var cobj = document.createElement("DIV");
	var d = (obj.et != "")? " : " + obj.et.substring(0,2) + "/" + obj.et.substring(2,4) + "/" + obj.et.substring(4,6) + " " + obj.et.substring(6,8) + ":" + obj.et.substring(8,10) : "";
	var md = (obj.mt != "")? obj.mt.substring(0,2) + "/" + obj.mt.substring(2,4) + "/" + obj.mt.substring(4,6) + " " + obj.mt.substring(6,8) + ":" + obj.mt.substring(8,10) : "";
	

	
	cobj.className = 'fusen_info';
	cobj.id = 'fusen_id' + id + 'info';
	cobj.innerHTML = '<span title="付箋作成者 at '+md+'">' + obj.name + '</span>' + '<span title="最終更新日時' + md + '">' + d + '</span>';
	cobj.onmouseout = fusen_moving_on;
	cobj.onmouseover = fusen_moving_off;
	return cobj;
}

function fusen_create_contentsobj(id, obj) {
	var cobj = document.createElement("DIV");
	cobj.className = 'fusen_contents';
	cobj.id = 'fusen_id' + id + 'contents';
	cobj.innerHTML = obj.disp;
	cobj.onmouseout = fusen_moving_on;
	cobj.onmouseover = fusen_moving_off;
	cobj.title = '';
	return cobj;
}

function fusen_create_resizeobj(id,obj) {
	var cobj = document.createElement("IMG");
	cobj.className = 'fusen_resize';
	cobj.id = 'fusen_id' + id + 'resize';
	cobj.src = './image/resize.gif';
	cobj.title = cobj.alt = 'Resize';
	cobj.onmousedown = function(){fusenResizeFlg=1;return true;};
	if (obj.lk)
	{
		cobj.style.visibility = 'hidden';
	}
	return cobj;
}

function fusen_create_wresizeobj(id,obj) {
	var cobj = document.createElement("IMG");
	cobj.className = 'fusen_wresize';
	cobj.id = 'fusen_id' + id + 'wresize';
	cobj.src = './image/w_resize.gif';
	cobj.title = cobj.alt = 'Set Width';
	cobj.onmousedown = function(){fusenResizeFlg=2;return true;};
	if (obj.lk)
	{
		cobj.style.visibility = 'hidden';
	}
	return cobj;
	/*
	var iobj = document.createElement("A");
	iobj.name = "fusenid"+id;
	iobj.appendChild(cobj);
	return iobj;
	*/
}

function fusen_create(id, obj) {
	var fusenobj = document.createElement("DIV");
	var menuobj;
	var border;
	var visible = 'visible';
	var ox = obj.x;
	var oy = obj.y;
	
	if (obj.del) {
		menuobj =  fusen_create_menuobj(id, 'del', obj);
		border = fusenBorderObj['del'];
		visible = 'hidden';
	} else  if (obj.lk) {
		menuobj =  fusen_create_menuobj(id, 'lock', obj);
		border = fusenBorderObj['lock'];
		fusenobj.style.cursor = 'auto';
	} else {
		menuobj =  fusen_create_menuobj(id, 'normal', obj);
		border = fusenBorderObj['normal'];
		if (fusenObj[id].auth)
			fusenobj.title = "ダブルクリック->編集";
		else
			fusenobj.title = "";
	}
	
	// ロック?
	if (obj.lk)
		fusenobj.style.cursor = 'auto';
	else
		fusenobj.style.cursor = 'move';
	
	// サイズ固定？
	if (obj.fix)
	{
		fusenobj.style.overflow = 'hidden';
		fusenobj.style.whiteSpace = 'normal';
		fusenobj.style.width = obj.w + 'px';
		fusenobj.style.height = (obj.fix == 1)? obj.h + 'px' : 'auto';
		if (obj.fix == 1) fusenobj.title = "ダブルクリック->すべて表示";
	}
	
	if (obj.bx) ox += parseInt(getElement('edit_bx').value) - obj.bx;
	if (obj.by) oy += parseInt(getElement('edit_by').value) - obj.by;
	
	ox = Math.max(0,ox);
	oy = Math.max(0,oy);
	
	fusenobj.id = 'fusen_id' + id;
	fusenobj.className = fusenBodyStyle;
	fusenobj.style.left = ox + 'px';
	fusenobj.style.top =  oy + 'px';
	fusenobj.style.color = obj.tc;
	fusenobj.style.backgroundColor = obj.bg;
	fusenobj.style.zIndex = obj.z;
	fusenobj.style.border = border;
	fusenobj.style.visibility = visible;
	fusenobj.appendChild(menuobj);
	fusenobj.appendChild(fusen_create_infoobj(id, obj));
	fusenobj.appendChild(fusen_create_contentsobj(id, obj));
	fusenobj.appendChild(fusen_create_wresizeobj(id, obj));
	fusenobj.appendChild(fusen_create_resizeobj(id, obj));
	fusenobj.ondblclick = fusen_ondblclick;
	
	return fusenobj;
}


// Line draw

function fusen_removelines(t_id) {
	var id, lineid, obj;
	for(id in fusenObj) {
		if (fusenObj[id].ln)
		{
			if (!t_id || t_id == id || t_id == fusenObj[id].ln)
			{
				lineid = 'line' + id + '_' + fusenObj[id].ln;
				obj = getElement(lineid);
				if (obj) obj.parentNode.removeChild(obj);
			}
		}
	}
}

function fusen_setlines(t_id)
{
	if (fusenLines[t_id] instanceof Array)
	{
		if (fusenLines[t_id])
		{
			for (var id in fusenLines[t_id])
			{
				fusen_setline2(id, fusenObj[id].ln);
			}
		}
	}
	else
	{
		if (!!t_id) fusenLines[t_id] = new Array();
		for(var id in fusenObj)
		{
			if (fusenObj[id].ln && !fusenObj[id].del && fusenObj[fusenObj[id].ln] && !fusenObj[fusenObj[id].ln].del)
			{
				if (!t_id || t_id == id || t_id == fusenObj[id].ln)
				{
					fusen_setline2(id, fusenObj[id].ln);
					if (!!t_id) {fusenLines[t_id][id] = true;}
				}
			}
		}
	}
}

function fusen_setline2(fromid, toid)
{
	try
	{
	function getCenter(obj){
		x = parseInt(obj.style.left.replace("px",""));
		x = x + obj.offsetWidth / 2;
		return x;
	}
	function getVCenter(obj){
		y = parseInt(obj.style.top.replace("px",""));
		y = y + obj.offsetHeight / 2;
		return y;
	}

	var lineid = 'line' + fromid + '_' + toid;
	var obj = getElement(lineid);
	if (obj) obj.parentNode.removeChild(obj);
	var fobj = getElement('fusen_id' + fromid);
	var tobj = getElement('fusen_id' + toid);
	if(!fobj) return;
	if(!tobj) return;
	
	var fx = getCenter(fobj);
	var fy = getVCenter(fobj);
	var fw = fobj.offsetWidth / 2;
	var fh = fobj.offsetHeight / 2;
	
	var tx = getCenter(tobj);
	var ty = getVCenter(tobj);
	var tw = tobj.offsetWidth / 2;
	var th = tobj.offsetHeight / 2;

	var ft = parseInt(fobj.style.top.replace("px","")) - 1;
	var fb = ft + fobj.offsetHeight;
	var fl = parseInt(fobj.style.left.replace("px","")) - 1;
	var fr = fl + fobj.offsetWidth;
	
	var tt = parseInt(tobj.style.top.replace("px","")) - 1;
	var tb = tt + tobj.offsetHeight;
	var tl = parseInt(tobj.style.left.replace("px","")) - 1;
	var tr = tl + tobj.offsetWidth;

	if (!IE)
	{
//		fb += 2;
//		fr += 2;
//		tb += 2;
//		tr += 2;
	}
	
	var lx;
	var ly;
	var lh;
	var lw;
	
	if (fx < tr && fb  < ty )
	{
		// 左上
		lx = fx;
		ly = fb + 1;
		lw = tl - lx;
		lh = ty - ly;
		if (!IE)
		{
			lw += 2;
			lh += 2;
		}
		border = 4;
		if (tl < fx)
		{
			lh = tt - ly;
			if (!IE) lh ++;
			lw = 0;
		}
		else if (fb > ty)
		{
			lx = fr + 2;
			lh = 0;
			lw = lw - fw - 2;
		}
	}
	else if (fx >= tr && fb < ty)
	{
		// 右上
		lx = tr + 1;
		ly = fb + 1;
		lw = fx - lx - 1;
		lh = ty - ly;
		if (!IE)
		{
			lw += 2;
			lh += 2;
		}
		border = 3;
		if (fx <= tr)
		{
			lx = fx;
			lh = tt - ly;
			lw = 0;
		}
		else if (fb > ty)
		{
			lw = lw - fw - 1;
			if (!IE) lw ++;
			lh = 0;
		}
	}
	else if (fx >= tr && fb >= ty)
	{
		// 右下
		lx = tr + 1;
		ly = ty;
		lw = fx - lx - 1;
		lh = ft - ly;
		if (!IE)
		{
			ly ++;
			lw += 2;
			lh += 1;
		}
		border = 2;
		if (fx <= tr)
		{
			lx = fx + 1;
			ly = tb;
			lw = 0;
		}
		else if (ft < ly)
		{
			lw = fl - tr - 1;
			if (!IE) lw ++;
			lh = 0;
		}
	}
	else
	{
		// 左下
		lx = fx;
		ly = ty;
		lw = tl - lx;
		lh = ft - ly;
		if (!IE)
		{
			ly ++;
			lw += 2;
			lh += 1;
		}
		border = 1;
		if (tl < fx)
		{
			lx = fx + 1;
			ly = tb + 1;
			lw = 0;
			lh = ft - tb - 1;
			if (!IE) lh += 2;
		}
		else if (ft < ly)
		{
			lx = fr + 1;
			lw = tl - fr - 1;
			if (!IE) lw ++;
			lh = 0;
		}
	}
	
	
	
	var obj = fusen_drawLine2(lx, ly, lw, lh, '#000000', lineid, border);
	document.getElementById('fusen_area').appendChild(obj);
	}
	catch(e)
	{
		alert(e);
	}
}

function fusen_drawLine2(x, y, w, h, color, nid, border){
	function _drawLine(x,y,w,h,color,b)
	{
		x = Math.max(0,parseInt(x));
		y = Math.max(0,parseInt(y));
		w = Math.max(0,parseInt(w));
		h = Math.max(0,parseInt(h));
		//window.status = x+','+y+','+w+','+h+','+color+','+b;
		var objLine = document.createElement("div");
		var strColor = color;
		with(objLine.style)
		{
			backgroundColor = 'transparent';
			position  = "absolute";
			overflow  = "hidden";
			width     = w + "px";
			height    = h + "px";
			top  = y + "px";
			left = x + "px";
			borderColor = color;
			borderColor = "blue";
			borderWidth = "0px";
			borderStyle = "solid";
			zIndex = "0";
		}
		if (border == 1) {objLine.style.borderTopWidth = "1px"; objLine.style.borderLeftWidth = "1px";}
		if (border == 2) {objLine.style.borderTopWidth = "1px"; objLine.style.borderRightWidth = "1px";}
		if (border == 3) {objLine.style.borderBottomWidth = "1px"; objLine.style.borderRightWidth = "1px";}
		if (border == 4) {objLine.style.borderBottomWidth = "1px"; objLine.style.borderLeftWidth = "1px";}
		return objLine;
	}
	
	function _Img1(x,y,w,h,color,b)
	{
		var obj = document.createElement("img");
		obj.src = "./image/connect.gif";
		obj.style.zIndex = 0;
		obj.style.position  = "absolute";
		if (IE)
		{
			var ox = 3;
			var oy = 3;
		}
		else
		{
			var ox = 4;
			var oy = 4;
		}
		if (border == 1){obj.style.top = (y + h - oy) + "px";obj.style.left = (x - ox) + "px";}
		if (border == 2){obj.style.top = (y + h - oy) + "px";obj.style.left = (x + w - ox) + "px";}
		if (border == 3){obj.style.top = (y - oy) + "px";obj.style.left = (x + w - ox) + "px";}
		if (border == 4){obj.style.top = (y - oy) + "px";obj.style.left = (x - ox) + "px";}
		return obj;
	}
	function _Img2(x,y,w,h,color,b)
	{
		var obj = document.createElement("img");
		obj.src = "./image/connect.gif";
		obj.style.zIndex = 0;
		obj.style.position  = "absolute";
		if (IE)
		{
			var ox = 3;
			var oy = 3;
		}
		else
		{
			var ox = 4;
			var oy = 4;
		}
		if (border == 1){obj.style.top = (y - oy) + "px";obj.style.left = (x + w - ox) + "px";}
		if (border == 2){obj.style.top = (y - oy) + "px";obj.style.left = (x - ox) + "px";}
		if (border == 3){obj.style.top = (y + h - oy) + "px";obj.style.left = (x - ox) + "px";}
		if (border == 4){obj.style.top = (y + h - oy) + "px";obj.style.left = (x + w - ox) + "px";}
		return obj;
	}

	var objLines = document.createElement("div")
	objLines.id = nid;
	objLines.appendChild(_drawLine(x, y, w, h, color, border));
	objLines.appendChild(_Img1(x, y, w, h, color, border));
	objLines.appendChild(_Img2(x, y, w, h, color, border));
	return objLines;
}


// Event

function fusen_onmousedown(e) {
	if (IE)
	{
		if (event.button != 1) return;
		var tag = String(event.srcElement.tagName);
	}
	else
	{
		if (e.which != 1) return;
		var tag = String(e.target.tagName);
	}
	
	if (!tag.match(/div|img|form/i))
	{
		this.cancelBubble = true;
		return;
	}
	
	if (fusenNowMovingOff) return;
	
	//fusen_select_clear();
	if (fusenTimerID) clearTimeout(fusenTimerID);
	
	fusenMovingObj = this;
	fusenClickW = fusenMovingObj.offsetWidth - 2;
	fusenClickH = fusenMovingObj.offsetHeight - 2;
	if (IE) {
		offsetX = event.clientX - fusenMovingObj.style.posLeft;
		offsetY = event.clientY - fusenMovingObj.style.posTop;
		fusenClickX = event.clientX;
		fusenClickY = event.clientY;
	} else {
		offsetX = e.pageX - parseInt(fusenMovingObj.style.left.replace("px",""));
		offsetY = e.pageY - parseInt(fusenMovingObj.style.top.replace("px",""));
		fusenClickX = e.pageX;
		fusenClickY = e.pageY;

	}
	for(var id in fusenObj) {
		getElement('fusen_id' + id).style.zIndex = 1;
	}
	fusenMovingObj.style.zIndex = 90;
	fusenMovingFlg = false;
	return false;
}

function fusen_onmousemove(e)
{
	var nowpos;
	if(IE)
	{
		if (document.compatMode && document.compatMode=='CSS1Compat')
		{
			mouseX = document.documentElement.scrollLeft + event.clientX;
			mouseY = document.documentElement.scrollTop + event.clientY;
		}
		else
		{
			mouseX = document.body.scrollLeft + event.clientX;
			mouseY = document.body.scrollTop + event.clientY;
		}
		nowpos = event.clientX + "," + event.clientY;
	} else {
		mouseX = e.pageX;
		mouseY = e.pageY;
		nowpos = e.pageX + "," +e.pageY;
	}
	if (fusenMovingObj && nowpos != (fusenClickX + "," + fusenClickY))
	{
		var id = fusenMovingObj.id.replace('fusen_id','');
		if (fusenResizeFlg)
		{
			if (IE) {
				var x = Math.max(fusenMinWidth,fusenClickW + (event.clientX - fusenClickX));
				var y = Math.max(fusenMinHeight,fusenClickH + (event.clientY - fusenClickY));
				fusenMovingObj.style.width = x + "px";
				if (fusenResizeFlg == 1) fusenMovingObj.style.height = y + "px";
			} else {
				var x = Math.max(fusenMinWidth,fusenClickW + (e.pageX - fusenClickX));
				var y = Math.max(fusenMinHeight,fusenClickH + (e.pageY - fusenClickY));
				fusenMovingObj.style.width = x + "px";
				if (fusenResizeFlg == 1) fusenMovingObj.style.height = y + "px";
			}
			if (fusenResizeFlg == 1)
			{
				getElement('fusen_id' + id).style.overflow = "hidden";
				getElement('fusen_id' + id).style.whiteSpace = 'normal';
			}
			else
			{
				getElement('fusen_id' + id).style.overflow = "hidden";
				getElement('fusen_id' + id).style.whiteSpace = 'normal';
				getElement('fusen_id' + id).style.height = 'auto';
			}
			fusenObj[id].fix = fusenResizeFlg;
			window.status = "付箋 "+id+" をリサイズ中...[ W:"+x+", H:"+y+" ]";
		}
		else
		{
			if (IE) {
				var x = event.clientX + document.body.scrollLeft - offsetX;
				var y = event.clientY + document.body.scrollTop - offsetY;
			} else {
				var x = (e.pageX - offsetX);
				var y = (e.pageY - offsetY);
			}
			fusenMovingObj.style.left = x + "px";
			fusenMovingObj.style.top = y + "px";
			if (IE && fusenMovingObj.id.indexOf('fusen_id') != -1){fusenMovingObj.focus();} //描画リフレッシュのため(IEのみ)
			window.status = "付箋 "+id+" を移動中...[ X:"+x+", Y:"+y+" ]";
		}
		if (!fusenDustboxFlg) {fusen_setlines(id);}
		fusenMovingFlg = true;
		return false;
	}
}

function fusen_onmouseup(e) {
	if (!fusenDustboxFlg && fusenMovingFlg && fusenMovingObj && fusenMovingObj.id.indexOf('fusen_id') == 0)
	{
		var id = fusenMovingObj.id.replace('fusen_id','');
		if (!fusenResizeFlg && fusenObj[id].fix)
		{
			fusenMovingObj = null;
			fusen_show_full(id,'close');
		}
		fusen_setpos(id,0);
	}
	fusenMovingObj = null;
	window.status = "";
	fusenMovingFlg = false;
	fusenResizeFlg = false;
	fusen_set_timer();
}

function fusen_ondblclick(e)
{
	var id = parseInt(this.id.replace('fusen_id',''));
	
	if (id)
	{
		if (!fusenFullFlg[id]  && fusenObj[id].fix == 1)
		{
			fusenDblClick = true;
			fusen_show_full(id,'open');
		}
		else if (!fusenObj[id].lk)
		{
			fusenMovingObj = null;
			fusenDblClick = true;
			fusen_edit(id);
		}
	}
	return;
}

function fusen_moving_off()
{
	if (fusenMovingFlg) return true;
	fusenNowMovingOff = true;
	fusenMovingObj = null;
}

function fusen_moving_on()
{
	if (fusenMovingFlg) return true;
	fusenNowMovingOff = false;
}

function fusen_set_onmousedown(obj,id)
{
	if (!id) return;
	
	if (fusenObj[id].lk)
	{
		obj.onmousedown = null;
	}
	else
	{
		obj.onmousedown = fusen_onmousedown;
	}

}
function fusen_onmouseover(e)
{
	var id = parseInt(this.id.replace('fusen_id',''));
	if (fusenFullTimerID[id]) clearTimeout(fusenFullTimerID[id]);
	if (fusenFullFlg[id])
	{
		//if (fusenFullTimerID[id]) clearTimeout(fusenFullTimerID[id]);
	}
	else
	{
		if (fusenObj[id].fix && (fusenObj[id].w == fusenMinWidth || fusenObj[id].h == fusenMinHeight))
		{
			eval('fusenFullTimerID[' + id + ']=setInterval("fusen_show_full(' + id + ',\'open\')", 500);');
		}
	}
	return;
}

function fusen_onmouseout(e)
{
	var id = parseInt(this.id.replace('fusen_id',''));

	if (id && !fusenMovingObj)
	{
		if (fusenObj[id].fix)
		{
			if (fusenFullTimerID[id]) clearTimeout(fusenFullTimerID[id]);
			//if (this.style.overflow != "hidden")
			if (fusenFullFlg[id])
			{
				eval('fusenFullTimerID[' + id + ']=setInterval("fusen_show_full(' + id + ',\'close\')", 500);');
			}
		}
	}
	return;
}

function fusen_show_full(id,mode)
{
	var obj = getElement('fusen_id' + id);
	
	if (fusenFullTimerID[id]) clearTimeout(fusenFullTimerID[id]);
	
	if (fusenMovingObj) return;
	
	if (fusenObj[id].fix)
	{
		if (mode == 'open')
		{
			fusenFullFlg[id] = true;
			obj.style.height = 'auto';
			obj.style.zIndex = 90;
			obj.title = (!fusenObj[id].auth || fusenObj[id].lk)? '' :"ダブルクリック->編集";
			if (fusenObj[id].w < 50)
			{
				fusen_size_init(obj);
			}
		}
		else
		{
			if (fusenObj[id].w && fusenObj[id].h)
			{
				obj.style.overflow = 'hidden';
				obj.style.whiteSpace = 'normal';
				obj.style.width = fusenObj[id].w + 'px';
				obj.style.height = fusenObj[id].h + 'px';
				obj.style.zIndex = 1;
				obj.title = (fusenObj[id].fix == 1)? "ダブルクリック->すべて表示" : '';
			}
			fusenFullFlg[id] = false;
			fusenDblClick = false;
		}
		fusen_setlines();
	}
}

function fusen_select(selectid,nolist)
{
	if (!fusenObj[selectid]) return;
	
	var top = parseInt(getElement('fusen_id' + selectid).style.top);
	var left = parseInt(getElement('fusen_id' + selectid).style.left);
	
	var dustbox = fusenObj[selectid].del;
	
	if (fusenObj[selectid].fix && (fusenObj[selectid].w <= fusenMinWidth || fusenObj[selectid].h <= fusenMinHeight))
	{
		fusen_show_full(selectid,'open');
		eval('fusenFullTimerID[' + selectid + ']=setInterval("fusen_show_full(' + selectid + ',\'close\')", 10000);');
	}
	
	fusen_editbox_hide();
	fusen_select_clear('on');
	with(getElement('fusen_id' + selectid))
	{
		style.border = fusenBorderObj['select'];
		className = 'fusen_body';
		style.zIndex = 150;
	}
	if (!nolist)
	{
		getElement('fusen_list').style.top = top + 'px';
		getElement('fusen_list').style.left = (left + getElement('fusen_id' + selectid).offsetWidth + 1) + 'px';left + 'px';
	}
	window.scrollTo(left,top);
	
	fusenDustboxFlg = !dustbox;
	fusen_dustbox();
}

function fusen_select_clear(mode)
{
	if (mode == 'on')
	{
		fusenBodyStyle = 'fusen_body';
	}
	else
	{
		fusenBodyStyle = 'fusen_body_trans';
	}
	fusen_transparent();
	
	for(var id in fusenObj)
	{
		if (fusenObj[id].del) {
			border = fusenBorderObj['del'];
		} else  if (fusenObj[id].lk) {
			border = fusenBorderObj['lock'];
		} else {
			border = fusenBorderObj['normal'];
		}
		with(getElement('fusen_id' + id))
		{
			style.border = border;
			style.zIndex = 1;
		}
	}
}

function fusen_winScroll(x,y)
{
	wy+=1;
	if( wy > y ){return;}
	window.scrollTo(x,wy);
	eval('setTimeout("fusen_winScroll(' + x + ',' + y + ')",1);');
}

// Initialize

function fusen_init(mode)
{
	if (fusenRetTimerID) clearTimeout(fusenRetTimerID);
	if (mode) 
	{
		fusen_getdata('GET');
	}
	else
	{
		fusen_getdata('HEAD');
	}
}

function fusen_set_elements()
{
	var html;
	html = '[<a href="javascript:fusen_hide(\'fusen_help\')">×</a>]'
+'<ul>'
+'<li>ダブルクリックで新しい付箋を作成できます。</li>'
+'<li>書き込むと、付箋が表示されます。</li>'
+'<li>付箋はドラッグして位置を移動できます。</li>'
+'<li>"edit"を押す、または付箋をダブルクリックすると、その付箋を編集できます。<br />※自分で作成した付箋のみ編集できます。</li>'
+'<li>"lock"を押すと、編集・移動を禁止します。lockした付箋は"unlock"で元に戻せます。<br />※自分で作成した付箋のみlockできます。</li>'
+'<li>"del"を押すと、付箋をゴミ箱へ移動します。ゴミ箱の付箋は"recover"で元に戻せます。<br />'
+'ゴミ箱の付箋で"del"を押すと、付箋を完全に削除します。<br />※自分で作成した付箋のみdelできます。</li>'
+'</ul>'
+'<dl>'
+'<dt>[新規]</dt>'
+'<dd>新しい付箋の編集画面を表示します。</dd>'
+'<dt>[ゴミ箱]</dt>'
+'<dd>ゴミ箱に入れられた付箋を表示します。</dd>'
+'<dt>[透明]</dt>'
+'<dd>すべての付箋を半透明表示にします。</dd>'
+'<dt>[更新]</dt>'
+'<dd>付箋を最新の状態に更新します。</dd>'
+'<dt>[リスト]</dt>'
+'<dd>このページの付箋を一覧表示します。</dd>'
+'<dt>[ヘルプ]</dt>'
+'<dd>この説明書きを表示します。</dd>'
+'<dt>検索</dt>'
+'<dd>入力したキーワードを持つ付箋のみ表示します。</dd>'
+'</dl>';
	var hobj = getElement('fusen_help');
	hobj.innerHTML = html;
	hobj.style.width = 'auto';
	hobj.style.width = hobj.offsetWidth + 'px';
	var eobj = getElement('fusen_editbox');
	eobj.style.width = 'auto';
	eobj.style.width = eobj.offsetWidth + 'px';
}

function fusen_size_init(obj)
{
	v_tmp = obj.style.visibility;
	l_tmp = obj.style.left;
	with(obj.style)
	{
		visibility = 'hidden'
		left = '0px';
		overflow = 'visible';
		whiteSpace = 'nowrap';
		width = 'auto';
		width = obj.offsetWidth + 'px';
		whiteSpace = 'normal';
		left = l_tmp;
		visibility = v_tmp;
	}
}

function fusen_list_make()
{
	var listobj = getElement('fusen_list');
	var listcount = 0;
	var burn;
	burn = (fusenX_admin)? " [ <a href=\"JavaScript:fusen_burn()\" title=\"ごみ箱を空にする\">ごみ箱を空にする</a> ]" : "";
	listobj.innerHTML = '[<a href="javascript:fusen_hide(\'fusen_list\');" title="閉じる">×</a>] [ <a href="javascript:fusen_hide(\'fusen_list\');JavaScript:fusen_new();" title="新しい付箋を貼る">新規</a> ]'+burn+' [ <a href="JavaScript:fusen_show(\'fusen_help\');" title="使い方">ヘルプ</a> ]<ul>';
	for(var id in fusenObj)
	{
		listcount ++;
		
		// リスト表示追加
		addtxt = fusenObj[id].disp.replace(/<[^>]+>/g,'').replace(/&nbsp;/g,' ').replace(/[\s]+/g,' ');
		if (addtxt.length > 30) addtxt = addtxt.substr(0,30) + '...';
		if (!addtxt.replace(/^[\s]+$/,'')) addtxt = "- no text -";
		
		dustbox = 0;
		if (fusenObj[id].del)
		{
			addtxt = '(ごみ箱)' + addtxt;
			//dustbox = 1;
		}
		listobj.innerHTML += '<li><a href="javascript:fusen_select('+id+')">'+addtxt+'</a></li>';
	}
	if (!listcount) listobj.innerHTML += '<li>このページに付箋はありません。</li>';
	if (getElement('pukiwiki_fusenlist'))
	{
		getElement('pukiwiki_fusenlist').innerHTML = '&nbsp;[ <a href="JavaScript:fusen_show(\'fusen_list\')">付箋(' + listcount + ')</a> ]';
	}
	listobj.innerHTML += '</ul>';
	list_left = listobj.style.left;
	list_visibility = listobj.style.visibility;
	listobj.style.visibility = 'hidden';
	listobj.style.left = '0px';
	listobj.style.width = 'auto';
	listobj.style.width = listobj.offsetWidth + 'px';
	listobj.style.left = list_left;
	listobj.style.visibility = list_visibility;
	listobj.style.zIndex = 100;
	return;
}

var __fusen_onload_save = window.onload;
window.onload = function() {
	if (__fusen_onload_save) __fusen_onload_save();
	//getElement('fusen_area').style.width = '1000px;'
	fusen_set_elements();
	fusen_init(1);

	if (IE) {
		var __fusen_ondblclick_save = document.ondblclick;
		document.ondblclick = function() {
			if (__fusen_ondblclick_save) __fusen_ondblclick_save();
			if (!fusenDblClick) fusen_new(true);
		}
	} else {
		var __fusen_ondblclick_save = window.ondblclick;
		window.ondblclick = function() {
			if (__fusen_ondblclick_save) __fusen_ondblclick_save();
			if (!fusenDblClick) fusen_new(true);
		}
	}
}

