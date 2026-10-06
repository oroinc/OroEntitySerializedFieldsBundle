<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Duplicator\Filter;

use DeepCopy\Filter\Filter;
use DeepCopy\Reflection\ReflectionHelper;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Component\Duplicator\Filter\SourceBagRetentionTrait;
use Oro\Component\Duplicator\Filter\SourceEntityAwareFilterInterface;
use Oro\Component\Duplicator\PropertyBag;
use Oro\Component\Duplicator\PropertyBagInterface;

/**
 * DeepCopy Serialized Data Filter
 */
class SerializedDataFilter implements Filter, SourceEntityAwareFilterInterface
{
    use SourceBagRetentionTrait;

    private ?object $sourceEntity = null;

    #[\Override]
    public function setSourceEntity(object $sourceEntity): void
    {
        $this->sourceEntity = $sourceEntity;
    }

    #[\Override]
    public function apply($object, $property, $objectCopier): void
    {
        $entityClass = $this->getEntityClass($object);
        if ($entityClass === null) {
            return;
        }

        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $value = $reflectionProperty->getValue($object);

        if (!is_array($value)) {
            return;
        }

        $bag = new PropertyBag($entityClass, $value);
        $this->retainSourceBag($bag);
        $copiedBag = $objectCopier($bag);

        $normalized = $copiedBag->toArray();
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

    /**
     * The storage bag knows its owner; any other bag falls back to the source entity.
     */
    private function getEntityClass(object $object): ?string
    {
        if ($object instanceof PropertyBagInterface) {
            return $object->getOwnerClass();
        }

        return $this->sourceEntity === null ? null : ClassUtils::getClass($this->sourceEntity);
    }
}
