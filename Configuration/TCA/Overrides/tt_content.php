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
        'displayCond' => 'FIELD:CType:=:templavoila_pi1',
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

$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['templavoila_pi1'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('templavoilaplus') . '/Resources/Public/Icon/icon_fce_ce.png';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['templavoila_pi1'] = 'extensions-templavoila-type-fce';

$GLOBALS['TCA']['tt_content']['types']['templavoila_pi1']['showitem'] =
    '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,'
    . '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,'
    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,'
    . '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,'
    . '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,'
    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,'
    . '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,'
    . '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended';

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
if ($_EXTCONF['enable.']['selectDataStructure']) {
    if ($GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] !== '') {
        $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',';
    }
    $GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= 'tx_templavoilaplus_ds';
}

if ($_EXTCONF['enable.']['selectDataStructure']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_templavoilaplus_ds,tx_templavoilaplus_to',
        'templavoila_pi1',
        'after:layout'
    );
} else {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tt_content',
        'tx_templavoilaplus_to',
        'templavoila_pi1',
        'after:layout'
    );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'tx_templavoilaplus_flex',
    'templavoila_pi1',
    'after:subheader'
);
