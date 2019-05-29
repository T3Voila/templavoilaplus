<?php
defined('TYPO3_MODE') or die();

// Adding the new content element, "Flexible Content":
$tempColumns = array(
    'tx_templavoilaplus_ds' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoilaplus_ds',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoilaplus_to' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoilaplus_to',
        'displayCond' => 'FIELD:CType:=:templavoilaplus_pi1',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => \Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoilaplus_flex' => array(
        'l10n_cat' => 'text',
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoilaplus_flex',
        'displayCond' => 'FIELD:tx_templavoilaplus_ds:REQ:true',
        'config' => array(
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoilaplus_ds',
            'ds_tableField' => 'tx_templavoilaplus_datastructure:dataprot',
        )
    ),
    'tx_templavoilaplus_pito' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoilaplus_pito',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => \Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->pi_templates',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:tt_content.CType_pi1',
        'templavoilaplus_pi1',
        'EXT:templavoilaplus/Resources/Public/Icon/icon_fce_ce.png'
    ],
    'CType',
    'templavoilaplus'
);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['templavoilaplus_pi1'] = 'extensions-templavoila-type-fce';

if (version_compare(TYPO3_version, '9.0.0', '>=')) {
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
} else {
    $GLOBALS['TCA']['tt_content']['types']['templavoilaplus_pi1']['showitem'] = '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    ';
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoilaplus_to',
    'templavoilaplus_pi1',
    'after:layout'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoilaplus_flex',
    'templavoilaplus_pi1',
    'after:subheader'
);
