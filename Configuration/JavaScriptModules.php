<?php

return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@templavoilaplus/' => [
            'path' => 'EXT:templavoilaplus/Resources/Public/JavaScript/',
            // Exclude files of the following folders from being import-mapped
            'exclude' => [
                'EXT:templavoilaplus/Resources/Public/JavaScript/Contrib/',
                'EXT:templavoilaplus/Resources/Public/JavaScript/Overrides/',
            ],
        ],
        // Overriding a file from another package
        '@typo3/backend/form-engine-link-browser-adapter.js' => 'EXT:templavoilaplus/Resources/Public/JavaScript/FormEngineLinkBrowserAdapter.js',
    ],
];
