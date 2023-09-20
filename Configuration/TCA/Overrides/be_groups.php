<?php

defined('TYPO3') || die();

// Adding access list to be_groups
$tempColumns = [
    'tx_templavoilaplus_access' => [
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:be_groups.tx_templavoilaplus_access',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Tvp\TemplaVoilaPlus\Service\ItemsProcFunc::class . '->mapItems',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        ],
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_templavoilaplus_access');
