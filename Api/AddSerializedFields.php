<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Api;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Adds configuration for serialized fields and remove 'exclude' attribute for 'serialized_data' field.
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
        if (empty($definition)) {
            // there is no config definition
            return;
        }

        if (isset($definition[ConfigUtil::FIELDS])
            && is_array($definition[ConfigUtil::FIELDS])
            && ConfigUtil::isExcludeAll($definition)
            && $this->extendConfigProvider->hasConfig($context->getClassName())
        ) {
            $fieldNames = array_keys($definition[ConfigUtil::FIELDS]);
            foreach ($fieldNames as $fieldName) {
                if ('serialized_data' === $fieldName) {
                    // remove 'exclude' attribute if set
                    $fieldConfig = $definition[ConfigUtil::FIELDS][$fieldName];
                    if (is_array($fieldConfig)) {
                        $config = &$fieldConfig;
                        if (array_key_exists(ConfigUtil::DEFINITION, $fieldConfig)) {
                            $config = &$fieldConfig[ConfigUtil::DEFINITION];
                        }
                        if (is_array($config) && ConfigUtil::isExclude($config)) {
                            unset($config[ConfigUtil::EXCLUDE]);
                            if (empty($config)) {
                                $config = null;
                            }
                            $definition[ConfigUtil::FIELDS][$fieldName] = $fieldConfig;
                        }
                    }
                    // add serialized fields
                    $this->addSerializedFields($definition[ConfigUtil::FIELDS], $context->getClassName());
                    break;
                }
            }
            $context->setResult($definition);
        }
    }

    /**
     * @param array  $fields
     * @param string $entityClass
     */
    protected function addSerializedFields(array &$fields, $entityClass)
    {
        $fieldConfigs = $this->extendConfigProvider->getConfigs($entityClass);
        foreach ($fieldConfigs as $fieldConfig) {
            if ($fieldConfig->is('is_serialized')
                && ExtendHelper::isFieldAccessible($fieldConfig)
                && !array_key_exists($fieldConfig->getId()->getFieldName(), $fields)
            ) {
                $fields[$fieldConfig->getId()->getFieldName()] = [
                    ConfigUtil::DEFINITION => null
                ];
            }
        }
    }
}
