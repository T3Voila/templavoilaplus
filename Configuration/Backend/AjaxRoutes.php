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
];
