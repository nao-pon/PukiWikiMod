<?php
	/////// Moblog���� ////////
	///////////////////////////
	////// ɬ��������� ///////
	
	// �����ѥ᡼�륢�ɥ쥹
	$mail = "";
	// POP�����С�
	$host = "127.0.0.1";
	// POP�����С����������
	$user = "";
	// POP�����С��ѥ����
	$pass = "";

	// ���������ɥ쥹�ˤ�äƿ���ʬ����ڡ����λ���ʥ֥饱�åȤϤĤ��ʤ���
	// '�᡼�륢�ɥ쥹' => array('�ڡ���̾',UserID�ʥ�С�),
	
	$adr2page = array(
	//	'hoge@hoge.com' => array('����',1),	// ������
		'other' => array('',0),	// ��Ͽ�ᥢ�ɰʳ�
	);
	
	////// ɬ��������ܽ�λ //////
	//////////////////////////////
	///// �ʲ��Ϥ����ߤ����� /////
	
	//ref�ץ饰������ɲå��ץ����
	$ref_option = ',left,around,mw:200';

	// ����ź���̡ʥХ��ȡ�1�ե�����ˤĤ��ˢ�Ķ�����Τ���¸���ʤ�
	$maxbyte = "1000000";//1MB

	// ��ʸʸ�����¡�Ⱦ�Ѥ�
	$body_limit = 1000;
	
	// �Ǿ���ư�����ֳ֡�ʬ��
	$refresh_min = 5;

	// ��̾���ʤ��Ȥ�����̾
	$nosubject = "";

	// �������ĥ��ɥ쥹�ʥ����˵�Ͽ���ʤ���
	$deny = array('163.com','bigfoot.com','boss.com','yahoo-delivers@mail.yahoo.co.jp');

	// �������ĥ᡼�顼(perl�ߴ�����ɽ��)�ʥ����˵�Ͽ���ʤ���
	$deny_mailer = '/(Mail\s*Magic|Easy\s*DM|Friend\s*Mailer|Extra\s*Japan|The\s*Bat)/i';

	// �������ĥ����ȥ�(perl�ߴ�����ɽ��)�ʥ����˵�Ͽ���ʤ���
	$deny_title = '/((̤|��)\s?��\s?(��|ǧ)\s?��\s?��)|��ߥ��/i';

	// �������ĥ���饯�������å�(perl�ߴ�����ɽ��)�ʥ����˵�Ͽ���ʤ���
	$deny_lang = '/us-ascii|big5|euc-kr|gb2312|iso-2022-kr|ks_c_5601-1987/i';

	// �б�MIME�����ס�����ɽ����Content-Type: image/jpeg�θ������ʬ��octet-stream�ϴ�������
	$subtype = "gif|jpe?g|png|bmp|octet-stream|x-pmd|x-mld|x-mid|x-smd|x-smaf|x-mpeg";

	// ��¸���ʤ��ե�����(����ɽ��)
	$viri = ".+\.exe$|.+\.zip$|.+\.pif$|.+\.scr$";

	// 25���ʾ�β����Ϻ���ʹ�����ڤ��
	$del_ereg = "[_]{25,}";

	// ��ʸ����������ʸ����
	$word[] = "�����Ͽ��̵��  ���¤������ʥ����ƥ�ʤ� MSN �����������";
	$word[] = "http://auction.msn.co.jp/";
	$word[] = "Do You Yahoo!?";
	$word[] = "Yahoo! BB is Broadband by Yahoo!";
	$word[] = "http://bb.yahoo.co.jp/";

	// ź�ե᡼��Τߵ�Ͽ���롩Yes=1 No=0����ʸ�Τߤϥ����˺ܤ��ʤ���
	$imgonly = 0;

	////////////////// ���ꤳ���ޤ� ///////////////////
?>