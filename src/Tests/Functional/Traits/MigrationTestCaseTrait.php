<?php

namespace Momocode\ShopwareBase\Tests\Functional\Traits;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use PDO;

/**
 * @author Moritz Müller <moritz@momocode.de>
 */
trait MigrationTestCaseTrait
{
    /**
     * @param $tableName
     * @param $columnName
     *
     * @return bool
     *
     * @throws DBALException
     */
    public function checkAttribute($tableName, $columnName)
    {
        $query = "SELECT 1 
                  FROM information_schema.COLUMNS 
                  WHERE TABLE_NAME = '$tableName' 
                  AND COLUMN_NAME = '$columnName'";

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        return (bool) $connection->executeQuery($query)->fetch(PDO::FETCH_COLUMN);
    }
}
