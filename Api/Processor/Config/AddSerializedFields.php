<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds serialized fields and sets "exclude" attribute for "serialized_data" field.
 */
class AddSerializedFields implements ProcessorInterface
{
    const SERIALIZED_DATA_FIELD = 'serialized_data';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigProvider $extendConfigProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll()) {
            // expected completed config
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }
        if (!$this->extendConfigProvider->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $serializedDataField = $definition->getField(self::SERIALIZED_DATA_FIELD);
        if ($serializedDataField) {
            // exclude 'serialized_data' field as it should not be used directly,
            // but it will be loaded from the database only if at least field depends on it
            if (!$serializedDataField->isExcluded()) {
                $serializedDataField->setExcluded();
            }
            // add serialized fields
            $this->addSerializedFields($definition, $context->getClassName());
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function addSerializedFields(EntityDefinitionConfig $definition, $entityClass)
    {
        $fieldConfigs = $this->extendConfigProvider->getConfigs($entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('is_serialized') || !ExtendHelper::isFieldAccessible($fieldConfig)) {
                continue;
            }
            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();
            $field = $definition->findField($fieldId->getFieldName(), true);
            if (null === $field) {
                $field = $definition->addField($fieldId->getFieldName());
                $field->setDataType($fieldId->getFieldType());
                $field->setDependsOn([self::SERIALIZED_DATA_FIELD]);
            } else {
                if (!$field->getDataType()) {
                    $field->setDataType($fieldId->getFieldType());
                }
                $dependsOn = $field->getDependsOn();
                if (empty($dependsOn)) {
                    $field->setDependsOn([self::SERIALIZED_DATA_FIELD]);
                } elseif (!in_array(self::SERIALIZED_DATA_FIELD, $dependsOn, true)) {
                    $dependsOn[] = self::SERIALIZED_DATA_FIELD;
                    $field->setDependsOn($dependsOn);
                }
            }
        }
    }
}
