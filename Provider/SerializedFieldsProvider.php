<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * Provides an array of serialized field names for the specified entity class.
 */
class SerializedFieldsProvider
{
    private EntityConfigManager $entityConfigManager;

    public function __construct(EntityConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * Provides an array of serialized field names of the specified $type for the given entity class name.
     *
     * @param string $className
     * @param string $type Field type to filter by. Empty string to get all serialized fields names.
     *
     * @return string[] Array of fields names.
     */
    public function getSerializedFields(string $className, string $type = ''): array
    {
        $serializedFieldsNames = [];
        foreach ($this->entityConfigManager->getConfigs('extend', $className, true) as $fieldConfig) {
            if ($fieldConfig->is('is_serialized')) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                if (!$type || $fieldConfigId->getFieldType() === $type) {
                    $serializedFieldsNames[] = $fieldConfigId->getFieldName();
                }
            }
        }

        return $serializedFieldsNames;
    }
}
