<?php
defined('TYPO3_MODE') or die();
// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF);

// Register language aware flex form handling in FormEngine
// Register render elements
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361297] = [
    'nodeName' => 'flex',
    'priority' => 40,
    'class' => \Extension\Templavoila\Form\Container\FlexFormEntryContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361298] = [
    'nodeName' => 'flexFormNoTabsContainer',
    'priority' => 40,
    'class' => \Extension\Templavoila\Form\Container\FlexFormNoTabsContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361299] = [
    'nodeName' => 'flexFormTabsContainer',
    'priority' => 40,
    'class' => \Extension\Templavoila\Form\Container\FlexFormTabsContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361300] = [
    'nodeName' => 'flexFormElementContainer',
    'priority' => 40,
    'class' => \Extension\Templavoila\Form\Container\FlexFormElementContainer::class,
];
// Unregister stock TcaFlex* data provider and substitute with own data provider at the same position
\Extension\Templavoila\Utility\FormEngineUtility::replaceInFormDataGroups(
    [
        \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class
            => \Extension\Templavoila\Form\FormDataProvider\TcaFlexProcess::class,
        \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class
            => \Extension\Templavoila\Form\FormDataProvider\TcaFlexFetch::class,
        \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class
            => \Extension\Templavoila\Form\FormDataProvider\TcaFlexPrepare::class,
    ]
);

// Register "XCLASS" of FlexFormTools for language parsing
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
    = \Extension\Templavoila\Configuration\FlexForm\FlexFormTools::class;
// Language diff updating in flex
$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] = true;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\View\ContentCreationPagePositionMap::class]['className']
    = \Extension\Templavoila\Tree\View\ContentCreationPagePositionMap::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\View\PagePositionMap::class]['className']
    = \Extension\Templavoila\Xclass\PagePositionMap::class;

// Adding the two plugins TypoScript:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('templavoila', 'setup', '
# Setting templavoila plugin TypoScript
plugin.tx_templavoila_pi1 = USER
plugin.tx_templavoila_pi1.userFunc = Extension\Templavoila\Controller\FrontendController->main
plugin.tx_templavoila_pi1.disableExplosivePreview = 1

tt_content.templavoila_pi1 = COA
tt_content.templavoila_pi1 {
' . ($_EXTCONF['enable.']['renderFCEHeader'] ? '
    10 < lib.stdheader
    ' : '') . '
    20 < plugin.tx_templavoila_pi1
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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
    'Extension\\Templavoila\\Module\\Mod1\\Ajax::moveRecord',
    \Extension\Templavoila\Module\Mod1\Ajax::class . '->moveRecord'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
    'Extension\\Templavoila\\Module\\Mod1\\Ajax::unlinkRecord',
    \Extension\Templavoila\Module\Mod1\Ajax::class . '->unlinkRecord'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
    'Extension\\Templavoila\\Module\\Cm1\\Ajax::getDisplayFileContent',
    \Extension\Templavoila\Module\Cm1\Ajax::class . '->getDisplayFileContent'
);
