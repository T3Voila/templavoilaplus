<?php
namespace Ppi\TemplaVoilaPlus\Module\Cm1;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

/**
 * Ajax class for displaying content form a file
 */
class Ajax
{
    /**
     * Return the content of the current "displayFile"
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getDisplayFileContent(ServerRequestInterface $request, ResponseInterface $response)
    {
        $session = TemplaVoilaUtility::getBackendUser()->getSessionData($request->getQueryParams()['key']);
        $content = GeneralUtility::getUrl(GeneralUtility::getFileAbsFileName($session['displayFile']));

        $response->getBody()->write(json_encode($content));
        return $response;
    }
}
