<?php
defined('TYPO3_MODE') or die();

$tempColumns = array(
    'tx_templavoilaplus_ds' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_ds',
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
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_to',
        'displayCond' => 'FIELD:tx_templavoilaplus_ds:REQ:true',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoilaplus_next_ds' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_next_ds',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'allowNonIdValues' => 1,
            'itemsProcFunc' => Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoilaplus_next_to' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_next_to',
        'displayCond' => 'FIELD:tx_templavoilaplus_next_ds:REQ:true',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => Ppi\TemplaVoilaPlus\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoilaplus_flex' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_flex',
        'config' => array(
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoilaplus_ds',
            'ds_pointerField_searchParent' => 'pid',
            'ds_pointerField_searchParent_subField' => 'tx_templavoilaplus_next_ds',
            'ds_tableField' => 'tx_templavoilaplus_datastructure:dataprot',
        )
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
if ($_EXTCONF['enable.']['selectDataStructure']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoilaplus_ds,tx_templavoilaplus_to',
        '',
        'replace:backend_layout'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoilaplus_next_ds,tx_templavoilaplus_next_to',
        '',
        'replace:backend_layout_next_level'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoilaplus_flex',
        '',
        'after:title'
    );

    if ($GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] !== '') {
        $GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= ',';
    }
    $GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= 'tx_templavoilaplus_ds,tx_templavoilaplus_next_ds';
} else {
    if (!$_EXTCONF['enable.']['oldPageModule']) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoilaplus_to',
            '',
            'replace:backend_layout'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoilaplus_next_to',
            '',
            'replace:backend_layout_next_level'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoilaplus_flex',
            '',
            'after:title'
        );
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
            'pages',
            'layout',
            '--linebreak--, tx_templavoilaplus_to, tx_templavoilaplus_next_to',
            'after:backend_layout_next_level'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoilaplus_flex',
            '',
            'after:title'
        );
    }

    unset($GLOBALS['TCA']['pages']['columns']['tx_templavoilaplus_to']['displayCond']);
    unset($GLOBALS['TCA']['pages']['columns']['tx_templavoilaplus_next_to']['displayCond']);
}

// Add "pages.storage_pid" field to TCA column
$additionalColumns = array(
    'storage_pid' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.storage_pid',
        'config' => array(
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'size' => '1',
            'maxitems' => '1',
            'minitems' => '0',
            'default' => 0,
            'show_thumbs' => '1',
            'wizards' => array(
                'suggest' => array(
                    'type' => 'suggest'
                )
            )
        )
    )
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $additionalColumns);

// Add palette
$GLOBALS['TCA']['pages']['palettes']['storage'] = array(
    'showitem' => 'storage_pid;LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.storage_pid_formlabel',
    'canNotCollapse' => 1
);

// Add to "normal" pages, "external URL", "shortcut page" and "storage PID"
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--palette--;LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.palettes.storage;storage',
    \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER,
    'after:media'
);

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tv+'] = 'extensions-templavoila-templavoila-logo';
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'tv+',
    'tv+',
    'extensions-templavoila-templavoila-logo',
];
