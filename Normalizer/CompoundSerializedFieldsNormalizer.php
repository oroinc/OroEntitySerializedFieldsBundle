<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Normalizer;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Compound serialized fields' value normalizer that delegates normalization to field type specific once.
 */
class CompoundSerializedFieldsNormalizer
{
    public function __construct(private ServiceLocator $locator)
    {
    }

    /**
     * @param string $fieldType
     * @param mixed $value
     * @return mixed
     */
    public function normalize(string $fieldType, $value, $fieldName = null)
    {
        if ($normalizer = $this->getNormalizer($fieldType)) {
            return null !== $fieldName ? $normalizer->normalize($value, $fieldName) : $normalizer->normalize($value);
        }

        return $value;
    }

    public function denormalize(string $fieldType, $value)
    {
        if ($normalizer = $this->getNormalizer($fieldType)) {
            return $normalizer->denormalize($value);
        }

        return $value;
    }

    /**
     * @throws \RuntimeException
     */
    private function getNormalizer(string $fieldType): ?SerializedFieldNormalizerInterface
    {
        if ($this->locator->has($fieldType)) {
            $normalizer = $this->locator->get($fieldType);

            if (!$normalizer instanceof SerializedFieldNormalizerInterface) {
                throw new \RuntimeException(sprintf(
                    "Serialized field typed normalizer must implement '%s' interface",
                    SerializedFieldNormalizerInterface::class
                ));
            }

            return $normalizer;
        }

        return null;
    }
}
