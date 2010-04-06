<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_templavoila_tmplobj'] = Array (
	'ctrl' => $TCA['tx_templavoila_tmplobj']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,datastructure,fileref',
		'maxDBListItems' => 60,
	),
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.title',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'eval' => 'required,trim',
			)
		),
		'parent' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.parent',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_templavoila_tmplobj',
				'foreign_table_where' => 'AND tx_templavoila_tmplobj.parent=0 AND tx_templavoila_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoila_tmplobj.title',
				'suppress_icons' => 'ONLY_SELECTED',
				'items' => Array(
					Array('',0)
				)
			)
		),
		'rendertype_ref' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.rendertype_ref',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'tx_templavoila_tmplobj',
				'foreign_table_where' => 'AND tx_templavoila_tmplobj.parent=0 AND tx_templavoila_tmplobj.uid!=\'###REC_FIELD_uid###\' ORDER BY tx_templavoila_tmplobj.title',
				'suppress_icons' => 'ONLY_SELECTED',
				'items' => Array(
					Array('',0)
				)
			)
		),
		'datastructure' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.datastructure',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tx_templavoila_datastructure',
				'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###CURRENT_PID### ORDER BY tx_templavoila_datastructure.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
                'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->main',
				'allowNonIdValues' => 1,
				'suppress_icons' => 'ONLY_SELECTED',
				'wizards' => Array(
					'_PADDING' => 2,
					'_VERTICAL' => 1,
					'add' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.ds_createnew',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'tx_templavoila_datastructure',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'set'
						),
						'script' => 'wizard_add.php',
					),
				),
			)
		),
		'fileref' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.fileref',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
						'params' => Array(
							'blindLinkOptions' => 'page,url,mail,spec,folder',
							'allowedExtensions' => $TYPO3_CONF_VARS['SYS']['textfile_ext'],
						)
					),
				),
				'eval' => 'required,nospace',
				'softref' => 'typolink'
			)
		),
		'belayout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.belayout',
			'config' => Array (
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
							'allowedExtensions' => $TYPO3_CONF_VARS['SYS']['textfile_ext'],
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					),
				),
				'eval' => 'nospace',
				'softref' => 'typolink'
			)
		),
		'previewicon' => Array(
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.previewicon',
			'displayCond' => 'REC:NEW:false',
			'config' => Array (
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
		'description' => Array (
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.description',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'max' => '256',
				'eval' => 'trim',
			)
		),
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('',0)
				)
			)
		),
		'rendertype' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.rendertype',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.rendertype.I.0', ''),
					Array('LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.rendertype.I.1', 'print'),
				),
			)
		),
		'templatemapping' => Array ('config' => Array ('type' => 'passthrough')),
		'fileref_mtime' => Array ('config' => Array ('type' => 'passthrough')),
		'fileref_md5' => Array ('config' => Array ('type' => 'passthrough')),
		'localprocessing' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.localProc',
			'config' => Array (
				'type' => 'text',
				'wrap' => 'OFF',
				'cols' => '30',
				'rows' => '2',
			),
			'defaultExtras' => 'fixed-font:enable-tab'
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'title;;;;2-2-2, parent, fileref, belayout, datastructure;;;;3-3-3, rendertype_ref, previewicon, description, localprocessing;;;;1-1-1'),
		'1' => Array('showitem' => 'title;;;;2-2-2, parent, fileref, belayout, sys_language_uid;;;;3-3-3, rendertype,localprocessing;;;;1-1-1')
	)
);


$TCA['tx_templavoila_datastructure'] = Array (
	'ctrl' => $TCA['tx_templavoila_datastructure']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,dataprot',
		'maxDBListItems' => 60,
	),
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datastructure.title',
			'config' => Array (
				'type' => 'input',
				'size' => '48',
				'eval' => 'required,trim',
			)
		),
		'dataprot' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datastructure.dataprot',
			'config' => Array (
				'type' => 'text',
				'wrap' => 'OFF',
				'cols' => '48',
				'rows' => '20',
			),
			'defaultExtras' => 'fixed-font:enable-tab'
		),
		'scope' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datastructure.scope',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datasource.scope.I.0',0),
					Array('LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datastructure.scope.I.1',1),
					Array('LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datastructure.scope.I.2',2),
				),
			)
		),
		'previewicon' => Array(
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.previewicon',
			'config' => Array (
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
		'belayout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj.belayout',
			'config' => Array (
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
							'allowedExtensions' => $TYPO3_CONF_VARS['SYS']['textfile_ext'],
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					),
				),
				'eval' => 'nospace',
				'softref' => 'typolink'
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'title;;;;2-2-2, scope, previewicon, belayout, dataprot;;;;3-3-3')
	)
);

?>