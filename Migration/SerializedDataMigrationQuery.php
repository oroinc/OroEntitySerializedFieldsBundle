<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration;

use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Comparator;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
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
                if (isset($config['extend']['schema']['doctrine'][$entityClass]['table'])) {
                    $tableName = $config['extend']['schema']['doctrine'][$entityClass]['table'];
                } else {
                    $tableName = $this->metadataHelper->getTableNameByEntityClass($entityClass);
                }

                // Process only existing tables
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
                            'notnull'       => false,
                            OroOptions::KEY => [
                                ExtendOptionsManager::MODE_OPTION => ConfigModelManager::MODE_HIDDEN,
                                'entity'                          => ['label' => 'data'],
                                'extend'                          => [
                                    'is_extend' => false,
                                    'owner'     => ExtendScope::OWNER_CUSTOM
                                ],
                                'datagrid'                        => ['is_visible' => false],
                                'form'                            => ['is_enabled' => false],
                                'view'                            => ['is_displayable' => false],
                                'merge'                           => ['display' => false],
                                'dataaudit'                       => ['auditable' => false]
                            ]
                        ]
                    );
                }
            }
        }

        if ($hasSchemaChanges) {
            $comparator = new Comparator();
            $platform   = $this->connection->getDatabasePlatform();
            $schemaDiff = $comparator->compare($this->schema, $toSchema);
            $queries    = $schemaDiff->toSql($platform);
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
