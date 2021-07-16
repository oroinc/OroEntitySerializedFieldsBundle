<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateSerializedDataFieldsLabelsQuery extends ParametrizedMigrationQuery
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
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $entities       = $this->getConfigurableEntitiesData($logger);
        $fieldsToUpdate = [];
        foreach ($entities as $entity => $configData) {
            $config = $configData['data'];
            if (isset($config['extend']['is_extend']) && $config['extend']['is_extend'] === true) {
                $entityFields = $this->getConfigurableEntityFieldsData($logger, $configData['id']);
                foreach ($entityFields as $fieldId => $fieldConfig) {
                    if ($fieldConfig['field_name'] === 'serialized_data'
                        && isset($fieldConfig['data']['entity']['label'])
                        && $fieldConfig['data']['entity']['label'] === 'data'
                    ) {
                        $fieldsToUpdate[$fieldId] = $fieldConfig['data'];
                    }
                }
            }
        }
        $this->executeUpdates($fieldsToUpdate, $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
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
     * @param                 $entityId
     *
     * @return array
     */
    protected function getConfigurableEntityFieldsData(LoggerInterface $logger, $entityId)
    {
        $sql = sprintf(
            'SELECT id, data, field_name FROM oro_entity_config_field WHERE entity_id = %d',
            $entityId
        );
        $this->logQuery($logger, $sql);

        $result = [];
        if ($entityId) {
            $rows = $this->connection->fetchAll($sql);
            foreach ($rows as $row) {
                $result[$row['id']] = [
                    'data'       => $this->connection->convertToPHPValue($row['data'], 'array'),
                    'field_name' => $row['field_name']
                ];
            }
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeUpdates(array $fieldsToUpdate, LoggerInterface $logger, $dryRun)
    {
        $scope = 'entity';
        $code  = 'label';
        $label = 'oro.entity_serialized_fields.data.label';
        foreach ($fieldsToUpdate as $fieldId => $fieldData) {
            $fieldData[$scope][$code] = $label;
            $data                     = $this->connection->convertToDatabaseValue($fieldData, Types::ARRAY);

            $fieldSql    = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
            $fieldParams = [$data, $fieldId];

            $indexSql    = "UPDATE oro_entity_config_index_value
                        SET value = ?
                        WHERE
                            field_id = ? AND
                            entity_id IS NULL AND
                            scope = ? AND
                            code = ?
                        ";
            $indexParams = [$label, $fieldId, $scope, $code];

            $this->logQuery($logger, $fieldSql, $fieldParams);
            $this->logQuery($logger, $indexSql, $indexParams);

            if (!$dryRun) {
                $this->connection->prepare($fieldSql)->execute($fieldParams);
                $this->connection->prepare($indexSql)->execute($indexParams);
            }
        }
    }
}
