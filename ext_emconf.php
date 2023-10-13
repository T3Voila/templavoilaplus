<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà! Plus',
    'description' => 'Building kit for custom  pages and content elements with individual fields, containers and backend layouts. Supporting drag\'n\'drop and multiple references.',
    'category' => 'misc',
    'version' => '12.0.0-alpha-1',
    'state' => 'alpha',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz',
    'author_email' => 'alexander.opitz@davitec.de',
    'author_company' => 'Davitec GmbH',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.2.99',
            'typo3' => '12.4.0-12.4.99',
            'install' => '12.4.0-12.4.99',
        ],
    ],
];
