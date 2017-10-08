<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

    if (version_compare(TYPO3_version, '8.6.0', '>=')) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][$_EXTKEY]
            = \Ppi\TemplaVoilaPlus\ContextMenu\ItemProvider::class;
    } else {
        // Adding click menu item:
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
            'name' => \Ppi\TemplaVoilaPlus\Service\ClickMenu\MainClickMenu::class
        ];
    }

    $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'][\Ppi\TemplaVoilaPlus\Hooks\WizardItems::class]
        = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Hooks/WizardItems.php';

    // Adding backend modules:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
        'web',
        'txtemplavoilaplusLayout',
        'top',
        '',
        [
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icon/Modules/PageModuleIcon.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/BackendLayout.xlf',
            'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],

            'name' => 'web_txtemplavoilaplusLayout',
            'extensionName' => 'templavoilaplus',
            'navigationComponentId' => 'typo3-pagetree',
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
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icon/Modules/AdministrationModuleIcon.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/BackendControlCenter.xlf',
            'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],

            'name' => 'web_txtemplavoilaplusCenter',
            'extensionName' => 'templavoilaplus',
            'routeTarget' => \Ppi\TemplaVoilaPlus\Controller\BackendControlCenterController::class . '::mainAction',
        ]
    );

    $_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
    // Remove default Page module (layout) manually if wanted:
    if (!$_EXTCONF['enable.']['oldPageModule']) {
        $tmp = $GLOBALS['TBE_MODULES']['web'];
        $GLOBALS['TBE_MODULES']['web'] = str_replace(',,', ',', str_replace('layout', '', $tmp));
        unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
    }

    $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tv+'] = 'extensions-templavoila-templavoila-logo';

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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.CType_pi1',
        'templavoilaplus_pi1',
        'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png'
    ],
    'CType'
);

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
        'unlink' => 'EXT:templavoilaplus/Resources/Public/Icon/unlink.png',
        'htmlvalidate' => 'EXT:templavoilaplus/Resources/Public/Icon/html_go.png',
        'type-fce' => 'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png',
        'templavoila-logo' => 'EXT:templavoilaplus/Resources/Public/Image/templavoila-logo.png',
    ];
    $iconsSvg = [
        'default-preview-icon' => 'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_default.svg',
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
}
