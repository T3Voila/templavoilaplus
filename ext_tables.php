<?php

defined('TYPO3') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][\Tvp\TemplaVoilaPlus\Hooks\WizardItems::class]
    = \Tvp\TemplaVoilaPlus\Hooks\WizardItems::class;

$GLOBALS['TBE_STYLES']['skins']['templavoilaplus']['stylesheetDirectories'][]
    = 'EXT:templavoilaplus/Resources/Public/StyleSheet/Skin';

$navigationComponentId = 'TYPO3/CMS/Backend/PageTree/PageTreeElement';

$moduleName = 'TemplaVoilaPlus';
$typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
if (version_compare($typo3Version->getVersion(), '12.0.0', '<=')) {
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
    //             'configureModuleFunction' => [\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class, 'configureModule'],
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

    $GLOBALS['TBE_MODULES']['web'] = str_replace(',,', ',', str_replace('layout', '', $GLOBALS['TBE_MODULES']['web']));
    unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
}
