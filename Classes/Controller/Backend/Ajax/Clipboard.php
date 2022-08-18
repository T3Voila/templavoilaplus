<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Controller\Backend\Ajax;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tvp\TemplaVoilaPlus\Core\Http\HtmlResponse;
use Tvp\TemplaVoilaPlus\Core\Http\JsonResponse;
use Tvp\TemplaVoilaPlus\Service\ProcessingService;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use TYPO3\CMS\Backend\Clipboard\Clipboard as CoreClipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Clipboard extends AbstractResponse
{
    /** @var CoreClipboard */
    protected $typo3Clipboard;

    /** @var IconFactory */
    protected $iconFactory;

    public function __construct()
    {
        $this->typo3Clipboard = GeneralUtility::makeInstance(CoreClipboard::class);
        $this->typo3Clipboard->initializeClipboard();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function load(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();

        /**
         * @TODO Needs rewrite to $this->typo3Clipboard->getClipboardData('tt_content') which isn't available in TYPO3 v8LTS
         */
        $clipboardData = $this->clipboard2fluid();

        /** @TODO Only show tt_content at the moment */
        foreach ($clipboardData as $table => $tableEntries) {
            if (
                $table !== '__totalCount__'
                && $table !== 'tt_content'
            ) {
                unset($clipboardData[$table]);
            }
        }

        $view = $this->getFluidTemplateObject('EXT:templavoilaplus/Resources/Private/Templates/Backend/Ajax/Clipboard.html', $this->getSettings());
        $view->assign('clipboardData', $clipboardData);

        return new HtmlResponse($view->render());
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function action(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();
        $result = null;

        switch ($parameters['mode']) {
            case 'copy':
                $result = $this->copy($parameters);
                break;
            case 'move':
                $result = $this->move($parameters);
                break;
            case 'reference':
                $result = $this->reference($parameters);
                break;
            default:
                // Empty by design
                break;
        }

        if ($result) {
            return new JsonResponse([
                'uid' => $result,
                'nodeHtml' => $this->record2html('tt_content', $result),
                'clipboard' => $this->clipboard2fluid(),
            ]);
        } else {
            return new JsonResponse(
                [
                    'error' => $result
                ],
                400 /* Bad request */
            );
        }
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function release(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();
        $result = null;

        $result = $this->removeFromClipboard(
            $parameters['table'],
            (int) $parameters['uid']
        );

        if ($result) {
            return new JsonResponse([
                'uid' => $result,
                'nodeHtml' => $this->record2html('tt_content', (int) $parameters['uid']),
                'clipboard' => $this->clipboard2fluid(),
            ]);
        } else {
            return new JsonResponse(
                [
                    'error' => $result
                ],
                400 /* Bad request */
            );
        }
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function add(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();
        $result = null;

        $result = $this->addToClipboard(
            $parameters['table'],
            (int) $parameters['uid']
        );

        if ($result) {
            return new JsonResponse([
                'uid' => $result,
                'nodeHtml' => $this->record2html('tt_content', (int) $parameters['uid']),
                'clipboard' => $this->clipboard2fluid(),
            ]);
        } else {
            return new JsonResponse(
                [
                    'error' => $result
                ],
                400 /* Bad request */
            );
        }
    }

    /**
     * @param array $parameters the current request
     * @return int|bool The new uid or FALSE
     */
    protected function copy(array $parameters)
    {
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);

        return $processingService->copyElement(
            $parameters['destinationPointer'] ?? '',
            $parameters['sourceTable'] ?? '',
            (int) $parameters['sourceUid'] ?? 0
        );
    }

    /**
     * @param array $parameters the current request
     * @return int|bool The new uid or FALSE
     */
    protected function reference(array $parameters)
    {
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);
        $sourceUid = (int) $parameters['sourceUid'] ?? 0;
        $sourceTable = $parameters['sourceTable'] ?? '';

        $result = $processingService->referenceElement(
            $parameters['destinationPointer'] ?? '',
            $sourceTable,
            $sourceUid
        );

        if ($result) {
            $this->removeFromClipboard($sourceTable, $sourceUid);
            return (int) $parameters['sourceUid'];
        }
        return false;
    }

    /**
     * @param array $parameters the current request
     * @return int The uid or FALSE
     */
    protected function move(array $parameters)
    {
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);
        $sourceUid = (int) $parameters['sourceUid'] ?? 0;
        $sourceTable = $parameters['sourceTable'] ?? '';

        if ($sourceUid <= 0) {
            return false;
        }
        if (!isset($GLOBALS['TCA'][$sourceTable])) {
            return false;
        }

        // We should only move tt_content IMHO
        if ($sourceTable !== 'tt_content') {
            return false;
        }

        // Find in clipboard
        $dataInClipboard = $this->findInClipboard($sourceTable, $sourceUid);
        if ($dataInClipboard === 0) {
            return false;
        }

        // Find record
        $record = BackendUtility::getRecordWSOL($sourceTable, $sourceUid);
        if (!$record) {
            return false;
        }

        // Find orig position (copied by ListMode?)
        $sourcePointer = $processingService->findRecordsSourcePointer($record);

        $result = $processingService->moveElement(
            $sourcePointer,
            $parameters['destinationPointer'] ?? ''
        );

        if ($result) {
            $this->removeFromClipboard($sourceTable, $sourceUid);
            return (int) $parameters['sourceUid'];
        }

        return false;
    }

    protected function removeFromClipboard(string $table, int $uid): bool
    {
        $key = $table . '|' . $uid;

        foreach ($this->typo3Clipboard->clipData as $clipBoardName => $clipBoardData) {
            if (isset($clipBoardData['el'])) {
                foreach ($clipBoardData['el'] as $clipBoardElement => $value) {
                    if ($clipBoardElement === $key) {
                        $this->typo3Clipboard->setCmd(
                            [
                                'setP' => $clipBoardName,
                                'remove' => $key,
                            ]
                        );
                        $this->typo3Clipboard->endClipboard();
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function addToClipboard(string $table, int $uid): bool
    {
        $key = $table . '|' . $uid;

        $this->typo3Clipboard->setCmd(
            [
                'setP' => 'tab_1',
                'el' => [
                    $key => 1, /** @TODO We could set this to a flexform pointer (like old TV) but the pointer won't get updated on moves etc. */
                ]
            ]
        );
        $this->typo3Clipboard->endClipboard();
        return true;
    }

    protected function findInClipboard(string $table, int $uid)
    {
        $key = $table . '|' . $uid;

        foreach ($this->typo3Clipboard->clipData as $clipBoardName => $clipBoardData) {
            if (isset($clipBoardData['el'])) {
                foreach ($clipBoardData['el'] as $clipBoardElement => $value) {
                    if ($clipBoardElement === $key) {
                        return $value;
                    }
                }
            }
        }

        return 0;
    }

    protected function clipboard2fluid(): array
    {
        $clipBoard = [
            '__totalCount__' => 0,
        ];

        foreach ($this->typo3Clipboard->clipData as $clipBoardName => $clipBoardData) {
            if (isset($clipBoardData['el'])) {
                foreach ($clipBoardData['el'] as $clipBoardElement => $value) {
                    [$table, $uid] = explode('|', $clipBoardElement);
                    if (!isset($clipBoard[$table])) {
                        $clipBoard[$table] = [
                            'label' => $GLOBALS['TCA'][$table]['ctrl']['title'],
                            'count' => 0,
                            'elements' => [],
                        ];
                    }

                    $record = BackendUtility::getRecordWSOL($table, (int)$uid);
                    if (is_array($record)) {
                        $clipBoard['__totalCount__']++;
                        $clipBoard[$table]['count']++;
                        $clipBoard[$table]['elements'][] = [
                            'uid' => $uid,
                            'icon' => $this->iconFactory->getIconForRecord($table, $record, Icon::SIZE_DEFAULT)->render(),
                            'title' => GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $record), 50),
                        ];
                    }
                }
            }
        }

        return $clipBoard;
    }
}
