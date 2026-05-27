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
        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $value = $reflectionProperty->getValue($object);

        if (!is_array($value)) {
            return;
        }

        $entityClass = ClassUtils::getClass($this->sourceEntity);

        $copiedBag = $objectCopier((object)$value);

        $copiedArray = (array)$copiedBag;

        // Post-pass: normalize objects → raw arrays
        $normalized = $this->normalizeData($entityClass, $copiedArray);

        $reflectionProperty->setValue($object, $normalized);
        $object->serialized_normalized = $copiedArray;
    }

    private function normalizeData(string $entityClass, array $normalized): array
    {
        $result = [];

        foreach ($normalized as $field => $val) {
            $result[$field] = EntitySerializedFieldsHolder::normalize($entityClass, $field, $val);
        }

        return $result;
    }
}
