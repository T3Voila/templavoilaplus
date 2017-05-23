<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà Plus',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3. Replacement for old TemplaVoila.',
    'category' => 'misc',
    'version' => '7.1.0',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoilaplus/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz',
    'author_email' => 'opitz@pluspol-interactive.de',
    'author_company' => 'PLUSPOL interactive,,',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.1.99',
            'typo3' => '7.6.0-8.7.99',
        ],
        'conflicts' => [
            'templavoila' => '-3.0.0',
        ],
        'suggests' => [],
    ],
];
