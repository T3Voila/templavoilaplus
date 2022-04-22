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

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Tvp\TemplaVoilaPlus\Domain\Model\Place;

class YamlLoadSaveHandler extends AbstractFileLoadSaveHandler implements LoadSaveHandlerInterface
{
    public static $identifier = 'TVP\LoadSaveHandler\Yaml';

    protected $fileExtension = '.tvp.yaml';

    public function load(\Symfony\Component\Finder\SplFileInfo $file): array
    {
        $configuration = [];

        /** @var YamlFileLoader */
        $yamlFileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
        $yamlContent = $yamlFileLoader->load(GeneralUtility::fixWindowsFilePath($file->getPathname()));

        return $yamlContent;
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
