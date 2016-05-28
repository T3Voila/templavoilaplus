<?php

return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj',
		'label' => 'title',
		'label_userFunc' => 'EXT:templavoila/Classes/Service/UserFunc/Label.php:&Extension\Templavoila\Service\UserFunc\Label->getLabel',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'Resources/Public/Icon/icon_to.gif',
		'selicon_field' => 'previewicon',
		'selicon_field_path' => 'uploads/tx_templavoila',
		'type' => 'parent', // kept to make sure the user is force to reload the form
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'title,datastructure,rendertype,sys_language_uid,parent,rendertype_ref',
	),
	'interface' => array(
		'showRecordFieldList' => 'title,datastructure,fileref',
		'maxDBListItems' => 60,
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.title',
			'config' => array(
				'type' => 'input',
				'size' => '48',
				'eval' => 'required,trim',
			)
		),
		'parent' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.parent',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_templavoila_tmplobj',
				'foreign_table_where' => 'AND tx_templavoila_tmplobj.parent=0 AND tx_templavoila_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoila_tmplobj.title',
				'suppress_icons' => 'ONLY_SELECTED',
				'items' => array(
					array('', 0)
				)
			)
		),
		'rendertype_ref' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype_ref',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_templavoila_tmplobj',
				'foreign_table_where' => 'AND tx_templavoila_tmplobj.parent=0 AND tx_templavoila_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoila_tmplobj.title',
				'suppress_icons' => 'ONLY_SELECTED',
				'items' => array(
					array('', 0)
				)
			),
			'displayCond' => 'FIELD:parent:=:0'
		),
		'datastructure' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.datastructure',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_templavoila_datastructure',
				'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###CURRENT_PID### ORDER BY tx_templavoila_datastructure.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->main',
				'allowNonIdValues' => 1,
				'suppress_icons' => 'ONLY_SELECTED',
				'wizards' => array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.ds_createnew',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'tx_templavoila_datastructure',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'set'
						),
						'module' => array(
							'name' => 'wizard_add.php'
						)
					)
				)
			),
			'displayCond' => 'FIELD:parent:=:0'
		),
		'fileref' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.fileref',
			'config' => array(
				'type' => 'input',
				'size' => '48',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard',
							),
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
						'params' => array(
							'blindLinkOptions' => 'page,url,mail,spec,folder',
							'allowedExtensions' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'],
						)
					),
				),
				'eval' => 'required,nospace',
				'softref' => 'typolink'
			)
		),
		'belayout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.belayout',
			'config' => array(
				'type' => 'input',
				'size' => '48',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard',
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
		'previewicon' => array(
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.previewicon',
			'displayCond' => 'REC:NEW:false',
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
			),
			'displayCond' => 'FIELD:parent:=:0'
		),
		'description' => array(
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.description',
			'config' => array(
				'type' => 'input',
				'size' => '48',
				'max' => '256',
				'eval' => 'trim'
			),
			'displayCond' => 'FIELD:parent:=:0'
		),
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('', 0)
				)
			),
			'displayCond' => 'FIELD:parent:!=:0'
		),
		'rendertype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype.I.0', ''),
					array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.rendertype.I.1', 'print'),
				)
			),
			'displayCond' => 'FIELD:parent:!=:0'
		),
		'templatemapping' => array('config' => array('type' => 'passthrough')),
		'fileref_mtime' => array('config' => array('type' => 'passthrough')),
		'fileref_md5' => array('config' => array('type' => 'passthrough')),
		'localprocessing' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tx_templavoila_tmplobj.localProc',
			'config' => array(
				'type' => 'text',
				'wrap' => 'OFF',
				'cols' => '30',
				'rows' => '2',
			),
			'defaultExtras' => 'fixed-font:enable-tab'
		),
	),
	'types' => array(
		'0' => array('showitem' => 'title;;;;2-2-2, parent, fileref, belayout, datastructure;;;;3-3-3, sys_language_uid;;;;3-3-3, rendertype, rendertype_ref, previewicon, description, localprocessing;;;;1-1-1'),
		'1' => array('showitem' => 'title;;;;2-2-2, parent, fileref, belayout, datastructure;;;;3-3-3, sys_language_uid;;;;3-3-3, rendertype, rendertype_ref, previewicon, description, localprocessing;;;;1-1-1'),
	)
);
