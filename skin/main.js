// Init.
var pukiwiki_WinIE=(document.all&&!window.opera&&navigator.platform=="Win32");
var pukiwiki_Gecko=(navigator && navigator.userAgent && navigator.userAgent.indexOf("Gecko/") != -1);

// Helper image tag set
var pukiwiki_helper_img = 
'<img src="'+pukiwiki_root_url+'image/buttons.gif" width="103" height="16" border="0" usemap="#map_button" tabindex="-1">&nbsp;'+
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
	document.write(str);
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