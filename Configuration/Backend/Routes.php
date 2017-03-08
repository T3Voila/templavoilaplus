<?php
use Ppi\TemplaVoilaPlus\Controller;

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
    'templavoilaplus_mapping' => [
        'path' => '/templavoilaplus/mapping',
        'access' => 'user,group',
        'target' => Controller\BackendTemplateMappingController::class . '::mainAction',
    ],
    'templavoilaplus_template_disply' => [
        'path' => '/templavoilaplus/template/display',
        'access' => 'user,group',
        'target' => Controller\BackendTemplateDisplayController::class . '::mainAction',
    ],
    'templavoilaplus_new_site_wizard' => [
        'path' => '/templavoilaplus/new_site_wizard',
        'access' => 'user,group',
        'target' => Controller\BackendNewSiteWizardController::class . '::mainAction',
    ],
    'templavoilaplus_flexform_cleaner' => [
        'path' => '/templavoilaplus/flexform_cleaner',
        'access' => 'admin',
        'target' => Controller\BackendFlexformCleanerController::class . '::mainAction',
    ],
];
