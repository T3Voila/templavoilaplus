<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
		// Adding click menu item:
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
		'name' => 'tx_templavoila_cm1',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_templavoila_cm1.php'
	);
	include_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_handlestaticdatastructures.php');

		// Adding backend modules:
  t3lib_extMgm::addModule('web','txtemplavoilaM1','top',t3lib_extMgm::extPath($_EXTKEY).'mod1/');
  t3lib_extMgm::addModule('web','txtemplavoilaM2','',t3lib_extMgm::extPath($_EXTKEY).'mod2/');

  	// Remove default Page module (layout) manually
  $tmp = $GLOBALS['TBE_MODULES']['web'];
  $GLOBALS['TBE_MODULES']['web'] = str_replace (',,',',',str_replace ('layout','',$tmp));
  unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);

}

	// Adding tables:
$TCA['tx_templavoila_tmplobj'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:templavoila/locallang_db.php:tx_templavoila_tmplobj',		
		'label' => 'title',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',	
		'delete' => 'deleted',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_to.gif',
		'selicon_field' => 'previewicon',
		'selicon_field_path' => 'uploads/tx_templavoila',
		'type' => 'parent',
	)
);
$TCA['tx_templavoila_datastructure'] = Array (
	'ctrl' => Array (
		'title' => 'LLL:EXT:templavoila/locallang_db.php:tx_templavoila_datastructure',		
		'label' => 'title',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',	
		'delete' => 'deleted',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icon_ds.gif',
		'selicon_field' => 'previewicon',
		'selicon_field_path' => 'uploads/tx_templavoila',
	)
);

	// Adding the new content element, "Flexible Content":
t3lib_div::loadTCA('tt_content');
$tempColumns = Array (
    'tx_templavoila_ds' => Array (        
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/locallang_db.php:tt_content.tx_templavoila_ds',
        'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_datastructure',
			'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###STORAGE_PID### AND tx_templavoila_datastructure.scope IN (2) ORDER BY tx_templavoila_datastructure.title',
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->main_scope2',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
        )
    ),
    'tx_templavoila_to' => Array (
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/locallang_db.php:tt_content.tx_templavoila_to',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => Array (
			'type' => 'select',
			'items' => Array (
				Array('',0),
			),
			'foreign_table' => 'tx_templavoila_tmplobj',
			'foreign_table_where' => 'AND tx_templavoila_tmplobj.pid=###STORAGE_PID### AND tx_templavoila_tmplobj.datastructure="###REC_FIELD_tx_templavoila_ds###" AND tx_templavoila_tmplobj.parent=0 ORDER BY tx_templavoila_tmplobj.title',
#			'disableNoMatchingValueElement' => 1,
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
        )
    ),
    'tx_templavoila_flex' => Array (        
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/locallang_db.php:tt_content.tx_templavoila_flex',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => Array (
            'type' => 'flex',    
			'ds_pointerField' => 'tx_templavoila_ds',
			'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
        )
    ),
    'tx_templavoila_pito' => Array (        
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/locallang_db.php:tt_content.tx_templavoila_pito',
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

$TCA['tt_content']['types'][$_EXTKEY.'_pi1']['showitem']='CType;;4;button;1-1-1, header;;;;2-2-2,tx_templavoila_ds,tx_templavoila_to,tx_templavoila_flex;;;;2-2-2';
$TCA['tt_content']['types'][$_EXTKEY.'_pi2']['showitem']='CType;;4;button;1-1-1, header;;;;2-2-2';
t3lib_extMgm::addPlugin(Array('LLL:EXT:templavoila/locallang_db.php:tt_content.CType_pi1', $_EXTKEY.'_pi1'),'CType');
t3lib_extMgm::addPlugin(Array('LLL:EXT:templavoila/locallang_db.php:tt_content.CType_pi2', $_EXTKEY.'_pi2'),'CType');


$_EXTCONF = unserialize($_EXTCONF);	// unserializing the configuration so we can use it here:
if ($_EXTCONF['enable.']['pageTemplateSelector'])	{

		// For pages:
	$tempColumns = Array (
	    'tx_templavoila_ds' => Array (        
	        'exclude' => 1,
	        'label' => 'LLL:EXT:templavoila/locallang_db.php:pages.tx_templavoila_ds',
	        'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tx_templavoila_datastructure',
				'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###STORAGE_PID### AND tx_templavoila_datastructure.scope IN (1) ORDER BY tx_templavoila_datastructure.title',
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
	        'label' => 'LLL:EXT:templavoila/locallang_db.php:pages.tx_templavoila_to',
			'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
	        'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tx_templavoila_tmplobj',
				'foreign_table_where' => 'AND tx_templavoila_tmplobj.pid=###STORAGE_PID### AND tx_templavoila_tmplobj.datastructure="###REC_FIELD_tx_templavoila_ds###" AND tx_templavoila_tmplobj.parent=0 ORDER BY tx_templavoila_tmplobj.title',
	#			'disableNoMatchingValueElement' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
	        )
	    ),
	    'tx_templavoila_next_ds' => Array (        
	        'exclude' => 1,
	        'label' => 'LLL:EXT:templavoila/locallang_db.php:pages.tx_templavoila_next_ds',
	        'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tx_templavoila_datastructure',
				'foreign_table_where' => 'AND tx_templavoila_datastructure.pid=###STORAGE_PID### AND tx_templavoila_datastructure.scope IN (1) ORDER BY tx_templavoila_datastructure.title',
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
	        'label' => 'LLL:EXT:templavoila/locallang_db.php:pages.tx_templavoila_next_to',
			'displayCond' => 'FIELD:tx_templavoila_next_ds:REQ:true',
	        'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'foreign_table' => 'tx_templavoila_tmplobj',
				'foreign_table_where' => 'AND tx_templavoila_tmplobj.pid=###STORAGE_PID### AND tx_templavoila_tmplobj.datastructure="###REC_FIELD_tx_templavoila_next_ds###" AND tx_templavoila_tmplobj.parent=0 ORDER BY tx_templavoila_tmplobj.title',
	#			'disableNoMatchingValueElement' => 1,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
	        )
	    ),	
	    'tx_templavoila_flex' => Array (        
	        'exclude' => 1,
	        'label' => 'LLL:EXT:templavoila/locallang_db.php:pages.tx_templavoila_flex',
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
	t3lib_extMgm::addLLrefForTCAdescr('pages','EXT:templavoila/locallang_csh_pages.php');
}

?>
