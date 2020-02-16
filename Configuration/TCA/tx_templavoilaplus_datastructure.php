<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure',
        'label' => 'title',
        'label_userFunc' => 'Ppi\TemplaVoilaPlus\Service\UserFunc\Label->getLabel',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => 'EXT:templavoilaplus/Resources/Public/Icons/DataStructureDefault.svg',
        'selicon_field' => 'previewicon',
        'selicon_field_path' => 'uploads/tx_templavoilaplus',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'shadowColumnsForNewPlaceholders' => 'scope,title',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,dataprot',
        'maxDBListItems' => 60,
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure.title',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'required,trim',
            ]
        ],
        'dataprot' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure.dataprot',
            'config' => [
                'type' => 'text',
                'wrap' => 'OFF',
                'cols' => '48',
                'rows' => '20',
            ],
            'defaultExtras' => 'fixed-font:enable-tab'
        ],
        'scope' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure.scope',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure.scope.I.0', 0],
                    ['LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure.scope.I.1', 1],
                    ['LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_datastructure.scope.I.2', 2],
                ],
            ]
        ],
        'previewicon' => [
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.previewicon',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'gif,png',
                'max_size' => '100',
                'uploadfolder' => 'uploads/tx_templavoilaplus',
                'show_thumbs' => '1',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0'
            ]
        ],
        'belayout' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.belayout',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim,nospace',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, scope, previewicon, belayout, dataprot']
    ]
];
