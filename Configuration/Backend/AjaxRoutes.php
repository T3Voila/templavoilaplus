<?php

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
    'templavoilaplus_contentElement_create' => [
        'path' => '/templavoilaplus/contentElement/create',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElements::class . '::create',
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
    'templavoilaplus_contentElement_makelocal' => [
        'path' => '/templavoilaplus/contentElement/makelocalcopy',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElements::class . '::makelocalcopy',
    ],
    'templavoilaplus_contentElementWizard' => [
        'path' => '/templavoilaplus/contentElementWizard',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\ContentElementWizard::class . '::wizardAction',
    ],
    'templavoilaplus_clipboard_load' => [
        'path' => '/templavoilaplus/clipboard/load',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Clipboard::class . '::load',
    ],
    'templavoilaplus_clipboard_action' => [
        'path' => '/templavoilaplus/clipboard/action',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Clipboard::class . '::action',
    ],
    'templavoilaplus_clipboard_release' => [
        'path' => '/templavoilaplus/clipboard/release',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Clipboard::class . '::release',
    ],
    'templavoilaplus_clipboard_add' => [
        'path' => '/templavoilaplus/clipboard/add',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Clipboard::class . '::add',
    ],
    'templavoilaplus_trash_load' => [
        'path' => '/templavoilaplus/trash/load',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Trash::class . '::load',
    ],
    'templavoilaplus_trash_link' => [
        'path' => '/templavoilaplus/trash/link',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Trash::class . '::link',
    ],
    'templavoilaplus_trash_unlink' => [
        'path' => '/templavoilaplus/trash/unlink',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Trash::class . '::unlink',
    ],
    'templavoilaplus_trash_delete' => [
        'path' => '/templavoilaplus/trash/delete',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Trash::class . '::delete',
    ],
    'templavoilaplus_record_switch_visibility' => [
        'path' => '/templavoilaplus/record/switchvisibility',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Record::class . '::switchVisibility',
    ],
    'templavoilaplus_record_edit' => [
        'path' => '/templavoilaplus/record/editform',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Record::class . '::editform',
    ],
    'templavoilaplus_record_localize' => [
        'path' => '/templavoilaplus/record/localize',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\Record::class . '::localize',
    ],
    'templavoilaplus_usersettings_enableDarkMode' => [
        'path' => '/templavoilaplus/usersettings/enableDarkMode',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\UserSettings::class . '::enableDarkMode',
    ],
    'templavoilaplus_usersettings_setClipboardMode' => [
        'path' => '/templavoilaplus/usersettings/setClipboardMode',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\Ajax\UserSettings::class . '::setClipboardMode',
    ],
];
