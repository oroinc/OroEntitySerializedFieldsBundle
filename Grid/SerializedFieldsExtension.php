<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

class SerializedFieldsExtension extends DynamicFieldsExtension
{
    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        parent::visitResult($config, $result);

        $fields = $this->getSerializedFields($config);
        if (count($fields) === 0) {
            return;
        }

        // copy serialized fields data from storage to columns
        /** @var ResultRecord $record */
        foreach ($result->getData() as $record) {
            $serializedData = $record->getValue('serialized_data');
            /** @var FieldConfigId $serializedField */
            foreach ($fields as $serializedField) {
                $fieldName = $serializedField->getFieldName();
                $value = $serializedData && array_key_exists($fieldName, $serializedData)
                    ? $serializedData[$fieldName]
                    : null;
                $record->setValue($fieldName, $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildExpression(array $fields, DatagridConfiguration $config, $alias)
    {
        $config->getOrmQuery()->addSelect(sprintf('%s.%s', $alias, 'serialized_data'));

        $fields = array_filter($fields, function (FieldConfigId $field) use ($config) {
            return !$this->isSerializedField($field, $config);
        });

        parent::buildExpression($fields, $config, $alias);
    }

    /**
     * @param FieldConfigId $field
     * @param DatagridConfiguration $config
     * @return bool
     */
    private function isSerializedField(FieldConfigId $field, DatagridConfiguration $config)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));

        return $extendConfigProvider
            ->getConfig($entityClassName, $field->getFieldName())
            ->is('is_serialized');
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return FieldConfigId[]
     */
    protected function getSerializedFields(DatagridConfiguration $config)
    {
        $fields = array_filter($this->getFields($config), function (FieldConfigId $field) use ($config) {
            return $this->isSerializedField($field, $config);
        });

        return $fields;
    }
}
