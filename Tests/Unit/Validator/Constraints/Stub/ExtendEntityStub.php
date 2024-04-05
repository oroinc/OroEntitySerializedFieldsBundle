<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Bundle\EntitySerializedFieldsBundle\Exception\NoSuchPropertyException;

class ExtendEntityStub implements ExtendEntityInterface
{
    private array $serializedData = [];

    public function getSerializedData(): array
    {
        return $this->serializedData;
    }

    public function get(string $name): mixed
    {
    }

    public function set(string $name, mixed $value): static
    {
    }

    public function __set($name, $value)
    {
        $this->validateFieldAvailability($name);

        $this->serializedData[$name] = EntitySerializedFieldsHolder::denormalize(self::class, $name, $value);
    }

    public function __get($name)
    {
        $this->validateFieldAvailability($name);

        if (!\array_key_exists($name, $this->serializedData)) {
            return null;
        }

        return EntitySerializedFieldsHolder::normalize(self::class, $name, $this->serializedData[$name]);
    }

    public function __isset($name): bool
    {
        return \in_array($name, $this->getSerializedFields(), true);
    }

    private function validateFieldAvailability(string $name): void
    {
        if (!\in_array($name, $this->getSerializedFields(), true)) {
            throw new NoSuchPropertyException(sprintf('There is no "%s" field in "%s" entity', $name, self::class));
        }
    }

    private function getSerializedFields(): array
    {
        return EntitySerializedFieldsHolder::getEntityFields(self::class) ?? [];
    }
}
