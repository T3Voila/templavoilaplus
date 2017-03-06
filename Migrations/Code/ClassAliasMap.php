<?php

return [
    'tx_templavoilaplus_pi1' => \Ppi\TemplaVoilaPlus\Controller\FrontendController::class,
    'tx_templavoilaplus_module1' => \Ppi\TemplaVoilaPlus\Controller\BackendLayoutController::class,
    \Extension\Templavoila\Controller\FrontendController::class => \Ppi\TemplaVoilaPlus\Controller\FrontendController::class,
    \Extension\Templavoila\Controller\BackendLayoutController::class => \Ppi\TemplaVoilaPlus\Controller\BackendLayoutController::class,
];
