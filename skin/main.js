// Init.
var pukiwiki_WinIE=(document.all&&!window.opera&&navigator.platform=="Win32");
var pukiwiki_Gecko=(navigator && navigator.userAgent && navigator.userAgent.indexOf("Gecko/") != -1);

// cookie
var pukiwiki_adv = pukiwiki_load_cookie("pwmod");

// Helper image tag set
var pukiwiki_adv_tag = '';
if (pukiwiki_adv == "on") pukiwiki_adv_tag = '<span style="cursor:hand;">'+
'<img src="'+pukiwiki_root_url+'image/ncr.gif" width="22" height="16" border="0" title="'+pukiwiki_msg_to_ncr+'" alt="'+pukiwiki_msg_to_ncr+'" onClick="javascript:pukiwiki_charcode(); return false;">'+
'<img src="'+pukiwiki_root_url+'image/br.gif" width="18" height="16" border="0" title="&amp;br;" alt="&amp;br;" onClick="javascript:pukiwiki_ins(\'&br;\'); return false;">'+
'<img src="'+pukiwiki_root_url+'image/iplugin.gif" width="18" height="16" border="0" title="Inline Plugin" alt="Inline Plugin" onClick="javascript:pukiwiki_ins(\'&(){};\'); return false;">'+
'<'+'/'+'span><br>';

var pukiwiki_helper_img = 
'<img src="'+pukiwiki_root_url+'image/buttons.gif" width="103" height="16" border="0" usemap="#map_button" tabindex="-1">&nbsp;'+
pukiwiki_adv_tag +
'<img src="'+pukiwiki_root_url+'image/colors.gif" width="64" height="16" border="0" usemap="#map_color" tabindex="-1">&nbsp;'+
'<span style="cursor:hand;">'+
'<img src="'+pukiwiki_root_url+'face/smile.gif" width="15" height="15" border="0" title=":)" alt=":)" onClick="javascript:pukiwiki_face(\':)\'); return false;">'+
'<img src="'+pukiwiki_root_url+'face/bigsmile.gif" width="15" height="15" border="0" title=":D" alt=":D" onClick="javascript:pukiwiki_face(\':D\'); return false;">'+
'<img src="'+pukiwiki_root_url+'face/huh.gif" width="15" height="15" border="0" title=":p" alt=":p" onClick="javascript:pukiwiki_face(\':p\'); return false;">'+
'<img src="'+pukiwiki_root_url+'face/oh.gif" width="15" height="15" border="0" title="XD" alt="XD" onClick="javascript:pukiwiki_face(\'XD\'); return false;">'+
'<img src="'+pukiwiki_root_url+'face/wink.gif" width="15" height="15" border="0" title=";)" alt=";)" onClick="javascript:pukiwiki_face(\';)\'); return false;">'+
'<img src="'+pukiwiki_root_url+'face/sad.gif" width="15" height="15" border="0" title=";(" alt=";(" onClick="javascript:pukiwiki_face(\';(\'); return false;">'+
'<img src="'+pukiwiki_root_url+'face/heart.gif" width="15" height="15" border="0" title="&amp;heart;" alt="&amp;heart;" onClick="javascript:pukiwiki_face(\'&amp;heart;\'); return false;">'+
'<'+'/'+'span>';

// Common function.
function open_mini(URL,width,height){
	aWindow = window.open(URL, "mini", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=yes,resizable=no,width="+width+",height="+height);
}

function pukiwiki_show_fontset_img()
{
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
	pukiwiki_save_cookie("pwmod",pukiwiki_adv,1,"/");
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