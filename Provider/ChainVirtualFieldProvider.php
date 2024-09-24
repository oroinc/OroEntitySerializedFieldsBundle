<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Symfony\Component\PropertyAccess\Exception\AccessException;

/**
 * Virtual field provider for entity's serialized fields.
 */
class ChainVirtualFieldProvider implements VirtualFieldProviderInterface
{
    #[\Override]
    public function isVirtualField($className, $fieldName): bool
    {
        return $this->isSerializedField($className, $fieldName);
    }

    #[\Override]
    public function getVirtualFieldQuery($className, $fieldName): array
    {
        if (!$this->isSerializedField($className, $fieldName)) {
            throw new AccessException(sprintf(
                "Attempt to access non-serialized field '%s' of '%s' entity",
                $fieldName,
                $className
            ));
        }

        return [
            'select' => [
                'expr' => sprintf("JSON_EXTRACT(entity.serialized_data,'%s')", $fieldName),
                'return_type' => EntitySerializedFieldsHolder::getFieldType($className, $fieldName)
            ]
        ];
    }

    #[\Override]
    public function getVirtualFields($className)
    {
        return EntitySerializedFieldsHolder::getEntityFields($className);
    }

    private function isSerializedField(string $className, string $fieldName): bool
    {
        return in_array($fieldName, EntitySerializedFieldsHolder::getEntityFields($className), true);
    }
}
