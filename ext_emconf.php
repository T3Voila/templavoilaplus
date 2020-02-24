<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà! Plus',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3. Replacement for old TemplaVoilà!.',
    'category' => 'misc',
    'version' => '7.3.3',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoilaplus/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz',
    'author_email' => 'opitz@extrameile-gehen.de',
    'author_company' => 'Extrameile GmbH',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.3.99',
            'typo3' => '7.6.0-9.5.99',
            'install' => '7.6.0-9.5.99',
        ],
        'conflicts' => [
            'templavoila' => '',
        ],
        'suggests' => [
            'typo3db_legacy' => '1.1.1-1.99.99',
        ],
    ],
];
