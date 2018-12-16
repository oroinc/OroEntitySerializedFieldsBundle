<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds serialized fields and sets "exclude" attribute for "serialized_data" field.
 */
class AddSerializedFields implements ProcessorInterface
{
    private const SERIALIZED_DATA_FIELD = 'serialized_data';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager  $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
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
        if (!$this->configManager->hasConfig($entityClass)) {
            // only configurable entities are supported
            return;
        }

        $serializedDataField = $definition->getField(self::SERIALIZED_DATA_FIELD);
        if ($serializedDataField && !$serializedDataField->isExcluded()) {
            // exclude 'serialized_data' field as it should not be used directly,
            // but it will be loaded from the database only if at least one field depends on it
            $serializedDataField->setExcluded();
            // add serialized fields
            $this->addSerializedFields($definition, $context->getClassName());
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function addSerializedFields(EntityDefinitionConfig $definition, $entityClass)
    {
        $fieldConfigs = $this->configManager->getConfigs('extend', $entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('is_serialized') || !ExtendHelper::isFieldAccessible($fieldConfig)) {
                continue;
            }

            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();
            $fieldName = $fieldId->getFieldName();
            $field = $definition->findField($fieldName, true);
            if (null === $field) {
                $field = $definition->addField($fieldName);
                $field->setDataType($fieldId->getFieldType());
                $field->setDependsOn([self::SERIALIZED_DATA_FIELD]);
            } else {
                if (!$field->getDataType()) {
                    $field->setDataType($fieldId->getFieldType());
                }
                $dependsOn = $field->getDependsOn();
                if (empty($dependsOn)) {
                    $field->setDependsOn([self::SERIALIZED_DATA_FIELD]);
                } elseif (!\in_array(self::SERIALIZED_DATA_FIELD, $dependsOn, true)) {
                    $dependsOn[] = self::SERIALIZED_DATA_FIELD;
                    $field->setDependsOn($dependsOn);
                }
            }
        }
    }
}
