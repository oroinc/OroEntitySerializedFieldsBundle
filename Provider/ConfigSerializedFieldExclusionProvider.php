<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * The aim of the class is to exclude custom fields with is_serialized property or if it equals to false
 */
class ConfigSerializedFieldExclusionProvider implements ExclusionProviderInterface
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $organizationConfigProvider
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
