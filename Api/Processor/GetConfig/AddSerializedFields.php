<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds serialized fields and sets "exclude" attribute for "serialized_data" field.
 */
class AddSerializedFields implements ProcessorInterface
{
    private const SERIALIZED_DATA_FIELD = 'serialized_data';

    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
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
            $skipNotConfiguredCustomFields =
                $context->getRequestedExclusionPolicy() === ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS
                && $this->isExtendSystemEntity($entityClass);
            $this->addSerializedFields($definition, $entityClass, $skipNotConfiguredCustomFields);
        }
    }

    private function addSerializedFields(
        EntityDefinitionConfig $definition,
        string $entityClass,
        bool $skipNotConfiguredCustomFields
    ): void {
        $fieldConfigs = $this->configManager->getConfigs('extend', $entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if (!$fieldConfig->is('is_serialized') || !ExtendHelper::isFieldAccessible($fieldConfig)) {
                continue;
            }

            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();
            $fieldName = $fieldId->getFieldName();
            $field = $definition->findField($fieldName, true);
            if (null !== $field) {
                if (!$field->getDataType()) {
                    $field->setDataType($fieldId->getFieldType());
                }
                $field->addDependsOn(self::SERIALIZED_DATA_FIELD);
            } elseif (!$skipNotConfiguredCustomFields || !$this->isCustomField($fieldConfig)) {
                $field = $definition->addField($fieldName);
                $field->setDataType($fieldId->getFieldType());
                $field->setDependsOn([self::SERIALIZED_DATA_FIELD]);
            }
        }
    }

    private function isExtendSystemEntity(string $entityClass): bool
    {
        $entityConfig = $this->configManager->getEntityConfig('extend', $entityClass);

        return
            $entityConfig->is('is_extend')
            && !$entityConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }

    private function isCustomField(ConfigInterface $fieldConfig): bool
    {
        return
            $fieldConfig->is('is_extend')
            && $fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }
}
