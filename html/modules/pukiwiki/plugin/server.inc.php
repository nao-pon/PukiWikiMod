<?php
 /*
 
 PukiWiki �����С�����ɽ���ץ饰����
 
 by Reimy
 http://pukiwiki.reimy.com/
 
 
 init.php ��16���ܤ�����˲����ιԤ��ɲä��Ƥ��餴���Ѥ�������
 define("SERVER_NAME",$HTTP_SERVER_VARS["SERVER_NAME"]);
 define("SERVER_SOFTWARE",$HTTP_SERVER_VARS["SERVER_SOFTWARE"]);
 define("SERVER_ADMIN",$HTTP_SERVER_VARS["SERVER_ADMIN"]);

 ��SERVER_NAME��init.php�Ǵ������ꤵ��Ƥ���Ϥ��Ǥ��Τǡ��Ĥ�2�Ԥ��ɲä��Ƥ�������
 
 $Id: server.inc.php,v 1.3 2004/11/24 13:15:35 nao-pon Exp $
 
 */

 function plugin_server_convert()
 {
   $string = "<dl><dt>Server Name</dt>\n<dd>"
   .SERVER_NAME
   ."</dd>\n<dt>Server Software</dt>\n<dd>"
   .SERVER_SOFTWARE
   ."</dd>\n<dt>Server Admin</dt>\n<dd>"
   ."<a href=\"mailto:"
   .SERVER_ADMIN
   ."\">"
   .SERVER_ADMIN
   ."</a></dd></dl>\n";
   return $string;
 }
?>