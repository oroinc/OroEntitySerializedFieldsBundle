<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Entity;

use Oro\Bundle\EntitySerializedFieldsBundle\Exception\NoSuchPropertyException;

/**
 * NOT FOR THE MANUAL USE!
 * Trait is automatically added to generated entity class for all the configurable entities.
 * Handles serialized data fields retrieval and set with magic methods.
 *
 * @method array getSerializedData() For internal use only
 * @method self setSerializedData(array $data) For internal use only
 */
trait SerializedFieldsTrait
{
    private array $normalizedSerializedValues = [];

    public function __set($fieldName, $fieldValue)
    {
        $this->validateFieldAvailability($fieldName);

        if (!empty($this->serialized_data) && !is_array($this->serialized_data)) {
            return $this;
        }

        if (empty($this->serialized_data)) {
            $this->serialized_data = [];
        }

        if ($fieldValue !== null) {
            $this->serialized_data[$fieldName] =
                EntitySerializedFieldsHolder::denormalize(static::class, $fieldName, $fieldValue);
            $this->normalizedSerializedValues[$fieldName] = $fieldValue;
        } elseif (isset($this->serialized_data[$fieldName])) {
            unset($this->serialized_data[$fieldName], $this->normalizedSerializedValues[$fieldName]);
        }
    }

    public function __get($fieldName)
    {
        $this->validateFieldAvailability($fieldName);

        if (is_array($this->serialized_data) && isset($this->serialized_data[$fieldName])) {
            if (!array_key_exists($fieldName, $this->normalizedSerializedValues)) {
                $this->normalizedSerializedValues[$fieldName] = EntitySerializedFieldsHolder::normalize(
                    static::class,
                    $fieldName,
                    $this->serialized_data[$fieldName]
                );
            }

            return $this->normalizedSerializedValues[$fieldName];
        }

        return null;
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
