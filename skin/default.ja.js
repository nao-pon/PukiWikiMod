
var pukiwiki_msg_copyed = "����åץܡ��ɤ˥��ԡ����ޤ�����";
var pukiwiki_msg_select = "�о��ϰϤ����򤷤Ƥ���������";
var pukiwiki_msg_fontsize = "ʸ�����礭�� ( % �ޤ��� pt[��ά��] �ǻ���): ";

var pukiwiki_WinIE=(document.all&&!window.opera&&navigator.platform=="Win32");

function open_mini(URL,width,height){
	aWindow = window.open(URL, "mini", "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=yes,resizable=no,width="+width+",height="+height);
}

if (pukiwiki_WinIE)
{
	document.write ('<script type="text/javascript" src="' + pukiwiki_root_url + 'skin/winie.js"></script>');
}
else
{
	document.write ('<script type="text/javascript" src="' + pukiwiki_root_url + 'skin/other.js"></script>');
}
