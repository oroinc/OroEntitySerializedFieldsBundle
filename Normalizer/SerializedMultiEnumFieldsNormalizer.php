<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Normalizer;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

/**
 * Normalizes string enum ids to EnumOption entity references and vice-versa for multi-enums.
 */
class SerializedMultiEnumFieldsNormalizer implements SerializedFieldNormalizerInterface
{
    public function __construct(protected DoctrineHelper $doctrineHelper)
    {
    }

    #[\Override]
    public function normalize($value, string $fieldName = null): mixed
    {
        if (!is_array($value) && !$value instanceof Collection) {
            return $value;
        }

        $enumFields = [];
        foreach ($value as $enumField) {
            $enumFields[] = $this->doctrineHelper->getEntityManager(EnumOption::class)
                ?->getReference(EnumOption::class, $enumField);
        }

        return $enumFields;
    }

    #[\Override]
    public function denormalize($value): mixed
    {
        if (!is_array($value) && !$value instanceof Collection) {
            return is_object($value) ? [$value->getId()] : $value;
        }
        $result = [];
        foreach ($value as $item) {
            $result[] = is_object($item) ? $item->getId() : $item;
        }

        return $result;
    }
}
