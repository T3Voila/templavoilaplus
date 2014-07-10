<?php
defined('TYPO3_MODE') or die();

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);

// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
	$_EXTKEY,
	'pi1/class.tx_templavoila_pi1.php',
	'_pi1',
	'CType',
	1
);

$tvSetup = array('plugin.tx_templavoila_pi1.disableExplosivePreview = 1');
if (!$_EXTCONF['enable.']['renderFCEHeader']) {
	$tvSetup[] = 'tt_content.templavoila_pi1.10 >';
}

//sectionIndex replacement
$tvSetup[] = 'tt_content.menu.20.3 = USER
	tt_content.menu.20.3.userFunc = tx_templavoila_pi1->tvSectionIndex
	tt_content.menu.20.3.select.where >
	tt_content.menu.20.3.indexField.data = register:tx_templavoila_pi1.current_field
';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
	$_EXTKEY,
	'setup',
	implode(PHP_EOL, $tvSetup),
	43
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
	'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/TSConfig/Page.ts">'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
	'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/TSConfig/User.ts">'
);

// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to';

// Register our classes at a the hooks:
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['templavoila'] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:\Extension\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['templavoila'] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:\Extension\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['templavoila'] = 'EXT:templavoila/Classes/Service/DataHandling/DataHandler.php:\Extension\Templavoila\Service\DataHandling\DataHandler';
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals']['templavoila'] = 'EXT:templavoila/Classes/Service/UserFunc/Access.php:&\Extension\Templavoila\Service\UserFunc\Access->recordEditAccessInternals';

$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce'] = array('EXT:templavoila/Classes/Comand/UnusedContentElementComand.php:\Extension\Templavoila\Comand\UnusedContentElementComand');
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE'] = array('EXT:templavoila/Classes/Service/UserFunc/UsedContentElement.php:\Extension\Templavoila\Service\UserFunc\UsedContentElement');

// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['default'] = 'EXT:templavoila/Classes/Controller/Preview/DefaultController.php:&\Extension\Templavoila\Controller\Preview\DefaultController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['text'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&\Extension\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['table'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&\Extension\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['mailform'] = 'EXT:templavoila/Classes/Controller/Preview/TextController.php:&\Extension\Templavoila\Controller\Preview\TextController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['header'] = 'EXT:templavoila/Classes/Controller/Preview/HeaderController.php:&\Extension\Templavoila\Controller\Preview\HeaderController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['multimedia'] = 'EXT:templavoila/Classes/Controller/Preview/MultimediaController.php:&\Extension\Templavoila\Controller\Preview\MultimediaController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['media'] = 'EXT:templavoila/Classes/Controller/Preview/MediaController.php:&\Extension\Templavoila\Controller\Preview\MediaController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['uploads'] = 'EXT:templavoila/Classes/Controller/Preview/UploadsController.php:&\Extension\Templavoila\Controller\Preview\UploadsController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['textpic'] = 'EXT:templavoila/Classes/Controller/Preview/TextpicController.php:&\Extension\Templavoila\Controller\Preview\TextpicController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['splash'] = 'EXT:templavoila/Classes/Controller/Preview/TextpicController.php:&\Extension\Templavoila\Controller\Preview\TextpicController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['image'] = 'EXT:templavoila/Classes/Controller/Preview/ImageController.php:&\Extension\Templavoila\Controller\Preview\ImageController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['bullets'] = 'EXT:templavoila/Classes/Controller/Preview/BulletsController.php:&\Extension\Templavoila\Controller\Preview\BulletsController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['html'] = 'EXT:templavoila/Classes/Controller/Preview/HtmlController.php:&\Extension\Templavoila\Controller\Preview\HtmlController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['menu'] = 'EXT:templavoila/Classes/Controller/Preview/MenuController.php:&\Extension\Templavoila\Controller\Preview\MenuController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['list'] = 'EXT:templavoila/Classes/Controller/Preview/ListController.php:&\Extension\Templavoila\Controller\Preview\ListController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['search'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['login'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['shortcut'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['div'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['templavoila_pi1'] = 'EXT:templavoila/Classes/Controller/Preview/NullController.php:&\Extension\Templavoila\Controller\Preview\NullController';

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_templavoila_mod1_ajax::moveRecord'] =
	'EXT:templavoila/mod1/class.tx_templavoila_mod1_ajax.php:tx_templavoila_mod1_ajax->moveRecord';

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_templavoila_cm1_ajax::getDisplayFileContent'] =
	'EXT:templavoila/cm1/class.tx_templavoila_cm1_ajax.php:tx_templavoila_cm1_ajax->getDisplayFileContent';
