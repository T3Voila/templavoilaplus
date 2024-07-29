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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Trash extends AbstractResponse
{
    /** @var IconFactory */
    protected $iconFactory;

    public function __construct()
    {
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

        $unusedElements = $this->trash2fluid((int)$parameters['pid']);

        $view = $this->getFluidTemplateObject('EXT:templavoilaplus/Resources/Private/Templates/Backend/Ajax/Trash.html', $request, $this->getSettings());
        $view->assign('unusedElements', $unusedElements);

        return new HtmlResponse($view->render());
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function link(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();
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
            return new JsonResponse([
                'uid' => $result,
                'nodeHtml' => $this->record2html($sourceTable, $result, $request),
                'trash' => $this->trash2fluid((int)$parameters['pid']),
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
    public function unlink(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);

        $result = $processingService->unlinkElement(
            $parameters['sourcePointer'] ?? ''
        );

        return new JsonResponse([
            'result' => $result,
            'trash' => $this->trash2fluid((int)$parameters['pid']),
        ]);
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array */
        $parameters = $request->getParsedBody();
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);

        // Delete
        $cmdArray = [];
        $cmdArray['tt_content'][(int)$parameters['uid']]['delete'] = 1;
        // Element UID should always be that of the online version here...

        /** @var DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        $tce->start([], $cmdArray);
        $tce->process_cmdmap();

        return new JsonResponse([
            'uid' => (int)$parameters['uid'],
            'trash' => $this->trash2fluid((int)$parameters['pid']),
        ]);
    }

    protected function trash2fluid(int $pid): array
    {
        /** @var array */
        $pageRecord = BackendUtility::getRecord('pages', $pid);
        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);
        $nodeTree = $processingService->getNodeWithTree('pages', $pageRecord);
        $unusedElements = $processingService->getUnusedElements($pageRecord, $nodeTree['usedElements']);

        $trash = [
            'totalCount' => 0,
            'elements' => [],
        ];

        foreach ($unusedElements as $element) {
            if (is_array($element)) {
                $trash['totalCount']++;
                $trash['elements'][] = [
                    'uid' => $element['uid'],
                    'icon' => $this->iconFactory->getIconForRecord('tt_content', $element, Icon::SIZE_DEFAULT)->render(),
                    'title' => GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle('tt_content', $element), 50),
                ];
            }
        }

        return $trash;
    }
}
