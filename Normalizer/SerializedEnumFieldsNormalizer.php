<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Normalizer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

/**
 * Normalizes string enum ids to EnumOption entity references and vice-versa for enums.
 */
class SerializedEnumFieldsNormalizer implements SerializedFieldNormalizerInterface
{
    public function __construct(protected DoctrineHelper $doctrineHelper)
    {
    }

    public function normalize($value, string $fieldName = null): mixed
    {
        if (null === $value) {
            return null;
        }

        return $this->doctrineHelper->getEntityManager(EnumOption::class)
            ?->getReference(EnumOption::class, $value);
    }

    public function denormalize($value): mixed
    {
        if (!is_object($value) || !is_callable([$value, 'getId'])) {
            return $value;
        }

        return (string)$value->getId();
    }
}
