<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Normalizer;

/**
 * Serialized field's value normalizer interface.
 */
interface SerializedFieldNormalizerInterface
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function normalize($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function denormalize($value);
}
