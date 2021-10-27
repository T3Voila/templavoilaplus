<?php

defined('TYPO3_MODE') or die();
// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);

// Register "XCLASS" of FlexFormTools for language parsing
// Done also in TableConfigurationPostProcessingHook!
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
    = \Tvp\TemplaVoilaPlus\Configuration\FlexForm\FlexFormTools8::class;

// Language diff updating in flex
$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] = true;

$renderFceHeader = '';
if ($_EXTCONF['enable.']['renderFCEHeader']) {
    $renderFceHeader = '
    10 < lib.stdheader';
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fluid_styled_content')) {
        $renderFceHeader = '
        10 =< lib.fluidContent
        10.templateName = Header';
    }
}

// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('templavoilaplus', 'setup', '
# Setting templavoila plugin TypoScript
plugin.tx_templavoilaplus_pi1 = USER
plugin.tx_templavoilaplus_pi1.userFunc = Tvp\TemplaVoilaPlus\Controller\Frontend\FrontendController->renderContent
plugin.tx_templavoilaplus_pi1.disableExplosivePreview = 1

tt_content.templavoilaplus_pi1 = COA
tt_content.templavoilaplus_pi1 {
    ' . $renderFceHeader . '
    20 < plugin.tx_templavoilaplus_pi1
}

tt_content.menu.20.3 = USER
tt_content.menu.20.3.userFunc = Tvp\TemplaVoilaPlus\Controller\SectionIndexController->mainAction
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
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_templavoilaplus_map,tx_templavoilaplus_next_map';

// Register our classes at a the hooks:
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['templavoilaplus']
    = \Tvp\TemplaVoilaPlus\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['templavoilaplus']
    = \Tvp\TemplaVoilaPlus\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['templavoilaplus']
    = \Tvp\TemplaVoilaPlus\Service\DataHandling\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['recordEditAccessInternals']['templavoilaplus']
    = \Tvp\TemplaVoilaPlus\Service\UserFunc\Access::class . '->recordEditAccessInternals';
// Hook after ext_tables run to do all FormHandler registering things
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing']['templavoilaplus']
    = \Tvp\TemplaVoilaPlus\Hooks\TableConfigurationPostProcessingHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess']['templavoilaplus']
    = \Tvp\TemplaVoilaPlus\Hooks\BackendControllerHook::class . '->addInlineSettings';


$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['indexFilter']['tx_templavoilaplus_usedCE']
    = array(\Tvp\TemplaVoilaPlus\Service\UserFunc\UsedContentElement::class);

// Register Preview Classes for Page Module
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['menu']     = \Tvp\TemplaVoilaPlus\Controller\Preview\MenuController::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoilaplus']['mod1']['renderPreviewContent']['templavoilaplus_pi1'] = \Tvp\TemplaVoilaPlus\Controller\Preview\NullController::class;

// Register slot for translation mirror url
/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Lang\Service\TranslationService::class,
    'postProcessMirrorUrl',
    \Tvp\TemplaVoilaPlus\Slots\TranslationServiceSlot::class,
    'postProcessMirrorUrl'
);

// Register install/update processes
// 8LTS Update
// Add us as first Update process, so we can run before DatabaseRowsUpdateWizard
if (version_compare(TYPO3_version, '9.5.0', '>=')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] = array_merge(
        [\Tvp\TemplaVoilaPlus\Updates\Typo3Lts9Update::class => \Tvp\TemplaVoilaPlus\Updates\Typo3Lts9Update::class],
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']
    );
} else {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] = array_merge(
        [\Tvp\TemplaVoilaPlus\Updates\Typo3Lts8Update::class => \Tvp\TemplaVoilaPlus\Updates\Typo3Lts8Update::class],
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']
    );
}


// Since TV+ 8.0.0
\Tvp\TemplaVoilaPlus\Utility\ExtensionUtility::registerExtension('templavoilaplus');

if (TYPO3_MODE === 'BE') {
    // Hook to enrich tt_content form flex element with finisher settings and form list drop down
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing'][
        \Tvp\TemplaVoilaPlus\Configuration\FlexForm\DataStructureIdentifierHook::class
    ] = \Tvp\TemplaVoilaPlus\Configuration\FlexForm\DataStructureIdentifierHook::class;
}
