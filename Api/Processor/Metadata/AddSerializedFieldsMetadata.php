<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Metadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds metadata for serialized fields.
 */
class AddSerializedFieldsMetadata implements ProcessorInterface
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
        /** @var MetadataContext $context */

        if (!$context->hasResult()) {
            // metadata is not loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config || !$config->hasFields()) {
            // a configuration does not exist or empty
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

        $this->addSerializedFields($context->getResult(), $entityClass, $config);
    }

    /**
     * @param EntityMetadata         $metadata
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     */
    protected function addSerializedFields(EntityMetadata $metadata, $entityClass, EntityDefinitionConfig $config)
    {
        foreach ($config->getFields() as $fieldName => $field) {
            if ($metadata->hasField($fieldName)) {
                continue;
            }
            if (!$this->extendConfigProvider->hasConfig($entityClass, $fieldName)) {
                continue;
            }
            $fieldConfig = $this->extendConfigProvider->getConfig($entityClass, $fieldName);
            if ($fieldConfig->is('is_serialized') && ExtendHelper::isFieldAccessible($fieldConfig)) {
                $fieldMetadata = new FieldMetadata();
                $fieldMetadata->setName($fieldName);
                $fieldMetadata->setDataType($fieldConfig->getId()->getFieldType());

                $metadata->addField($fieldMetadata);
            }
        }
    }
}
