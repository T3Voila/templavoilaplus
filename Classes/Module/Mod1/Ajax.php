<?php
namespace Ppi\TemplaVoilaPlus\Module\Mod1;

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

/**
 * Class 'Ajax' for module 1 of the 'templavoilaplus' extension.
 *
 * @author Nikolas Hagelstein <lists@shr-now.de>
 */
class Ajax
{
    /**
     * @var \Ppi\TemplaVoilaPlus\Service\ApiService
     */
    private $apiObj;

    /**
     * @return \Ajax
     */
    public function __construct()
    {
        $this->apiObj = GeneralUtility::makeInstance(\Ppi\TemplaVoilaPlus\Service\ApiService::class);
    }

    /**
     * Performs a move action for the requested element
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function moveRecord(ServerRequestInterface $request, ResponseInterface $response)
    {
        $postParams = $request->getParsedBody();
        $sourcePointer = $this->apiObj->flexform_getPointerFromString($postParams['source']);
        $destinationPointer = $this->apiObj->flexform_getPointerFromString($postParams['destination']);

        $this->apiObj->moveElement($sourcePointer, $destinationPointer);

        $response->getBody()->write(json_encode([]));
        return $response;
    }

    /**
     * Performs a move action for the requested element
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function unlinkRecord(ServerRequestInterface $request, ResponseInterface $response)
    {
        $postParams = $request->getParsedBody();
        $unlinkPointer = $this->apiObj->flexform_getPointerFromString($postParams['unlink']);

        $this->apiObj->unlinkElement($unlinkPointer);

        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
