<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds configuration for serialized fields and remove "exclude" attribute for "serialized_data" field.
 */
class AddSerializedFields implements ProcessorInterface
{
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

        $serializedDataField = $definition->getField('serialized_data');
        if ($serializedDataField) {
            // remove 'exclude' attribute if set
            if ($serializedDataField->isExcluded()) {
                $serializedDataField->setExcluded(false);
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
            if ($fieldConfig->is('is_serialized')
                && ExtendHelper::isFieldAccessible($fieldConfig)
                && !$definition->hasField($fieldConfig->getId()->getFieldName())
            ) {
                $definition->addField($fieldConfig->getId()->getFieldName());
            }
        }
    }
}
