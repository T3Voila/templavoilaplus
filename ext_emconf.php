<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà! Plus',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3. Replacement for old TemplaVoilà!.',
    'category' => 'misc',
    'version' => '8.0.0-alpha.4',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoilaplus/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz',
    'author_email' => 'opitz@extrameile-gehen.de',
    'author_company' => 'Extrameile GmbH',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.4.99',
            'typo3' => '8.7.0-11.0.99',
            'install' => '8.7.0-11.0.99',
        ],
        'conflicts' => [
            'templavoila' => '',
        ],
    ],
];
