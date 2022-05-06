<?php

$targetRecordTreeData = Tvp\TemplaVoilaPlus\Form\Controller\FormSelectTreeAjaxController::class . '::fetchDataAction';
$targetRecordFlexContainerAdd = Tvp\TemplaVoilaPlus\Form\Controller\FormFlexAjaxController::class . '::containerAdd';
if (version_compare(TYPO3_version, '9.0.0', '>=')) {
    $targetRecordTreeData = Tvp\TemplaVoilaPlus\Form\Controller\FormSelectTreeAjaxController9::class . '::fetchDataAction';
    $targetRecordFlexContainerAdd = Tvp\TemplaVoilaPlus\Form\Controller\FormFlexAjaxController9::class . '::containerAdd';
}

/**
 * Definitions for routes provided by EXT:templavoilaplus
 * Contains all "ajax" routes for entry points
 */
return [
    'templavoilaplus_displayFileContent' => [
        'path' => '/templavoilaplus/fileContent',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Module\Cm1\Ajax::class . '::getDisplayFileContent',
    ],
    'templavoilaplus_contentElement_insert' => [
        'path' => '/templavoilaplus/contentElement/insert',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElements::class . '::insert',
    ],
    'templavoilaplus_contentElement_reload' => [
        'path' => '/templavoilaplus/contentElement/reload',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElements::class . '::reload',
    ],
    'templavoilaplus_contentElement_move' => [
        'path' => '/templavoilaplus/contentElement/move',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElements::class . '::move',
    ],
    'templavoilaplus_contentElement_remove' => [
        'path' => '/templavoilaplus/contentElement/remove',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElements::class . '::remove',
    ],
    'templavoilaplus_contentElementWizard' => [
        'path' => '/templavoilaplus/contentElementWizard',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElementWizard::class . '::wizardAction',
    ],
    'templavoilaplus_record_edit' => [
        'path' => '/templavoilaplus/record/editform',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Record::class . '::editform',
    ],
    'templavoilaplus_usersettings_enableDarkMode' => [
        'path' => '/templavoilaplus/usersettings/enableDarkMode',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\UserSettings::class . '::enableDarkMode',
    ],

    // Overwrite from core
    // Fetch the tree-structured data from a record for the tree selection
    'record_tree_data' => [
        'path' => '/record/tree/fetchData',
        'target' => $targetRecordTreeData,
    ],
    // Add a flex form section container
    'record_flex_container_add' => [
        'path' => '/record/flex/containeradd',
        'target' => $targetRecordFlexContainerAdd,
    ],
];
