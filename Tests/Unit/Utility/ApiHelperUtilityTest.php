<?php

namespace Tvp\TemplaVoilaPlus\Tests\Utility;

use Tvp\TemplaVoilaPlus\Exception\InvalidIdentifierException;
use Tvp\TemplaVoilaPlus\Exception\MissingPlacesException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ApiHelperUtilityTest extends UnitTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        defined('TYPO3_version') or define('TYPO3_version', '11.5.0');
        $this->resetSingletonInstances = true;
    }

    /**
     * @test
     */
    public function getMappingConfigurationFailsWithEmptyIdentifier()
    {
        $this->expectException(InvalidIdentifierException::class);
        $result = ApiHelperUtility::getMappingConfiguration('');
    }

    /**
     * @test
     */
    public function getMappingConfigurationFailsWithUnknownPlaceInIdentifier()
    {
        $this->expectException(MissingPlacesException::class);
        $result = ApiHelperUtility::getMappingConfiguration('thisDoesntExist:thisDoesntExistEither');
    }
}
