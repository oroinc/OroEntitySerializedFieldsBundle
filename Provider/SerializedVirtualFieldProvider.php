<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Symfony\Component\PropertyAccess\Exception\AccessException;

/**
 * Virtual field provider for entity's serialized fields.
 */
class SerializedVirtualFieldProvider implements VirtualFieldProviderInterface
{
    private array $dbalTypes;

    public function __construct(array $dbalTypes)
    {
        $this->dbalTypes = $dbalTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function isVirtualField($className, $fieldName): bool
    {
        return $this->isSerializedField($className, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getVirtualFieldQuery($className, $fieldName): array
    {
        if (!$this->isSerializedField($className, $fieldName)) {
            throw new AccessException(sprintf(
                "Attempt to access non-serialized field '%s' of '%s' entity",
                $fieldName,
                $className
            ));
        }

        $fieldType = EntitySerializedFieldsHolder::getFieldType($className, $fieldName);
        $dbalType = $this->dbalTypes[$fieldType];

        return [
            'select' => [
                'expr' => sprintf(
                    "CAST(JSON_EXTRACT(entity.serialized_data,'%s') as %s)",
                    $fieldName,
                    $dbalType
                ),
                'return_type' => $dbalType,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getVirtualFields($className)
    {
        return EntitySerializedFieldsHolder::getEntityFields($className);
    }

    private function isSerializedField(string $className, string $fieldName): bool
    {
        return in_array($fieldName, EntitySerializedFieldsHolder::getEntityFields($className), true);
    }
}
