<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TemplaVoila!',
    'description' => 'Point-and-click, popular and easy template engine for TYPO3. Public free support is provided only through TYPO3 mailing lists! Contact by e-mail for commercial support.',
    'category' => 'misc',
    'version' => '2.1.0-dev',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => 'uploads/tx_templavoila/',
    'clearcacheonload' => 1,
    'author' => 'Benjamin Mack',
    'author_email' => 'benni@typo3.org',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.0.99',
            'typo3' => '7.6.0-7.6.99',
            'static_info_tables' => '',
        ],
        'conflicts' => [
            'kb_tv_clipboard' => '-0.1.0',
            'templavoila_cw' => '-0.1.0',
            'eu_tradvoila' => '-0.0.2',
            'me_templavoilalayout' => '',
            'me_templavoilalayout2' => '',
        ],
        'suggests' => [],
    ),
];
