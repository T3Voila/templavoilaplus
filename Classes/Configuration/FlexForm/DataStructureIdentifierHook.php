<?php
declare(strict_types = 1);
namespace Ppi\TemplaVoilaPlus\Configuration\FlexForm;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Domain\Model\DataStructure;
use Ppi\TemplaVoilaPlus\Domain\Model\MappingConfiguration;
use Ppi\TemplaVoilaPlus\Utility\ApiHelperUtility;

class DataStructureIdentifierHook
{
    public function parseDataStructureByIdentifierPreProcess(array $identifier)
    {
        $dataStructure = ''; // I know, wrong naming, but thats it insie FlexFormTools
        if ($identifier['type'] === 'combinedMappingIdentifier') {
            if (empty($identifier['tableName']) || empty($identifier['uid']) || empty($identifier['fieldName'])) {
                throw new \RuntimeException(
                    'Incomplete "record" based identifier: ' . json_encode($identifier),
                    1478113873
                );
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($identifier['tableName']);
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $dataStructure = $queryBuilder
                ->select($identifier['fieldName'])
                ->from($identifier['tableName'])
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($identifier['uid'], \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetchColumn(0);
            try {
                $mappingConfiguration = ApiHelperUtility::getMappingConfiguration($dataStructure);
                $dataStructure = ApiHelperUtility::getDataStructure($mappingConfiguration->getCombinedDataStructureIdentifier());

                $dataStructure = $dataStructure->getDataStructureArray();
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                /** @TODO Do logging, if we cannot found the DS? */
            }
        }

        return $dataStructure;
    }
}
