<?php

defined('TYPO3_MODE') or die();

// Adding access list to be_groups
$tempColumns = array(
    'tx_templavoilaplus_access' => array(
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:be_groups.tx_templavoilaplus_access',
        'config' => array(
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'tx_templavoilaplus_datastructure,tx_templavoilaplus_tmplobj',
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
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_templavoilaplus_access');
