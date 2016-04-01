<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Comparator;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
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
        $entities         = $this->getEntities($logger);
        $hasSchemaChanges = false;
        $toSchema         = clone $this->schema;
        foreach ($entities as $entityClass => $tableName) {
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
                            ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                            'entity'                          => [
                                'label' => 'oro.entity_serialized_fields.data.label'
                            ],
                            'extend'                          => [
                                'is_extend' => false,
                                'owner'     => ExtendScope::OWNER_CUSTOM
                            ],
                            'datagrid'                        => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                            'form'                            => ['is_enabled' => false],
                            'view'                            => ['is_displayable' => false],
                            'merge'                           => ['display' => false],
                            'dataaudit'                       => ['auditable' => false]
                        ]
                    ]
                );
            }
        }

        if ($hasSchemaChanges) {
            // Run schema related SQLs manually because this query run when diff is already calculated by schema tool
            $comparator = new Comparator();
            $platform   = $this->connection->getDatabasePlatform();
            $schemaDiff = $comparator->compare($this->schema, $toSchema);
            foreach ($schemaDiff->toSql($platform) as $query) {
                $this->logQuery($logger, $query);
                if (!$dryRun) {
                    $this->connection->query($query);
                }
            }
        }
    }

    /**
     * Returns entities that should be checked for 'serialized_data' field
     *
     * @param LoggerInterface $logger
     *
     * @return array [class name => table name]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getEntities(LoggerInterface $logger)
    {
        $result = [];

        $sql    = 'SELECT class_name, data FROM oro_entity_config WHERE mode = ?';
        $params = [ConfigModel::MODE_DEFAULT];
        $types  = [Type::STRING];

        $this->logQuery($logger, $sql, $params, $types);
        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $entityClass = $row['class_name'];
            $config      = $this->connection->convertToPHPValue($row['data'], Type::TARRAY);
            if (isset($config['extend']['is_extend'])
                && $config['extend']['is_extend']
                && $config['extend']['state'] === ExtendScope::STATE_ACTIVE
            ) {
                $tableName = isset($config['extend']['schema']['doctrine'][$entityClass]['table'])
                    ? $config['extend']['schema']['doctrine'][$entityClass]['table']
                    : $this->metadataHelper->getTableNameByEntityClass($entityClass);

                $result[$entityClass] = $tableName;
            }
        }

        // add entities that are being created in migrations, for example custom entities
        if ($this->schema instanceof ExtendSchema) {
            $options = $this->schema->getExtendOptions();
            foreach ($options as $key => $value) {
                if ($this->isTableOptions($key)
                    && isset($value['extend']['is_extend'])
                    && $value['extend']['is_extend']
                    && isset($value[ExtendOptionsManager::ENTITY_CLASS_OPTION])
                    && (
                        !isset($value[ExtendOptionsManager::MODE_OPTION])
                        || ConfigModel::MODE_DEFAULT === $value[ExtendOptionsManager::MODE_OPTION]
                    )
                ) {
                    $entityClass = $value[ExtendOptionsManager::ENTITY_CLASS_OPTION];
                    if (!isset($result[$entityClass])) {
                        $result[$entityClass] = $key;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    protected function isTableOptions($key)
    {
        if (false !== strpos($key, '!')) {
            // it is a column options
            return false;
        }
        if (0 === strpos($key, '_')) {
            // it is an auxiliary section, for example '_append'
            return false;
        }

        return true;
    }
}
