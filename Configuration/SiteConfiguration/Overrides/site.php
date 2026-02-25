<?php

$GLOBALS['SiteConfiguration']['site']['columns']['templavoilaplus_allowed_places'] = [
    'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/SiteConfiguration.xlf:site.templavoilaplus_allowed_places.label',
    'description' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/SiteConfiguration.xlf:site.templavoilaplus_allowed_places.description',
    'config' => [
        'type' => 'select',
        // Using selectCheckBox as it have bigger and also working icons besides all other options.
        'renderType' => 'selectCheckBox',
        'appearance' => [
            'expandAll' => true,
        ],
        'itemsProcFunc' => \Tvp\TemplaVoilaPlus\Configuration\SiteConfiguration::class . '->getThemeItems',
    ],
];

// And add it to showitem
$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',--div--;TV+, templavoilaplus_allowed_places';
