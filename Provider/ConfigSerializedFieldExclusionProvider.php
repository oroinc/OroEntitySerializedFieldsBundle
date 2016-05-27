<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * The aim of the class is to exclude serialized custom fields.
 * They can be detected by property "is_serialized" in scope "extend" via config provider.
 */
class ConfigSerializedFieldExclusionProvider extends AbstractExclusionProvider
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
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if (!$metadata->hasField($fieldName)) {
            // skip virtual fields
            return false;
        }

        $className = $metadata->getName();
        if ($this->extendConfigProvider->hasConfig($className, $fieldName)) {
            return $this->extendConfigProvider
                ->getConfig($className, $fieldName)
                ->get('is_serialized', false, false);
        }

        return false;
    }
}
