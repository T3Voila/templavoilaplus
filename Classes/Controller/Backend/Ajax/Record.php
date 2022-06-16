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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Record extends AbstractResponse
{
    /**
     */
    public function switchVisibility(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getParsedBody();
        $table = $parameters['table'];
        $uid = (int)$parameters['uid'];

        // Check if record type have a visibility field
        if (!isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'])) {
            return new JsonResponse(
                [
                    'error' => 'Visibility not changeable',
                ],
                400 /* Bad request */
            );
        }

        // Check if record exists
        $record = BackendUtility::getRecord($table, (int)$uid);
        if (!$record) {
            return new JsonResponse(
                [
                    'error' => 'Record not found',
                ],
                400 /* Bad request */
            );
        }

        $visibilityField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];

        $dataHandlerData = [
            $table => [
                $uid => [
                    $visibilityField => (int)(!(bool)$record[$visibilityField]),
                ],
            ],
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataHandlerData, []);
        $dataHandler->process_datamap();

        return new JsonResponse([
            'uid' => $uid,
            'nodeHtml' => '',
        ]);
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function editform(ServerRequestInterface $request): ResponseInterface
    {
        $table = $request->getQueryParams()['table'];
        $uid = (int)$request->getQueryParams()['uid'];

        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $uri = $uriBuilder->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    $table => [
                        $uid => 'edit',
                    ],
                ],
                'returnUrl' => (string)$uriBuilder->buildUriFromRoute('templavoilaplus_modalhelper_close'),
            ]
        );

        return new HtmlResponse(
            '<iframe width="100%" height="100%" class="t3js-modal-iframe" src="' . $uri . '" />'
        );
    }
}
