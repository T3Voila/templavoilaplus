<?php
use Extension\Templavoila\Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all "regular" routes for entry points
 *
 * Please note that this setup is preliminary until all core use-cases are set up here.
 * Especially some more properties regarding modules will be added until TYPO3 CMS 7 LTS, and might change.
 *
 * Currently the "access" property is only used so no token creation + validation is made,
 * but will be extended further.
 */
return [
    'templavoila_mapping' => [
        'path' => '/templavoila/mapping',
        'access' => 'user,group',
        'target' => Controller\BackendTemplateMappingController::class . '::mainAction',
    ],
];
