<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders']['templavoilaplus']
        = \Ppi\TemplaVoilaPlus\ContextMenu\ItemProvider::class;

    $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'][\Ppi\TemplaVoilaPlus\Hooks\WizardItems::class]
        = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoilaplus') . 'Classes/Hooks/WizardItems.php';

    $navigationComponentId = 'TYPO3/CMS/Backend/PageTree/PageTreeElement';
    if (version_compare(TYPO3_version, '9.0.0', '<')) {
        $navigationComponentId = 'typo3-pagetree';
    }
    // Adding backend modules:
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Ppi.TemplaVoilaPlus',
        'web',
        'Layout',
        'top',
        [
            'Backend\PageLayout' => 'show',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/PageModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
            'navigationComponentId' => $navigationComponentId,
//             'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Ppi.TemplaVoilaPlus',
        'web',
        'ControlCenter',
        'bottom',
        [
            'Backend\ControlCenter' => 'show,debug',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/AdministrationModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/BackendControlCenter.xlf',
            'navigationComponentId' => '',
            'inheritNavigationComponentFromMainModule' => false
        ]
    );

    // @TODO: Old Modules
    // Adding backend modules:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'txtemplavoilaplusLayout',
        'top',
        '',
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/PageModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/BackendLayout.xlf',
            'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],

            'name' => 'web_txtemplavoilaplusLayout',
            'extensionName' => 'templavoilaplus',
            'navigationComponentId' => $navigationComponentId,
            'routeTarget' => \Ppi\TemplaVoilaPlus\Controller\BackendLayoutController::class . '::mainAction',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'txtemplavoilaplusCenter',
        '',
        '',
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/AdministrationModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/BackendControlCenter.xlf',
            'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],

            'name' => 'web_txtemplavoilaplusCenter',
            'extensionName' => 'templavoilaplus',
            'routeTarget' => \Ppi\TemplaVoilaPlus\Controller\BackendControlCenterController::class . '::mainAction',
        ]
    );

    $oldPageModule = false;
    if (version_compare(TYPO3_version, '9.0.0', '>=')) {
        $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['templavoilaplus'];
        $oldPageModule = (bool) $_EXTCONF['enable']['oldPageModule'];
    } else {
        $_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
        $oldPageModule = (bool) $_EXTCONF['enable.']['oldPageModule'];
    }

    // Remove default Page module (layout) manually if wanted:
    if (!$oldPageModule) {
        $tmp = $GLOBALS['TBE_MODULES']['web'];
        $GLOBALS['TBE_MODULES']['web'] = str_replace(',,', ',', str_replace('layout', '', $tmp));
        unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
    }

    // Registering CSH:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'be_groups',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_begr.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'pages',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_pages.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tt_content',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_ttc.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tx_templavoilaplus_datastructure',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_ds.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tx_templavoilaplus_tmplobj',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_to.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'xMOD_tx_templavoilaplus',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_module.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'xEXT_templavoila',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_intro.xlf'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        '_MOD_web_txtemplavoilaplusLayout',
        'EXT:templavoilaplus/Resources/Private/Language/locallang_csh_pm.xlf'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_func',
        'Ppi\\TemplaVoilaPlus\Controller\\ReferenceElementWizardController',
        NULL,
        'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:wiz_refElements'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_func',
        'Ppi\\TemplaVoilaPlus\Controller\\RenameFieldInPageFlexWizardController',
        NULL,
        'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:wiz_renameFieldsInPage'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_crpages/locallang_csh.xlf');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templavoilaplus_datastructure');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templavoilaplus_tmplobj');

// complex condition to make sure the icons are available during frontend editing...
if (
    TYPO3_MODE === 'BE' ||
    (
        TYPO3_MODE === 'FE'
        && isset($GLOBALS['BE_USER'])
        && method_exists($GLOBALS['BE_USER'], 'isFrontendEditingActive')
        && $GLOBALS['BE_USER']->isFrontendEditingActive()
    )
) {
    $iconsBitmap = [
        'paste' =>  'EXT:templavoilaplus/Resources/Public/Icon/clip_pasteafter.gif',
        'pasteSubRef' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_pastesubref.gif',
        'makelocalcopy' => 'EXT:templavoilaplus/Resources/Public/Icon/makelocalcopy.gif',
        'clip_ref' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_ref.gif',
        'clip_ref-release' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_ref_h.gif',
        'htmlvalidate' => 'EXT:templavoilaplus/Resources/Public/Icon/html_go.png',
        'type-fce' => 'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png',
    ];
    $iconsSvg = [
        'template-default' => 'EXT:templavoilaplus/Resources/Public/Icons/TemplateDefault.svg',
        'datastructure-default' => 'EXT:templavoilaplus/Resources/Public/Icons/DataStructureDefault.svg',
        'folder' => 'EXT:templavoilaplus/Resources/Public/Icons/Folder.svg',
        'menu-item' => 'EXT:templavoilaplus/Resources/Public/Icons/MenuItem.svg',
    ];
    $iconsFontAwesome = [
        'unlink' => 'unlink',
    ];


    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($iconsBitmap as $identifier => $file) {
        $iconRegistry->registerIcon(
            'extensions-templavoila-' . $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => $file]
        );
    }

    foreach ($iconsSvg as $identifier => $file) {
        $iconRegistry->registerIcon(
            'extensions-templavoila-' . $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => $file]
        );
    }

    foreach ($iconsFontAwesome as $identifier => $name) {
        $iconRegistry->registerIcon(
            'extensions-templavoila-' . $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
            ['name' => $name]
        );
    }
}

\Ppi\TemplaVoilaPlus\Utility\ExtensionUtility::handleExtension('templavoilaplus');
