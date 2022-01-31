<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;

/**
 * Provides information about entities that uses serialized_data field.
 */
class SerializedColumnsHolderHelper
{
    private array $tablesData = [];
    private EntityMetadataHelper $metadataHelper;

    public function __construct(EntityMetadataHelper $metadataHelper)
    {
        $this->metadataHelper = $metadataHelper;
    }

    public function getTablesInfo(Connection $connection, Schema $schema)
    {
        if ($this->tablesData) {
            return $this->tablesData;
        }

        $sql = 'SELECT e.id, e.data, e.class_name FROM oro_entity_config e WHERE EXISTS(
            SELECT 1 FROM oro_entity_config_field ef WHERE ef. field_name = ? AND ef.entity_id = e.id
        )';
        $rows = $connection->executeQuery($sql, ['serialized_data'])->fetchAllAssociative();

        foreach ($rows as $entityRow) {
            $entityClass = $entityRow['class_name'];
            $entityData = $connection->convertToPHPValue($entityRow['data'], Types::ARRAY);
            $tableName = $entityData['extend']['table'] ?? null;
            if (!$tableName) {
                $tableName = $entityData['extend']['schema']['doctrine'][$entityClass]['table'] ?? null;
            }
            if (!$tableName) {
                $tableName = $this->metadataHelper->getTableNameByEntityClass($entityClass);
            }

            $fieldsWithDateTime = $connection->executeQuery(
                "SELECT f.field_name, f.data, f.type FROM oro_entity_config_field f WHERE f.entity_id = ?",
                [$entityRow['id']],
                [ParameterType::INTEGER]
            )
                ->fetchAllAssociative();
            $fieldNames = $dateFieldNames =  [];
            foreach ($fieldsWithDateTime as &$field) {
                $field['data'] = $connection->convertToPHPValue($field['data'], Types::ARRAY);
                if ($field['data']['extend']['is_serialized']) {
                    $fieldNames[] = $field['field_name'];
                    if (in_array($field['type'], ['date', 'datetime'])) {
                        $dateFieldNames[] = $field['field_name'];
                    }
                }
            }
            $table = $schema->getTable($tableName);
            $primaryKeyColumns = $table->getPrimaryKeyColumns();
            $id = reset($primaryKeyColumns);
            $idColumn = $table->getColumn($id);

            $this->tablesData[] = [
                'table' => $tableName,
                'tempTable' => $tableName . '_tmp',
                'id' => $idColumn,
                'entityConfig' => ['id' => $entityRow['id'], 'data' => $entityData],
                'fieldNames' => $fieldNames,
                'datetimeFields' => $dateFieldNames
            ];
        }

        return $this->tablesData;
    }
}
