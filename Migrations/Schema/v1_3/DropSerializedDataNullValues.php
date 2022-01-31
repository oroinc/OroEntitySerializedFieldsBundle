<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Removes serialized_data column's(JSON type) null valued items.
 */
class DropSerializedDataNullValues implements
    Migration,
    ConnectionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected Connection $connection;

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $helper = $this->container->get('oro_serialized_fields.migration.serialized_columns_holder_helper');

        foreach ($helper->getTablesInfo($this->connection, $schema) as $tableData) {
            $this->dropSerializedDataNullValuesForTable($tableData['table'], $tableData['fieldNames']);
        }
    }

    private function dropSerializedDataNullValuesForTable(string $tableName, array $fieldNames): void
    {
        if (!count($fieldNames)) {
            return;
        }

        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $caseWhens = [];
            foreach ($fieldNames as $fieldName) {
                $caseWhens[] = sprintf(
                    "CASE WHEN JSON_UNQUOTE(serialized_data->'$.%s') = 'null' THEN '$.%s' ELSE '$._' END",
                    $fieldName,
                    $fieldName
                );
            }

            $jsonRemovePart = sprintf('JSON_REMOVE(serialized_data, %s)', implode(',', $caseWhens));
            $this->connection->executeQuery("UPDATE $tableName SET serialized_data = $jsonRemovePart");
        } else {
            $this->connection->executeQuery(
                "UPDATE $tableName SET serialized_data = jsonb_strip_nulls(serialized_data)"
            );
        }
    }
}
