<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds metadata for serialized fields.
 */
class AddSerializedFieldsMetadata implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
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
        if (empty($config)) {
            // a configuration does not exist
            return;
        }

        if (isset($config[ConfigUtil::FIELDS])
            && is_array($config[ConfigUtil::FIELDS])
            && $this->extendConfigProvider->hasConfig($context->getClassName())
        ) {
            $fields = $config[ConfigUtil::FIELDS];
            /** @var EntityMetadata $entityMetadata */
            $entityMetadata = $context->getResult();
            $fieldConfigs   = $this->extendConfigProvider->getConfigs($context->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                /** @var FieldConfigId $fieldId */
                $fieldId   = $fieldConfig->getId();
                $fieldName = $fieldId->getFieldName();
                if (array_key_exists($fieldName, $fields)
                    && !$entityMetadata->hasField($fieldName)
                    && $fieldConfig->is('is_serialized')
                    && ExtendHelper::isFieldAccessible($fieldConfig)
                ) {
                    $fieldMetadata = new FieldMetadata();
                    $fieldMetadata->setName($fieldName);
                    $fieldMetadata->setDataType($fieldId->getFieldType());

                    $entityMetadata->addField($fieldMetadata);
                }
            }
        }
    }
}
