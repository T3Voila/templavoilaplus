<?php

use Tvp\TemplaVoilaPlus\Controller\Backend\PageLayoutController;

/**
 * Definitions for modules provided by EXT:backend
 */
return [
    'web_layout' => [
    ],
    'web_TemplaVoilaPlusLayout' => [
        'parent' => 'web',
        'position' => ['top'],
        'access' => 'user,group',
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
    ],
];
