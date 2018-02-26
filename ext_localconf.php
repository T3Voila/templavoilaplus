<?php
defined('TYPO3_MODE') or die();
// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);

// Register "XCLASS" of FlexFormTools for language parsing
// Done also in TableConfigurationPostProcessingHook!
if (version_compare(TYPO3_version, '8.5.0', '>=')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
        = \Ppi\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools8::class;
} else {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
        = \Ppi\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools::class;
}

// Register XCLASSes
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap::class]['className']
    = \Ppi\TemplaVoilaPlus\Tree\View\ContentCreationPagePositionMap::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\View\PagePositionMap::class]['className']
    = \Ppi\TemplaVoilaPlus\Xclass\PagePositionMap::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController::class]['className']
    = \Ppi\TemplaVoilaPlus\Xclass\NewContentElementController::class;

// Language diff updating in flex
$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] = true;


// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('templavoilaplus', 'setup', '
# Setting templavoila plugin TypoScript
plugin.tx_templavoilaplus_pi1 = USER
plugin.tx_templavoilaplus_pi1.userFunc = Ppi\TemplaVoilaPlus\Controller\FrontendController->main
plugin.tx_templavoilaplus_pi1.disableExplosivePreview = 1

tt_content.templavoilaplus_pi1 = COA
tt_content.templavoilaplus_pi1 {
' . ($_EXTCONF['enable.']['renderFCEHeader'] ? '
    10 < lib.stdheader
    ' : '') . '
    20 < plugin.tx_templavoilaplus_pi1
}

tt_content.menu.20.3 = USER
tt_content.menu.20.3.userFunc = Ppi\TemplaVoilaPlus\Controller\SectionIndexController->mainAction
tt_content.menu.20.3.select.where >
tt_content.menu.20.3.indexField.data = register:tx_templavoilaplus_pi1.current_field

', 'defaultContentRendering');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:templavoilaplus/Configuration/TSConfig/Page.ts">'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:templavoilaplus/Configuration/TSConfig/User.ts">'
);

// Adding Page Template Selector Fields to root line:
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_templavoilaplus_ds,tx_templavoilaplus_to,tx_templavoilaplus_next_ds,tx_templavoilaplus_next_to,storage_pid';

// Register our classes at a the hooks:
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['templavoilaplus']
    = \Ppi\TemplaVoilaPlus\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['templavoilaplus']
    = \Ppi\TemplaVoilaPlus\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['templavoilaplus']
    = \Ppi\TemplaVoilaPlus\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals']['templavoilaplus']
    = \Ppi\TemplaVoilaPlus\Service\UserFunc\Access::class . '->recordEditAccessInternals';
// Hook after ext_tables run to do all FormHandler registering things
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']['templavoilaplus']
    = \Ppi\TemplaVoilaPlus\Hooks\TableConfigurationPostProcessingHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess']['templavoilaplus']
    = \Ppi\TemplaVoilaPlus\Hooks\BackendControllerHook::class . '->addInlineSettings';


$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules']['tx_templavoilaplus_unusedce']
    = array(\Ppi\TemplaVoilaPlus\Command\UnusedContentElementCommand::class);
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoilaplus_usedCE']
    = array(\Ppi\TemplaVoilaPlus\Service\UserFunc\UsedContentElement::class);

// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['default']  = \Ppi\TemplaVoilaPlus\Controller\Preview\DefaultController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['text']     = \Ppi\TemplaVoilaPlus\Controller\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['table']    = \Ppi\TemplaVoilaPlus\Controller\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['mailform'] = \Ppi\TemplaVoilaPlus\Controller\Preview\TextController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['header']   = \Ppi\TemplaVoilaPlus\Controller\Preview\HeaderController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['multimedia'] = \Ppi\TemplaVoilaPlus\Controller\Preview\MultimediaController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['media']    = \Ppi\TemplaVoilaPlus\Controller\Preview\MediaController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['uploads']  = \Ppi\TemplaVoilaPlus\Controller\Preview\UploadsController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['textpic']  = \Ppi\TemplaVoilaPlus\Controller\Preview\TextpicController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['splash']   = \Ppi\TemplaVoilaPlus\Controller\Preview\TextpicController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['image']    = \Ppi\TemplaVoilaPlus\Controller\Preview\ImageController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['bullets']  = \Ppi\TemplaVoilaPlus\Controller\Preview\BulletsController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['html']     = \Ppi\TemplaVoilaPlus\Controller\Preview\HtmlController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['menu']     = \Ppi\TemplaVoilaPlus\Controller\Preview\MenuController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['list']     = \Ppi\TemplaVoilaPlus\Controller\Preview\ListController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['search']   = \Ppi\TemplaVoilaPlus\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['login']    = \Ppi\TemplaVoilaPlus\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['shortcut'] = \Ppi\TemplaVoilaPlus\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['div']      = \Ppi\TemplaVoilaPlus\Controller\Preview\NullController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['templavoilaplus_pi1'] = \Ppi\TemplaVoilaPlus\Controller\Preview\NullController::class;

// Register slot for translation mirror url
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Lang\Service\TranslationService::class,
    'postProcessMirrorUrl',
    \Ppi\TemplaVoilaPlus\Slots\TranslationServiceSlot::class,
    'postProcessMirrorUrl'
);

// Register install/update processes
// 8LTS Update
if (version_compare(TYPO3_version, '8.6.0', '>=')) {
    // Add us as first Update process, so we can run before DatabaseRowsUpdateWizard
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] = array_merge(
        [\Ppi\TemplaVoilaPlus\Updates\Typo8Update::class => \Ppi\TemplaVoilaPlus\Updates\Typo3Lts8Update::class],
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']
    );
}
