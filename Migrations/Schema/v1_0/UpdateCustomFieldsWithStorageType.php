<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateCustomFieldsWithStorageType extends ParametrizedMigrationQuery
{
    /** @var Schema */
    protected $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $entities            = $this->getConfigurableEntitiesData($logger);
        $hasSchemaChanges    = false;
        $updateConfigQueries = [];

        foreach ($entities as $configData) {
            $config = $configData['data'];
            if (isset($config['extend']['is_extend']) && $config['extend']['is_extend'] == true) {
                $entityFields = $this->getConfigurableEntityFieldsData($logger, $configData['id']);
                foreach ($entityFields as $fieldId => $fieldConfig) {
                    if (isset($fieldConfig['extend']['is_extend']) && $fieldConfig['extend']['is_extend'] == true) {
                        $hasSchemaChanges = true;
                        $fieldConfig['extend']['is_serialized'] = false;
                        $updateConfigQueries[] = sprintf(
                            "UPDATE oro_entity_config_field SET data = '%s' WHERE id = %s",
                            $this->connection->convertToDatabaseValue($fieldConfig, 'array'),
                            $fieldId
                        );
                    }
                }
            }
        }

        if ($hasSchemaChanges) {
            foreach ($updateConfigQueries as $query) {
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
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
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

    /**
     * @param LoggerInterface $logger
     * @param null            $entityId
     *
     * @return array
     *  key - field id
     *  value - field config array data
     */
    protected function getConfigurableEntityFieldsData(LoggerInterface $logger, $entityId = null)
    {
        $sql = sprintf(
            'SELECT id, data FROM oro_entity_config_field WHERE entity_id = %d',
            $entityId
        );
        $this->logQuery($logger, $sql);

        $result = [];
        if ($entityId) {
            $rows = $this->connection->fetchAll($sql);
            foreach ($rows as $row) {
                $result[$row['id']] = $this->connection->convertToPHPValue($row['data'], 'array');
            }
        }

        return $result;
    }
}
