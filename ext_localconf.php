<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	$version = class_exists('t3lib_utility_VersionNumber')
			? t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version)
			: t3lib_div::int_from_ver(TYPO3_version);

		// Register a XCLASS for the extension manager, in 4.4 and 4.5
	if ($version < 4005000) {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['typo3/mod/tools/em/index.php'] = t3lib_extMgm::extPath('xliff') . 'xclass/class.ux_tx_em_install.php';
	} elseif ($version < 4007000) {	// Temporary to allow tests using master
		$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS']['typo3/sysext/em/classes/install/class.tx_em_install.php'] = t3lib_extMgm::extPath('xliff') . 'xclass/class.ux_tx_em_install.php';
	}
}
?>