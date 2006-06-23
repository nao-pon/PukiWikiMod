// Init.
var pukiwiki_WinIE=(document.all&&!window.opera&&navigator.platform=="Win32");
var pukiwiki_Gecko=(navigator && navigator.userAgent && navigator.userAgent.indexOf("Gecko/") != -1);
var pukiwiki_Is_pukiwikimod = (document.URL.indexOf(pukiwiki_root_url,0) == 0);

var pukiwiki_mapLoad=0;
var pukiwiki_initLoad=0;

// cookie
var pukiwiki_adv = pukiwiki_load_cookie("pwmod");
if (pukiwiki_adv) pukiwiki_save_cookie("pwmod",pukiwiki_adv,90,"/");

// Helper image tag set
var pukiwiki_adv_tag = '';
if (pukiwiki_adv == "on")
{
	pukiwiki_adv_tag += '<span style="cursor:pointer;">';
	
	if (pukiwiki_Is_pukiwikimod) pukiwiki_adv_tag +=
'<img src="'+pukiwiki_root_url+'image/clip.png" width="18" height="16" border="0" title="'+pukiwiki_msg_attach+'" alt="&amp;attachref;" onClick="javascript:pukiwiki_ins(\'&attachref();\'); return false;" '+'/'+'>';
	
	 pukiwiki_adv_tag +=
'<img src="'+pukiwiki_root_url+'image/ncr.gif" width="22" height="16" border="0" title="'+pukiwiki_msg_to_ncr+'" alt="'+pukiwiki_msg_to_ncr+'" onClick="javascript:pukiwiki_charcode(); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'image/br.gif" width="18" height="16" border="0" title="&amp;br;" alt="&amp;br;" onClick="javascript:pukiwiki_ins(\'&br;\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'image/iplugin.gif" width="18" height="16" border="0" title="Inline Plugin" alt="Inline Plugin" onClick="javascript:pukiwiki_ins(\'&(){};\'); return false;" '+'/'+'>'+
'<'+'/'+'span><br>';
}

var pukiwiki_helper_img = 
'<img src="'+pukiwiki_root_url+'image/buttons.gif" width="103" height="16" border="0" usemap="#map_button" tabindex="-1" '+'/'+'>'+
' '+
pukiwiki_adv_tag +
'<img src="'+pukiwiki_root_url+'image/colors.gif" width="64" height="16" border="0" usemap="#map_color" tabindex="-1" '+'/'+'> '+
'<span style="cursor:pointer;">'+
'<img src="'+pukiwiki_root_url+'face/smile.gif" width="15" height="15" border="0" title=":)" alt=":)" onClick="javascript:pukiwiki_face(\':)\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'face/bigsmile.gif" width="15" height="15" border="0" title=":D" alt=":D" onClick="javascript:pukiwiki_face(\':D\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'face/huh.gif" width="15" height="15" border="0" title=":p" alt=":p" onClick="javascript:pukiwiki_face(\':p\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'face/oh.gif" width="15" height="15" border="0" title="XD" alt="XD" onClick="javascript:pukiwiki_face(\'XD\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'face/wink.gif" width="15" height="15" border="0" title=";)" alt=";)" onClick="javascript:pukiwiki_face(\';)\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'face/sad.gif" width="15" height="15" border="0" title=";(" alt=";(" onClick="javascript:pukiwiki_face(\';(\'); return false;" '+'/'+'>'+
'<img src="'+pukiwiki_root_url+'face/heart.gif" width="15" height="15" border="0" title="&amp;heart;" alt="&amp;heart;" onClick="javascript:pukiwiki_face(\'&amp;heart;\'); return false;" '+'/'+'>'+
'<'+'/'+'span>';

// Common function.
function open_mini(URL,width,height){
	aWindow = window.open(URL, "mini", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=yes,resizable=no,width="+width+",height="+height);
}

function pukiwiki_show_fontset_img()
{
	if (!pukiwiki_mapLoad)
	{
		pukiwiki_mapLoad = 1;
		var map='<map name="map_button">'+
		'<area shape="rect" coords="0,0,22,16" title="URL" alt="URL" href="#" onClick="javascript:pukiwiki_linkPrompt(\'url\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="24,0,40,16" title="B" alt="B" href="#" onClick="javascript:pukiwiki_tag(\'b\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="43,0,59,16" title="I" alt="I" href="#" onClick="javascript:pukiwiki_tag(\'i\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="62,0,79,16" title="U" alt="U" href="#" onClick="javascript:pukiwiki_tag(\'u\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="81,0,103,16" title="SIZE" alt="SIZE" href="#" onClick="javascript:pukiwiki_tag(\'size\'); return false;" '+'/'+'>'+
		'<'+'/'+'map>'+
		'<map name="map_color">'+
		'<area shape="rect" coords="0,0,8,8" title="Black" alt="Black" href="#" onClick="javascript:pukiwiki_tag(\'Black\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="8,0,16,8" title="Maroon" alt="Maroon" href="#" onClick="javascript:pukiwiki_tag(\'Maroon\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="16,0,24,8" title="Green" alt="Green" href="#" onClick="javascript:pukiwiki_tag(\'Green\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="24,0,32,8" title="Olive" alt="Olive" href="#" onClick="javascript:pukiwiki_tag(\'Olive\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="32,0,40,8" title="Navy" alt="Navy" href="#" onClick="javascript:pukiwiki_tag(\'Navy\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="40,0,48,8" title="Purple" alt="Purple" href="#" onClick="javascript:pukiwiki_tag(\'Purple\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="48,0,55,8" title="Teal" alt="Teal" href="#" onClick="javascript:pukiwiki_tag(\'Teal\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="56,0,64,8" title="Gray" alt="Gray" href="#" onClick="javascript:pukiwiki_tag(\'Gray\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="0,8,8,16" title="Silver" alt="Silver" href="#" onClick="javascript:pukiwiki_tag(\'Silver\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="8,8,16,16" title="Red" alt="Red" href="#" onClick="javascript:pukiwiki_tag(\'Red\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="16,8,24,16" title="Lime" alt="Lime" href="#" onClick="javascript:pukiwiki_tag(\'Lime\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="24,8,32,16" title="Yellow" alt="Yellow" href="#" onClick="javascript:pukiwiki_tag(\'Yellow\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="32,8,40,16" title="Blue" alt="Blue" href="#" onClick="javascript:pukiwiki_tag(\'Blue\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="40,8,48,16" title="Fuchsia" alt="Fuchsia" href="#" onClick="javascript:pukiwiki_tag(\'Fuchsia\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="48,8,56,16" title="Aqua" alt="Aqua" href="#" onClick="javascript:pukiwiki_tag(\'Aqua\'); return false;" '+'/'+'>'+
		'<area shape="rect" coords="56,8,64,16" title="White" alt="White" href="#" onClick="javascript:pukiwiki_tag(\'White\'); return false;" '+'/'+'>'+
		'<'+'/'+'map>';
		document.write(map);
	}

	var str =  pukiwiki_helper_img + '<small> [ <a href="#" onClick="javascript:pukiwiki_show_hint(); return false;">' + pukiwiki_msg_hint + '<'+'/'+'a> ]<'+'/'+'small>';
	
	if (pukiwiki_adv == "on")
	{
		str = str + '<small> [ <a href="#" title="'+pukiwiki_msg_to_easy_t+'" onClick="javascript:pukiwiki_adv_swich(); return false;">' + 'Easy' + '<'+'/'+'a> ]<'+'/'+'small>';
	}
	else
	{
		str = str + '<small> [ <a href="#" title="'+pukiwiki_msg_to_adv_t+'" onClick="javascript:pukiwiki_adv_swich(); return false;">' + 'Adv.' + '<'+'/'+'a> ]<'+'/'+'small>';
	}
	
	document.write(str);
	
}

function pukiwiki_adv_swich()
{
	if (pukiwiki_adv == "on")
	{
		pukiwiki_adv = "off";
		pukiwiki_ans = confirm(pukiwiki_msg_to_easy);
	}
	else
	{
		pukiwiki_adv = "on";
		pukiwiki_ans = confirm(pukiwiki_msg_to_adv);
	}
	pukiwiki_save_cookie("pwmod",pukiwiki_adv,90,"/");
	if (pukiwiki_ans) window.location.reload();
}
function pukiwiki_save_cookie(arg1,arg2,arg3,arg4){ //arg1=dataname arg2=data arg3=expiration days
	if(arg1&&arg2)
	{
		if(arg3)
		{
			xDay = new Date;
			xDay.setDate(xDay.getDate() + eval(arg3));
			xDay = xDay.toGMTString();
			_exp = ";expires=" + xDay;
		}
		else
		{
			_exp ="";
		}
		if(arg4)
		{
			_path = ";path=" + arg4;
		}
		else
		{
			_path= "";
		}
		document.cookie = escape(arg1) + "=" + escape(arg2) + _exp + _path +";";
	}
}

function pukiwiki_load_cookie(arg){ //arg=dataname
	if(arg)
	{
		cookieData = document.cookie + ";" ;
		arg = escape(arg);
		startPoint1 = cookieData.indexOf(arg);
		startPoint2 = cookieData.indexOf("=",startPoint1) +1;
		endPoint = cookieData.indexOf(";",startPoint1);
		if(startPoint2 < endPoint && startPoint1 > -1 &&startPoint2-startPoint1 == arg.length+1)
		{
			cookieData = cookieData.substring(startPoint2,endPoint);
			cookieData = unescape(cookieData);
			return cookieData
		}
	}
	return false
}

function pukiwiki_area_highlite(id,mode)
{
	if (mode)
	{
		document.getElementById(id).className = "area_on";
	}
	else
	{
		document.getElementById(id).className = "area_off";
	}
	
}

function pukiwiki_check(f)
{
	if (pukiwiki_elem && pukiwiki_elem.type == "text")
	{
		if (!confirm(pukiwiki_msg_submit))
		{
			pukiwiki_elem.focus();
			return false;
		}
	}
	
	for (i = 0; i < f.elements.length; i++)
	{
		oElement = f.elements[i];
		if (oElement.type == "submit" && (!oElement.name || oElement.name == "comment"))
		{
			oElement.disabled = true;
		}
	}
	
	return true;

}

// Branch.
if (pukiwiki_WinIE)
{
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="' + pukiwiki_root_url + 'skin/winie.js"></scr'+'ipt>');
}
else if (pukiwiki_Gecko)
{
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="' + pukiwiki_root_url + 'skin/gecko.js"></scr'+'ipt>');
}
else
{
	document.write ('<scr'+'ipt type="text/javascr'+'ipt" src="' + pukiwiki_root_url + 'skin/other.js"></scr'+'ipt>');
}

// Add function in 'window.onload' event.
void function()
{
	var onload = window.onload;
	window.onload = function()
	{
		if (onload) onload();
		pukiwiki_initTexts();
	}
} ();

