<?php
declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldExtensionInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer as FieldsNormalizer;

/**
 * Extend Entity Field Extension for serialized fields
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

    /**
     * @inheritDoc
     */
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

            $result = null;
            if (isset($serialized[$transport->getName()])) {
                if (!array_key_exists($transport->getName(), $normalized)) {
                    $normalized[$transport->getName()] = $this->normalizer->normalize(
                        $this->getProperty($transport)['fieldType'],
                        $serialized[$transport->getName()]
                    );
                }

                $result = $normalized[$transport->getName()];
            }

            $transport->setResult($result);
            $transport->setProcessed(true);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(EntityFieldProcessTransport $transport): void
    {
        if ($transport->getName() === self::PROPERTY) {
            $this->initStorage($transport);
            $transport->getStorage()->offsetSet(static::PROPERTY, $transport->getValue());
            $transport->getStorage()->offsetSet(static::PROPERTY_NORMALIZED, []);

            $transport->setProcessed(true);
        }

        if ($this->isSerializedProperty($transport)) {
            $this->initStorage($transport);
            $serialized = &$transport->getStorage()[static::PROPERTY];
            $normalized = &$transport->getStorage()[static::PROPERTY_NORMALIZED];

            $value = $transport->getValue();
            if ($value !== null) {
                $serialized[$transport->getName()] = $this->normalizer->denormalize(
                    $this->getProperty($transport)['fieldType'],
                    $value
                );
                $normalized[$transport->getName()] = $value;
            } else {
                unset($serialized[$transport->getName()]);
                unset($normalized[$transport->getName()]);
            }

            $transport->setProcessed(true);
        }
    }

    /**
     * @inheritDoc
     */
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
    }

    /**
     * @inheritDoc
     */
    public function isset(EntityFieldProcessTransport $transport): void
    {
        if ($this->isSerializedProperty($transport) || $transport->getName() === self::PROPERTY) {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    /**
     * @inheritDoc
     */
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        if ($this->isSerializedProperty($transport) || $transport->getName() === self::PROPERTY) {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        if (in_array($transport->getName(), [self::GET_METHOD, self::SET_METHOD])) {
            $transport->setResult(true);
            $transport->setProcessed(true);
            ExtendEntityStaticCache::setMethodExistsCache($transport, true);
        }
    }
}
