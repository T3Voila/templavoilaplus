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
use Tvp\TemplaVoilaPlus\Core\Http\JsonResponse;
use Tvp\TemplaVoilaPlus\Utility\TemplaVoilaUtility;

class UserSettings
{
    public function enableDarkMode(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = TemplaVoilaUtility::getBackendUser();

        $userconfig = ($backendUser->uc['templavoilaplus'] ?? []);
        $userconfig['enableDarkMode'] = (bool)$request->getQueryParams()['enable'];
        $backendUser->uc['templavoilaplus'] = $userconfig;
        $backendUser->writeUC();
        return new JsonResponse(['success' => true]);
    }

    public function setClipboardMode(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = TemplaVoilaUtility::getBackendUser();

        $userconfig = ($backendUser->uc['templavoilaplus'] ?? []);
        $userconfig['clipboardMode'] = $request->getQueryParams()['mode'];
        $backendUser->uc['templavoilaplus'] = $userconfig;
        $backendUser->writeUC();
        return new JsonResponse(['success' => true]);
    }
}
