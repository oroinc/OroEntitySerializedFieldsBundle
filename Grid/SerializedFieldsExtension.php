<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

/**
 * Dynamic field data grid extension(ORM source) decorator that properly handles serialized fields DB query's part.
 */
class SerializedFieldsExtension extends DynamicFieldsExtension
{
    private array $dbalTypes;

    public function setDbalTypes(array $dbalTypes): void
    {
        $this->dbalTypes = $dbalTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function buildExpression(array $fields, DatagridConfiguration $config, string $alias): void
    {
        $fields = array_filter($fields, function (FieldConfigId $field) use ($config, $alias) {
            $isSerializedField = $this->isSerializedField($field, $config);
            if ($isSerializedField) {
                $config->getOrmQuery()->addSelect(
                    sprintf(
                        "CAST(JSON_EXTRACT(%s.serialized_data,'%s') as %s) AS %s",
                        $alias,
                        $field->getFieldName(),
                        $this->dbalTypes[$field->getFieldType()],
                        $field->getFieldName()
                    )
                );
            }

            return !$isSerializedField;
        });

        parent::buildExpression($fields, $config, $alias);
    }

    private function isSerializedField(FieldConfigId $field, DatagridConfiguration $config): bool
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));

        return $extendConfigProvider
            ->getConfig($entityClassName, $field->getFieldName())
            ->is('is_serialized');
    }
}
