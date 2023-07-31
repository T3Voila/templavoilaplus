<?php

defined('TYPO3') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][\Tvp\TemplaVoilaPlus\Hooks\WizardItems::class]
    = \Tvp\TemplaVoilaPlus\Hooks\WizardItems::class;

$GLOBALS['TBE_STYLES']['skins']['templavoilaplus']['stylesheetDirectories'][]
    = 'EXT:templavoilaplus/Resources/Public/StyleSheet/Skin';

if (version_compare((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion(), '12', '<')) {
    $navigationComponentId = 'TYPO3/CMS/Backend/PageTree/PageTreeElement';

    $moduleName = 'TemplaVoilaPlus';

    // Adding backend modules:
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $moduleName,
        'web',
        'Layout',
        'top',
        [
            \Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController::class => 'show',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/PageModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
            'navigationComponentId' => $navigationComponentId,
            // 'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],
        ]
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $moduleName,
        'tools',
        'ControlCenter',
        'bottom',
        [
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenterController::class => 'show,debug',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\DataStructuresController::class => 'list,info,delete',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\MappingsController::class => 'list',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\TemplatesController::class => 'list,info',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\UpdateController::class => 'info',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\TemplaVoilaPlus8Controller::class => 'stepStart,step1,step2,step3,step3NewExtension,step3ExistingExtension,step4,step5,stepFinal',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\ServerMigrationController::class => 'stepStart',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV8Controller::class => 'stepStart,stepFinal',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV10Controller::class => 'stepStart,stepFinal',
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV11Controller::class => 'stepStart,stepFinal',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:templavoilaplus/Resources/Public/Icons/AdministrationModuleIcon.svg',
            'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter.xlf',
            'navigationComponentId' => '',
            'inheritNavigationComponentFromMainModule' => false
        ]
    );

    if (isset($GLOBALS['TBE_MODULES']['web'])) {
        // <=12LTS only
        $GLOBALS['TBE_MODULES']['web'] = str_replace(',,', ',', str_replace('layout', '', $GLOBALS['TBE_MODULES']['web']));
        unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
    }

    // complex condition to make sure the icons are available during frontend editing...
    $iconsBitmap = [
        'paste' => 'EXT:templavoilaplus/Resources/Public/Icon/clip_pasteafter.gif',
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
        'unlink' => 'EXT:templavoilaplus/Resources/Public/Icons/Unlink.svg',
        'pagemodule' => 'EXT:templavoilaplus/Resources/Public/Icons/PageModuleIcon.svg',
        'administrationmodule' => 'EXT:templavoilaplus/Resources/Public/Icons/AdministrationModuleIcon.svg'
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
