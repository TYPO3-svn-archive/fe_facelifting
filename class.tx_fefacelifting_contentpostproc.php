<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sven Juergens <t3@blue-side.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
class tx_fefacelifting_contentpostproc {

    public function includeJavaScript($params, &$parentObject) {
        if (is_object($GLOBALS['BE_USER']) && $GLOBALS['TSFE']->beUserLogin) {
            $content = '';
            $config = $GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.'];
            if(empty($config)){
                return;
            }

            if ( isset($config['integrateJQueryLibrary']) && $config['integrateJQueryLibrary'] == 1) {
                $content .=  $GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['jQueryIntegration'];
            }
            if (isset($config['integrateAdditionalJS']) && $config['integrateAdditionalJS'] == 1) {
                $content .= PHP_EOL . '
<script type="text/javascript"> '
                           . $GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['additionalJS'] . '
</script>';
            }
            if ( isset($config['integrateAdditionalCSS']) && $config['integrateAdditionalCSS'] == 1) {
                $content .= '<style type="text/css"> ' . $GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['additionalCSS'] . ' </style>';
            }
            if(!empty($content)){
                $parentObject->content = str_replace('</body>', $content . '</body>', $parentObject->content);
            }
        }
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fe_facelifting/view/class.tx_fefacelifting_contentpostproc.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fe_facelifting/view/class.tx_fefacelifting_contentpostproc.php']);
}
?>