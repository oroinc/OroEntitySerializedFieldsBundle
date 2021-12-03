<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Move serialized_data type array from original table to serialized_data type json in temp table.
 */
class MoveSerializedDataToTempTables implements
    Migration,
    OrderedMigrationInterface,
    ConnectionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const ORDER = CreateTempTables::ORDER + 10;
    private const CHUNK_SIZE = 1000;

    protected Connection $connection;

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOrder()
    {
        return self::ORDER;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $helper = $this->container->get('oro_serialized_fields.migration.serialized_columns_holder_helper');
        foreach ($helper->getTablesInfo($this->connection, $schema) as $tableData) {
            $tableName = $tableData['table'];
            $tempTableName = $tableData['tempTable'];
            $idColumnTypeName = $tableData['id']->getType()->getName();

            $emptyArrayValue = $this->connection->convertToDatabaseValue([], Types::ARRAY);
            $nullArrayValue = $this->connection->convertToDatabaseValue(null, Types::ARRAY);
            $emptyArrayValues = [$emptyArrayValue, $nullArrayValue];
            $count = $this->connection
                ->executeQuery(
                    "SELECT COUNT(*) FROM $tableName WHERE serialized_data NOT IN(?)",
                    [$emptyArrayValues],
                    [Connection::PARAM_STR_ARRAY]
                )
                ->fetchOne();

            $pages = ceil($count / self::CHUNK_SIZE);
            for ($page = 0; $page < $pages; $page++) {
                $builder = $this->connection->createQueryBuilder();
                $statement = $builder
                    ->select(['u.id', 'u.serialized_data'])
                    ->from($tableName, 'u')
                    ->orderBy('u.id')
                    ->andWhere($builder->expr()->notIn('u.serialized_data', ':emptyArray'))
                    ->setParameter('emptyArray', $emptyArrayValues, Connection::PARAM_STR_ARRAY)
                    ->setMaxResults(self::CHUNK_SIZE)
                    ->setFirstResult($page * self::CHUNK_SIZE)
                    ->execute();
                if (!$statement) {
                    throw new QueryException(sprintf('Failed query : "%s" ', $builder->getSQL()));
                }
                $data = $statement->fetchAllAssociative();

                $parameters = [];
                foreach ($data as $row) {
                    $parameters[] = $row['id'];
                    $serializedData = $this->connection->convertToPHPValue($row['serialized_data'], Types::ARRAY);
                    // Convert \DateTime object to string in ATOM format applicable for storing in JSON
                    foreach ($tableData['datetimeFields'] as $datetimeField) {
                        if ($serializedData[$datetimeField] instanceof \DateTimeInterface) {
                            $serializedData[$datetimeField] = $serializedData[$datetimeField]->format(\DateTime::ATOM);
                        }
                    }
                    $parameters[] = $serializedData;
                }
                $rowsCount = count($data);

                $insert = "INSERT INTO $tempTableName (id, serialized_data) VALUES";
                $insert .= ' ' . rtrim(str_repeat('(?, ?), ', $rowsCount), ', ');
                $this->connection->executeQuery(
                    $insert,
                    $parameters,
                    array_merge(...array_fill(0, $rowsCount, [$idColumnTypeName, Types::JSON]))
                );
            }

            $this->connection->executeQuery("UPDATE $tableName SET serialized_data = NULL");
            $this->connection->executeQuery("CREATE INDEX {$tempTableName}_id_idx ON $tempTableName (id)");
        }
    }
}
