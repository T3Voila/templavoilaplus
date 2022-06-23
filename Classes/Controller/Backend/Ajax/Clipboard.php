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
        $parameters = $request->getParsedBody();

        /**
         * @TODO Needs rewrite to $this->typo3Clipboard->getClipboardData('tt_content') which isn't available in TYPO3 v8LTS
         */
        $clipboardData = $this->clipboard2fluid();

        $settings = [
            'configuration' => [
                'is8orNewer' => version_compare(TYPO3_version, '8.0.0', '>=') ? true : false,
                'is9orNewer' => version_compare(TYPO3_version, '9.0.0', '>=') ? true : false,
                'is10orNewer' => version_compare(TYPO3_version, '10.0.0', '>=') ? true : false,
                'is11orNewer' => version_compare(TYPO3_version, '11.0.0', '>=') ? true : false,
                'is12orNewer' => version_compare(TYPO3_version, '12.0.0', '>=') ? true : false,
            ],
        ];

        $view = $this->getFluidTemplateObject('EXT:templavoilaplus/Resources/Private/Templates/Backend/Ajax/Clipboard.html', $settings);
        $view->assign('clipboardData', $clipboardData);

        return new HtmlResponse($view->render());
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
                            'label' => $GLOBALS['TCA']['tt_content']['ctrl']['title'],
                            'count' => 0,
                            'elements' => [],
                        ];
                    }

                    $record = BackendUtility::getRecordWSOL($table, (int)$uid);
                    if (is_array($record)) {
                        $clipBoard['__totalCount__']++;
                        $clipBoard[$table]['count']++;
                        $clipBoard[$table]['elements'][] = [
                            'identifier' => $table . '|' . $uid,
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
