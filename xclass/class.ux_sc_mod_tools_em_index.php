<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Xavier Perseguers <xavier@typo3.org>
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
 * XCLASS for TYPO3 Extension Manager in TYPO3 4.4.
 *
 * @package     TYPO3
 * @subpackage  tx_xliff
 * @author      Xavier Perseguers <xavier@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class ux_SC_mod_tools_em_index extends SC_mod_tools_em_index {

	/**
	 * Interrupts the database comparison process to allow user to dynamically
	 * create ll-XML files out of XLIFF locaization files.
	 *
	 * @param string $extKey Extension key
	 * @param array $extInfo Extension information array
	 * @param boolean $infoOnly If TRUE, returns array with info.
	 * @return array|string If $infoOnly, returns array with information. Otherwise performs update.
	 */
	public function checkDBupdates($extKey, array $extInfo, $infoOnly = FALSE) {
		$output = '';
		if ($infoOnly || !isset($extInfo['EM_CONF']['constraints']['depends']['xliff'])) {
			return parent::checkDBupdates($extKey, $extInfo, $infoOnly);
		}

		/** @var $converter tx_xliff_converter */
		$converter = t3lib_div::makeInstance('tx_xliff_converter', $extKey, $extInfo['EM_CONF']['version']);
		if ($converter->isConversionNeeded()) {
			$output = $converter->generateLlXml();
		}

		return $output ? $output : parent::checkDBupdates($extKey, $extInfo, $infoOnly);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/xliff/xclass/class.ux_sc_mod_tools_em_index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/xliff/xclass/class.ux_sc_mod_tools_em_index.php']);
}
?>