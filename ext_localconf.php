<?php

defined('TYPO3') or die();

// Language diff updating in flex
$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] = true;

$renderFceHeader = '';

$backendConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Tvp\TemplaVoilaPlus\Service\ConfigurationService::class)->getExtensionConfig();

if (isset($backendConfiguration['enable']['renderFCEHeader']) && $backendConfiguration['enable']['renderFCEHeader']) {
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

// Since TV+ 8.0.0
\Tvp\TemplaVoilaPlus\Utility\ExtensionUtility::registerExtension('templavoilaplus');

// Hook to enrich tt_content form flex element with finisher settings and form list drop down
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing'][
    \Tvp\TemplaVoilaPlus\Configuration\FlexForm\DataStructureIdentifierHook::class
] = \Tvp\TemplaVoilaPlus\Configuration\FlexForm\DataStructureIdentifierHook::class;

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['templavoilaplus'] = 'EXT:templavoilaplus/Resources/Public/StyleSheet/Skin';
