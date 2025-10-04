<?php

defined('TYPO3') || die();

// Adding the new content element, "Flexible Content":
$tempColumns = [
    'tx_templavoilaplus_map' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoilaplus_map',
        'displayCond' => 'FIELD:CType:=:templavoilaplus_pi1',
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
        'l10n_cat' => 'text',
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoilaplus_flex',
        'displayCond' => 'FIELD:tx_templavoilaplus_map:REQ:true',
        'config' => [
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoilaplus_map',
            'ds' => [],
            'ds_pointerType' => 'combinedMappingIdentifier',
        ],
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.CType_pi1',
        'templavoilaplus_pi1',
        'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png',
    ],
    'CType',
    'templavoilaplus'
);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['templavoilaplus_pi1'] = 'extensions-templavoila-type-fce';

$GLOBALS['TCA']['tt_content']['types']['templavoilaplus_pi1']['showitem'] = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        --palette--;;general,
        --palette--;;headers,
    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
        --palette--;;frames,
        --palette--;;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;language,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;;access,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoilaplus_map',
    'templavoilaplus_pi1',
    'after:colPos'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoilaplus_flex',
    'templavoilaplus_pi1',
    'after:subheader'
);
