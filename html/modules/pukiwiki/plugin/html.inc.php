<?php
function plugin_html_convert()
{
	list($file) = func_get_args();
	if ($ret = plugin_html_get($file))
		return "<div>$ret</div>";
	else
		return false;
}
function plugin_html_inline()
{
	list($file) = func_get_args();
	return plugin_html_get($file);
}
function plugin_html_get($file)
{
	$path = "./htmls/";
	if (!file_exists($path.$file))
		return false;
	else
		return join('',file($path.$file));

}
?>