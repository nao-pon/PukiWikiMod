function open_mini(URL,width,height){
	aWindow = window.open(URL, "mini", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=yes,resizable=no,width="+width+",height="+height);
}

function h_pukiwiki_make_copy_button(arg)
{
	if(navigator.userAgent.indexOf('MSIE') >=0 && navigator.userAgent.indexOf('Windows')>=0){
		document.write ("<input class=\"copyButton\" type=\"button\" value=\"COPY\" onclick=\"h_pukiwiki_doCopy('" + arg + "')\"><br />");
	}
}

function h_pukiwiki_doCopy(arg)
{
	var doc = document.body.createTextRange();
	doc.moveToElementText(document.all(arg));
	doc.execCommand("copy");
	alert('クリップボードにコピーしました。');
}
