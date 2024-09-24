<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Normalizer;

/**
 * Percent typed serialized field's value normalizer.
 */
class PercentSerializedFieldNormalizer implements SerializedFieldNormalizerInterface
{
    #[\Override]
    public function normalize($value)
    {
        return is_numeric($value) ? $value : null;
    }

    #[\Override]
    public function denormalize($value)
    {
        return is_numeric($value) ? json_decode($value) : null;
    }
}
