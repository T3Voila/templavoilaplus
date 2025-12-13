<?php

/**
 * Definitions for modules provided by EXT:backend
 */

use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenterController;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\DataStructuresController;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\MappingsController;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\TemplatesController;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\UpdateController;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV10Controller;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV11Controller;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV12Controller;
use Tvp\TemplaVoilaPlus\Controller\Backend\ControlCenter\Update\DataStructureV13Controller;

return [
    'web_layout' => [
    ],
    'web_TemplaVoilaPlusLayout' => [
        'parent' => 'web',
        'position' => ['top'],
        'access' => 'user',
        'path' => '/module/web/templavoilaplus/',
        'iconIdentifier' => 'extensions-templavoila-page-module',
        'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
        'extensionName' => 'TemplaVoilaPlus',
        'controllerActions' => [
            PageLayoutController::class => [
                'show',
            ],
        ],
        'moduleData' => [
            'function' => 1,
            'language' => 0,
            'showHidden' => true,
        ],
        'aliases' => ['web_layout'],
    ],
    'web_TemplaVoilaPlusControlCenter' => [
        'parent' => 'tools',
        'position' => ['*'],
        'access' => 'user',
        'path' => '/module/web/templavoilaplus/admin',
        'iconIdentifier' => 'extensions-templavoila-admin-module',
        'labels' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/ControlCenter.xlf',
        'extensionName' => 'TemplaVoilaPlus',
        'controllerActions' => [
            ControlCenterController::class => [
                'show', 'debug'
            ],
            DataStructuresController::class => [
                'list', 'info', 'delete'
            ],
            MappingsController::class => [
                'list'
            ],
            TemplatesController::class => [
                'list', 'info'
            ],
            UpdateController::class => [
                'info'
            ],
            DataStructureV10Controller::class => [
                'stepStart', 'stepFinal'
            ],
            DataStructureV11Controller::class => [
                'stepStart', 'stepFinal'
            ],
            DataStructureV12Controller::class => [
                'stepStart', 'stepFinal'
            ],
            DataStructureV13Controller::class => [
                'stepStart', 'stepFinal'
            ],
        ],
        'moduleData' => [
            'function' => 1,
            'language' => 0,
            'showHidden' => true,
        ],
    ],
];
