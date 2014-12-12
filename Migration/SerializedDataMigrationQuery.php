<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;

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
            if (isset($config['extend']['is_extend'])
                && $config['extend']['is_extend'] == true
                && $config['extend']['state'] == ExtendScope::STATE_ACTIVE
            ) {
                $tableName = isset($config['extend']['schema']['doctrine'][$entityClass]['table'])
                    ? $config['extend']['schema']['doctrine'][$entityClass]['table']
                    : $this->metadataHelper->getTableNameByEntityClass($entityClass);

                if (!$toSchema->hasTable($tableName)) {
                    continue;
                }

                $table = $toSchema->getTable($tableName);
                if (!$table->hasColumn('serialized_data')) {
                    $hasSchemaChanges = true;
                    $table->addColumn(
                        'serialized_data',
                        'array',
                        [
                            'notnull' => false
                        ]
                    );
                    $updateConfigQueries[] = [
                        "DELETE FROM oro_entity_config_field WHERE entity_id = :entityId AND field_name = :fieldName",
                        ['entityId' => $configData['id'], 'fieldName' => 'serialized_data'],
                        ['entityId' => Type::INTEGER, 'fieldName' => Type::STRING]
                    ];
                    $updateConfigQueries[] = [
                        "INSERT INTO oro_entity_config_field" .
                        "  (entity_id, field_name, type, created, updated, mode, data)" .
                        "  values (:entity_id, :field_name, :type, :created, :updated, :mode, :data)",
                        [
                            'entity_id'  => $configData['id'],
                            'field_name' => 'serialized_data',
                            'type'       => 'array',
                            'created'    => new \DateTime('now', new \DateTimeZone('UTC')),
                            'updated'    => new \DateTime('now', new \DateTimeZone('UTC')),
                            'mode'       => 'hidden',
                            'data'       => [
                                'entity'    => ['label' => 'data'],
                                'extend'    => ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => false],
                                'datagrid'  => ['is_visible' => false],
                                'merge'     => ['display' => false],
                                'dataaudit' => ['auditable' => false]
                            ]
                        ],
                        [
                            'entity_id'  => Type::INTEGER,
                            'field_name' => Type::STRING,
                            'type'       => Type::STRING,
                            'created'    => Type::DATETIME,
                            'updated'    => Type::DATETIME,
                            'mode'       => Type::STRING,
                            'data'       => Type::TARRAY
                        ]
                    ];
                }
            }
        }

        if ($hasSchemaChanges) {
            $comparator = new Comparator();
            $platform = $this->connection->getDatabasePlatform();
            $schemaDiff = $comparator->compare($this->schema, $toSchema);
            $queries = $schemaDiff->toSql($platform);

            foreach ($queries as $query) {
                $this->logQuery($logger, $query);
                if (!$dryRun) {
                    $this->connection->executeQuery($query);
                }
            }
            foreach ($updateConfigQueries as $query) {
                $this->logQuery($logger, $query[0], $query[1], $query[2]);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query[0], $query[1], $query[2]);
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
        $rows = $this->connection->fetchAll($sql);
        foreach ($rows as $row) {
            $result[$row['class_name']] = [
                'id'   => $row['id'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }
}
