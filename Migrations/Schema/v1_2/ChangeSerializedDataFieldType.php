<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Change serialized_data column type to JSON in table and move data back from temp table.
 */
class ChangeSerializedDataFieldType implements
    Migration,
    OrderedMigrationInterface,
    ConnectionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const ORDER = MoveSerializedDataToTempTables::ORDER + 10;
    private const BATCH_SIZE = 10000;

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

            $this->changeSerializedColumnTypeToJson($tableName);
            $this->moveData($tableName, $tableData['tempTable'], $tableData['id']);
            $this->updateEntityConfig($queries, $tableData['entityConfig']);
        }
    }

    private function changeSerializedColumnTypeToJson(string $tableName): void
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $this->connection->executeQuery("ALTER TABLE $tableName MODIFY COLUMN serialized_data json");
        } else {
            $this->connection->executeQuery("
                ALTER TABLE $tableName
                ALTER COLUMN serialized_data TYPE jsonb
                USING serialized_data::jsonb
            ");
            $this->connection->executeQuery("ALTER TABLE $tableName ALTER serialized_data DROP DEFAULT");
            $this->connection->executeQuery("COMMENT ON COLUMN $tableName.serialized_data IS '(DC2Type:json)'");
        }
    }

    private function moveData(string $tableName, string $tempTableName, Column $idColumn)
    {
        $sourceIdColumn = $idColumn->getName();

        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $update = "UPDATE $tableName s
                JOIN $tempTableName t ON t.id = s.$sourceIdColumn
                SET s.serialized_data = t.serialized_data
                WHERE t.serialized_data IS NOT NULL";
        } else {
            $update = "UPDATE $tableName s
                SET serialized_data = t.serialized_data
                FROM $tempTableName t
                WHERE t.id = s.$sourceIdColumn";
        }

        // For non-numerical IDs execute query without batches
        if (!\in_array($idColumn->getType()->getName(), [Types::SMALLINT, Types::INTEGER, Types::BIGINT], true)) {
            $this->connection->executeQuery($update);

            return;
        }

        $min = $this->connection->executeQuery("SELECT MIN(id) FROM $tempTableName")->fetchOne();
        // There are no records in this table (all serialized_data is empty), skip
        if ($min === null) {
            return;
        }

        $max = $this->connection->executeQuery("SELECT MAX(id) FROM $tempTableName")->fetchOne();
        $stmt = $this->connection->prepare($update . ' AND t.id BETWEEN ? AND ?');
        while ($min <= $max) {
            $currentMax = $min + self::BATCH_SIZE;
            if ($currentMax > $max) {
                $currentMax = $max;
            }
            $stmt->executeQuery([$min, $currentMax]);

            $min = $currentMax + 1;
        }
    }

    private function updateEntityConfig(QueryBag $queries, array $entityConfig): void
    {
        $data = $entityConfig['data'];
        $exClass = $data['extend']['schema']['entity'];
        $fieldName = 'serialized_data';
        $data['extend']['schema']['doctrine'][$exClass]['fields'][$fieldName]['type'] = Types::JSON;

        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_entity_config SET data = :data WHERE id = :entityId',
            ['entityId' => $entityConfig['id'], 'data' => $data],
            ['entityId' => Types::INTEGER, 'data' => Types::ARRAY]
        ));
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_entity_config_field SET type = :type WHERE entity_id = :entityId and field_name = :fieldName',
            ['type' => Types::JSON, 'entityId' => $entityConfig['id'], 'fieldName' => $fieldName],
            ['type' => Types::STRING, 'entityId' => Types::INTEGER, 'fieldName' => Types::STRING]
        ));
    }
}
