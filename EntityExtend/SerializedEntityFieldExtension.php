<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\EntityExtend;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldAccessorsHelper;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer as FieldsNormalizer;

/**
 * Extend Entity Field Extension for serialized fields
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SerializedEntityFieldExtension extends AbstractEntityFieldExtension implements EntityFieldExtensionInterface
{
    private const PROPERTY = 'serialized_data';
    private const PROPERTY_NORMALIZED = 'serialized_normalized';
    private const SET_METHOD = 'setSerializedData';
    private const GET_METHOD = 'getSerializedData';

    private FieldsNormalizer $normalizer;

    public function __construct(FieldsNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    private function getProperty(EntityFieldProcessTransport $transport): ?array
    {
        $filedMetadata = $transport->getFieldsMetadata();
        if (isset($filedMetadata[$transport->getName()]) && $filedMetadata[$transport->getName()]['is_serialized']) {
            return $filedMetadata[$transport->getName()];
        }

        return null;
    }

    private function isSerializedProperty(EntityFieldProcessTransport $transport): bool
    {
        return (bool)$this->getProperty($transport);
    }

    private function initStorage(EntityFieldProcessTransport $transport): void
    {
        $storage = $transport->getStorage();
        if (!$storage->offsetExists(static::PROPERTY)) {
            $storage->offsetSet(static::PROPERTY, []);
        }
        if (!$storage->offsetExists(static::PROPERTY_NORMALIZED)) {
            $storage->offsetSet(static::PROPERTY_NORMALIZED, []);
        }
    }

    #[\Override]
    public function get(EntityFieldProcessTransport $transport): void
    {
        if ($transport->getName() === self::PROPERTY) {
            $this->initStorage($transport);
            $transport->setResult($transport->getStorage()->offsetGet(static::PROPERTY));
            $transport->setProcessed(true);
            ExtendEntityStaticCache::allowIgnoreGetCache($transport);
        }

        if ($this->isSerializedProperty($transport)) {
            $this->initStorage($transport);
            $serialized = &$transport->getStorage()[static::PROPERTY];
            $normalized = &$transport->getStorage()[static::PROPERTY_NORMALIZED];
            $this->initializeDefault($transport);

            $result = null;
            if (isset($serialized[$transport->getName()])) {
                if (!array_key_exists($transport->getName(), $normalized)
                    || !$this->isFreshNormalize($normalized[$transport->getName()], $serialized[$transport->getName()])
                ) {
                    $normalized[$transport->getName()] = $this->normalizer->normalize(
                        $this->getProperty($transport)['fieldType'],
                        $serialized[$transport->getName()],
                        $transport->getName()
                    );
                }

                $result = $normalized[$transport->getName()];
            }

            $transport->setResult($result);
            $transport->setProcessed(true);
        }
    }

    #[\Override]
    public function set(EntityFieldProcessTransport $transport): void
    {
        if ($transport->getName() === self::PROPERTY) {
            $this->initStorage($transport);
            $transport->getStorage()->offsetSet(static::PROPERTY, $transport->getValue());
            $transport->getStorage()->offsetSet(static::PROPERTY_NORMALIZED, []);

            $transport->setProcessed(true);
            $transport->setResult($transport->getObject());
        }

        if ($this->isSerializedProperty($transport)) {
            $this->initStorage($transport);
            $serialized = &$transport->getStorage()[static::PROPERTY];
            $normalized = &$transport->getStorage()[static::PROPERTY_NORMALIZED];

            $value = $transport->getValue();

            if ($transport->getArgument(0)) {
                $value = $transport->getArgument(0);
            }

            if ($value !== null) {
                $serialized[$transport->getName()] = $this->normalizer->denormalize(
                    $this->getProperty($transport)['fieldType'],
                    $value,
                );
                $normalized[$transport->getName()] = $value;
            } else {
                unset($serialized[$transport->getName()]);
                unset($normalized[$transport->getName()]);
            }

            $transport->setProcessed(true);
            $transport->setResult($transport->getObject());
        }
    }

    #[\Override]
    public function call(EntityFieldProcessTransport $transport): void
    {
        if ($transport->getName() === self::SET_METHOD) {
            $transport->getStorage()->offsetSet(static::PROPERTY, $transport->getValue());
            $transport->setResult($transport->getObject());
            $transport->setProcessed(true);
        }

        if ($transport->getName() === self::GET_METHOD) {
            $transport->setProcessed(true);
            $transport->setResult($transport->getStorage()->offsetGet(static::PROPERTY));
        }
        // Oro enums processing
        $enumMethods = $this->getEnumMethods($transport);
        if (isset($enumMethods[$transport->getName()])) {
            $originName = $transport->getName();
            $transport->setName($enumMethods[$transport->getName()]);
            if (str_starts_with($originName, 'get')) {
                $this->get($transport);
            }
            if (str_starts_with($originName, 'set')) {
                $this->set($transport);
            }
            // Multi enum
            if (str_starts_with($originName, 'add')) {
                $this->addMultiEnumOption($transport);
            }
            if (str_starts_with($originName, 'remove')) {
                $this->removeMultiEnumOption($transport);
            }
        }
    }

    public function removeMultiEnumOption(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isSerializedProperty($transport)) {
            return;
        }
        $this->initStorage($transport);
        $serialized = &$transport->getStorage()[static::PROPERTY];
        $normalized = &$transport->getStorage()[static::PROPERTY_NORMALIZED];
        $value = $transport->getValue();
        if ($transport->getArgument(0)) {
            $value = $transport->getArgument(0);
        }
        if (isset($serialized[$transport->getName()])) {
            $searchKey = array_search($value->getId(), $serialized[$transport->getName()]);
            if (false !== $searchKey) {
                unset($serialized[$transport->getName()][$searchKey]);
                $serialized[$transport->getName()] = array_values($serialized[$transport->getName()]);
            }
        }
        if (isset($normalized[$transport->getValue()])) {
            $normalizedKey = array_search($value, $normalized[$transport->getName()]);
            if (false !== $normalizedKey) {
                unset($serialized[$transport->getName()][$normalizedKey]);
            }
        }
        $transport->setProcessed(true);
        $transport->setResult($transport->getObject());
    }

    public function addMultiEnumOption(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isSerializedProperty($transport)) {
            return;
        }
        $this->initStorage($transport);
        $serialized = &$transport->getStorage()[static::PROPERTY];
        $normalized = &$transport->getStorage()[static::PROPERTY_NORMALIZED];
        $value = $transport->getValue();
        if ($transport->getArgument(0)) {
            $value = $transport->getArgument(0);
        }
        if ($value !== null) {
            if (!isset($serialized[$transport->getName()])) {
                $serialized[$transport->getName()] = [];
                $normalized[$transport->getName()] = [];
            }
            if (!array_search($value->getId(), $serialized[$transport->getName()])) {
                array_push($serialized[$transport->getName()], $value->getId());
                array_push($normalized[$transport->getName()], $value);
            }
        }

        $transport->setProcessed(true);
        $transport->setResult($transport->getObject());
    }

    private function getEnumMethods(EntityFieldProcessTransport $transport): array
    {
        $fieldsMetadata = $transport->getFieldsMetadata();
        $enumMethods = [];
        foreach ($fieldsMetadata as $fieldName => $fieldData) {
            if (!ExtendHelper::isEnumerableType($fieldData['fieldType']) || !$fieldData['is_serialized']) {
                continue;
            }
            $enumMethods[EntityFieldAccessorsHelper::getterName($fieldName)] = $fieldName;
            $enumMethods[EntityFieldAccessorsHelper::setterName($fieldName)] = $fieldName;
            if (ExtendHelper::isMultiEnumType($fieldData['fieldType'])) {
                $enumMethods[EntityFieldAccessorsHelper::adderName($fieldName)] = $fieldName;
                $enumMethods[EntityFieldAccessorsHelper::removerName($fieldName)] = $fieldName;
            }
        }

        return $enumMethods;
    }

    #[\Override]
    public function isset(EntityFieldProcessTransport $transport): void
    {
        if ($this->isSerializedProperty($transport) || $transport->getName() === self::PROPERTY) {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    #[\Override]
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        if ($this->isSerializedProperty($transport)
            || $transport->getName() === self::PROPERTY
            || in_array($transport->getName(), $this->getEnumMethods($transport))) {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    #[\Override]
    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        if (in_array($transport->getName(), [self::GET_METHOD, self::SET_METHOD])
            || array_key_exists($transport->getName(), $this->getEnumMethods($transport))) {
            $transport->setResult(true);
            $transport->setProcessed(true);
            ExtendEntityStaticCache::setMethodExistsCache($transport, true);
        }
    }

    protected function initializeDefault(EntityFieldProcessTransport $transport): void
    {
        if (!is_array($transport->getStorage()[static::PROPERTY])
            || array_key_exists($transport->getName(), $transport->getStorage()[static::PROPERTY])) {
            return;
        }
        $defaultValue = null;
        $propertyType = $this->getProperty($transport)['fieldType'];
        if (ExtendHelper::isMultiEnumType($propertyType)) {
            $defaultValue = [];
        }
        $transport->getStorage()[static::PROPERTY][$transport->getName()] = $defaultValue;
        $transport->getStorage()[static::PROPERTY_NORMALIZED][$transport->getName()] = $defaultValue;
    }

    private function isFreshNormalize(mixed $normalizedValue, mixed $serializedValue): bool
    {
        if ($normalizedValue instanceof EnumOptionInterface && $normalizedValue->getId() !== $serializedValue) {
            return false;
        }

        return true;
    }
}
