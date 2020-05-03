<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Handler\LoadSave;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Domain\Model\Place;

class MarkerBasedFileLoadSaveHandler
    extends AbstractFileLoadSaveHandler
    implements LoadSaveHandlerInterface
{
    static public $identifier = 'TVP\LoadSaveHandler\MarkerBasedFile';

    protected $fileExtension = '.html';

    public function load(\Symfony\Component\Finder\SplFileInfo $file): array
    {
        $configuration = [
            'tvp-beLayout' => [
                'meta' => [
                    'name' => $file->getBasename(),
                    'renderer' => \Ppi\TemplaVoilaPlus\Handler\Render\MarkerBasedRenderHandler::$identifier,
                    'template' => $file->getFilename(),
                ],
            ],
        ];

        return $configuration;
    }

    public function save()
    {
        throw new \Exception('Not Yet Implemented');
    }

    public function delete()
    {
        throw new \Exception('Not Yet Implemented');
    }
}
