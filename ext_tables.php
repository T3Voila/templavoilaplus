<?php
# TYPO3 CVS ID: $Id$
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);

if (TYPO3_MODE === 'BE') {

	// Adding click menu item:
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
		'name' => 'tx_templavoila_cm1',
		'path' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'class.tx_templavoila_cm1.php'
	);
	include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('templavoila') . 'class.tx_templavoila_handlestaticdatastructures.php');

	// Adding backend modules:
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'txtemplavoilaM1',
		'top',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/'
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'txtemplavoilaM2',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod2/'
	);

	// Remove default Page module (layout) manually if wanted:
	if (!$_EXTCONF['enable.']['oldPageModule']) {
		$tmp = $GLOBALS['TBE_MODULES']['web'];
		$GLOBALS['TBE_MODULES']['web'] = str_replace(',,', ',', str_replace('layout', '', $tmp));
		unset ($GLOBALS['TBE_MODULES']['_PATHS']['web_layout']);
	}

	// Registering CSH:
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_groups', 'EXT:templavoila/Resources/Private/Language/locallang_csh_begr.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:templavoila/Resources/Private/Language/locallang_csh_pages.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tt_content', 'EXT:templavoila/Resources/Private/Language/locallang_csh_ttc.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_templavoila_datastructure', 'EXT:templavoila/Resources/Private/Language/locallang_csh_ds.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_templavoila_tmplobj', 'EXT:templavoila/Resources/Private/Language/locallang_csh_to.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xMOD_tx_templavoila', 'EXT:templavoila/Resources/Private/Language/locallang_csh_module.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xEXT_templavoila', 'EXT:templavoila/Resources/Private/Language/locallang_csh_intro.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_txtemplavoilaM1', 'EXT:templavoila/Resources/Private/Language/locallang_csh_pm.xml');

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'tools_txextdevevalM1',
		'tx_templavoila_extdeveval',
		NULL,
		'TemplaVoila L10N Mode Conversion Tool'
	);
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templavoila_datastructure');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_templavoila_tmplobj');


// Adding access list to be_groups
$tempColumns = array(
	'tx_templavoila_access' => array(
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:be_groups.tx_templavoila_access',
		'config' => Array(
			'type' => 'group',
			'internal_type' => 'db',
			'allowed' => 'tx_templavoila_datastructure,tx_templavoila_tmplobj',
			'prepend_tname' => 1,
			'size' => 5,
			'autoSizeMax' => 15,
			'multiple' => 1,
			'minitems' => 0,
			'maxitems' => 1000,
			'show_thumbs' => 1,
		),
	)
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_templavoila_access;;;;1-1-1', '1');

// Adding the new content element, "Flexible Content":
$tempColumns = array(
	'tx_templavoila_ds' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tt_content.tx_templavoila_ds',
		'config' => Array(
			'type' => 'select',
			'items' => Array(
				Array('', 0),
			),
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->dataSourceItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'selicon_cols' => 10,
		)
	),
	'tx_templavoila_to' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tt_content.tx_templavoila_to',
		'displayCond' => 'FIELD:CType:=:' . $_EXTKEY . '_pi1',
		'config' => Array(
			'type' => 'select',
			'items' => Array(
				Array('', 0),
			),
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->templateObjectItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'selicon_cols' => 10,
		)
	),
	'tx_templavoila_flex' => Array(
		'l10n_cat' => 'text',
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tt_content.tx_templavoila_flex',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
		'config' => Array(
			'type' => 'flex',
			'ds_pointerField' => 'tx_templavoila_ds',
			'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
		)
	),
	'tx_templavoila_pito' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tt_content.tx_templavoila_pito',
		'config' => Array(
			'type' => 'select',
			'items' => Array(
				Array('', 0),
			),
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->pi_templates',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'selicon_cols' => 10,
		)
	),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicons'][$_EXTKEY . '_pi1'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . '/Resources/Public/Icon/icon_fce_ce.png';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$_EXTKEY . '_pi1'] = 'extensions-templavoila-type-fce';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tt_content.CType_pi1', $_EXTKEY . '_pi1', 'EXT:' . $_EXTKEY . '/Resources/Public/Icon/icon_fce_ce.png'), 'CType');

if ($_EXTCONF['enable.']['selectDataStructure']) {
	if ($GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] != '') {
		$GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',';
	}
	$GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds';
}


if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) >= 4005000) {

	$GLOBALS['TCA']['tt_content']['types'][$_EXTKEY . '_pi1']['showitem'] =
		'--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
		--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.headers;headers,
	--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
		--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
		--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
	--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
		--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
	--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended';
	if ($_EXTCONF['enable.']['selectDataStructure']) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_templavoila_ds;;;;1-1-1,tx_templavoila_to', $_EXTKEY . '_pi1', 'after:layout');
	} else {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_templavoila_to', $_EXTKEY . '_pi1', 'after:layout');
	}
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_templavoila_flex;;;;1-1-1', $_EXTKEY . '_pi1', 'after:subheader');
} else {
	$GLOBALS['TCA']['tt_content']['types'][$_EXTKEY . '_pi1']['showitem'] =
		'CType;;4;;1-1-1, hidden, header;;' . (($_EXTCONF['enable.']['renderFCEHeader']) ? '3' : '') . ';;2-2-2, linkToTop;;;;3-3-3,
		--div--;LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:tt_content.CType_pi1,' . (($_EXTCONF['enable.']['selectDataStructure']) ? 'tx_templavoila_ds,' : '') . 'tx_templavoila_to,tx_templavoila_flex;;;;2-2-2,
		--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group';
}


// For pages:
$tempColumns = array(
	'tx_templavoila_ds' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:pages.tx_templavoila_ds',
		'config' => array(
			'type' => 'select',
			'items' => Array(
				array('', 0),
			),
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->dataSourceItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'suppress_icons' => 'ONLY_SELECTED',
			'selicon_cols' => 10,
		)
	),
	'tx_templavoila_to' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:pages.tx_templavoila_to',
		'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
		'config' => Array(
			'type' => 'select',
			'items' => Array(
				Array('', 0),
			),
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->templateObjectItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'suppress_icons' => 'ONLY_SELECTED',
			'selicon_cols' => 10,
		)
	),
	'tx_templavoila_next_ds' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:pages.tx_templavoila_next_ds',
		'config' => Array(
			'type' => 'select',
			'items' => Array(
				Array('', 0),
			),
			'allowNonIdValues' => 1,
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->dataSourceItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'suppress_icons' => 'ONLY_SELECTED',
			'selicon_cols' => 10,
		)
	),
	'tx_templavoila_next_to' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:pages.tx_templavoila_next_to',
		'displayCond' => 'FIELD:tx_templavoila_next_ds:REQ:true',
		'config' => Array(
			'type' => 'select',
			'items' => Array(
				Array('', 0),
			),
			'itemsProcFunc' => 'tx_templavoila_handleStaticdatastructures->templateObjectItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
			'suppress_icons' => 'ONLY_SELECTED',
			'selicon_cols' => 10,
		)
	),
	'tx_templavoila_flex' => Array(
		'exclude' => 1,
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:pages.tx_templavoila_flex',
		'config' => Array(
			'type' => 'flex',
			'ds_pointerField' => 'tx_templavoila_ds',
			'ds_pointerField_searchParent' => 'pid',
			'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
			'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
		)
	),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);
if ($_EXTCONF['enable.']['selectDataStructure']) {

	if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) >= 4005000) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_ds;;;;1-1-1,tx_templavoila_to', '', 'replace:backend_layout');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_next_ds;;;;1-1-1,tx_templavoila_next_to', '', 'replace:backend_layout_next_level');
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_flex;;;;1-1-1', '', 'after:title');
	} else {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_ds;;;;1-1-1,tx_templavoila_to,tx_templavoila_next_ds;;;;1-1-1,tx_templavoila_next_to,tx_templavoila_flex;;;;1-1-1');
	}

	if ($GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] != '') {
		$GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= ',';
	}
	$GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds,tx_templavoila_next_ds';
} else {
	if (tx_templavoila_div::convertVersionNumberToInteger(TYPO3_version) >= 4005000) {
		if (!$_EXTCONF['enable.']['oldPageModule']) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_to;;;;1-1-1', '', 'replace:backend_layout');
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_next_to;;;;1-1-1', '', 'replace:backend_layout_next_level');
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_flex;;;;1-1-1', '', 'after:title');
		} else {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'layout', '--linebreak--, tx_templavoila_to;;;;1-1-1, tx_templavoila_next_to;;;;1-1-1', 'after:backend_layout_next_level');
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_flex;;;;1-1-1', '', 'after:title');
		}
	} else {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_templavoila_to;;;;1-1-1,tx_templavoila_next_to;;;;1-1-1,tx_templavoila_flex;;;;1-1-1');
	}

	unset($GLOBALS['TCA']['pages']['columns']['tx_templavoila_to']['displayCond']);
	unset($GLOBALS['TCA']['pages']['columns']['tx_templavoila_next_to']['displayCond']);
}

// Configure the referencing wizard to be used in the web_func module:
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'Extension\\Templavoila\\Controller\\ReferenceElementWizardController',
		NULL,
		'LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:wiz_refElements',
		'wiz'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_func',
		'Extension\\Templavoila\\Controller\\RenameFieldInPageFlexWizardController',
		NULL,
		'LLL:EXT:templavoila/Resources/Private/Language/locallang.xml:wiz_renameFieldsInPage',
		'wiz'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_func', 'EXT:wizard_crpages/locallang_csh.xml');
}
// complex condition to make sure the icons are available during frontend editing...
if (TYPO3_MODE === 'BE' ||
	(TYPO3_MODE === 'FE' && isset($GLOBALS['BE_USER']) && method_exists($GLOBALS['BE_USER'], 'isFrontendEditingActive') && $GLOBALS['BE_USER']->isFrontendEditingActive())
) {
	$icons = array(
		'paste' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/clip_pasteafter.gif',
		'pasteSubRef' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/clip_pastesubref.gif',
		'makelocalcopy' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/makelocalcopy.gif',
		'clip_ref' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/clip_ref.gif',
		'clip_ref-release' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/clip_ref_h.gif',
		'unlink' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'mod1/unlink.png',
		'htmlvalidate' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'Resources/Public/Icon/html_go.png',
		'type-fce' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . 'Resources/Public/Icon/icon_fce_ce.png'
	);
	\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $_EXTKEY);
}
