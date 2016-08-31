<?php
defined('TYPO3_MODE') or die();

$tempColumns = array(
    'tx_templavoila_ds' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_ds',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoila_to' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_to',
        'displayCond' => 'FIELD:tx_templavoila_ds:REQ:true',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoila_next_ds' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_next_ds',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'allowNonIdValues' => 1,
            'itemsProcFunc' => Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->dataSourceItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoila_next_to' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_next_to',
        'displayCond' => 'FIELD:tx_templavoila_next_ds:REQ:true',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => array(
                array('', 0),
            ),
            'itemsProcFunc' => Extension\Templavoila\Service\ItemProcFunc\StaticDataStructuresHandler::class . '->templateObjectItemsProcFunc',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'suppress_icons' => 'ONLY_SELECTED',
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoila_flex' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoila_flex',
        'config' => array(
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoila_ds',
            'ds_pointerField_searchParent' => 'pid',
            'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
            'ds_tableField' => 'tx_templavoila_datastructure:dataprot',
        )
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

$_EXTCONF = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
if ($_EXTCONF['enable.']['selectDataStructure']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoila_ds,tx_templavoila_to',
        '',
        'replace:backend_layout'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoila_next_ds,tx_templavoila_next_to',
        '',
        'replace:backend_layout_next_level'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        'tx_templavoila_flex',
        '',
        'after:title'
    );

    if ($GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] !== '') {
        $GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= ',';
    }
    $GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= 'tx_templavoila_ds,tx_templavoila_next_ds';
} else {
    if (!$_EXTCONF['enable.']['oldPageModule']) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_to',
            '',
            'replace:backend_layout'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_next_to',
            '',
            'replace:backend_layout_next_level'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_flex',
            '',
            'after:title'
        );
    } else {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
            'pages',
            'layout',
            '--linebreak--, tx_templavoila_to, tx_templavoila_next_to',
            'after:backend_layout_next_level'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            'tx_templavoila_flex',
            '',
            'after:title'
        );
    }

    unset($GLOBALS['TCA']['pages']['columns']['tx_templavoila_to']['displayCond']);
    unset($GLOBALS['TCA']['pages']['columns']['tx_templavoila_next_to']['displayCond']);
}

// Add "pages.storage_pid" field to TCA column
$additionalColumns = array(
    'storage_pid' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.storage_pid',
        'config' => array(
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'size' => '1',
            'maxitems' => '1',
            'minitems' => '0',
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
    'showitem' => 'storage_pid;LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.storage_pid_formlabel',
    'canNotCollapse' => 1
);

// Add to "normal" pages, "external URL", "shortcut page" and "storage PID"
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages',
    '--palette--;LLL:EXT:templavoila/Resources/Private/Language/locallang_db.xlf:pages.palettes.storage;storage',
    \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT . ','
    . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER,
    'after:media'
);
