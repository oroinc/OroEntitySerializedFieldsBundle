<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\SerializedFieldsTrait;

class ExtendEntityStub implements ExtendEntityInterface
{
    use SerializedFieldsTrait;

    public function __construct(private array $serialized_data = [])
    {
    }

    public function getSerializedData(): array
    {
        return $this->serialized_data;
    }

    public function get(string $name): mixed
    {
    }

    public function set(string $name, mixed $value): static
    {
    }
}
