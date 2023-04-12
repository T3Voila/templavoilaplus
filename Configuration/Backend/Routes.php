<?php
$targetWizardLink = \TYPO3\CMS\Backend\Controller\LinkBrowserController::class . '::mainAction';
if (version_compare(TYPO3_version, '11.0.0', '<')) {
    $targetWizardLink = \Tvp\TemplaVoilaPlus\Controller\Backend\LinkBrowserController::class . '::mainAction';
}

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
<<<<<<< HEAD

=======
	
>>>>>>> 885d9325 ([BUGFIX] fixes #502)
    // Overwrite from core
    // Register link wizard
    'wizard_link' => [
        'path' => '/wizard/link/browse',
        'target' => $targetWizardLink
    ],
];
