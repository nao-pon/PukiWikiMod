<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
//  $Id: config.def.php,v 1.1 2005/03/09 12:14:51 nao-pon Exp $
//

/*
 �v���O�C�� attach �ݒ�t�@�C��
*/

// �Ǘ��҂������Y�t�t�@�C�����A�b�v���[�h�ł���悤�ɂ���
define('ATTACH_UPLOAD_ADMIN_ONLY',FALSE); // FALSE or TRUE

// �y�[�W�ҏW����������l�̂ݓY�t�t�@�C�����A�b�v���[�h�ł���悤�ɂ���
define('ATTACH_UPLOAD_EDITER_ONLY',FALSE); // FALSE or TRUE

// �y�[�W�ҏW�������Ȃ��ꍇ�ɃA�b�v���[�h�ł���g���q(�J���}��؂�)
// ATTACH_UPLOAD_EDITER_ONLY = FALSE �̂Ƃ��Ɏg�p
define('ATTACH_UPLOAD_EXTENSION','jpg, jpeg, gif, png, txt, spch, zip, lzh, tar, taz, tgz, gz, z');

// �Ǘ��҂ƃy�[�W�쐬�҂������Y�t�t�@�C�����폜�ł���悤�ɂ���
define('ATTACH_DELETE_ADMIN_ONLY',FALSE); // FALSE or TRUE

// �Ǘ��҂ƃy�[�W�쐬�҂��Y�t�t�@�C�����폜����Ƃ��́A�o�b�N�A�b�v�����Ȃ�
define('ATTACH_DELETE_ADMIN_NOBACKUP',TRUE); // FALSE or TRUE 

// �Q�X�g���[�U�[�̃A�b�v���[�h/�폜���Ƀp�X���[�h��v������
// (ADMIN_ONLY���D�� TRUE ����������)
define('ATTACH_PASSWORD_REQUIRE',TRUE); // FALSE or TRUE

// �t�@�C���̃A�N�Z�X�� 
define('ATTACH_FILE_MODE',0644); 
//define('ATTACH_FILE_MODE',0604); // for XREA.COM 

// open, delete, upload ���Ƀ��t�@�����`�F�b�N����
// 0:�`�F�b�N���Ȃ�, 1:����`�͋���, 2:����`���s����
define('ATTACH_REFCHECK',1);

// file icon image
if (!defined('FILE_ICON'))
{
	define('FILE_ICON','<img src="./image/file.png" width="20" height="20" alt="file" style="border-width:0px" />');
}

// mime-type���L�q�����y�[�W
define('ATTACH_CONFIG_PAGE_MIME','plugin/attach/mime-type');

// �ڍ׏��E�t�@�C���ꗗ(�C���[�W���[�h)�Ŏg�p���� ref �v���O�C���̒ǉ��I�v�V����
define('ATTACH_CONFIG_REF_OPTION',',mw:160,mh:120');

// tar
define('TAR_HDR_LEN',512);			// �w�b�_�̑傫��
define('TAR_BLK_LEN',512);			// �P�ʃu���b�N����
define('TAR_HDR_NAME_OFFSET',0);	// �t�@�C�����̃I�t�Z�b�g
define('TAR_HDR_NAME_LEN',100);		// �t�@�C�����̍ő咷��
define('TAR_HDR_SIZE_OFFSET',124);	// �T�C�Y�ւ̃I�t�Z�b�g
define('TAR_HDR_SIZE_LEN',12);		// �T�C�Y�̒���
define('TAR_HDR_TYPE_OFFSET',156);	// �t�@�C���^�C�v�ւ̃I�t�Z�b�g
define('TAR_HDR_TYPE_LEN',1);		// �t�@�C���^�C�v�̒���

?>
