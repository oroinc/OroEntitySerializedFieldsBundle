<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Normalizer;

/**
 * Datetime(date) typed serialized field's value normalizer.
 */
class DatetimeSerializedFieldNormalizer implements SerializedFieldNormalizerInterface
{
    /**
     * {@inheriDoc}
     */
    public function normalize($value)
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }

        if (!is_string($value)) {
            throw new \RuntimeException('Given value must be a string');
        }

        $dateTime = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $value);
        if ($dateTime === false) {
            throw new \RuntimeException(sprintf("Can't convert '%s' date string into 'DateTime' object", $value));
        }

        return $dateTime;
    }

    /**
     * {@inheriDoc}
     */
    public function denormalize($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        throw new \RuntimeException(
            sprintf(
                "Given value must be instance of '%s'",
                \DateTimeInterface::class
            )
        );
    }
}
