<?php

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure',
        'label' => 'title',
        'label_userFunc' => 'EXT:templavoila/Classes/Service/UserFunc/Label.php:&Extension\Templavoila\Service\UserFunc\Label->getLabel',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'iconfile' => 'EXT:templavoila/Resources/Public/Icon/icon_ds.gif',
        'selicon_field' => 'previewicon',
        'selicon_field_path' => 'uploads/tx_templavoila',
        'versioningWS' => TRUE,
        'origUid' => 't3_origuid',
        'shadowColumnsForNewPlaceholders' => 'scope,title',
    ),
    'interface' => array(
        'showRecordFieldList' => 'title,dataprot',
        'maxDBListItems' => 60,
    ),
    'columns' => array(
        'title' => array(
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.title',
            'config' => array(
                'type' => 'input',
                'size' => '48',
                'eval' => 'required,trim',
            )
        ),
        'dataprot' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.dataprot',
            'config' => array(
                'type' => 'text',
                'wrap' => 'OFF',
                'cols' => '48',
                'rows' => '20',
            ),
            'defaultExtras' => 'fixed-font:enable-tab'
        ),
        'scope' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.scope',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datasource.scope.I.0', 0),
                    array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.scope.I.1', 1),
                    array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_datastructure.scope.I.2', 2),
                ),
            )
        ),
        'previewicon' => array(
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.previewicon',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'gif,png',
                'max_size' => '100',
                'uploadfolder' => 'uploads/tx_templavoila',
                'show_thumbs' => '1',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0'
            )
        ),
        'belayout' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.belayout',
            'config' => array(
                'type' => 'input',
                'size' => '48',
                'wizards' => Array(
                    '_PADDING' => 2,
                    'link' => array(
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                        'module' => array(
                            'name' => 'wizard_link',
                            'urlParameters' => array(
                                'act' => 'file',
                            ),
                        ),
                        'params' => array(
                            'blindLinkOptions' => 'page,folder,mail,spec,url',
                            'allowedExtensions' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'],
                        ),
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
                    ),
                ),
                'eval' => 'nospace',
                'softref' => 'typolink'
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'title, scope, previewicon, belayout, dataprot')
    )
);
