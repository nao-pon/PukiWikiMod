
var pukiwiki_msg_copyed = "クリップボードにコピーしました。";
var pukiwiki_msg_select = "対象範囲を選択してください。";
var pukiwiki_msg_fontsize = "文字の大きさ ( % または pt[省略可] で指定): ";

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
