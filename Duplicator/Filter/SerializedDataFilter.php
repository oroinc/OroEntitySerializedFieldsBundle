<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Duplicator\Filter;

use DeepCopy\Filter\Filter;
use DeepCopy\Reflection\ReflectionHelper;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Component\Duplicator\Filter\SourceEntityAwareFilterInterface;

/**
 * DeepCopy Serialized Data Filter
 */
class SerializedDataFilter implements Filter, SourceEntityAwareFilterInterface
{
    private ?object $sourceEntity = null;

    #[\Override]
    public function setSourceEntity(object $sourceEntity): void
    {
        $this->sourceEntity = $sourceEntity;
    }

    #[\Override]
    public function apply($object, $property, $objectCopier): void
    {
        if ($this->sourceEntity === null) {
            return;
        }

        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $value = $reflectionProperty->getValue($object);

        if (!is_array($value)) {
            return;
        }

        $entityClass = ClassUtils::getClass($this->sourceEntity);

        $copiedBag = $objectCopier((object)$value);

        $normalized = (array)$copiedBag;
        // Normalize after the copying raw scalars → object to avoid errors with enums and multi enums copying
        foreach ($normalized as $field => $raw) {
            // Skip already normalized and empty collections
            if (!is_object($raw)) {
                $normalized[$field] = EntitySerializedFieldsHolder::normalize($entityClass, $field, $raw);
            }
        }

        // Post-pass: denormalize objects → raw form; drop nulls (canonical "field not set").
        $denormalized = [];
        foreach ($normalized as $field => $val) {
            $denormalized[$field] = EntitySerializedFieldsHolder::denormalize($entityClass, $field, $val);
        }

        $reflectionProperty->setValue($object, $denormalized);
        $object->serialized_normalized = $normalized;
    }
}
