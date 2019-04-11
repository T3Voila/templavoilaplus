<?php
namespace Ppi\TemplaVoilaPlus\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * This wizard renames a field in pages.tx_templavoilaplus_flex, to avoid
 * a remapping
 */
class RenameFieldInPageFlexWizardController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{

    /**
     * @return string
     */
    public function main()
    {
        if ($this->getBackendUser()->isAdmin()) {
            if ((int)$this->pObj->id > 0) {
                $content = $this->showForm() . $this->executeCommand();
            } else {
                // should never happen, as function module catches this already,
                // but save is save ;)
                $content = 'Please select a page from the tree';
            }
        } else {
            $message = new FlashMessage('Module only available for admins.', '', FlashMessage::ERROR);
            $this->getFlashMessageQueue()->enqueue($message);
        }

        return $content;
    }

    /**
     * @param integer $uid
     *
     * @return array
     */
    protected function getAllSubPages($uid)
    {
        $pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $completeRecords = $pageRepository->getMenu([$uid]);
        $return = array($uid);
        if (count($completeRecords) > 0) {
            foreach ($completeRecords as $record) {
                $return = array_merge($return, $this->getAllSubPages($record['uid']));
            }
        }

        return $return;
    }

    /**
     * @return string
     */
    protected function executeCommand()
    {
        $buffer = '';

        if (GeneralUtility::_GP('executeRename') == 1) {
            if (GeneralUtility::_GP('sourceField') === GeneralUtility::_GP('destinationField')) {
                $message = new FlashMessage(
                    'Renaming a field to itself is senseless, execution aborted.',
                    '',
                    FlashMessage::ERROR
                );
                $this->getFlashMessageQueue()->enqueue($message);
                return '';
            }
            $escapedSource = $this->getDatabaseConnection()->fullQuoteStr('%' . GeneralUtility::_GP('sourceField') . '%', 'pages');
            $escapedDest = $this->getDatabaseConnection()->fullQuoteStr('%' . GeneralUtility::_GP('destinationField') . '%', 'pages');

            $condition = 'tx_templavoilaplus_flex LIKE ' . $escapedSource
                . ' AND NOT tx_templavoilaplus_flex LIKE ' . $escapedDest . ' '
                . ' AND uid IN ('
                . implode(',', $this->getAllSubPages($this->pObj->id)) . ')';

            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'uid, title',
                'pages',
                $condition
            );
            if (count($rows) > 0) {
                // build message for simulation
                $mbuffer = 'Affects ' . count($rows) . ': <ul>';
                foreach ($rows as $row) {
                    $mbuffer .= '<li>' . htmlspecialchars($row['title']) . ' (uid: ' . (int)$row['uid'] . ')</li>';
                }
                $mbuffer .= '</ul>';
                $message = new FlashMessage($mbuffer, '', FlashMessage::INFO);
                $this->getFlashMessageQueue()->enqueue($message);
                unset($mbuffer);
                //really do it
                if (!GeneralUtility::_GP('simulateField')) {
                    $escapedSource = $this->getDatabaseConnection()->fullQuoteStr(GeneralUtility::_GP('sourceField'), 'pages');
                    $escapedDest = $this->getDatabaseConnection()->fullQuoteStr(GeneralUtility::_GP('destinationField'), 'pages');
                    $this->getDatabaseConnection()->admin_query('
						UPDATE pages
						SET tx_templavoilaplus_flex = REPLACE(tx_templavoilaplus_flex, ' . $escapedSource . ', ' . $escapedDest . ')
						WHERE ' . $condition . '
					');
                    $message = new FlashMessage('DONE', '', FlashMessage::OK);
                    $this->getFlashMessageQueue()->enqueue($message);
                }
            } else {
                $message = new FlashMessage('Nothing to do, can´t find something to replace.', '', FlashMessage::ERROR);
                $this->getFlashMessageQueue()->enqueue($message);
            }
        }

        return $buffer;
    }

    /**
     * @return string
     */
    protected function showForm()
    {
        $message = new FlashMessage(
            'This action can affect ' . count($this->getAllSubPages($this->pObj->id)) . ' pages, please ensure, you know what you do!, Please backup your TYPO3 Installation before running that wizard.',
            '',
            FlashMessage::WARNING
        );
        $this->getFlashMessageQueue()->enqueue($message);

        $buffer .= '<form action="' . $this->getLinkModuleRoot() . '"><div id="formFieldContainer">';
        $options = $this->getDSFieldOptionCode();
        $buffer .= $this->addFormField('sourceField', null, 'select_optgroup', $options);
        $buffer .= $this->addFormField('destinationField', null, 'select_optgroup', $options);
        $buffer .= $this->addFormField('simulateField', 1, 'checkbox');
        $buffer .= $this->addFormField('executeRename', 1, 'hidden');
        $buffer .= $this->addFormField('submit', null, 'submit');
        $buffer .= '</div></form>';
        $this->getKnownPageDS();

        return $buffer;
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $type
     * @param array $options
     *
     * @return string
     */
    protected function addFormField($name, $value = '', $type = 'text', $options = array())
    {
        if ($value === null) {
            $value = GeneralUtility::_GP($name);
        }
        switch ($type) {
            case 'checkbox':
                if (GeneralUtility::_GP($name) || $value) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }

                return '<div id="form-line-0">'
                . '<label for="' . $name . '" style="width:200px;display:block;float:left;">' . $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:field_' . $name) . '</label>'
                . '<input type="checkbox" id="' . $name . '" name="' . $name . '" ' . $checked . ' value="1">'
                . '</div>';
                break;
            case 'submit':
                return '<div id="form-line-0">'
                . '<input type="submit" id="' . $name . '" name="' . $name . '" value="' . $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:field_' . $name) . '">'
                . '</div>';
                break;
            case 'hidden':
                return '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '">';
                break;
            case 'select_optgroup':
                $buffer = '';
                foreach ($options as $optgroup => $options) {
                    $buffer .= '<optgroup label="' . $optgroup . '">';
                    foreach ($options as $option) {
                        if ($value === $option) {
                            $buffer .= '<option selected>' . htmlspecialchars($option) . '</option>';
                        } else {
                            $buffer .= '<option>' . htmlspecialchars($option) . '</option>';
                        }
                    }
                    $buffer .= '</optgroup>';
                }

                return '<div id="form-line-0">'
                . '<label style="width:200px;display:block;float:left;" for="' . $name . '">' . $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:field_' . $name) . '</label>'
                . '<select id="' . $name . '" name="' . $name . '">' . $buffer . '</select>'
                . '</div>';
                break;
            case 'text':
            default:
                return '<div id="form-line-0">'
                . '<label for="' . $name . '">' . $this->getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/locallang.xlf:field_' . $name) . '</label>'
                . '<input type="text" id="' . $name . '" name="' . $name . '" value="' . htmlspecialchars($value) . '">'
                . '</div>';
        }
    }

    /**
     * @return string
     */
    protected function getLinkModuleRoot()
    {
        $urlParams = $this->pObj->MOD_SETTINGS;
        $urlParams['id'] = $this->pObj->id;

        return GeneralUtility::implodeArrayForUrl(
            '',
            $urlParams
        );
    }

    /**
     * @return mixed
     */
    protected function getKnownPageDS()
    {
        $dsRepo = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Domain\Repository\DataStructureRepository::class);

        return $dsRepo->getDatastructuresByScope(1);
    }

    /**
     * @return array
     */
    protected function getDSFieldOptionCode()
    {
        $dsList = $this->getKnownPageDS();
        $return = array();
        foreach ($dsList as $ds) {
            /** @var $ds \Ppi\TemplaVoilaPlus\Domain\Model\AbstractDataStructure */
            $return[$ds->getLabel()] = array();
            $t = $ds->getDataprotArray();
            foreach (array_keys($t['ROOT']['el']) as $field) {
                $return[$ds->getLabel()][] = $field;
            }
        }

        return $return;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return TemplaVoilaUtility::getDatabaseConnection();
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return FlashMessageQueue
     */
    protected function getFlashMessageQueue()
    {
        if (!isset($this->flashMessageQueue)) {
            /** @var FlashMessageService $service */
            $service = GeneralUtility::makeInstance(FlashMessageService::class);
            $this->flashMessageQueue = $service->getMessageQueueByIdentifier();
        }
        return $this->flashMessageQueue;
    }
}
