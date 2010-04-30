<?php
/**
 * On module update function
 */

if (!function_exists('xoops_module_update_pukiwiki_base')) {

function xoops_module_update_pukiwiki_base($module, $dirnum) {

	global $msgs ; // TODO :-D

	$mydirname = 'pukiwiki' . $dirnum;
	// for Cube 2.1
	if( defined( 'XOOPS_CUBE_LEGACY' ) ) {
		$root =& XCube_Root::getSingleton();
		$root->mDelegateManager->add( 'Legacy.Admin.Event.ModuleUpdate.' . ucfirst($mydirname) . '.Success', 'xpwiki_message_append_onupdate' ) ;
		$root->mDelegateManager->add( 'Legacy.Admin.Event.ModuleUpdate.' . ucfirst($mydirname) . '.Fail' , 'xpwiki_message_append_onupdate' ) ;
		$msgs = array() ;
	} else {
		if( ! is_array( $msgs ) ) $msgs = array() ;
	}

	$db =& Database::getInstance();

	$mydirname = 'pukiwikimod' . $dirnum;

	// ADD INDEXES
	$table = $db->prefix($mydirname.'_attach');
    if ($result = $db->query('SHOW INDEX FROM `' . $table . '`')) {
        $keys = array( 'pgid' => '',
		               'owner'=> '',
		               'name' => '',
		               'type' => '',
		               'mode' => '',
		               'age' => '' );
        while($arr = $db->fetchArray($result)) {
        	unset($keys[$arr['Key_name']]);
        }
        foreach ($keys as $_key => $_val) {
        	$query = 'ALTER TABLE `' . $table . '` ADD INDEX(`'.$_key.'`'.$_val.')';
        	$db->query($query);
        	//$msgs[] = $query;
        }
    }
	$table = $db->prefix($mydirname.'_pginfo');
    if ($result = $db->query('SHOW INDEX FROM `' . $table . '`')) {
        $keys = array( 'editedtime' => '',
		               'unvisible' => '',
		               'freeze' => '',
		               'gids' => '',
		               'vgids' => '',
		               'aids' => '(255)',
		               'vaids' => '(255)' );
        while($arr = $db->fetchArray($result)) {
        	unset($keys[$arr['Key_name']]);
        }
        foreach ($keys as $_key => $_val) {
        	$query = 'ALTER TABLE `' . $table . '` ADD INDEX(`'.$_key.'`'.$_val.')';
        	$db->query($query);
        	//$msgs[] = $query;
        }
    }
	// rel テーブルのPRIMARY KEY 設定
	$table = $db->prefix($mydirname . '_rel');
    $query = 'SHOW INDEX FROM `' . $table . '`';
    //$msgs[] = $query;
    if ($result = $db->query($query)) {
        $keys = array( 'PRIMARY' => '' );
        while($arr = $db->fetchArray($result)) {
        	unset($keys[$arr['Key_name']]);
        }
        if ($keys) {
			// 重複レコードの削除
			$dels = array();
			$query = 'SELECT CONCAT(pgid, \'_\', relid) as id, (count(*)-1) as count FROM `'.$table.'` GROUP BY id HAVING count >= 1';
			if ($result = $db->query($query)) {
				while($arr = $db->fetchRow($result)) {
					$dels[$arr[0]] = $arr[1];
				}
			}
			foreach($dels as $key => $limit) {
				$arr = explode('_', $key);
				$query = 'DELETE FROM ' . $table . ' WHERE pgid='.$arr[0].' AND relid='.$arr[1].' LIMIT '.$limit;
				$db->query($query);
				//$msgs[] = $query;
			}

        	// PRIMARY KEY 設定
        	$query = 'ALTER TABLE `' . $table . '` ADD PRIMARY KEY(`pgid`,`relid`)';
        	$db->query($query);
        	//$msgs[] = $query;
        }
    }

	return true;
}

function xpwiki_message_append_onupdate( &$module_obj , &$log )
{
	if( is_array( @$GLOBALS['msgs'] ) ) {
		foreach( $GLOBALS['msgs'] as $message ) {
			$log->add( strip_tags( $message ) ) ;
		}
	}

	// use mLog->addWarning() or mLog->addError() if necessary
}

function xoops_module_update_pukiwiki($module) {
	return xoops_module_update_pukiwiki_base($module, '');
}
function xoops_module_update_pukiwiki0($module) {
	return xoops_module_update_pukiwiki_base($module, '0');
}
function xoops_module_update_pukiwiki1($module) {
	return xoops_module_update_pukiwiki_base($module, '1');
}
function xoops_module_update_pukiwiki2($module) {
	return xoops_module_update_pukiwiki_base($module, '2');
}
function xoops_module_update_pukiwiki3($module) {
	return xoops_module_update_pukiwiki_base($module, '3');
}
function xoops_module_update_pukiwiki4($module) {
	return xoops_module_update_pukiwiki_base($module, '4');
}
function xoops_module_update_pukiwiki5($module) {
	return xoops_module_update_pukiwiki_base($module, '5');
}
function xoops_module_update_pukiwiki6($module) {
	return xoops_module_update_pukiwiki_base($module, '6');
}
function xoops_module_update_pukiwiki7($module) {
	return xoops_module_update_pukiwiki_base($module, '7');
}
function xoops_module_update_pukiwiki8($module) {
	return xoops_module_update_pukiwiki_base($module, '8');
}
function xoops_module_update_pukiwiki9($module) {
	return xoops_module_update_pukiwiki_base($module, '9');
}

}
