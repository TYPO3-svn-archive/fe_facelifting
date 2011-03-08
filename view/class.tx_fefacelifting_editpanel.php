<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2009 Jeff Segars <jeff@webempoweredchurch.org>
 *  (c) 2008-2009 David Slayback <dave@webempoweredchurch.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * View class for the edit panels in frontend editing.
 *
 * $Id: class.tx_feedit_editpanel.php 5761 2009-08-05 10:05:29Z rupi $
 *
 * @author	Jeff Segars <jeff@webempoweredchurch.org>
 * @author	David Slayback <dave@webempoweredchurch.org>
 * @package TYPO3
 * @subpackage feedit
 */
class tx_fefacelifting_editpanel {

	/**
	 * Local instance of tslib_cObj.
	 *
	 * @var tslib_cObj
	 */
	protected $cObj;

	protected $extConf = '';

	/**
	 * Constructor for the edit panel. Creates a new cObject instance to be used in wrapping, etc.
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->start(array());

		//get config from Extension Manager
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fe_facelifting']);
	}

	/**
	 * Fallback for missing function in Core
	 *
	 * @return	string
	 */
	public function extGetLL2($key) {
		global $LOCAL_LANG;
		if (!is_array($LOCAL_LANG)) {
			$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_tsfe.php');
			#include('./'.TYPO3_mainDir.'sysext/lang/locallang_tsfe.php');
			if (!is_array($LOCAL_LANG)) {
				$LOCAL_LANG = array();
			}
		}

		$labelStr = htmlspecialchars($GLOBALS['LANG']->getLL($key)); // Label string in the default backend output charset.

		// Convert to utf-8, then to entities:
		if ($GLOBALS['LANG']->charSet != 'utf-8') {
			$labelStr = $GLOBALS['LANG']->csConvObj->utf8_encode($labelStr, $GLOBALS['LANG']->charSet);
		}
		#$labelStr = $GLOBALS['LANG']->csConvObj->utf8_to_entities($labelStr);

		// Return the result:
		return $labelStr;
	}

	/**
	 * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
	 * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
	 * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
	 *
	 * @param	string		A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
	 * @param	array		TypoScript configuration properties for the editPanel
	 * @param	string		The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW"
	 * @param	array		Alternative data array to use. Default is $this->data
	 * @return	string		The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=375&cHash=7d8915d508
	 */
	public function editPanel($content, array $conf, $currentRecord = '', array $dataArr = array(), $table = '', $allow = '', $newUID = 0, array $hiddenFields = array()) {
		// Special content is about to be shown, so the cache must be disabled.
		$GLOBALS['TSFE']->set_no_cache();
		$formName = 'TSFE_EDIT_FORM_' . substr($GLOBALS['TSFE']->uniqueHash(), 0, 4);
		$formTag = '<form name="' . $formName . '" id ="' . $formName . '" action="' . htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')) . '" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" onsubmit="return TBE_EDITOR.checkSubmit(1);" style="margin: 0 0 0 0;">';
		$sortField = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
		$labelField = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		$blackLine = $conf['line'] ? '<img src="clear.gif" width="1" height="' . intval($conf['line']) . '" alt="" title="" /><br /><table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="black" style="border: 0px;" summary=""><tr style="border: 0px;"><td style="border: 0px;"><img src="clear.gif" width="1" height="1" alt="" title="" /></td></tr></table><br />' : '';

		$theCmd = '';
		$TSFE_EDIT = $GLOBALS['BE_USER']->frontendEdit->TSFE_EDIT;
		if (is_array($TSFE_EDIT) && $TSFE_EDIT['record'] == $currentRecord && !$TSFE_EDIT['update_close']) {
			$theCmd = $TSFE_EDIT['cmd'];
		}

		switch ($theCmd) {
			case 'edit':
			case 'new':
				$finalOut = $this->editContent($formTag, $formName, $theCmd, $newUID, $dataArr, $table, $currentRecord, $blackLine);
				break;
			default:
				$panel = '';
				if (isset($allow['toolbar']) && ($GLOBALS['BE_USER']->adminPanel instanceof tslib_AdminPanel)) {
					$panel .= $GLOBALS['BE_USER']->adminPanel->ext_makeToolBar() . '<img src="clear.gif" width="2" height="1" alt="" title="" />';
				}
				if (isset($allow['edit'])) {
					$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'edit2.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" hspace="2" border="0" title="' . $this->extGetLL2('p_editRecord') . '" align="top" alt="" />', $formName, 'edit', $dataArr['_LOCALIZED_UID'] ? $table . ':' . $dataArr['_LOCALIZED_UID'] : $currentRecord);
				}
				// Hiding in workspaces because implementation is incomplete
				if (isset($allow['move']) && $sortField && $GLOBALS['BE_USER']->workspace === 0) {
					$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'button_up.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_moveUp') . '" align="top" alt="" />', $formName, 'up');
					$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'button_down.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_moveDown') . '" align="top" alt="" />', $formName, 'down');
				}
				// Hiding in workspaces because implementation is incomplete, Hiding for localizations because it is unknown what should be the function in that case
				if (isset($allow['hide']) && $hideField && $GLOBALS['BE_USER']->workspace === 0 && !$dataArr['_LOCALIZED_UID']) {
					if ($dataArr[$hideField]) {
						$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'button_unhide.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_unhide') . '" align="top" alt="" />', $formName, 'unhide');
					} else {
						$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'button_hide.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_hide') . '" align="top" alt="" />', $formName, 'hide', '', $this->extGetLL2('p_hideConfirm'));
					}
				}
				if (isset($allow['new'])) {
					if ($table == 'pages') {
						$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'new_page.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_newSubpage') . '" align="top" alt="" />', $formName, 'new', $currentRecord, '', $newUID);
					} else {
						$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'new_record.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_newRecordAfter') . '" align="top" alt="" />', $formName, 'new', $currentRecord, '', $newUID);
					}
				}
				// Hiding in workspaces because implementation is incomplete, Hiding for localizations because it is unknown what should be the function in that case
				if (isset($allow['delete']) && $GLOBALS['BE_USER']->workspace === 0 && !$dataArr['_LOCALIZED_UID']) {
					$panel .= $this->editPanelLinkWrap('<img src="' . $this->extConf['path_to_icons'] . 'garbage.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" vspace="1" hspace="2" border="0" title="' . $this->extGetLL2('p_delete') . '" align="top" alt="" />', $formName, 'delete', '', $this->extGetLL2('p_deleteConfirm'));
				}
				//	Final
				$labelTxt = $this->cObj->stdWrap($conf['label'], $conf['label.']);

				foreach ((array) $hiddenFields as $name => $value) {
					$hiddenFieldString .= '<input type="hidden" name="TSFE_EDIT[' . $name . ']" value="' . $value . '"/>' . chr(10);
				}

				$panel = '

							<!-- BE_USER Edit Panel: -->
							' . $formTag .
						$hiddenFieldString . '
								<input type="hidden" name="TSFE_EDIT[cmd]" value="" />
								<input type="hidden" name="TSFE_EDIT[record]" value="' . $currentRecord . '" />
								<table border="0" cellpadding="0" cellspacing="0" class="typo3-editPanel" summary="">
									<tr>
										<td nowrap="nowrap" bgcolor="' . $this->extConf['bgcolor_icons'] . '" class="typo3-editPanel-controls">' . $panel . '</td>' .
						($labelTxt ? '<td nowrap="nowrap" bgcolor="' . $this->extConf['bgcolor_label'] . '" class="typo3-editPanel-label"><font face="verdana" size="1" color="black">&nbsp;' . sprintf($labelTxt, htmlspecialchars(t3lib_div::fixed_lgd_cs($dataArr[$labelField], 50))) . '&nbsp;</font></td>' : '') . '
									</tr>
								</table>
							</form>';
				// wrap the panel
				if ($conf['innerWrap']) {
					$panel = $this->cObj->wrap($panel, $conf['innerWrap']);
				}
				if ($conf['innerWrap.']) {
					$panel = $this->cObj->stdWrap($panel, $conf['innerWrap.']);
				}

				// add black line:
				$panel .= $blackLine;

				// wrap the complete panel
				if ($conf['outerWrap']) {
					$panel = $this->cObj->wrap($panel, $conf['outerWrap']);
				}
				if ($conf['outerWrap.']) {
					$panel = $this->cObj->stdWrap($panel, $conf['outerWrap.']);
				}
				if ($conf['printBeforeContent']) {
					$finalOut = $panel . $content;
				} else {
					$finalOut = $content . $panel;
				}
				break;
		}

		if ($conf['previewBorder']) {
			if (!is_array($conf['previewBorder.'])) {
				$conf['previewBorder.'] = array();
			}
			$finalOut = $this->editPanelPreviewBorder($table, $dataArr, $finalOut, $conf['previewBorder'], $conf['previewBorder.']);
		}

		return $finalOut;
	}

	/**
	 * Adds an edit icon to the content string. The edit icon links to alt_doc.php with proper parameters for editing the table/fields of the context.
	 * This implements TYPO3 context sensitive editing facilities. Only backend users will have access (if properly configured as well).
	 *
	 * @param	string		The content to which the edit icons should be appended
	 * @param	string		The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to alt_doc.php
	 * @param	array		TypoScript properties for configuring the edit icons.
	 * @param	string		The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW"
	 * @param	array		Alternative data array to use. Default is $this->data
	 * @param	string		Additional URL parameters for the link pointing to alt_doc.php
	 * @return	string		The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
	 */
	public function editIcons($content, $params, array $conf = array(), $currentRecord = '', array $dataArr = array(), $addUrlParamStr = '', $table, $editUid, $fieldList) {
		// Special content is about to be shown, so the cache must be disabled.
		$GLOBALS['TSFE']->set_no_cache();
		$style = $conf['styleAttribute'] ? ' style="' . htmlspecialchars($conf['styleAttribute']) . '"' : '';
		$iconTitle = $this->cObj->stdWrap($conf['iconTitle'], $conf['iconTitle.']);
		$iconImg = $conf['iconImg'] ? $conf['iconImg'] : '<img src="' . $this->extConf['path_to_icons'] . 'edit_fe.gif" width="' . $this->extConf['icon_w'] . '" height="' . $this->extConf['icon_h'] . '" border="0" align="top" title="' . t3lib_div::deHSCentities(htmlspecialchars($iconTitle)) . '"' . $style . ' class="frontEndEditIcons" alt="" />';
		$nV = t3lib_div::_GP('ADMCMD_view') ? 1 : 0;
		$adminURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir;
		$icon = $this->editPanelLinkWrap_doWrap($iconImg, $adminURL . 'alt_doc.php?edit[' . $table . '][' . $editUid . ']=edit&columnsOnly=' . rawurlencode($fieldList) . '&noView=' . $nV . $addUrlParamStr, $currentRecord);

		if ($conf['beforeLastTag'] < 0) {
			$content = $icon . $content;
		} elseif ($conf['beforeLastTag'] > 0) {
			$cBuf = rtrim($content);
			$securCount = 30;
			while ($securCount && substr($cBuf, -1) == '>' && substr($cBuf, -4) != '</a>') {
				$cBuf = rtrim(preg_replace('/<[^<]*>$/', '', $cBuf));
				$securCount--;
			}
			$content = (strlen($cBuf) && $securCount) ? substr($content, 0, strlen($cBuf)) . $icon . substr($content, strlen($cBuf)) : $content = $icon . $content;
		} else {
			$content .= $icon;
		}

		return $content;
	}

	/**
	 * Helper function for editPanel() which wraps icons in the panel in a link with the action of the panel.
	 * The links are for some of them not simple hyperlinks but onclick-actions which submits a little form which the panel is wrapped in.
	 *
	 * @param	string		The string to wrap in a link, typ. and image used as button in the edit panel.
	 * @param	string		The name of the form wrapping the edit panel.
	 * @param	string		The command of the link. There is a predefined list available: edit, new, up, down etc.
	 * @param	string		The "table:uid" of the record being processed by the panel.
	 * @param	string		Text string with confirmation message; If set a confirm box will be displayed before carrying out the action (if Yes is pressed)
	 * @param	integer		"New pid" - for new records
	 * @return	string		A <a> tag wrapped string.
	 * @see	editPanel(), editIcons(), t3lib_tsfeBeUserAuth::extEditAction()
	 */
	protected function editPanelLinkWrap($string, $formName, $cmd, $currentRecord = '', $confirm = '', $nPid = '') {
		// Editing forms on page only supported in Live workspace (because of incomplete implementation)
		$editFormsOnPage = $GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editFormsOnPage'] && $GLOBALS['BE_USER']->workspace === 0;
		$nV = t3lib_div::_GP('ADMCMD_view') ? 1 : 0;
		$adminURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir;

		if ($cmd == 'edit' && !$editFormsOnPage) {
			$rParts = explode(':', $currentRecord);
			$out = $this->editPanelLinkWrap_doWrap($string, $adminURL . 'alt_doc.php?edit[' . $rParts[0] . '][' . $rParts[1] . ']=edit&noView=' . $nV, $currentRecord);
		} elseif ($cmd == 'new' && !$editFormsOnPage) {
			$rParts = explode(':', $currentRecord);
			if ($rParts[0] == 'pages') {
				$out = $this->editPanelLinkWrap_doWrap($string, $adminURL . 'db_new.php?id=' . $rParts[1] . '&pagesOnly=1', $currentRecord);
			} else {
				if (!intval($nPid)) {
					$nPid = t3lib_div::testInt($rParts[1]) ? -$rParts[1] : $GLOBALS['TSFE']->id;
				}
				$out = $this->editPanelLinkWrap_doWrap($string, $adminURL . 'alt_doc.php?edit[' . $rParts[0] . '][' . $nPid . ']=new&noView=' . $nV, $currentRecord);
			}
		} else {
			if ($confirm && $GLOBALS['BE_USER']->jsConfirmation(8)) {
				// Gets htmlspecialchared later
				$cf1 = 'if (confirm(' . t3lib_div::quoteJSvalue($confirm, true) . ')) {';
				$cf2 = '}';
			} else {
				$cf1 = $cf2 = '';
			}
			$out = '<a href="#" onclick="' .
					htmlspecialchars($cf1 . 'document.' . $formName . '[\'TSFE_EDIT[cmd]\'].value=\'' . $cmd . '\'; document.' . $formName . '.submit();' . $cf2 . ' return false;') .
					'">' . $string . '</a>';
		}

		return $out;
	}

	/**
	 * Creates a link to a script (eg. typo3/alt_doc.php or typo3/db_new.php) which either opens in the current frame OR in a pop-up window.
	 *
	 * @param	string		The string to wrap in a link, typ. and image used as button in the edit panel.
	 * @param	string		The URL of the link. Should be absolute if supposed to work with <base> path set.
	 * @param	string		The "table:uid" of the record being processed by the panel.
	 * @return	string		A <a> tag wrapped string.
	 * @see	editPanelLinkWrap()
	 */
	protected function editPanelLinkWrap_doWrap($string, $url, $currentRecord) {

		if (empty($this->extConf)) $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fe_facelifting']);

		if ($this->extConf['overrideWithUserTsConfig']) {

			if (is_array($GLOBALS['BE_USER']->getTSConfig('tx_fe_facelifting.'))) {
				$tmp = array();
				$tmp = $GLOBALS['BE_USER']->getTSConfig('tx_fe_facelifting.');

				if (is_array($tmp['properties']) && $GLOBALS['BE_USER']->uc['edit_wideDocument']) {
					$this->extConf['popup_width'] = $tmp['properties']['fe_popup_widthWideDocument'];
					$this->extConf['popup_height'] = $tmp['properties']['fe_popup_heightWideDocument'];
				} else {
					$this->extConf['popup_width'] = $tmp['properties']['fe_popup_width'];
					$this->extConf['popup_height'] = $tmp['properties']['fe_popup_height'];
				}
			}
		}

		if ($GLOBALS['BE_USER']->uc['edit_wideDocument'] && !$this->extConf['overrideWithUserTsConfig']) {
			$this->extConf['popup_width'] = $this->extConf['popup_widthWideDocument'];
			$this->extConf['popup_height'] = $this->extConf['popup_heightWideDocument'];
		}

		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup'] || $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceNoPopup']) {
			$retUrl = t3lib_div::getIndpEnv('REQUEST_URI');
			$rParts = explode(':', $currentRecord);
			// This parentRecordNumber is used to make sure that only elements 3- of ordinary content elements makes a 'anchor' jump down the page.
			if ($rParts[0] == 'tt_content' && $this->parentRecordNumber > 2) {
				$retUrl .= '#' . $rParts[1];
			}
			return '<a href="' . htmlspecialchars($url . '&returnUrl=' . rawurlencode($retUrl)) . '" class="frontEndEditIconLinks">' . $string . '</a>';
		} else {
			return '<a href="#" onclick="' .
					htmlspecialchars('vHWin=window.open(\'' . $url . '&returnUrl=close.html\',\'FEquickEditWindow\',\'' . ('width=' . $this->extConf['popup_width'] . ',height=' . $this->extConf['popup_height'] . ' ') . ',status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;') .
					'" class="frontEndEditIconLinks">' . $string . '</a>';
		}
	}

	/**
	 * Wraps the input content string in a table with a gray border if the table/row combination evaluates to being disabled/hidden.
	 * Used for marking previewed records in the frontend.
	 *
	 * @param	string		The table name
	 * @param	array		The data record from $table
	 * @param	string		The content string to wrap
	 * @param	integer		The thickness of the border
	 * @param	array		The array with TypoScript properties for the content object
	 * @return	string		The input string wrapped in a table with a border color of #cccccc and thickness = $thick
	 * @see	editPanel()
	 */
	protected function editPanelPreviewBorder($table, array $row, $content, $thick, array $conf = array()) {
		if ($this->isDisabled($table, $row)) {
			$thick = t3lib_div::intInRange($thick, 1, 100);
			$color = $conf['color'] ? $conf['color'] : '#cccccc';
			if ($conf['innerWrap']) {
				$content = $this->wrap($content, $conf['innerWrap']);
			}
			if ($conf['innerWrap.']) {
				$content = $this->stdWrap($content, $conf['innerWrap.']);
			}
			$content = '<table class="typo3-editPanel-previewBorder" border="' . $thick . '" cellpadding="0" cellspacing="0" bordercolor="' . $color . '" width="100%" summary=""><tr><td>' . $content . '</td></tr></table>';
			if ($conf['outerWrap']) {
				$content = $this->wrap($content, $conf['outerWrap']);
			}
			if ($conf['outerWrap.']) {
				$content = $this->stdWrap($panel, $conf['outerWrap.']);
			}
		}

		return $content;
	}

	/**
	 * Returns true if the input table/row would be hidden in the frontend (according nto the current time and simulate user group)
	 *
	 * @param	string		The table name
	 * @param	array		The data record
	 * @return	boolean
	 * @see	editPanelPreviewBorder()
	 */
	protected function isDisabled($table, $row) {
		if (($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled']]) ||
				($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'] && $GLOBALS['TSFE']->simUserGroup && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group']] == $GLOBALS['TSFE']->simUserGroup) ||
				($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime']] > $GLOBALS['EXEC_TIME']) ||
				($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] && $row[$GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime']] < $GLOBALS['EXEC_TIME'])) {
			return true;
		}
	}

	/**
	 * Returns the editing form for a content element.
	 *
	 * @param	string		Form tag
	 * @param	string		Form name
	 * @param	string		the command
	 * @param	integer		newUID
	 * @param	array		dataArray for element
	 * @param	string		Table name of element
	 * @param	string		Current record
	 * @param	string		Blackline
	 * @return	string
	 */
	protected function editContent($formTag, $formName, $theCmd, $newUID, array $dataArray, $table, $currentRecord, $blackLine) {
		$tceforms = t3lib_div::makeInstance('t3lib_TCEforms_FE');
		$tceforms->prependFormFieldNames = 'TSFE_EDIT[data]';
		$tceforms->prependFormFieldNames_file = 'TSFE_EDIT_file';
		$tceforms->doSaveFieldName = 'TSFE_EDIT[doSave]';
		$tceforms->formName = $formName;
		$tceforms->backPath = TYPO3_mainDir;
		$tceforms->setFancyDesign();
		$tceforms->defStyle = 'font-family:Verdana;font-size:10px;';
		$tceforms->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];

		// Icon only mode for CSH destroys the layout for frontend editing so force full text mode instead.
		// @todo	Make sure the necessary Javascript and CSS are included so that CSH can work properly in all modes.
		if ($tceforms->edit_showFieldHelp == 'icon') {
			$tceforms->edit_showFieldHelp = 'text';
		}

		$tceforms->helpTextFontTag = '<font face="verdana,sans-serif" color="#333333" size="1">';

		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = true;
		$trData->lockRecords = 1;
		// Added without testing - should provide ability to submit default values in frontend editing, in-page.
		$trData->defVals = t3lib_div::_GP('defVals');
		$trData->fetchRecord($table, ($theCmd == 'new' ? $newUID : $dataArray['uid']), ($theCmd == 'new' ? 'new' : ''));
		reset($trData->regTableItems_data);
		$processedDataArr = current($trData->regTableItems_data);
		$processedDataArr['uid'] = $theCmd == 'new' ? 'NEW' : $dataArray['uid'];
		$processedDataArr['pid'] = $theCmd == 'new' ? $newUID : $dataArray['pid'];

		$panel = '';
		$buttons = '<input type="image" border="0" name="TSFE_EDIT[update]" src="' . $tceforms->backPath . 'gfx/savedok.gif" hspace="2" width="21" height="16" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" />';
		$buttons .= '<input type="image" border="0" name="TSFE_EDIT[update_close]" src="' . $tceforms->backPath . 'gfx/saveandclosedok.gif" hspace="2" width="21" height="16" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', 1) . '" />';
		$buttons .= '<input type="image" border="0" name="TSFE_EDIT[cancel]" onclick="' .
				htmlspecialchars('window.location.href=\'' . t3lib_div::getIndpEnv('REQUEST_URI') . '\';return false;') .
				'" src="' . $tceforms->backPath . 'gfx/closedok.gif" hspace="2" width="21" height="16" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', 1) . '" />';
		// Buttons top
		$panel .= $tceforms->intoTemplate(array('ITEM' => $buttons));
		$panel .= $tceforms->getMainFields($table, $processedDataArr);

		$hiddenF = "";
		if ($theCmd == 'new') {
			$hiddenF .= '<input type="hidden" name="TSFE_EDIT[data][' . $table . '][NEW][pid]" value="' . $newUID . '" />';
			if ($table == 'pages') {
				// If a new page is created in front-end, then show it by default!
				$hiddenF .= '<input type="hidden" name="TSFE_EDIT[data][' . $table . '][NEW][hidden]" value="0" />';
			} else {
				$hiddenF .= '<input type="hidden" name="TSFE_EDIT[record]" value="' . $currentRecord . '" />';
				$hiddenF .= '<input type="hidden" name="TSFE_EDIT[cmd]" value="edit" />';
			}
		}
		$hiddenF .= '<input type="hidden" name="TSFE_EDIT[doSave]" value="0" />';
		// Buttons AND hidden fields bottom.
		$panel .= $tceforms->intoTemplate(array('ITEM' => $buttons . $hiddenF));

		$panel = $formTag . $tceforms->wrapTotal($panel, $dataArray, $table) . '</form>' . ($theCmd != 'new' ? $blackLine : '');

		$finalOut = $tceforms->printNeededJSFunctions_top() . ($conf['edit.']['displayRecord'] ? $content : '') . $panel . ($theCmd == 'new' ? $blackLine : '') . $tceforms->printNeededJSFunctions();

		$GLOBALS['SOBE']->doc->insertHeaderData();

		return $finalOut;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/feedit/view/class.tx_feedit_editpanel.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/feedit/view/class.tx_feedit_editpanel.php']);
}

?>