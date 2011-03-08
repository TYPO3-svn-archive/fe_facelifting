<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Administrator
 * Date: 08.03.11
 * Time: 23:35
 * To change this template use File | Settings | File Templates.
 */
 
class tx_fefacelifting_contentpostproc {

		public function includeJavaScript($params, &$parent) {
		if (is_object($GLOBALS['BE_USER']) && $GLOBALS['TSFE']->beUserLogin) {
			$content = '';
			if($GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['integrateJQuery'] == 1){
				$content .= PHP_EOL .'
<script src="' . t3lib_extMgm::siteRelPath('fe_facelifting') . 'Resources/jquery-1.5.1.min.js' . '" type="text/javascript"></script>
				';
			}
			if($GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['integrateJQueryHighlightCode'] == 1){
				$content .= PHP_EOL .'
<script type="text/javascript"> ' .
$GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['jQueryHighlightCode'] .'
</script>';
			}
			$content .= '<style type="text/css"> ' . $GLOBALS['TSFE']->tmpl->setup['plugin.']['fe_facelifting.']['jQueryHighlightCSS'] . ' </style>';
				$parent->content = str_replace('</body>', $content . '</body>', $parent->content);
		}
	}
}
?>