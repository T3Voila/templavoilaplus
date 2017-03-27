<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoilà Plus',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3. Replacement for old TemplaVoila.',
    'category' => 'misc',
    'version' => '7.0.3',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoilaplus/',
    'clearcacheonload' => 1,
    'author' => 'Alexander Opitz',
    'author_email' => 'opitz.alexander@pluspol-interactive.de',
    'author_company' => 'PLUSPOL interactive,,',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.1.99',
            'typo3' => '7.6.0-8.6.99',
        ],
        'conflicts' => [
            'kb_tv_clipboard' => '-0.1.0',
            'templavoila_cw' => '-0.1.0',
            'eu_tradvoila' => '-0.0.2',
            'me_templavoilalayout' => '',
            'me_templavoilalayout2' => '',
            'templavoila' => '-3.0.0',
        ],
        'suggests' => [],
    ],
];
