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
	 * @var string
	 */
	protected $extensionVersion;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * Default constructor.
	 *
	 * @param string $extensionKey
	 * @param string $extensionVersion
	 */
	public function __construct($extensionKey, $extensionVersion) {
		$this->version = class_exists('t3lib_utility_VersionNumber')
				? t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version)
				: t3lib_div::int_from_ver(TYPO3_version);

		$this->extensionKey = $extensionKey;
		$this->extensionVersion = $extensionVersion;

		if (file_exists(PATH_site . 'typo3conf/xliff_conf.php')) {
			$this->config = require_once(PATH_site . 'typo3conf/xliff_conf.php');
		} else {
			$this->config = array();
		}
	}

	/**
	 * Checks if conversion is needed.
	 *
	 * @return boolean
	 */
	public function isConversionNeeded() {
		if (isset($this->config[$this->extensionKey])) {
			if ($this->config[$this->extensionKey] === $this->extensionVersion) {
					// Conversion already done for this version
				return FALSE;
			}
		}

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
		$this->content = '';

		$GP = t3lib_div::_GP('xliff');
		if ($GP && $GP['done']) {
			$this->persist();
			return $this->content;
		}

		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$title = 'Localization Files Converter';
		$this->content .= $this->doc->header($title);
		$this->content .= $this->doc->spacer(5);

		$this->content .= $this->doc->section('',
			'Extension ' . $this->extensionKey . ' is using XLIFF as localization format which is only supported'
			. ' since TYPO3 4.6.<br />'
			. 'This form lets you generate localization files compatible with the TYPO3 version you are'
			. ' using: TYPO3 ' . TYPO3_version . '.<br />'
			. 'Please note that you only should start the conversion process when installing or upgrading'
			. ' this extension.'
		);

		if ($GP) {
			$messages = $this->convertFiles();
			$this->content .= $this->doc->section('Generated files', implode('<br />', $messages));
			$this->content .= '<input type="hidden" name="xliff[done]" value="1" />';
		} else {
			$files = $this->getXliffFiles();
			$languages = $this->getLanguages($files);
			$languages[0] = 'default (English)';

			$this->content .= $this->doc->section('XLIFF files', implode('<br />', $files));
			$this->content .= $this->doc->section('Languages', implode('<br />', $languages));
			$this->content .= '<input type="hidden" name="xliff[convert]" value="1" />';
		}

			// Add some space before "Make updates" button
		$this->content .= $this->doc->spacer(5);

		return $this->content;
	}

	/**
	 * Saves the fact that current extension is ready.
	 *
	 * @return void
	 */
	protected function persist() {
		$this->config[$this->extensionKey] = $this->extensionVersion;

		$output = array();
		$output[] = '<?php';
		$output[] = 'return ' . var_export($this->config, TRUE) . ';';
		$output[] = '?>';

		t3lib_div::writeFile(PATH_site . 'typo3conf/xliff_conf.php', implode(chr(10), $output));
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

	/**
	 * Extracts the languages from a list of localization files.
	 *
	 * @param array $files
	 * @return array
	 */
	protected function getLanguages(array $files) {
		$languages = array('default');
		foreach ($files as $file) {
			if (preg_match('/^([^.]+)\.locallang.*\.xlf$/', basename($file), $matches)) {
				if (!in_array($matches[1], $languages)) {
					$languages[] = $matches[1];
				}
			}
		}

		return $languages;
	}

	/**
	 * Generates ll-XML localization files from XLIFF files of this extension.
	 * ll-XML localization files for default language will be stored next to the
	 * XLIFF files. Other languages will be stored within typo3conf/l10n/, as for
	 * localization files retrieved from TER.
	 *
	 * @return array
	 */
	protected function convertFiles() {
		$files = $this->getXliffFiles();

			// Group files by language
		$sourceFiles = array_flip($this->getLanguages($files));
		foreach ($sourceFiles as $languageKey => $foo) {
			if (!is_array($foo)) {
				$sourceFiles[$languageKey] = array();
			}
			foreach ($files as $file) {
				if ($languageKey === 'default') {
					if (t3lib_div::isFirstPartOfStr(basename($file), 'locallang')) {
						$sourceFiles[$languageKey][] = $file;
					}
				} else {
					if (t3lib_div::isFirstPartOfStr(basename($file), $languageKey . '.locallang')) {
						$sourceFiles[$languageKey][] = $file;
					}
				}
			}
		}

			// Convert localization files
		$messages = array();
		$extDirectoryPrefix = substr(t3lib_extMgm::extPath($this->extensionKey), strlen(PATH_site));
		foreach ($sourceFiles as $languageKey => $files) {
			$l10nDirectory = 'typo3conf/l10n/' . $languageKey . '/' . $this->extensionKey . '/';
			foreach ($files as $file) {
				if ($languageKey === 'default') {
					$targetFile = substr($file, 0, strlen($file) - 4) . '.xml';
				} else {
					$llxmlFile = basename(substr($file, 0, strlen($file) - 4) . '.xml');
					$targetFile = $l10nDirectory . dirname(substr($file, strlen($extDirectoryPrefix))) . '/' . $llxmlFile;
				}

				if ($this->xliff2llxml($languageKey, PATH_site . $file, PATH_site . $targetFile)) {
					$messages[] = 'Created file ' . $targetFile;
				} else {
					$messages[] = 'ERROR creating file ' . $targetFile;
				}
			}
		}

			// Return the success/error messages for generated files
		return $messages;
	}

	/**
	 * Converts XLIFF localization file to ll-XML.
	 *
	 * @param string $languageKey
	 * @param string $xliffFile
	 * @param string $llxmlFile
	 * @return boolean
	 */
	protected function xliff2llxml($languageKey, $xliffFile, $llxmlFile) {
		try {
			$LOCAL_LANG = $this->parseXliff($xliffFile, $languageKey);
		} catch (Exception $e) {
			return FALSE;
		}

		switch (TRUE) {
			case substr($xliffFile, -7) === '_db.xlf':
				$type = 'database';
				$description = sprintf('Language labels for database tables/fields belonging to extension \'%s\'', $this->extensionKey);
				break;
			case substr($xliffFile, -8) === '_mod.xlf':
				$type = 'module';
				$description = sprintf('Language labels for module fields belonging to extension \'%s\'', $this->extensionKey);
				break;
			case substr($xliffFile, -8) === '_csh.xlf':
				$type = 'CSH';
				$description = sprintf('Context Sensitive Help language labels for plugin belonging to extension \'%s\'', $this->extensionKey);
				break;
			default:
				$type = 'module';
				$description = sprintf('Language labels for plugin belonging to extension \'%s\'', $this->extensionKey);
				break;
		}

		$xml = array();
		$xml[] = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>';
		$xml[] = '<T3locallang>';
		$xml[] = '	<meta type="array">';
		$xml[] = '		<type>' . $type . '</type>';
		$xml[] = '		<description>' . $description . '</description>';
		$xml[] = '	</meta>';
		$xml[] = '	<data type="array">';
		$xml[] = '		<languageKey index="' . $languageKey . '" type="array">';

		foreach ($LOCAL_LANG[$languageKey] as $key => $data) {
			$xml[] = '			<label index="' . $key . '">' . htmlspecialchars($data[0]['target']) . '</label>';
		}

		$xml[] = '		</languageKey>';
		$xml[] = '	</data>';
		$xml[] = '</T3locallang>';

		try {
			$ret = t3lib_div::mkdir_deep(PATH_site, substr(dirname($llxmlFile), strlen(PATH_site)) . '/');
			$OK = !$ret;
		} catch (RuntimeException $e) {
			$OK = FALSE;
		}

		if ($OK) {
			$OK = t3lib_div::writeFile($llxmlFile, implode(chr(10), $xml));
		}

		return $OK;
	}

	/**
	 * Parses an XLIFF file into a LOCAL_LANG array structure.
	 *
	 * @param string $file
	 * @param string $languageKey
	 * @return array
	 * @throws Exception
	 */
	protected function parseXliff($file, $languageKey) {
		$root = simplexml_load_file($file, 'SimpleXmlElement', \LIBXML_NOWARNING);
		$parsedData = array();
		$bodyOfFileTag = $root->file->body;

		foreach ($bodyOfFileTag->children() as $translationElement) {
			if ($translationElement->getName() === 'trans-unit' && !isset($translationElement['restype'])) {
					// If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)

				if ($languageKey === 'default') {
						// Default language coming from an XLIFF template (no target element)
					$parsedData[(string)$translationElement['id']][0] = array(
						'source' => (string)$translationElement->source,
						'target' => (string)$translationElement->source,
					);
				} else {
					$parsedData[(string)$translationElement['id']][0] = array(
						'source' => (string)$translationElement->source,
						'target' => (string)$translationElement->target,
					);
				}
			} elseif ($translationElement->getName() === 'group' && isset($translationElement['restype']) && (string)$translationElement['restype'] === 'x-gettext-plurals') {
					// This is a translation with plural forms
				$parsedTranslationElement = array();

				foreach ($translationElement->children() as $translationPluralForm) {
					if ($translationPluralForm->getName() === 'trans-unit') {
							// When using plural forms, ID looks like this: 1[0], 1[1] etc
						$formIndex = substr((string)$translationPluralForm['id'], strpos((string)$translationPluralForm['id'], '[') + 1, -1);

						if ($languageKey === 'default') {
								// Default language come from XLIFF template (no target element)
							$parsedTranslationElement[(int)$formIndex] = array(
								'source' => (string)$translationPluralForm->source,
								'target' => (string)$translationPluralForm->source,
							);
						} else {
							$parsedTranslationElement[(int)$formIndex] = array(
								'source' => (string)$translationPluralForm->source,
								'target' => (string)$translationPluralForm->target,
							);
						}
					}
				}

				if (!empty($parsedTranslationElement)) {
					if (isset($translationElement['id'])) {
						$id = (string)$translationElement['id'];
					} else {
						$id = (string)($translationElement->{'trans-unit'}[0]['id']);
						$id = substr($id, 0, strpos($id, '['));
					}

					$parsedData[$id] = $parsedTranslationElement;
				}
			}
		}

		$LOCAL_LANG = array();
		$LOCAL_LANG[$languageKey] = $parsedData;

		return $LOCAL_LANG;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/xliff/class.tx_xliff_converter.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/xliff/class.tx_xliff_converter.php']);
}
?>