<?php

namespace Tvp\TemplaVoilaPlus\Tests\Utility;

use Prophecy\PhpUnit\ProphecyTrait;
use Tvp\TemplaVoilaPlus\Exception\InvalidIdentifierException;
use Tvp\TemplaVoilaPlus\Exception\MissingPlacesException;
use Tvp\TemplaVoilaPlus\Utility\ApiHelperUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ApiHelperUtilityTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();
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
        $extensionManagerProphecy = $this->prophesize(ExtensionConfiguration::class);
        $extensionManagerProphecy->get('templavoilaplus')->shouldBeCalled()->willReturn(
            [
            ]
        );
        GeneralUtility::addInstance(ExtensionConfiguration::class, $extensionManagerProphecy->reveal());
        $this->expectException(MissingPlacesException::class);
        $result = ApiHelperUtility::getMappingConfiguration('thisDoesntExist:thisDoesntExistEither');
    }
}
