<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Entity;

use Oro\Bundle\EntitySerializedFieldsBundle\Exception\NoSuchPropertyException;

/**
 * NOT FOR THE MANUAL USE!
 * Trait is automatically added to generated entity class for all the configurable entities.
 * Handles serialized data fields retrieval and set with magic methods.
 */
trait SerializedFieldsTrait
{
    public function __set($fieldName, $fieldValue)
    {
        $this->validateFieldAvailability($fieldName);

        if (!empty($this->serialized_data) && !is_array($this->serialized_data)) {
            return $this;
        }

        if (empty($this->serialized_data)) {
            $this->serialized_data = [];
        }
        $this->serialized_data[$fieldName] = $fieldValue;
    }

    public function __get($fieldName)
    {
        $this->validateFieldAvailability($fieldName);

        return is_array($this->serialized_data) && isset($this->serialized_data[$fieldName])
            ? $this->serialized_data[$fieldName]
            : null;
    }

    public function __isset($fieldName): bool
    {
        $fields = EntitySerializedFieldsHolder::getEntityFields(static::class);

        return $fields === null || in_array($fieldName, $fields);
    }

    /**
     * @param string $fieldName
     */
    private function validateFieldAvailability(string $fieldName): void
    {
        if (!isset($this->{$fieldName})) {
            throw new NoSuchPropertyException(
                sprintf(
                    'There is no "%s" field in "%s" entity',
                    $fieldName,
                    static::class
                )
            );
        }
    }
}
