<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * The aim of the class is to exclude serialized custom fields.
 * They can be detected by property "is_serialized" in scope "extend" via entity config provider.
 */
class ConfigSerializedFieldExclusionProvider implements ExclusionProviderInterface
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
    public function isIgnoredEntity($className)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        $className = $metadata->getName();
        if ($this->extendConfigProvider->hasConfig($className, $fieldName)) {
            $config = $this->extendConfigProvider->getConfig($className, $fieldName);
            return $config->get('is_serialized', false, false);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return false;
    }
}
