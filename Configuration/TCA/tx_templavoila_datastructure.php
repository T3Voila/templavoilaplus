<?php

return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datastructure',
		'label' => 'title',
		'label_userFunc' => 'EXT:templavoila/Classes/class.tx_templavoila_label.php:&tx_templavoila_label->getLabel',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'Resources/Public/Icon/icon_ds.gif',
		'selicon_field' => 'previewicon',
		'selicon_field_path' => 'uploads/tx_templavoila',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'scope,title',
	),
	'interface' => Array(
		'showRecordFieldList' => 'title,dataprot',
		'maxDBListItems' => 60,
	),
	'columns' => Array(
		'title' => Array(
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datastructure.title',
			'config' => Array(
				'type' => 'input',
				'size' => '48',
				'eval' => 'required,trim',
			)
		),
		'dataprot' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datastructure.dataprot',
			'config' => Array(
				'type' => 'text',
				'wrap' => 'OFF',
				'cols' => '48',
				'rows' => '20',
			),
			'defaultExtras' => 'fixed-font:enable-tab'
		),
		'scope' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datastructure.scope',
			'config' => Array(
				'type' => 'select',
				'items' => Array(
					Array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datasource.scope.I.0', 0),
					Array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datastructure.scope.I.1', 1),
					Array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_datastructure.scope.I.2', 2),
				),
			)
		),
		'previewicon' => Array(
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_tmplobj.previewicon',
			'config' => Array(
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
		'belayout' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tx_templavoila_tmplobj.belayout',
			'config' => Array(
				'type' => 'input',
				'size' => '48',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard&amp;act=file',
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
	'types' => Array(
		'0' => Array('showitem' => 'title;;;;2-2-2, scope, previewicon, belayout, dataprot;;;;3-3-3')
	)
);
