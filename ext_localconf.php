<?php

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


if (t3lib_div::int_from_ver(TYPO3_version) >= t3lib_div::int_from_ver('4.2.99')) {
		$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = 'EXT:fe_facelifting/view/class.tx_fefacelifting_editpanel.php:tx_fefacelifting_editpanel';
	}else{
		$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_content.php'] = t3lib_extMgm::extPath($_EXTKEY).'class.ux_tslib_content.php';
}

$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:fe_facelifting/class.tx_fefacelifting_contentpostproc.php:tx_fefacelifting_contentpostproc->includeJavaScript';

// take it form feeditadvanced
// thanks for that
t3lib_extMgm::addUserTSConfig('
	admPanel {
			override.edit.displayIcons = 1
	}
');

?>

