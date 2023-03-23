<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà! Plus',
    'description' => 'Building kit for custom pages and content elements with individual fields, containers and backend layouts. Supporting drag\'n\'drop and multiple references.',
    'category' => 'misc',
    'version' => '8.1.3',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoilaplus/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz',
    'author_email' => 'alexander.opitz@davitec.de',
    'author_company' => 'Davitec GmbH',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-8.1.99',
            'typo3' => '8.7.0-11.5.99',
            'install' => '8.7.0-11.5.99',
        ],
    ],
];
