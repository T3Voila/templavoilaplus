<?php
defined('TYPO3_MODE') or die();

$tempColumns = array(
    'tx_templavoilaplus_map' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_map',
        'l10n_mode' => 'exclude',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Ppi\TemplaVoilaPlus\Service\ItemsProcFunc::class . '->mapItems',
            'size' => 1,
            'minitems' => 0,
            'maxitems' => 1,
            'showIconTable' => true,
            'selicon_cols' => 10,
        )
    ),
    'tx_templavoilaplus_next_map' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang_db.xlf:pages.tx_templavoilaplus_next_map',
        'config' => array(
            'type' => 'select',
            'renderType' => 'selectSingle',
            'allowNonIdValues' => 1,
            'itemsProcFunc' => \Ppi\TemplaVoilaPlus\Service\ItemsProcFunc::class . '->mapItems',
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
        'l10n_mode' => 'exclude',
        'config' => array(
            'type' => 'flex',
            'ds_pointerField' => 'tx_templavoilaplus_map',
            'ds_pointerType' => 'combinedMappingIdentifier',
        )
    ),
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

if (version_compare(TYPO3_version, '9.0.0', '>=')) {
    $extConfig = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['templavoilaplus'];
    $oldPageModule = (bool) $extConfig['enable']['oldPageModule'];
} else {
    $extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);
    $oldPageModule = (bool) $extConfig['enable.']['oldPageModule'];
}

$oldPageModule = false;

if (!$oldPageModule) {
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
} else {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
        'pages',
        '',
        '--linebreak--, tx_templavoilaplus_map, tx_templavoilaplus_next_map',
        'after:backend_layout_next_level'
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_templavoilaplus_flex',
    '',
    'after:title'
);

$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-tv+'] = 'extensions-templavoila-folder';
$GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = [
    'tv+',
    'tv+',
    'extensions-templavoila-folder',
];
