<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Add temporary tables.
 */
class CreateTempTables implements
    Migration,
    OrderedMigrationInterface,
    ConnectionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const ORDER = 10;

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
            $tempTableName = $tableData['tempTable'];
            /** @var Column $idColumn */
            $idColumn = $tableData['id'];

            if ($schema->hasTable($tempTableName)) {
                return;
            }
            $table = $schema->createTable($tempTableName);
            $table->addColumn('id', $idColumn->getType()->getName());
            $table->addColumn('serialized_data', Types::JSON, ['notnull' => false]);
        }
    }
}
