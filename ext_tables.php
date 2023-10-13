<?php

defined('TYPO3') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][\Tvp\TemplaVoilaPlus\Hooks\WizardItems::class]
    = \Tvp\TemplaVoilaPlus\Hooks\WizardItems::class;

$GLOBALS['TBE_STYLES']['skins']['templavoilaplus']['stylesheetDirectories'][]
    = 'EXT:templavoilaplus/Resources/Public/StyleSheet/Skin';
