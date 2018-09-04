<?php
namespace Ppi\TemplaVoilaPlus\ContextMenu;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Context menu item provider adding export and import items
 * PHP7 as can only be called from TYPO3 v8
 */
class ItemProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'mappingFile' => [
            'type' => 'item',
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:cm1_title',
            'iconIdentifier' => 'extensions-templavoila-menu-item',
            'callbackAction' => 'mappingFile', //'templavoilaplus_mapping'
        ],
        'mappingDb' => [
            'type' => 'item',
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:cm1_title',
            'iconIdentifier' => 'extensions-templavoila-menu-item',
            'callbackAction' => 'mappingDb', //'templavoilaplus_mapping',
        ],
        'viewsubelements' => [
            'type' => 'item',
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:cm1_viewsubelements',
            'iconIdentifier' => 'extensions-templavoila-menu-item',
            'callbackAction' => 'viewSubelements', //'web_txtemplavoilaplusLayout',
        ],
        'viewflexformxml' => [
            'type' => 'item',
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:cm1_viewflexformxml',
            'iconIdentifier' => 'extensions-templavoila-menu-item',
            'callbackAction' => 'viewFlexformXml', //'templavoilaplus_flexform_cleaner',
        ],
        'viewdsto' => [
            'type' => 'item',
            'label' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:cm_viewdsto',
            'iconIdentifier' => 'extensions-templavoila-menu-item',
            'callbackAction' => 'viewDsTo', //'templavoilaplus_mapping',
        ],
    ];

    /**
     * Returns the provider priority which is used for determining the order in which providers are adding items
     * to the result array. Highest priority means provider is evaluated first.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Export item is added for all database records except files
     *
     * @return bool
     */
    public function canHandle(): bool
    {
        return in_array(
            $this->table,
            [
                'pages',
                'sys_file',
                'tt_content',
                'tx_templavoilaplus_datastructure',
                'tx_templavoilaplus_tmplobj',
            ],
            true
        );
    }

    /**
     * Checks if the context item with given itemName should be rendered for
     * the element.
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($type, ['divider', 'submenu'], true)) {
            return true;
        }
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }
        $canRender = false;
        switch ($itemName) {
            case 'mappingFile':
                $canRender = $this->backendUser->isAdmin()
                    && $this->isXmlFile();
                break;
            case 'mappingDb':
                $canRender = $this->table === 'tx_templavoilaplus_datastructure'
                    || $this->table === 'tx_templavoilaplus_tmplobj';
                break;
            case 'viewsubelements':
                $canRender = $this->isTvContentElement();
                break;
            case 'viewflexformxml':
                $canRender = $this->backendUser->isAdmin()
                    && ($this->isTvContentElement() || $this->isTvPage());
                break;
            case 'viewdsto':
                $canRender = $this->backendUser->isAdmin()
                    && ($this->isTvContentElement() || $this->isTvPage())
                    && MathUtility::canBeInterpretedAsInteger($this->record['tx_templavoilaplus_ds']);
                break;
            default:
                // Empty as $canRender is already false
                break;

        }
        return $canRender;
    }

    /**
     * Checks if we are on sys_file table and if file exists and if it is a XML file
     *
     * @return bool
     */
    protected function isXmlFile(): bool
    {
        return $this->table === 'sys_file'
            && \Ppi\TemplaVoilaPlus\Domain\Model\File::is_file($this->identifier)
            && \Ppi\TemplaVoilaPlus\Domain\Model\File::is_xmlFile($this->identifier);
    }

    /**
     * Checks if we are in table tt_content and have CType for TV+ element with
     * field tx_templavoilaplus_flex filled.
     *
     * @return bool
     */
    protected function isTvContentElement(): bool
    {
        return $this->table === 'tt_content'
            && $this->record['CType'] === 'templavoilaplus_pi1'
            && $this->record['tx_templavoilaplus_flex'];
    }

    /**
     * Checks if we are in table pages with field tx_templavoilaplus_flex filled.
     *
     * @return bool
     */
    protected function isTvPage(): bool
    {
        return $this->table === 'pages'
            && $this->record['tx_templavoilaplus_flex'];
    }


    /**
     * Adds the attributes table, uid and data-* depending on itemName.
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [
            'data-callback-module' => 'TYPO3/CMS/Templavoilaplus/ContextMenuActions'
        ];

        switch ($itemName) {
            case 'mappingFile':
                $attributes += [
                    'uid' => $this->identifier,
                ];
                break;
            case 'mappingDb':
                $attributes += [
                    'table' => $this->table,
                    'uid' => $this->identifier,
                ];
                break;
            case 'viewsubelements':
            case 'viewflexformxml':
                $attributes += [
                    'table' => $this->table,
                    'uid' => $this->identifier,
                    'data-page-uid' => $this->record['pid'],
                ];
                break;
            case 'viewdsto':
                $attributes += [
                    'table' => 'tx_templavoilaplus_datastructure',
                    'uid' => $this->record['tx_templavoilaplus_ds'],
                ];
                break;
            default:
                // Nothing more to set into the array
                break;
        }
        return $attributes;
    }

    /**
     * Initialize db record
     */
    protected function initialize()
    {
        parent::initialize();
        $this->record = BackendUtility::getRecordWSOL($this->table, $this->identifier);
    }
}
