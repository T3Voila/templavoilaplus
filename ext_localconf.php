<?php
defined('TYPO3_MODE') or die();

// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);

// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('templavoila', 'setup', '
# Setting templavoila plugin TypoScript
plugin.tx_templavoila_pi1 = USER
plugin.tx_templavoila_pi1.userFunc = Extension\Templavoila\Controller\FrontendController->main
plugin.tx_templavoila_pi1.disableExplosivePreview = 1

tt_content.templavoila_pi1 = COA
tt_content.templavoila_pi1 {
' . ($_EXTCONF['enable.']['renderFCEHeader'] ? '
    10 =< lib.stdheader
    ' : '') . '
    20 =< plugin.tx_templavoila_pi1
}

tt_content.menu.20.3 = USER
tt_content.menu.20.3.userFunc = Extension\Templavoila\Controller\SectionIndexController->mainAction
tt_content.menu.20.3.select.where >
tt_content.menu.20.3.indexField.data = register:tx_templavoila_pi1.current_field

', 'defaultContentRendering');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:templavoila/Configuration/TSConfig/Page.ts">'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:templavoila/Configuration/TSConfig/User.ts">'
);

// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_templavoila_ds,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to,storage_pid';

// Register our classes at a the hooks:
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['templavoila']
    = \Extension\Templavoila\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['templavoila']
    = \Extension\Templavoila\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['templavoila']
    = \Extension\Templavoila\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals']['templavoila']
    = \Extension\Templavoila\Service\UserFunc\Access::class . '->recordEditAccessInternals';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoila_unusedce']
    = array(\Extension\Templavoila\Command\UnusedContentElementCommand::class);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoila_usedCE']
    = array(\Extension\Templavoila\Service\UserFunc\UsedContentElement::class);

// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['default']  = \Extension\Templavoila\Controller\Preview\DefaultController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['text']     = \Extension\Templavoila\Controller\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['table']    = \Extension\Templavoila\Controller\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['mailform'] = \Extension\Templavoila\Controller\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['header']   = \Extension\Templavoila\Controller\Preview\HeaderController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['multimedia'] = \Extension\Templavoila\Controller\Preview\MultimediaController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['media']    = \Extension\Templavoila\Controller\Preview\MediaController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['uploads']  = \Extension\Templavoila\Controller\Preview\UploadsController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['textpic']  = \Extension\Templavoila\Controller\Preview\TextpicController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['splash']   = \Extension\Templavoila\Controller\Preview\TextpicController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['image']    = \Extension\Templavoila\Controller\Preview\ImageController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['bullets']  = \Extension\Templavoila\Controller\Preview\BulletsController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['html']     = \Extension\Templavoila\Controller\Preview\HtmlController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['menu']     = \Extension\Templavoila\Controller\Preview\MenuController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['list']     = \Extension\Templavoila\Controller\Preview\ListController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['search']   = \Extension\Templavoila\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['login']    = \Extension\Templavoila\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['shortcut'] = \Extension\Templavoila\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['div']      = \Extension\Templavoila\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['mod1']['renderPreviewContent']['templavoila_pi1'] = \Extension\Templavoila\Controller\Preview\NullController::class;

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Extension\\Templavoila\\Module\\Mod1\\Ajax::moveRecord'] =
    'EXT:templavoila/Classes/Module/Mod1/Ajax->moveRecord';

$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Extension\\Templavoila\\Module\\Cm1\\Ajax::getDisplayFileContent'] =
    'EXT:templavoila/Classes/Module/Cm1/Ajax->getDisplayFileContent';
