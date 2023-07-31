<?php

return [
    'web_layout' => [
        'parent' => 'web',
        'position' => 'top',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => 'module/web/layout',
        'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
        'iconIdentifier' => 'extensions-templavoila-pagemodule',
        'extensionName' => 'Templavoilaplus',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
        'controllerActions' => [
            \Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController::class => [
                'show',
            ],
        ],
    ],
    'tools_controlcenter' => [
        'parent' => 'tools',
        'position' => ['top'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/tools/controlcenter',
        'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter.xlf',
        'iconIdentifier' => 'extensions-templavoila-administrationmodule',
        'extensionName' => 'Templavoilaplus',
        'inheritNavigationComponentFromMainModule' => false,
        'navigationComponentId' => '',
        'controllerActions' => [
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenterController::class => [
                'show',
                'debug'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\DataStructuresController::class => [
                'list',
                'info',
                'delete'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\MappingsController::class => [
                'list'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\TemplatesController::class => [
                'list',
                'info'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\UpdateController::class => [
                'info'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\TemplaVoilaPlus8Controller::class => [
                'stepStart',
                'step1',
                'step2',
                'step3',
                'step3NewExtension',
                'step3ExistingExtension',
                'step4',
                'step5',
                'stepFinal'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\ServerMigrationController::class => [
                'stepStart'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV8Controller::class => [
                'stepStart',
                'stepFinal'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV10Controller::class => [
                'stepStart',
                'stepFinal'
            ],
            \Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV11Controller::class => [
                'stepStart',
                'stepFinal'
            ],
        ],
    ],
];
