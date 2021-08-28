<?php

use Tvp\TemplaVoilaPlus\Controller;

/**
 * Definitions for routes provided by EXT:templavoilaplus
 * Contains all "regular" routes for entry points
 */

return [
    'templavoilaplus_modalhelper_close' => [
        'path' => '/templavoilaplus/modalhelper/close',
        'access' => 'user,group',
        'target' => \Tvp\TemplaVoilaPlus\Controller\Backend\ModalHelperController::class . '::closeAction',
    ],
];
