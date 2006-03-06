<?php
//----------------------------------------------------------------------
// picpost.php lot.040622  by SakaQ >> http://www.punyu.net/
//
// ��������POST���줿��������������TEMP����¸
//
// ���Υ�����ץȤ�PaintBBS������CGI�ˤ�PNG��¸�롼����򻲹ͤ�
// PHP�Ѥ˺���������ΤǤ���
//----------------------------------------------------------------------
// 2004/06/22 �桼�������̤���usercode����ƼԾ�����ɲ�
// 2003/12/22 JPEG�б�
// 2003/10/03 �����ڥ��󥿡����б�
// 2003/09/10 IP���ɥ쥹������ˡ�ѹ�
// 2003/09/09 PCH�ե�������б�.��ƼԾ���ε�Ͽ��ǽ�ɲ�
// 2003/09/01 PHP��(?)������
// 2003/08/28 perl -> php �ܿ�  by TakeponG >> http://www.chomkoubou.com/
// 2003/07/11 perl�ǽ����

//����
//include("config.php");
//�ƥ�ݥ��ǥ��쥯�ȥ�
define(TEMP_DIR, './tmp/');

//�ƥ�ݥ����Υե�����ͭ������(����)
define(TEMP_LIMIT, '14');

$syslog = TEMP_DIR."error.log";
$syslogmax = 100;

$time = time();
$imgfile = $time.substr(microtime(),2,3);	//�����ե�����̾

/* ���顼ȯ������SystemLOG�˥��顼��Ͽ */
function error($error){
  global $imgfile,$syslog,$syslogmax;

  //����
  $time = time();
  $youbi = array('��','��','��','��','��','��','��');
  $yd = $youbi[gmdate("w", $time+9*60*60)] ;
  $now = gmdate("y/m/d",$time+9*60*60)."(".(string)$yd.")".gmdate("H:i",$time+9*60*60);

  //�ե������������ɤ߹���
  if(@is_file($syslog)) $lines = file($syslog);

  //�񤭹��ߥ⡼�ɤǥ����ץ�
  $ep = @fopen($syslog , "w") or die($syslog."�������ޤ���");

  //�ե������å�
  flock($ep, LOCK_EX);

  //��Ƭ�˽񤭹���(�ե�����̾�����顼��å�����������)
  fputs ($ep, $imgfile."  ".$error." [".$now."]\n");

  //���ޤޤǤ�ʬ���ɵ�
  for($i = 0; $i < $syslogmax; $i++)
    fputs($ep, $lines[$i]);

  //�ե����륯����
  fclose ($ep);
}


/* ���������� �ᥤ����� ���������� */

//raw POST �ǡ�������
ini_set("always_populate_raw_post_data", "1");
$buffer = $HTTP_RAW_POST_DATA;
if($buffer == "") {
  $stdin = fopen("php://input", "rb");
  $buffer = fread($stdin, $_ENV['CONTENT_LENGTH']);
  fclose ($stdin);
}

if($buffer == "") {
  error("raw POST �ǡ����μ����˼��Ԥ��ޤ���������������������¸����ޤ���");
  exit;
}

// ��ĥ�إå���Ĺ�������
$headerLength = substr( $buffer , 1 , 8 );
// �����ե������Ĺ������Ф�
$imgLength = substr( $buffer , 1 + 8 + $headerLength , 8 );
// �������᡼������Ф�
$imgdata = substr($buffer, 1 + 8 + $headerLength + 8 + 2 , $imgLength );
// �����إå��������
$imgh = substr( $imgdata , 1 , 5 );
// ��ĥ������
if($imgh=="PNG\r\n"){
  $imgext = '.png';	// PNG
}else{
  $imgext = '.jpg';	// JPEG
}

// Ʊ̾�Υե����뤬¸�ߤ��ʤ��������å�
if( file_exists( TEMP_DIR.$imgfile.$imgext ) ){	// �ե����뤬¸�ߤ�����
  error("Ʊ̾�β����ե����뤬¸�ߤ��ޤ�����񤭤��ޤ���");
}

// �����ǡ�����ե�����˽񤭹���
$fp = fopen( TEMP_DIR.$imgfile.$imgext,"wb");

if( !$fp ){	// �ե����륪���ץ󥨥顼
  error("�����ե�����Υ����ץ�˼��Ԥ��ޤ���������������������¸����ޤ���");
  exit;

}else{	// �ե����륪���ץ������
  flock($fp, LOCK_EX);	//�ե������å�
  fwrite($fp, $imgdata);
  fclose ($fp);
}

// PCH�ե������Ĺ������Ф�
$pchLength = substr( $buffer , 1 + 8 + $headerLength + 8 + 2 + $imgLength , 8 );

if($pchLength!=0){
  // PCH���᡼������Ф�
  $PCHdata = substr($buffer, 1 + 8 + $headerLength + 8 + 2 + $imgLength + 8 , $pchLength );

  // �إå��������
  $h = substr( $buffer , 0 , 1 );

  // ��ĥ������
  if($h=='S'){
    $ext = '.spch';	// �����ڥ��󥿡�
  }else{
    $ext = '.pch';	// PaintBBS
  }

  // Ʊ̾�Υե����뤬¸�ߤ��ʤ��������å�
  if( file_exists( TEMP_DIR.$imgfile.$ext ) ){	// �ե����뤬¸�ߤ�����
    error("Ʊ̾��PCH�ե����뤬¸�ߤ��ޤ�����񤭤��ޤ���");
  }

  // PCH�ǡ�����ե�����˽񤭹���
  $fp = fopen( TEMP_DIR.$imgfile.$ext,"wb");

  if( !$fp ){	// �ե����륪���ץ󥨥顼
    error("PCH�ե�����Υ����ץ�˼��Ԥ��ޤ�����PCH����¸����ޤ���");
    exit;

  }else{	// �ե����륪���ץ������
    flock($fp, LOCK_EX);	//�ե������å�
    fwrite($fp, $PCHdata);
    fclose ($fp);
  }
}

/* ---------- ��ƼԾ���Ͽ ---------- */
$u_ip = getenv("HTTP_CLIENT_IP");
if(!$u_ip) $u_ip = getenv("HTTP_X_FORWARDED_FOR");
if(!$u_ip) $u_ip = getenv("REMOTE_ADDR");
$u_host = gethostbyaddr($u_ip);
$u_agent = getenv("HTTP_USER_AGENT");
$u_agent = str_replace("\t", "", $u_agent);

$userdata = "$u_ip\t$u_host\t$u_agent\t$imgext";

// ��ĥ�إå�������Ф�
$sendheader = substr( $buffer , 1 + 8 , $headerLength );
if($sendheader){
  $query_str = explode("&", $sendheader);
  foreach($query_str as $query_s){
    list($name,$value) = explode("=", $query_s);
    if($name == 'usercode') $userdata .= "\t$value"; // usercode�򥻥å�
    elseif($name == 'refer') $userdata .= "\t$value"; // �ڡ���̾�򥻥å�
	elseif($name == 'uid') $userdata .= "\t$value"; // UserID�򥻥å�
  }
}
$userdata .= "\n";

if( file_exists( TEMP_DIR.$imgfile.".dat" ) ){	// �ե����뤬¸�ߤ�����
  error("Ʊ̾�ξ���ե����뤬¸�ߤ��ޤ�����񤭤��ޤ���");
}

// ����ǡ�����ե�����˽񤭹���
$fp = fopen( TEMP_DIR.$imgfile.".dat","w");
if( !$fp ){	// �ե����륪���ץ󥨥顼
  error("����ե�����Υ����ץ�˼��Ԥ��ޤ�������ƼԾ���ϵ�Ͽ����ޤ���");
  exit;

}else{	// �ե����륪���ץ������
  flock($fp, LOCK_EX);	//�ե������å�
  fwrite($fp, $userdata);
  fclose ($fp);
}

die("ok");

?>