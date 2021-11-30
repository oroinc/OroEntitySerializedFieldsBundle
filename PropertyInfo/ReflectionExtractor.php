<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\PropertyInfo;

use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor as BasicReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyReadInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;

/**
 * Extended class reflection extractor that can delegate property accessibility check
 * to a class that supports such option.
 */
class ReflectionExtractor extends BasicReflectionExtractor
{
    /**
     * {@inheritdoc}
     */
    public function getReadInfo(string $class, string $property, array $context = []): ?PropertyReadInfo
    {
        $info = parent::getReadInfo($class, $property, $context);

        return $this->getInfo($info, $class, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function getWriteInfo(string $class, string $property, array $context = []): ?PropertyWriteInfo
    {
        $info = parent::getWriteInfo($class, $property, $context);

        $info = $this->getInfo($info, $class, $property);
        if (null !== $info) {
            return $info;
        }
        $failedInfo = new PropertyWriteInfo(PropertyWriteInfo::TYPE_NONE, $property);
        $errors = [sprintf("There is no %s property at %s", $property, $class)];
        $failedInfo->setErrors($errors);

        return $failedInfo;
    }

    /**
     * @param PropertyReadInfo|PropertyWriteInfo|null $info
     * @param string                                  $class
     * @param string                                  $property
     * @return PropertyReadInfo|PropertyWriteInfo|null
     */
    private function getInfo($info, string $class, string $property)
    {
        if ($info === null || $info->getType() !== PropertyWriteInfo::TYPE_PROPERTY) {
            return $info;
        }
        $reflClass = new \ReflectionClass($class);
        if ($reflClass->hasProperty($property) || !$reflClass->hasProperty('serialized_data')) {
            return $info;
        }

        return $this->hasSerializedField($class, $property) ? $info : null;
    }

    public function hasSerializedField(string $class, string $field): bool
    {
        $fields = EntitySerializedFieldsHolder::getEntityFields($class);

        return $fields === null || in_array($field, $fields);
    }
}
