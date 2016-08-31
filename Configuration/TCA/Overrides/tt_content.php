<?php
defined('TYPO3_MODE') or die();

// Adding the new content element, "Flexible Content":
$tempColumns = array(
    'tx_templavoila_ds' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_ds',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'allowNonIdValues' => 1,
            'itemsProcFunc' => 'Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoila_to' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_to',
        'displayCond' => 'FIELD:CType:=:templavoila_pi1',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => 'Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoila_flex' => array(
        'l10n_cat' => 'text',
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_flex',
        'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => array(
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoila_ds',
            'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
        )
    ),
    'tx_templavoila_pito' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:tt_content.tx_templavoila_pito',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => 'Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler->pi_templates',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'selicon_cols' => 10,
        )
),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['templavoila_pi1'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoila') . '/Resources/Public/Icon/icon_fce_ce.png';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['templavoila_pi1'] = 'extensions-templavoila-type-fce';

$GLOBALS['TCA']['tt_content']['types']['templavoila_pi1']['showitem'] =
    '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.headers;headers,'
    . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,'
    . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,'
    . '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,'
    . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended';

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
if ($_EXTCONF['enable.']['selectDataStructure']) {
    if ($GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] !== '') {
        $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',';
    }
    $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds';
}

if ($_EXTCONF['enable.']['selectDataStructure']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_templavoila_ds,tx_templavoila_to',
        'templavoila_pi1',
        'after:layout'
    );
} else {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_templavoila_to',
        'templavoila_pi1',
        'after:layout'
    );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoila_flex',
    'templavoila_pi1',
    'after:subheader'
);
