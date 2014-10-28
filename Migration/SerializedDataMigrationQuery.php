<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class SerializedDataMigrationQuery extends ParametrizedMigrationQuery
{
    /** @var Schema */
    protected $schema;

    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    /**
     * @param Schema               $schema
     * @param EntityMetadataHelper $metadataHelper
     */
    public function __construct(Schema $schema, EntityMetadataHelper $metadataHelper)
    {
        $this->schema         = $schema;
        $this->metadataHelper = $metadataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->runSerializedData($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->runSerializedData($logger, true);

        return $logger->getMessages();
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function runSerializedData(LoggerInterface $logger, $dryRun = false)
    {
        $entities            = $this->getConfigurableEntitiesData($logger);
        $hasSchemaChanges    = false;
        $toSchema            = clone $this->schema;
        $updateConfigQueries = [];
        foreach ($entities as $entityClass => $configData) {
            $config = $configData['data'];
            if (isset($config['extend']['is_extend']) && $config['extend']['is_extend'] == true) {
                $table = $toSchema->getTable($this->metadataHelper->getTableNameByEntityClass($entityClass));
                if (!$table->hasColumn('serialized_data')) {
                    $hasSchemaChanges = true;
                    $table->addColumn(
                        'serialized_data',
                        'array',
                        [
                            'notnull' => false
                        ]
                    );
                    $time = new \DateTime();

                    $updateConfigQueries[] = sprintf(
                        "DELETE FROM oro_entity_config_field WHERE entity_id = %d AND field_name = '%s'",
                        $configData['id'],
                        'serialized_data'
                    );

                    $updateConfigQueries[] = sprintf(
                        "INSERT INTO oro_entity_config_field" .
                        "  (entity_id, field_name, type, created, updated, mode, data)" .
                        "  values (%d, '%s', '%s', '%s', '%s', '%s', '%s')",
                        $configData['id'],
                        'serialized_data',
                        'array',
                        $time->format('Y-m-d'),
                        $time->format('Y-m-d'),
                        'hidden',
                        $this->connection->convertToDatabaseValue(
                            [
                                'entity'    => ['label' => 'data'],
                                'extend'    => ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => false],
                                'datagrid'  => ['is_visible' => false],
                                'merge'     => ['display' => false],
                                'dataaudit' => ['auditable' => false]
                            ],
                            'array'
                        )
                    );
                }
            }
        }

        if ($hasSchemaChanges) {
            $comparator = new Comparator();
            $platform   = $this->connection->getDatabasePlatform();
            $schemaDiff = $comparator->compare($this->schema, $toSchema);
            $queries    = $schemaDiff->toSql($platform);
            $queries    = array_merge($queries, $updateConfigQueries);
            foreach ($queries as $query) {
                $this->logQuery($logger, $query);
                if (!$dryRun) {
                    $this->connection->executeQuery($query);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     *  key - class name
     *  value - entity config array data
     */
    protected function getConfigurableEntitiesData(LoggerInterface $logger)
    {
        $sql = sprintf(
            "SELECT id, class_name, data FROM oro_entity_config WHERE mode = '%s'",
            ConfigModelManager::MODE_DEFAULT
        );
        $this->logQuery($logger, $sql);

        $result = [];
        $rows   = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[$row['class_name']] = [
                'id'   => $row['id'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }
}
