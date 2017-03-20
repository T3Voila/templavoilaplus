<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj',
        'label' => 'title',
        'label_userFunc' => 'EXT:templavoilaplus/Classes/Service/UserFunc/Label.php:&Ppi\TemplaVoilaPlus\Service\UserFunc\Label->getLabel',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => 'EXT:templavoilaplus/Resources/Public/Icon/icon_to.gif',
        'selicon_field' => 'previewicon',
        'selicon_field_path' => 'uploads/tx_templavoilaplus',
        'type' => 'parent', // kept to make sure the user is force to reload the form
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'shadowColumnsForNewPlaceholders' => 'title,datastructure,rendertype,sys_language_uid,parent,rendertype_ref',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,datastructure,fileref',
        'maxDBListItems' => 60,
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple',
                    ],
                ],
                'default' => 0,
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.title',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'required,trim',
            ]
        ],
        'parent' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_templavoilaplus_tmplobj',
                'foreign_table_where' => 'AND tx_templavoilaplus_tmplobj.parent=0 AND tx_templavoilaplus_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoilaplus_tmplobj.title',
                'items' => [
                    ['', 0]
                ]
            ]
        ],
        'rendertype_ref' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.rendertype_ref',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_templavoilaplus_tmplobj',
                'foreign_table_where' => 'AND tx_templavoilaplus_tmplobj.parent=0 AND tx_templavoilaplus_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoilaplus_tmplobj.title',
                'items' => [
                    ['', 0]
                ]
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'datastructure' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.datastructure',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_templavoilaplus_datastructure',
                'foreign_table_where' => 'AND tx_templavoilaplus_datastructure.pid=###CURRENT_PID### ORDER BY tx_templavoilaplus_datastructure.uid',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'itemsProcFunc' => \Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->main',
                'allowNonIdValues' => 1,
                'wizards' => [
                    '_PADDING' => 2,
                    '_VERTICAL' => 1,
                    'add' => [
                        'type' => 'script',
                        'title' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.ds_createnew',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif',
                        'params' => [
                            'table' => 'tx_templavoilaplus_datastructure',
                            'pid' => '###CURRENT_PID###',
                            'setValue' => 'set'
                        ],
                        'module' => [
                            'name' => 'wizard_add.php'
                        ]
                    ]
                ]
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'fileref' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.fileref',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'wizards' => [
                    '_PADDING' => 2,
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                        'module' => [
                            'name' => 'wizard_link',
                            'urlParameters' => [
                            ],
                        ],
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                        'params' => [
                            'blindLinkOptions' => 'page,url,mail,spec,folder',
                            'allowedExtensions' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'],
                        ]
                    ],
                ],
                'eval' => 'required,nospace',
                'softref' => 'typolink'
            ]
        ],
        'belayout' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.belayout',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'wizards' => [
                    '_PADDING' => 2,
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                        'module' => [
                            'name' => 'wizard_link',
                            'urlParameters' => [
                                'act' => 'file',
                            ],
                        ],
                        'params' => [
                            'blindLinkOptions' => 'page,folder,mail,spec,url',
                            'allowedExtensions' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'],
                        ],
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
                    ],
                ],
                'eval' => 'nospace',
                'softref' => 'typolink'
            ]
        ],
        'previewicon' => [
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.previewicon',
            'displayCond' => 'REC:NEW:false',
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
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'description' => [
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.description',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '256',
                'eval' => 'trim'
            ],
            'displayCond' => 'FIELD:parent:=:0'
        ],
        'rendertype' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.rendertype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.rendertype.I.0', ''],
                    ['LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.rendertype.I.1', 'print'],
                ]
            ],
            'displayCond' => 'FIELD:parent:!=:0'
        ],
        'templatemapping' => ['config' => ['type' => 'passthrough']],
        'fileref_mtime' => ['config' => ['type' => 'passthrough']],
        'fileref_md5' => ['config' => ['type' => 'passthrough']],
        'localprocessing' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tx_templavoilaplus_tmplobj.localProc',
            'config' => [
                'type' => 'text',
                'wrap' => 'OFF',
                'cols' => '30',
                'rows' => '2',
            ],
            'defaultExtras' => 'fixed-font:enable-tab'
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, parent, fileref, belayout, datastructure, sys_language_uid, rendertype, rendertype_ref, previewicon, description, localprocessing'],
        '1' => ['showitem' => 'title, parent, fileref, belayout, datastructure, sys_language_uid, rendertype, rendertype_ref, previewicon, description, localprocessing'],
    ]
];
