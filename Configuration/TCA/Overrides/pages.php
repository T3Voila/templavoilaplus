<?php

defined('TYPO3') || die();

$tempColumns = [
    'tx_templavoilaplus_map' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_map',
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Tvp\TemplaVoilaPlus\Service\ItemsProcFunc::class . '->mapItems',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        ],
    ],
    'tx_templavoilaplus_next_map' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_next_map',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Tvp\TemplaVoilaPlus\Service\ItemsProcFunc::class . '->mapItems',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        ],
    ],
    'tx_templavoilaplus_flex' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_flex',
        'l10n_mode' => 'exclude',
        'config' => [
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoilaplus_map',
            'ds_pointerField_searchParent' => 'pid',
            'ds_pointerField_searchParent_subField' => 'tx_templavoilaplus_next_map',
            'ds_pointerType' => 'combinedMappingIdentifier',
        ],
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_templavoilaplus_map',
    '',
    'replace:backend_layout'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_templavoilaplus_next_map',
    '',
    'replace:backend_layout_next_level'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tab.tx_templavoilaplus_flex,tx_templavoilaplus_flex'
);

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tv+'] = 'extensions-templavoila-folder';
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'tv+',
    'tv+',
    'extensions-templavoila-folder',
];
