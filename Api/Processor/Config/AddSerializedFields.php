<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds configuration for serialized fields and remove "exclude" attribute for "serialized_data" field.
 */
class AddSerializedFields implements ProcessorInterface
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
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if ($definition->isExcludeAll()
            && $this->extendConfigProvider->hasConfig($context->getClassName())
        ) {
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $fieldConfig) {
                if ('serialized_data' === $fieldName) {
                    // remove 'exclude' attribute if set
                    if ($fieldConfig->isExcluded()) {
                        $fieldConfig->setExcluded(false);
                    }
                    // add serialized fields
                    $this->addSerializedFields($definition, $context->getClassName());
                    break;
                }
            }
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
