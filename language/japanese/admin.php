<?php
// $Id: admin.php,v 1.19 2005/04/27 14:28:10 nao-pon Exp $

define("_AM_WIKI_TITLE0", "PukiWiki �������");
define("_AM_WIKI_INFO0", "��������λ���뤿��˼��Σ��ĤΥ����˥����������ƽ�����¹Ԥ��Ƥ���������<br />�̾���Ƴ�����ˣ���Τ߼¹Ԥ��ޤ���");
define("_AM_WIKI_DB_INIT", "�ǡ����١��������");
define("_AM_WIKI_PAGE_INIT", "�ڡ�����󥯾�������");

define("_AM_WIKI_TITLE1", "PukiWiki ��������");
define("_AM_WIKI_TITLE2", "�ѡ��ߥå������ѹ�");
define("_AM_WIKI_SUBMIT", "�ѹ�");
define("_AM_WIKI_ENABLE", "ͭ��");
define("_AM_WIKI_DISABLE", "̵��");
define("_AM_WIKI_NOLEAVE", "���Ĥ��ʤ�");
define("_AM_WIKI_LEAVE", "���Ĥ���");
define("_AM_WIKI_NONAVI", "����");
define("_AM_WIKI_NAVI", "�����ʤ�");

define("_AM_DBUPDATED", "�ե�����ؤν񤭹��ߤ���λ���ޤ�����");

define("_AM_WIKI_ERROR01", "�񤭹��߸��¤�����ޤ���");

define("_AM_WIKI_DEFAULTPAGE", "�ǥե���ȥڡ���");
define("_AM_WIKI_MODIFIER", "�Խ��Ԥ�̾��");
define("_AM_WIKI_MODIFIERLINK", "�Խ��ԤΥۡ���ڡ���");
define("_AM_WIKI_FUNCTION_FREEZE", "��뵡ǽ��ͭ���ˤ���");
define("_AM_WIKI_ADMINPASS", "������Ѥδ����ԥѥ����<br>�ʥѥ���ɤ��ѹ�������Τߵ������Ƥ���������");
define("_AM_WIKI_CSS", "�������륷���ȤΥ����С��饤��<br />�ʥơ��ޤˤ�äƸ��Ф��������˸��Ť餯�ʤä��ꡢ<br />Wiki�ο����Ѥ���������ͭ���Ǥ���");

define("_AM_WIKI_PERMIT_CHANGE", "�ѡ��ߥå������ѹ��������ե�����Τ���ǥ��쥯�ȥ�<br>�ʤ��ε�ǽ�ϥ⥸�塼��κ���λ��ʳ��˻Ȥ����Ȥ򤪴��ᤷ�ޤ���<br />nobody�����񤭹��߽���ʤ��ʤä�ʪ��0666�ˤ��ޤ�����");
define("_AM_WIKI_ANONWRITABLE", "�Խ�����Ĥ���桼����(�ץ饰����Ǥν񤭹��ߤ����)");
define("_AM_WIKI_HIDE_NAVI", "�ڡ����������˾����Υʥӥ��������С��򱣤�");
define("_AM_WIKI_MAIL_SW", "������ƻ��δ����ԤؤΥ᡼�����Τϡ�");
define("_AM_WIKI_ALL", "���٤Ƥ�ˬ���");
define("_AM_WIKI_REGIST", "��Ͽ�桼�����Τ�");
define("_AM_WIKI_ADMIN", "�����ԤΤ�");
define("_AM_WIKI_MAIL_ALL", "���٤�����");
define("_AM_WIKI_MAIL_NOADMIN", "��������ưʳ�������");
define("_AM_WIKI_MAIL_NONE", "���Τ��ʤ�");
define("_AM_ALLOW_EDIT_VALDEF", "�ڡ��������������Υڡ������Ȥ��Խ����µ�����");
define("_AM_WIKI_WRITABLE", "�嵭�����<b>�Խ�����Ĥ���桼����</b>");
define("_AM_WIKI_ANONWRITABLE_MSG", "<dl><dt>(����) �Խ�����Ĥ���桼����</dt><dd>�Ե��Ĥˤ����Ʋ��Ρ�<b>�ڡ������Ȥ��Խ�����</b>�פ��ͥ�褷�ޤ���<br />�㤨�С�����<br />��<b>�����ԤΤ�</b>�פ����򤷤���硢��<b>�ڡ������Ȥ��Խ�����</b>�פ˴ؤ�餺�����٤ƤΥڡ����������ԤΤߤ����Խ��Ǥ��ޤ���<br />��<b>���٤Ƥ�ˬ���</b>�פ����򤹤�ȡ���<b>�ڡ������Ȥ��Խ�����</b>�פǳƥڡ������Խ����¤򥳥�ȥ���Ǥ���褦�ˤʤ�ޤ���</dd></dl>");
define("_AM_WIKI_ALLOW_NEW", "�ڡ����ο�����������Ĥ���桼����");

define("_AM_WIKI_FUNCTION_UNVISIBLE", "�ڡ������Ȥα������µ�ǽ��ͭ���ˤ���");
define("_AM_WIKI_BACKUP_TIME", "����Хå����åפδֳ�(����(hour)�ǻ���[0�ǹ�����])");
define("_AM_WIKI_BACKUP_AGE", "�Хå����åפκ��������");
define("_AM_WIKI_PCMT_PAGE", 'pcomment�ץ饰����Ǥο��������ڡ���̾�Υǥե���� (%s�����֥ڡ���̾������)');
define("_AM_WIKI_USER_DIR", '�ե�����Ǥ�̾�����ϻ��Υե����ޥå�<br />(%1$s����ƻ���Name������)<br />��: <b>[[%1$s>user/%1$s]]</b><br />���������ꤷ�ʤ����ϳƥץ饰����Ǥ����꤬Ŭ�Ѥ���ޤ���');
define("_AM_WIKI_FUNCTION_JPREADING", "ChaSen, KAKASI �ˤ�롢�ڡ���̾���ɤ߼�����ͭ���ˤ���");
define("_AM_WIKI_KANJI2KANA_ENCODING", "ChaSen/KAKASI �Ȥμ����Ϥ��˻Ȥ����������� (UNIX�Ϥ� EUC-JP��Win�Ϥ� S-JIS ������)");
define("_AM_WIKI_PAGEREADING_CHASEN_PATH", "ChaSen �μ¹ԥե�����ѥ� (�Ƽ��δĶ��˹�碌������)");
define("_AM_WIKI_PAGEREADING_KAKASI_PATH", "KAKASI �μ¹ԥե�����ѥ� (�Ƽ��δĶ��˹�碌������)");
define("_AM_WIKI_PAGEREADING_CONFIG_PAGE", "�ڡ���̾�ɤߤ��Ǽ�����ڡ�����̾��");
define("_AM_WIKI_SITE_NAME", "���Υ����Ȥ�Wiki��̾��");
define("_AM_WIKI_FUNCTION_TRACKBACK", "�ڡ����������TrackBack Ping ����������<br />( 0 ��TrackBack��ǽ��OFF�ˤʤ�ޤ���)");

// Ver 0.08 b5
define("_AM_WIKI_PAGE_CACHE_MIN", "HTML�ؤΥ���С��ȷ�̤򥭥�å��夹��ʬ��<br />�����ȥ桼�����Τߥ���å��夬ͭ���Ȥʤ�ޤ���<br /> ( 0 �����ǥ���å���ʤ���)");
define("_AM_WIKI_USE_STATIC_URL", "Wiki�ڡ�����URL��[�ڡ���ID].html �Ȥ��ä���Ū�ڡ���URL���ˤ��롣<br />(.htaccess �Ǥ����꤬ɬ�ܤǤ���)");

define("_AM_WIKI_UPDATE_PING_TO", "�ڡ��������������Ping��������������<br />���Ԥޤ���Ⱦ�ѥ��ڡ����Ƕ��ڤ�");
define("_AM_WIKI_COMMON_DIRS", "���̥��(����)�ǥ��쥯�ȥ�<br />�����ǻ��ꤷ��(����)�ǥ��쥯�ȥ�Ͼ�ά���Ƥ���������󥯤���ޤ���<br />�Ǹ�� / (����å���)��ɬ�פǤ���<br />���Ԥޤ���Ⱦ�ѥ��ڡ����Ƕ��ڤ�");
define("_AM_SYSTEM_ADMENU","��������");
define("_AM_SYSTEM_ADMENU2","�֥�å�����");

// Ver 1.0.6
define("_AM_WIKI_ANCHOR_VISIBLE","���Ф��˸����󥯥��󥫡���ɽ������");

// Ver 1.0.8
define("_AM_WIKI_TRACKBACK_ENCODING","�ȥ�å��Хå���������ʸ��������");

// ver 1.0.9.1
define("_AM_WIKI_COUNTUP_XOOPS","�ڡ�����������XOOPS����ƿ��򥫥���ȥ��åפ���");
define("_AM_WIKI_TITLE3","���桼��������ƿ��κƥ������");
define("_AM_WIKI_DBDENIED","�������������ݤ���ޤ�����<br />(�ե������ͭ�����֤�10ʬ�֤Ǥ�)");
define("_AM_WIKI_CONFIG_SUBMIT","��������򹹿�����");
define("_AM_WIKI_PERM_SUBMIT","�ѡ��ߥå������ѹ�����");
define("_AM_WIKI_SYNC_SUBMIT","���桼��������ƿ���ƥ�����Ȥ���");
define("_AM_WIKI_SYNC_MSG","<p>XOOPS�δ��ܵ�ǽ����ƿ���ƥ�����Ȥ��ޤ���<br />PukiWikiMod�ǤΥڡ��������򥫥���ȥ��åפ��Ƥʤ����ϡ����Υܥ���򥯥�å����ʤ��Ǥ���������<br />�桼�����������㤷�ƽ������֤�Ĺ���ʤ�ޤ�����λ����ޤǵ�Ĺ�ˤ��Ԥ�����������</p><p>�����Ǻƥ�����Ȥ�����оݤϡ��ե���������ƿ���XOOPSɸ��Υ����ȷ����PukiWikiMod�Υڡ����������Ǥ���<br />".XOOPS_ROOT_PATH."/modules/system/admin/users/users.php ���ȼ��˲�¤���ơ�¾�Υ⥸�塼��⥫������оݤˤ��Ƥ�����ϡ�Ʊ�ͤ� ".XOOPS_ROOT_PATH."/modules/pukiwiki/admin/index.php ��29���ܤ�������¤���Ƥ���������</p>");

// ver 1.1.0
define("_AM_WIKI_USE_XOOPS_COMMENTS","�ڡ���������(XOOPS�Υ����ȵ�ǽ)��ͭ���ˤ���");

// ver 1.2.1
define("_AM_WIKI_ERROR02","�ȥ�å��Хå��ǡ������Ѵ���ɬ�פǤ��������򥯥�å����ơ��Ѵ���ԤäƤ���������");

// ver 1.2.6
define("_AM_WIKI_FUSEN_ENABLE_ALLPAGE","���(wema)��ǽ�����ڡ�����ͭ���ˤ���");
define("_AM_WIKI_TB_CHECK_LINK_TO_ME","��󥯤ʤ��ȥ�å��Хå��ϼ������ʤ���ǽ��ͭ���ˤ���");
?>