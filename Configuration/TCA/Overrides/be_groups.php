<?php
defined('TYPO3_MODE') or die();

// Adding access list to be_groups
$tempColumns = array(
	'tx_templavoila_access' => array(
		'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xml:be_groups.tx_templavoila_access',
		'config' => array(
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_templavoila_access');
