var pukiwiki_elem;

function h_pukiwiki_make_copy_button(arg)
{
	document.write ("<input class=\"copyButton\" type=\"button\" value=\"COPY\" onclick=\"h_pukiwiki_doCopy('" + arg + "')\"><br />");
}

function h_pukiwiki_doCopy(arg)
{
	var doc = document.body.createTextRange();
	doc.moveToElementText(document.all(arg));
	doc.execCommand("copy");
	alert(pukiwiki_msg_copyed);
}

function pukiwiki_pos(){
	var et = document.activeElement.type;
	if (!(et == "text" || et == "textarea"))
	{
		if (et == "submit") pukiwiki_elem = null;
		return;
	}
	
	pukiwiki_elem = document.activeElement;
	pukiwiki_elem.caretPos = document.selection.createRange().duplicate();
}

function pukiwiki_eclr(){
	pukiwiki_elem = NULL;
}

function pukiwiki_ins(v)
{
	if(!pukiwiki_elem)
	{
		alert(pukiwiki_msg_elem);
		return;	
	}
	
	if (v == "&(){};")
	{
		inp = prompt(pukiwiki_msg_inline1, '');
		if (inp == null) {pukiwiki_elem.focus();return;}
		v = "&" + inp;
		inp = prompt(pukiwiki_msg_inline2, '');
		if (inp == null) {pukiwiki_elem.focus();return;}
		v = v + "(" + inp + ")";
		inp = prompt(pukiwiki_msg_inline3, '');
		if (inp == null) {pukiwiki_elem.focus();return;}
		v = v + "{" + inp + "}";
		v = v + ";";
	}
	
	pukiwiki_elem.caretPos.text = v;
	pukiwiki_elem.focus();
}

function pukiwiki_face(v)
{
	if(!pukiwiki_elem)
	{
		alert(pukiwiki_msg_elem);
		return;	
	}
	
	if (pukiwiki_elem.caretPos.offsetLeft == pukiwiki_elem.createTextRange().offsetLeft)
		pukiwiki_elem.caretPos.text = '&nbsp; ' + v + ' ';
	else
		pukiwiki_elem.caretPos.text = ' ' + v + ' ';
	
	pukiwiki_elem.focus();
}

function pukiwiki_tag(v)
{
	if (!document.selection || !pukiwiki_elem)
	if (!pukiwiki_elem || !pukiwiki_elem.caretPos)
	{
		alert(pukiwiki_msg_elem);
		return;	
	}
	
	var str = pukiwiki_elem.caretPos.text;
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
	
	pukiwiki_elem.caretPos.text = str;
	pukiwiki_elem.focus();
	pukiwiki_pos();
}

function pukiwiki_linkPrompt(v)
{
	if (!document.selection || !pukiwiki_elem)
	{
		alert(pukiwiki_msg_elem);
		return;	
	}

	var str = document.selection.createRange().text;
	if (!str)
	{
		str = prompt(pukiwiki_msg_link, '');
		if (str == null) {pukiwiki_elem.focus();return;}
	}
	var default_url = "http://";
	regex = "^s?https?://[-_.!~*'()a-zA-Z0-9;/?:@&=+$,%#]+$";
	var cbText = clipboardData.getData("Text");
	if(cbText && cbText.match(regex))
		default_url = cbText;
	var my_link = prompt('URL: ', default_url);
	if (my_link != null)
		document.selection.createRange().text = '[[' + str + ':' + my_link + ']]';
	pukiwiki_elem.focus();
}

function pukiwiki_charcode()
{
	if (!document.selection || !pukiwiki_elem)
	{
		alert(pukiwiki_msg_elem);
		return;	
	}

	var str = document.selection.createRange().text;
	if (!str)
	{
		alert(pukiwiki_msg_select);
		return;
	}
	
	var j ="";
	for(var n = 0; n < str.length; n++) j += ("&#"+(str.charCodeAt(n))+";");
	str = j;
		
	document.selection.createRange().text = str;
	pukiwiki_elem.focus();
}

function pukiwiki_initTexts()
{
	return;
}

function pukiwiki_show_hint()
{
	alert(pukiwiki_msg_winie_hint_text);
	
	if (pukiwiki_elem != null) pukiwiki_elem.focus();
}
