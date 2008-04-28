<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE'))  die ('Access denied.');

if (TYPO3_MODE=='BE') {

		// unserializing the configuration so we can use it here:
	$_EXTCONF = unserialize($_EXTCONF);

		// Adding click menu item:
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
		'name' => 'tx_templavoila_cm1',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_templavoila_cm1.php'
	);
	include_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_handlestaticdatastructures.php');

		// Adding backend modules:
	t3lib_extMgm::addModule('web','txtemplavoilaM1','top',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
	t3lib_extMgm::addModule('web','txtemplavoilaM2','',t3lib_extMgm::extPath($_EXTKEY).'mod2/');

		// Remove default Page module (layout) manually if wanted:
	if (!$_EXTCONF['enable.']['oldPageModule']) {
		$tmp = $GLOBALS['TBE_MODULES']['web'];
		$GLOBALS['TBE_MODULES']['web'] = str_replace (',,',',',str_replace ('layout','',$tmp));
		unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
	}

		// Registering CSH:
	t3lib_extMgm::addLLrefForTCAdescr('be_groups','EXT:templavoila/locallang_csh_begr.xml');
	t3lib_extMgm::addLLrefForTCAdescr('pages','EXT:templavoila/locallang_csh_pages.xml');
	t3lib_extMgm::addLLrefForTCAdescr('tt_content','EXT:templavoila/locallang_csh_ttc.xml');
	t3lib_extMgm::addLLrefForTCAdescr('tx_templavoila_datastructure','EXT:templavoila/locallang_csh_ds.xml');
	t3lib_extMgm::addLLrefForTCAdescr('tx_templavoila_tmplobj','EXT:templavoila/locallang_csh_to.xml');
	t3lib_extMgm::addLLrefForTCAdescr('xMOD_tx_templavoila','EXT:templavoila/locallang_csh_module.xml');
	t3lib_extMgm::addLLrefForTCAdescr('xEXT_templavoila','EXT:templavoila/locallang_csh_intro.xml');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_txtemplavoilaM1','EXT:templavoila/locallang_csh_pm.xml');


	t3lib_extMgm::insertModuleFunction(
		'tools_txextdevevalM1',
		'tx_templavoila_extdeveval',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_templavoila_extdeveval.php',
		'TemplaVoila L10N Mode Conversion Tool'
	);
}

	// Adding tables:
$TCA['tx_templavoila_tmplobj'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_tmplobj',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_to.gif',
		'selicon_field' => 'previewicon',
		'selicon_field_path' => 'uploads/tx_templavoila',
		'type' => 'parent',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'title,datastructure,rendertype,sys_language_uid,parent,rendertype_ref',
	)
);
$TCA['tx_templavoila_datastructure'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:templavoila/locallang_db.xml:tx_templavoila_datastructure',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_ds.gif',
		'selicon_field' => 'previewicon',
		'selicon_field_path' => 'uploads/tx_templavoila',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'shadowColumnsForNewPlaceholders' => 'scope,title',
	)
);

t3lib_extMgm::allowTableOnStandardPages('tx_templavoila_datastructure');
t3lib_extMgm::allowTableOnStandardPages('tx_templavoila_tmplobj');


	// Adding access list to be_groups
t3lib_div::loadTCA('be_groups');
$tempColumns = array (
	'tx_templavoila_access' => array(
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:be_groups.tx_templavoila_access',
		'config' => Array (
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'tx_templavoila_datastructure,tx_templavoila_tmplobj',
			'prepend_tname' => 1,
			'size' => 5,
			'autoSizeMax' => 15,
			'multiple' => 1,
			'minitems' => 0,
			'maxitems' => 1000,
			'show_thumbs'=> 1,
		),
	)
);
t3lib_extMgm::addTCAcolumns('be_groups', $tempColumns, 1);
t3lib_extMgm::addToAllTCAtypes('be_groups','tx_templavoila_access;;;;1-1-1', '1');

	// Adding the new content element, "Flexible Content":
t3lib_div::loadTCA('tt_content');
$tempColumns = Array (
	'tx_templavoila_ds' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:tt_content.tx_templavoila_ds',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_datastructure',
			'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###STORAGE_PID### AND tx_templavoila_datastructure.scope IN (2) ORDER BY tx_templavoila_datastructure.sorting',
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->main_scope2',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_templavoila_to' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:tt_content.tx_templavoila_to',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_tmplobj',
			'foreign_table_where' => 'AND tx_templavoila_tmplobj.pid=###STORAGE_PID### AND tx_templavoila_tmplobj.datastructure=\'###REC_FIELD_tx_templavoila_ds###\' AND tx_templavoila_tmplobj.parent=0 ORDER BY tx_templavoila_tmplobj.sorting',
#			'disableNoMatchingValueElement' => 1,
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_templavoila_flex' => Array (
		'l10n_cat' => 'text',
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:tt_content.tx_templavoila_flex',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
		'config' => Array (
			'type' => 'flex',
			'ds_pointerField' => 'tx_templavoila_ds',
			'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
		)
	),
	'tx_templavoila_pito' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:tt_content.tx_templavoila_pito',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->pi_templates',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
);
t3lib_extMgm::addTCAcolumns('tt_content',$tempColumns,1);

$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem']='CType;;4;button;1-1-1, header;;;;2-2-2,tx_templavoila_ds,tx_templavoila_to,tx_templavoila_flex;;;;2-2-2, hidden;;1;;3-3-3';
t3lib_extMgm::addPlugin(Array('LLL:EXT:templavoila/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY.'_pi1'),'CType');

if ($TCA['tt_content']['ctrl']['requestUpdate'] != '') {
	$TCA['tt_content']['ctrl']['requestUpdate'] .= ',';
}
$TCA['tt_content']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds';

	// For pages:
$tempColumns = Array (
	'tx_templavoila_ds' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:pages.tx_templavoila_ds',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_datastructure',
			'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###STORAGE_PID### AND tx_templavoila_datastructure.scope IN (1) ORDER BY tx_templavoila_datastructure.sorting',
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->main_scope1',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'suppress_icons' => 'IF_VALUE_FALSE',
		)
	),
	'tx_templavoila_to' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:pages.tx_templavoila_to',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_tmplobj',
			'foreign_table_where' => 'AND tx_templavoila_tmplobj.pid=###STORAGE_PID### AND tx_templavoila_tmplobj.datastructure=\'###REC_FIELD_tx_templavoila_ds###\' AND tx_templavoila_tmplobj.parent=0 ORDER BY tx_templavoila_tmplobj.sorting',
#			'disableNoMatchingValueElement' => 1,
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_templavoila_next_ds' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:pages.tx_templavoila_next_ds',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_datastructure',
			'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###STORAGE_PID### AND tx_templavoila_datastructure.scope IN (1) ORDER BY tx_templavoila_datastructure.sorting',
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->main_scope1',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'suppress_icons' => 'IF_VALUE_FALSE',
		)
	),
	'tx_templavoila_next_to' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:pages.tx_templavoila_next_to',
		'displayCond' => 'FIELD:tx_templavoila_next_ds:REQ:true',
		'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_tmplobj',
			'foreign_table_where' => 'AND tx_templavoila_tmplobj.pid=###STORAGE_PID### AND tx_templavoila_tmplobj.datastructure=\'###REC_FIELD_tx_templavoila_next_ds###\' AND tx_templavoila_tmplobj.parent=0 ORDER BY tx_templavoila_tmplobj.sorting',
#			'disableNoMatchingValueElement' => 1,
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_templavoila_flex' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/locallang_db.xml:pages.tx_templavoila_flex',
#		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
		'config' => Array (
			'type' => 'flex',
			'ds_pointerField' => 'tx_templavoila_ds',
			'ds_pointerField_searchParent' => 'pid',
			'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
			'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
		)
	),
);
t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('pages','tx_templavoila_ds;;;;1-1-1,tx_templavoila_to,tx_templavoila_next_ds,tx_templavoila_next_to,tx_templavoila_flex;;;;1-1-1');
if ($TCA['pages']['ctrl']['requestUpdate'] != '') {
	$TCA['pages']['ctrl']['requestUpdate'] .= ',';
}
$TCA['pages']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds,tx_templavoila_next_ds';

	// Configure the referencing wizard to be used in the web_func module:
if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_func',
		'tx_templavoila_referenceElementsWizard',
		t3lib_extMgm::extPath($_EXTKEY).'func_wizards/class.tx_templavoila_referenceelementswizard.php',
		'LLL:EXT:templavoila/locallang.xml:wiz_refElements',
		'wiz'
	);
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_func','EXT:wizard_crpages/locallang_csh.xml');
}

?>