<?php
use Ppi\TemplaVoilaPlus\Controller;

/**
 * Definitions for routes provided by EXT:templavoilaplus
 * Contains all "ajax" routes for entry points
 */
return [
    'templavoilaplus_record_move' => [
        'path' => '/templavoilaplus/record/move',
        'access' => 'user,group',
        'target' => \Ppi\TemplaVoilaPlus\Module\Mod1\Ajax::class . '::moveRecord',
    ],
    'templavoilaplus_record_unlink' => [
        'path' => '/templavoilaplus/record/unlink',
        'access' => 'user,group',
        'target' => \Ppi\TemplaVoilaPlus\Module\Mod1\Ajax::class . '::unlinkRecord',
    ],
    'templavoilaplus_displayFileContent' => [
        'path' => '/templavoilaplus/fileContent',
        'access' => 'user,group',
        'target' => \Ppi\TemplaVoilaPlus\Module\Cm1\Ajax::class . '::getDisplayFileContent',
    ],

    // Overwrite from core
    // Fetch the tree-structured data from a record for the tree selection
    'record_tree_data' => [
        'path' => '/record/tree/fetchData',
        'target' => Ppi\TemplaVoilaPlus\Form\Controller\FormSelectTreeAjaxController::class . '::fetchDataAction'
    ],
    // Add a flex form section container
    'record_flex_container_add' => [
        'path' => '/record/flex/containeradd',
        'target' => Ppi\TemplaVoilaPlus\Form\Controller\FormFlexAjaxController::class . '::containerAdd',
    ],
];
