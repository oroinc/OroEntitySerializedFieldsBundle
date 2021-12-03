<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Add serialized_data field to extend entity tables in active state.
 */
class SerializedDataMigrationQuery extends ParametrizedMigrationQuery
{
    /**
     * @internal
     */
    const COLUMN_NAME = 'serialized_data';

    /** @var Schema */
    protected $schema;

    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    public function __construct(Schema $schema, EntityMetadataHelper $metadataHelper)
    {
        $this->schema = $schema;
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
     * @param bool $dryRun
     */
    protected function runSerializedData(LoggerInterface $logger, $dryRun = false)
    {
        $entities = $this->getEntities($logger);
        $hasSchemaChanges = false;
        $toSchema = clone $this->schema;
        $platform = $this->connection->getDatabasePlatform();

        $options = [
            'notnull' => false,
            OroOptions::KEY => [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                'entity' => [
                    'label' => 'oro.entity_serialized_fields.data.label'
                ],
                'extend' => [
                    'is_extend' => false,
                    'owner' => ExtendScope::OWNER_CUSTOM
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'dataaudit' => ['auditable' => false]
            ]
        ];
        if ($platform instanceof PostgreSQL94Platform) {
            $options['customSchemaOptions'] = ['jsonb' => true];
        }

        foreach ($entities as $tableName) {
            // Process only existing tables
            if (!$toSchema->hasTable($tableName)) {
                continue;
            }
            $table = $toSchema->getTable($tableName);

            if (!$table->hasColumn(static::COLUMN_NAME)) {
                $table->addColumn(static::COLUMN_NAME, Types::JSON, $options);
            } else { //Forcibly set options to existing field
                $serializedColumn = $table->getColumn(static::COLUMN_NAME);
                if ($serializedColumn->getType()->getName() !== Types::JSON) {
                    $serializedColumn->setType(Type::getType(Types::JSON));
                }
                $serializedColumn->setOptions($options);
            }
            $hasSchemaChanges = true;
        }

        if ($hasSchemaChanges) {
            $this->applySchemaChanges($toSchema, $platform, $logger, $dryRun);
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

        $sql = 'SELECT class_name, data FROM oro_entity_config WHERE mode = ?';
        $params = [ConfigModel::MODE_DEFAULT];
        $types = [Types::STRING];

        $this->logQuery($logger, $sql, $params, $types);
        $rows = $this->connection->fetchAllAssociative($sql, $params, $types);
        foreach ($rows as $row) {
            $entityClass = $row['class_name'];
            $config = $this->connection->convertToPHPValue($row['data'], Types::ARRAY);
            if (!empty($config['extend']['is_extend'])
                && $config['extend']['state'] === ExtendScope::STATE_ACTIVE
            ) {
                $tableName = $config['extend']['schema']['doctrine'][$entityClass]['table'] ??
                    $this->metadataHelper->getTableNameByEntityClass($entityClass);

                $result[$entityClass] = $tableName;
            }
        }

        // add entities that are being created in migrations, for example custom entities
        if ($this->schema instanceof ExtendSchema) {
            $options = $this->schema->getExtendOptions();
            foreach ($options as $key => $value) {
                if ($this->isTableOptions($key)
                    && !empty($config['extend']['is_extend'])
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
        if (str_contains($key, '!')) {
            // it is a column options
            return false;
        }
        if (str_starts_with($key, '_')) {
            // it is an auxiliary section, for example '_append'
            return false;
        }

        return true;
    }

    /**
     * Run schema related SQLs manually because this query run when diff is already calculated by schema tool
     */
    protected function applySchemaChanges(
        Schema $toSchema,
        AbstractPlatform $platform,
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $comparator = new Comparator();
        $schemaDiff = $comparator->compare($this->schema, $toSchema);
        $isMySql = $platform instanceof MySqlPlatform;
        foreach ($schemaDiff->toSql($platform) as $query) {
            if ($isMySql && stripos($query, 'CHANGE serialized_data serialized_data JSON') !== false) {
                $query = str_replace(['CHARACTER SET utf8', 'COLLATE `utf8_unicode_ci`'], '', $query);
            }
            $this->logQuery($logger, $query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
            }
        }
    }
}
