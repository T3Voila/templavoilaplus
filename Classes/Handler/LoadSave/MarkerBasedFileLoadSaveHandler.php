<?php

declare(strict_types=1);

namespace Tvp\TemplaVoilaPlus\Handler\LoadSave;

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

use Symfony\Component\Finder\SplFileInfo;

class MarkerBasedFileLoadSaveHandler extends AbstractFileLoadSaveHandler implements LoadSaveHandlerInterface
{
    public static $identifier = 'TVP\LoadSaveHandler\MarkerBasedFile';

    protected $fileExtension = '.html';

    public function load(SplFileInfo $file): array
    {
        $configuration = [
            'tvp-beLayout' => [
                'meta' => [
                    'name' => $file->getBasename(),
                    'renderer' => \Tvp\TemplaVoilaPlus\Handler\Render\MarkerBasedRenderHandler::$identifier,
                    'template' => $file->getFilename(),
                ],
            ],
        ];

        return $configuration;
    }

    public function save(SplFileInfo $store, array $data): void
    {
        throw new \Exception('Not Yet Implemented');
    }

    public function delete(SplFileInfo $store): void
    {
        throw new \Exception('Not Yet Implemented');
    }
}
