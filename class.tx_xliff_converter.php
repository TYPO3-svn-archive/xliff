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
 * XLIFF to ll-XML converter.
 *
 * @package     TYPO3
 * @subpackage  tx_xliff
 * @author      Xavier Perseguers <xavier@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_xliff_converter extends t3lib_SCbase {

	/**
	 * @var integer
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $extensionKey;

	/**
	 * Default constructor.
	 *
	 * @param string $extensionKey
	 */
	public function __construct($extensionKey) {
		$this->version = class_exists('t3lib_utility_VersionNumber')
				? t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version)
				: t3lib_div::int_from_ver(TYPO3_version);

		$this->extensionKey = $extensionKey;
	}

	/**
	 * Checks if conversion is needed.
	 *
	 * @return boolean
	 */
	public function isConversionNeeded() {
		// TODO: add further business logic (last ll-XML generation, ...)
		$files = $this->getXliffFiles();
		return count($files) > 0;
	}

	/**
	 * Outputs a HTML form to dynamically generate locallang*.xml files if needed
	 * or returns an empty string if no action is needed.
	 *
	 * @return string
	 */
	public function generateLlXml() {
		return '';
	}

	/**
	 * Returns an array of XLIFF files for this extension.
	 *
	 * @return array
	 */
	protected function getXliffFiles() {
		$files = t3lib_div::removePrefixPathFromList(
			t3lib_div::getAllFilesAndFoldersInPath(array(), t3lib_extMgm::extPath($this->extensionKey), 'xlf'),
			PATH_site
		);

		return $files;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/xliff/class.tx_xliff_converter.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/xliff/class.tx_xliff_converter.php']);
}
?>