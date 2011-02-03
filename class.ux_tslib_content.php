<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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

class ux_tslib_cObj extends tslib_cObj {

	var	$extConf = ''; 
	
	
	function extGetLL2($key)	{
		global $LOCAL_LANG;
		if (!is_array($LOCAL_LANG)) {
			$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_tsfe.php');
			#include('./'.TYPO3_mainDir.'sysext/lang/locallang_tsfe.php');
			if (!is_array($LOCAL_LANG)) {
				$LOCAL_LANG = array();
			}
		}

		$labelStr = htmlspecialchars($GLOBALS['LANG']->getLL($key));	// Label string in the default backend output charset.

			// Convert to utf-8, then to entities:
		if ($GLOBALS['LANG']->charSet!='utf-8') {
			$labelStr = $GLOBALS['LANG']->csConvObj->utf8_encode($labelStr,$GLOBALS['LANG']->charSet);
		}
		#$labelStr = $GLOBALS['LANG']->csConvObj->utf8_to_entities($labelStr);

			// Return the result:
		return $labelStr;
	}

	

function editPanel($content, $conf, $currentRecord='', $dataArr=array())	{
		global $TCA,$BE_USER,$LOCAL_LANG;
		
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fe_facelifting']);

			// If no backend user, return immediately
		if (!$GLOBALS['TSFE']->beUserLogin)	{ return $content; }

			// If a backend user is logged in, then go on...
		if ($conf['newRecordFromTable'])	{
			$currentRecord = $conf['newRecordFromTable'].':NEW';
			$conf['allow']='new';
		}

		if (!$currentRecord)	$currentRecord=$this->currentRecord;
		if (!count($dataArr))	$dataArr=$this->data;
		list($table,$uid) = explode(':',$currentRecord);
		$mayEdit=0;
		$nPid=intval($conf['newRecordInPid']);	// Page ID for new records, 0 if not specified

			// If no access right to record languages, return immediately
		if ($table === 'pages')	{
			$lang = $GLOBALS['TSFE']->sys_language_uid;
		} elseif ($table === 'tt_content')	{
			$lang = $GLOBALS['TSFE']->sys_language_content;
		} elseif ($TCA[$table]['ctrl']['languageField'])	{
			$lang = $currentRecord[$TCA[$table]['ctrl']['languageField']];
		} else {
			$lang = -1;
		}
		if (!$BE_USER->checkLanguageAccess($lang))	{ return $content; }

		if (!$conf['onlyCurrentPid'] || $dataArr['pid']==$GLOBALS['TSFE']->id)	{
				// Permissions:
			$types = t3lib_div::trimExplode(',',strtolower($conf['allow']),1);
			$allow = array_flip($types);

			$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
			if ($table=='pages')	{
				if (count($GLOBALS['TSFE']->config['rootLine'])==1)	{unset($allow['move']); unset($allow['hide']); unset($allow['delete']);}	// rootpage!
				if (!($perms&2))	{unset($allow['edit']);unset($allow['move']);unset($allow['hide']);}
				if (!($perms&4))	unset($allow['delete']);
				if (!($perms&8))	unset($allow['new']);
				if (count($allow))	$mayEdit=1;		// Can only display editbox if there are options in the menu
				$newUid = $uid;
			} else {
// changed by gec 23.08.07: do not display editpanel if user has no table modify access
//				$mayEdit = count($allow)&&($perms&16);
				$mayEdit = $BE_USER->check('tables_modify',$table) ? count($allow)&&($perms&16) : 0;
// end changed by gec 23.08.07
				if ($conf['newRecordFromTable'])	{
					$newUid=$GLOBALS['TSFE']->id;
					if ($nPid) $newUid=$nPid;
				} else {
					$newUid = -1*$uid;
				}
			}
		}

		if ($GLOBALS['TSFE']->displayEditIcons && $table && $mayEdit)	{
			$GLOBALS['TSFE']->set_no_cache();		// Special content is about to be shown, so the cache must be disabled.
			$formName = 'TSFE_EDIT_FORM_'.substr($GLOBALS['TSFE']->uniqueHash(),0,4);
			$formTag = '<form name="'.$formName.'" action="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" onsubmit="return TBE_EDITOR.checkSubmit(1);" style="margin: 0 0 0 0;">';
			$sortField = $TCA[$table]['ctrl']['sortby'];
			$labelField = $TCA[$table]['ctrl']['label'];
			$hideField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
			$blackLine = $conf['line']?'<img src="clear.gif" width="1" height="'.intval($conf['line']).'" alt="" title="" /><br /><table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="black" style="border: 0px;" summary=""><tr style="border: 0px;"><td style="border: 0px;"><img src="clear.gif" width="1" height="1" alt="" title="" /></td></tr></table><br />':'';

			$theCmd='';
			$TSFE_EDIT = t3lib_div::_POST('TSFE_EDIT');
			if (is_array($TSFE_EDIT) && $TSFE_EDIT['record']==$currentRecord && !$TSFE_EDIT['update_close'])	{
				$theCmd =$TSFE_EDIT['cmd'];
			}

			switch($theCmd)	{
				case 'edit':
				case 'new':
					$tceforms = t3lib_div::makeInstance('t3lib_TCEforms_FE');
					$tceforms->prependFormFieldNames = 'TSFE_EDIT[data]';
					$tceforms->prependFormFieldNames_file = 'TSFE_EDIT_file';
					$tceforms->doSaveFieldName = 'TSFE_EDIT[doSave]';
					$tceforms->formName = $formName;
					$tceforms->backPath = TYPO3_mainDir;
					$tceforms->setFancyDesign();
					$tceforms->defStyle = 'font-family:Verdana;font-size:10px;';
					$tceforms->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];
					$tceforms->helpTextFontTag='<font face="verdana,sans-serif" color="#333333" size="1">';

					$trData = t3lib_div::makeInstance('t3lib_transferData');
					$trData->addRawData = TRUE;
					$trData->defVals = t3lib_div::_GP('defVals');		// Added without testing - should provide ability to submit default values in frontend editing, in-page.
					$trData->fetchRecord($table,	($theCmd=='new'?$newUid:$dataArr['uid']), ($theCmd=='new'?'new':'') );
					reset($trData->regTableItems_data);
					$processedDataArr = current($trData->regTableItems_data);
					$processedDataArr['uid']=$theCmd=='new'?'NEW':$dataArr['uid'];
					$processedDataArr['pid']=$theCmd=='new'?$newUid:$dataArr['pid'];

					$panel='';
					$buttons = '<input type="image" border="0" name="TSFE_EDIT[update]" src="'.$this->extConf['path_to_icons'].'savedok.gif" hspace="2" width="21" height="'.$this->extConf['icon_h'] . '" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'" />';
					$buttons.= '<input type="image" border="0" name="TSFE_EDIT[update_close]" src="'.$this->extConf['path_to_icons'].'saveandclosedok.gif" hspace="2" width="21" height="'.$this->extConf['icon_h'] . '" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'" />';
					$buttons.= '<input type="image" border="0" name="TSFE_EDIT[cancel]" onclick="'.
						htmlspecialchars('window.location.href=\''.t3lib_div::getIndpEnv('REQUEST_URI').'\';return false;').
						'" src="'.$this->extConf['path_to_icons'].'closedok.gif" hspace="2" width="21" height="'.$this->extConf['icon_h'] . '" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'" />';
					$panel.=$tceforms->intoTemplate(array('ITEM'=>$buttons));		// Buttons top
					$panel.=$tceforms->getMainFields($table,$processedDataArr);

					$hiddenF="";
					if ($theCmd=='new')	{
						$hiddenF.='<input type="hidden" name="TSFE_EDIT[data]['.$table.'][NEW][pid]" value="'.$newUid.'" />';
						if ($table=='pages')	$hiddenF.='<input type="hidden" name="TSFE_EDIT[data]['.$table.'][NEW][hidden]" value="0" />';		// If a new page is created in front-end, then show it by default!
					} else {
						$hiddenF.='<input type="hidden" name="TSFE_EDIT[record]" value="'.$currentRecord.'" />';
						$hiddenF.='<input type="hidden" name="TSFE_EDIT[cmd]" value="edit" />';
					}
					$hiddenF.='<input type="hidden" name="TSFE_EDIT[doSave]" value="0" />';
					$panel.=$tceforms->intoTemplate(array('ITEM'=>$buttons.$hiddenF));	// Buttons AND hidden fields bottom.

					$panel=$formTag.$tceforms->wrapTotal($panel,$dataArr,$table).'</form>'.($theCmd!='new'?$blackLine:'');
					$finalOut = $tceforms->printNeededJSFunctions_top().($conf['edit.']['displayRecord']?$content:'').$panel.($theCmd=='new'?$blackLine:'').$tceforms->printNeededJSFunctions();
				break;
				default:
					$panel = '';
					if (isset($allow['toolbar']))		$panel.=$GLOBALS['BE_USER']->ext_makeToolBar().'<img src="clear.gif" width="2" height="1" alt="" title="" />';
					if (isset($allow['edit']))		$panel.=$this->editPanelLinkWrap('<img src="'. $this->extConf['path_to_icons'] . 'edit2.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_editRecord').'" align="top" alt="" />',$formName,'edit',$dataArr['_LOCALIZED_UID'] ? $table.':'.$dataArr['_LOCALIZED_UID'] : $currentRecord);
					if (isset($allow['move']) && $sortField && $BE_USER->workspace===0)	{	// Hiding in workspaces because implementation is incomplete
						$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'button_up.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_moveUp').'" align="top" alt="" />',$formName,'up');
						$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'button_down.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_moveDown').'" align="top" alt="" />',$formName,'down');
					}
					if (isset($allow['hide']) && $hideField && $BE_USER->workspace===0 && !$dataArr['_LOCALIZED_UID'])	{	// Hiding in workspaces because implementation is incomplete, Hiding for localizations because it is unknown what should be the function in that case
						if ($dataArr[$hideField])	{
							$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'button_unhide.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_unhide').'" align="top" alt="" />',$formName,'unhide');
						} else {
							$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'button_hide.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_hide').'" align="top" alt="" />',$formName,'hide','',$BE_USER->extGetLL('p_hideConfirm'));
						}
					}
					if (isset($allow['new']))	{
						if ($table=='pages')	{
							$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'new_page.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_newSubpage').'" align="top" alt="" />',$formName,'new',$currentRecord,'',$nPid);
						} else {
							$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'new_record.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$BE_USER->extGetLL('p_newRecordAfter').'" align="top" alt="" />',$formName,'new',$currentRecord,'',$nPid);
						}
					}
					if (isset($allow['delete']) && $BE_USER->workspace===0 && !$dataArr['_LOCALIZED_UID'])		{	// Hiding in workspaces because implementation is incomplete, Hiding for localizations because it is unknown what should be the function in that case
			
						$panel.=$this->editPanelLinkWrap('<img src="'.$this->extConf['path_to_icons'] . 'garbage.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" title="'.$this->extGetLL2('p_delete').'" align="top" alt="" />',$formName,'delete','',$this->extGetLL2('p_deleteConfirm'));
					}

						//	Final
					$labelTxt = $this->stdWrap($conf['label'],$conf['label.']);
					$panel='

								<!-- BE_USER Edit Panel: -->
								'.$formTag.'
									<input type="hidden" name="TSFE_EDIT[cmd]" value="" />
									<input type="hidden" name="TSFE_EDIT[record]" value="'.$currentRecord.'" />
									<table border="0" cellpadding="0" cellspacing="0" class="typo3-editPanel" summary="">
										<tr>
											<td nowrap="nowrap" bgcolor="' . $this->extConf['bgcolor_icons'] . '" class="typo3-editPanel-controls">'.$panel.'</td>'.($labelTxt?'
											<td nowrap="nowrap" bgcolor="' . $this->extConf['bgcolor_label'] . '" class="typo3-editPanel-label"><font face="verdana" color="black">&nbsp;'.sprintf($labelTxt,htmlspecialchars(t3lib_div::fixed_lgd($dataArr[$labelField],50))).'&nbsp;</font></td>':'').'
										</tr>
									</table>
								</form>';
						// wrap the panel
					if ($conf['innerWrap']) $panel = $this->wrap($panel,$conf['innerWrap']);
					if ($conf['innerWrap.']) $panel = $this->stdWrap($panel,$conf['innerWrap.']);
						// add black line:
					$panel.=$blackLine;
						// wrap the complete panel
					if ($conf['outerWrap']) $panel = $this->wrap($panel,$conf['outerWrap']);
					if ($conf['outerWrap.']) $panel = $this->stdWrap($panel,$conf['outerWrap.']);
					$finalOut = $content.$panel;
				break;
			}

			if ($conf['previewBorder']) $finalOut = $this->editPanelPreviewBorder($table,$dataArr,$finalOut,$conf['previewBorder'],$conf['previewBorder.']);
			return $finalOut;
		} else {
			return $content;
		}
	}
	
	function editPanelLinkWrap_doWrap($string,$url,$currentRecord)	{
			
		if(empty($this->extConf)) $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fe_facelifting']);
		
			if($this->extConf['overrideWithUserTsConfig']) {
		
			if(is_array($GLOBALS['BE_USER']->getTSConfig('tx_fe_facelifting.'))){
				$tmp = array();
				$tmp = $GLOBALS['BE_USER']->getTSConfig('tx_fe_facelifting.');
				
					if(is_array($tmp['properties'])){				
						$this->extConf['popup_width'] = $tmp['properties']['fe_popup_width'];
						$this->extConf['popup_height'] = $tmp['properties']['fe_popup_height'];
					}	
			}
		}
	
		if ($GLOBALS['BE_USER']->uc['TSFE_adminConfig']['edit_editNoPopup'] || $GLOBALS['BE_USER']->extAdminConfig['module.']['edit.']['forceNoPopup'])	{
			$retUrl = t3lib_div::getIndpEnv('REQUEST_URI');
			$rParts = explode(':',$currentRecord);
			if ($rParts[0]=='tt_content' && $this->parentRecordNumber>2)	{	// This parentRecordNumber is used to make sure that only elements 3- of ordinary content elements makes a 'anchor' jump down the page.
				$retUrl.='#'.$rParts[1];
			}
			return '<a href="'.htmlspecialchars($url.'&returnUrl='.rawurlencode($retUrl)).'">'.$string.'</a>';
		} else {
			return '<a href="#" onclick="'.
				htmlspecialchars('vHWin=window.open(\''.$url.'&returnUrl=close.html\',\'FEquickEditWindow\',\''.($GLOBALS['BE_USER']->uc['edit_wideDocument']?'width=690,height=500':'width='.$this->extConf['popup_width'].',height='.$this->extConf['popup_height'].' ').',status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;').
				'">'.$string.'</a>';
		}
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
	function editIcons($content,$params, $conf=array(), $currentRecord='', $dataArr=array(),$addUrlParamStr='')	{
		global $BE_USER;
		
		if(empty($this->extConf)) $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fe_facelifting']);

			// If no backend user, return immediately
		if (!$GLOBALS['TSFE']->beUserLogin)		{return $content;}

			// Check incoming params:
		$rParts = explode(':',$currentRecord?$currentRecord:$this->currentRecord);

		list($table,$fieldList)=t3lib_div::trimExplode(':',$params,1);
		if (!$fieldList)	{
			$fieldList=$table;
			$table=$rParts[0];
		} else {
			if ($table!=$rParts[0])	return $content;	// If the table is set as the first parameter, and does not match the table of the current record, then just return.
		}

			// Check if allowed to edit content:
		$mayEdit=0;
		$dataArr=count($dataArr)?$dataArr:$this->data;	// If pages-record, should contain correct perms-field, if not, should contain correct pid value.

		$editUid = $dataArr['_LOCALIZED_UID'] ? $dataArr['_LOCALIZED_UID'] : $rParts[1];

		if ($table=='pages')	{
			$mayEdit = $BE_USER->isAdmin()||$BE_USER->doesUserHaveAccess($dataArr,2)?1:0;
		} else {
// changed by gec 23.08.07: do not display editicons if user has no table modify access
//			$mayEdit = $BE_USER->isAdmin()||$BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages',$dataArr['pid']),16)?1:0;
			$mayEdit = $BE_USER->isAdmin()||(($BE_USER->doesUserHaveAccess(t3lib_BEfunc::getRecord('pages',$dataArr['pid']),16)?1:0) && $BE_USER->check('tables_modify',$table));
// end changed by gec 23.08.07
		}

			// Check if allowed to edit language
		if ($mayEdit)	{
			if ($table === 'pages')	{
				$lang = $GLOBALS['TSFE']->sys_language_uid;
			} elseif ($table === 'tt_content')	{
				$lang = $GLOBALS['TSFE']->sys_language_content;
			} elseif ($TCA[$table]['ctrl']['languageField'])	{
				$lang = $currentRecord[$TCA[$table]['ctrl']['languageField']];
			} else {
				$lang = -1;
			}
			if (!$BE_USER->checkLanguageAccess($lang))	{ $mayEdit = 0; }
		}

		if ($GLOBALS['TSFE']->displayFieldEditIcons && $table && $mayEdit && $fieldList)	{
			$GLOBALS['TSFE']->set_no_cache();		// Special content is about to be shown, so the cache must be disabled.
			$style = $conf['styleAttribute'] ? ' style="'.htmlspecialchars($conf['styleAttribute']).'"' : '';
			$iconTitle = $this->stdWrap($conf['iconTitle'],$conf['iconTitle.']);
			$iconImg = $conf['iconImg'] ? $conf['iconImg'] : '<img src="'.$this->extConf['path_to_icons'] . 'edit2.gif" width="'.$this->extConf['icon_w'] . '" height="'.$this->extConf['icon_h'] . '" border="0" align="top" title="'.t3lib_div::deHSCentities(htmlspecialchars($iconTitle)).'"'.$style.' class="frontEndEditIcons" alt="" />';
			$nV=t3lib_div::_GP('ADMCMD_view')?1:0;
			$adminURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir;
			$icon = $this->editPanelLinkWrap_doWrap($iconImg, $adminURL.'alt_doc.php?edit['.$rParts[0].']['.$editUid.']=edit&columnsOnly='.rawurlencode($fieldList).'&noView='.$nV.$addUrlParamStr,implode(':',$rParts));
			if ($conf['beforeLastTag']<0)	{
				$content=$icon.$content;
			} elseif ($conf['beforeLastTag']>0)	{
				$cBuf = rtrim($content);
				$securCount=30;
				while($securCount && substr($cBuf,-1)=='>' && substr($cBuf,-4)!='</a>')	{
					$cBuf = rtrim(ereg_replace('<[^<]*>$','',$cBuf));
					$securCount--;
				}
				$content = strlen($cBuf)&&$securCount ? substr($content,0,strlen($cBuf)).$icon.substr($content,strlen($cBuf)) : $content=$icon.$content;
			} else {
				$content.=$icon;
			}
		}
		return $content;
	}
	
}