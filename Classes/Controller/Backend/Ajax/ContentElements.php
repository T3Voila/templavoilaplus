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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use Tvp\TemplaVoilaPlus\Service\ApiService;
use Tvp\TemplaVoilaPlus\Service\ProcessingService;

class ContentElements extends AbstractResponse
{
    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function insert(ServerRequestInterface $request): ResponseInterface
    {
        /** @var ApiService */
        $apiService = GeneralUtility::makeInstance(ApiService::class);

        $parameters = $request->getParsedBody();

        /** @TODO LanguageHandling! */
        /** @TODO Should we hide every element on insert as it isn't configured yet? */
        $result = $apiService->insertElement(
            $parameters['destinationPointer'] ?? '',
            $parameters['elementRow'] ?? []
        );

        return new JsonResponse([
            'uid' => $result,
            'nodeHtml' => $this->record2html('tt_content', $result)
        ]);
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function reload(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getParsedBody();

        /** @TODO $parameters['table'] */
        return new JsonResponse([
            'nodeHtml' => $this->record2html('tt_content', (int)$parameters['uid'])
        ]);
    }

    protected function record2html(string $table, int $uid): string
    {
        $row = BackendUtility::getRecord($table, $uid);

        /** @var ProcessingService */
        $processingService = GeneralUtility::makeInstance(ProcessingService::class);
        $nodeTree = $processingService->getNodeWithTree($table, $row);

        $view = $this->getFluidTemplateObject('EXT:templavoilaplus/Resources/Private/Templates/Backend/Ajax/InsertNode.html');
        $view->assign('nodeTree', $nodeTree);

        /** @TODO better handle this with an configuration object */
        /** @TODO Duplicated more or less from PageLayoutController */
        $view->assign(
            'configuration',
            [
                'allAvailableLanguages' => TemplaVoilaUtility::getAvailableLanguages(0, true, true, []),
                'lllFile' => 'LLL:EXT:templavoilaplus/Resources/Private/Language/Backend/PageLayout.xlf',
                'userSettings' => TemplaVoilaUtility::getBackendUser()->uc['templavoilaplus'] ?? [],
            ]
        );

        return $view->render();
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function move(ServerRequestInterface $request): ResponseInterface
    {
        /** @var ApiService */
        $apiService = GeneralUtility::makeInstance(ApiService::class);

        $parameters = $request->getParsedBody();

        $result = $apiService->moveElement(
            $parameters['sourcePointer'] ?? '',
            $parameters['destinationPointer'] ?? ''
        );

        return new JsonResponse([$result]);
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function remove(ServerRequestInterface $request): ResponseInterface
    {
        $apiService = GeneralUtility::makeInstance(ApiService::class);

        $parameters = $request->getParsedBody();

        $result = $apiService->deleteElement(
            $parameters['sourcePointer'] ?? ''
        );

        return new JsonResponse([$result]);
    }
}
