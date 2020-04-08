<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Handler\Place;

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
use TYPO3\CMS\Core\Utility\StringUtility;

use Ppi\TemplaVoilaPlus\Domain\Model\TemplatePlace;
use Ppi\TemplaVoilaPlus\Domain\Model\TemplateYamlConfiguration;

class TemplateYamlPlaceHandler
    extends AbstractYamlPlaceHandler
    implements TemplatePlaceHandlerInterface
{
    public const NAME = 'templavoilaplus_handler_place_template_yaml';

    /**
     * @var array|null Runtime cache for loaded template configurations
     */
    protected $templateConfigurations;

    protected $configurationClassName = TemplateYamlConfiguration::class;

    public function __construct(TemplatePlace $place)
    {
        parent::__construct($place);
    }
}
