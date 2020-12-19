<?php
use Tvp\TemplaVoilaPlus\Controller;

/**
 * Definitions for routes provided by EXT:templavoilaplus
 * Contains all "regular" routes for entry points
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
