var pukiwiki_elem;
var pukiwiki_crl;
var pukiwiki_scrx;
var pukiwiki_scry;
var pukiwiki_rngx;
var pukiwiki_rngy;

function h_pukiwiki_make_copy_button(arg)
{
	if(pukiwiki_WinIE){
		document.write ("<input class=\"copyButton\" type=\"button\" value=\"COPY\" onclick=\"h_pukiwiki_doCopy('" + arg + "')\"><br />");
	}
}

function h_pukiwiki_doCopy(arg)
{
	var doc = document.body.createTextRange();
	doc.moveToElementText(document.all(arg));
	doc.execCommand("copy");
	alert(pukiwiki_msg_copyed);
}

function pukiwiki_pos(){
	if(!pukiwiki_WinIE)return;
	var et = document.activeElement.type;
	if (!(et == "text" || et == "textarea" || et == "submit"))
	{
		return;
	}
	
	var r=document.selection.createRange();
	pukiwiki_elem = document.activeElement;
	if (et == "text")
	{
		r.moveEnd("textedit");
		pukiwiki_crl =r.text.length;
	}
	else if (et == "textarea")
	{
		pukiwiki_rngx=r.offsetLeft;
		pukiwiki_rngy=r.offsetTop;
		pukiwiki_scrx=document.body.scrollLeft;
		pukiwiki_scry=document.body.scrollTop;
	}
	else if (et == "submit")
	{
		//document.activeElement.disabled = true;
	}
}

function pukiwiki_eclr(){
	if(!pukiwiki_WinIE)return;
	pukiwiki_elem = NULL;
}

function pukiwiki_face(v)
{
	if(!pukiwiki_WinIE || !pukiwiki_elem)return;

	if (pukiwiki_elem.type=="textarea")
	{
		document.body.scrollLeft=pukiwiki_scrx;
		document.body.scrollTop=pukiwiki_scry;
		var r=pukiwiki_elem.createTextRange();
		r.moveToPoint(pukiwiki_rngx,pukiwiki_rngy);
		r.text= ' ' + v + ' ';
		pukiwiki_elem.focus();
		pukiwiki_pos();
	}
	else if (pukiwiki_elem.type=="text")
	{
		var r=pukiwiki_elem.createTextRange();
		r.collapse();
		r.moveStart("character",pukiwiki_elem.value.length-pukiwiki_crl);
		r.text= ' ' + v + ' ';
		pukiwiki_elem.focus();
	}
}

function pukiwiki_tag(v) {
	if (!pukiwiki_WinIE || !document.selection) return;
	var str =
		document.selection.createRange().text;
	if (!str)
	{
		alert(pukiwiki_msg_select);
		return;
	}
	if ( v == 'size' )
	{
		var default_size = "%";
		v = prompt(pukiwiki_msg_fontsize, default_size);
		if (!v) return;
		if (!v.match(/(%|pt)$/))
			v += "pt";
		if (!v.match(/\d+(%|pt)/))
			return;
	}
	if (str.match(/^&font\([^\)]*\)\{.*\};$/))
	{
		str = str.replace(/^(&font\([^\)]*)(\)\{.*\};)$/,"$1," + v + "$2");
	}
	else
	{
		str = '&font(' + v + '){' + str + '};';
	}
	document.selection.createRange().text = str;
	if (pukiwiki_elem != null) pukiwiki_elem = null;
}

function pukiwiki_linkPrompt(v) {
	if (!pukiwiki_WinIE || !document.selection) return;
	var str = document.selection.createRange().text;
	if (!str)
	{
		alert(pukiwiki_msg_select);
		return;
	}
	var default_url = "http://";
	regex = "^s?https?://[-_.!~*'()a-zA-Z0-9;/?:@&=+$,%#]+$";
	var cbText = clipboardData.getData("Text");
	if(cbText && cbText.match(regex))
		default_url = cbText;
	var my_link = prompt('URL: ', default_url);
	if (my_link != null)
		document.selection.createRange().text = '[[' + str + ':' + my_link + ']]';
	if (pukiwiki_elem != null) pukiwiki_elem = null;
}

function pukiwiki_show_fontset_img(baseurl)
{
	if (!baseurl)
	{
		baseurl = '.';
	}
	
	if ( pukiwiki_WinIE )
	{
		var str = '<img src="'+baseurl+'/image/buttons.gif" width="103" height="16" border="0" usemap="#map_button" tabindex="-1">&nbsp;<img src="'+baseurl+'/image/colors.gif" width="64" height="16" border="0" usemap="#map_color" tabindex="-1">&nbsp;<span style="cursor:hand;"><img src="'+baseurl+'/face/smile.gif" width="15" height="15" border="0" alt=":)" onClick="javascript:pukiwiki_face(\':)\'); return false;"><img src="'+baseurl+'/face/bigsmile.gif" width="15" height="15" border="0" alt=":D" onClick="javascript:pukiwiki_face(\':D\'); return false;"><img src="'+baseurl+'/face/huh.gif" width="15" height="15" border="0" alt=":p" onClick="javascript:pukiwiki_face(\':p\'); return false;"><img src="'+baseurl+'/face/oh.gif" width="15" height="15" border="0" alt="XD" onClick="javascript:pukiwiki_face(\'XD\'); return false;"><img src="'+baseurl+'/face/wink.gif" width="15" height="15" border="0" alt=";)" onClick="javascript:pukiwiki_face(\';)\'); return false;"><img src="'+baseurl+'/face/sad.gif" width="15" height="15" border="0" alt=";(" onClick="javascript:pukiwiki_face(\';(\'); return false;"><img src="'+baseurl+'/face/heart.gif" width="15" height="15" border="0" alt="&amp;heart;" onClick="javascript:pukiwiki_face(\'&amp;heart;\'); return false;"><'+'/'+'span>';
		document.write(str);
	}
}