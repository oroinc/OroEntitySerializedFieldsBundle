<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;

class ExtendEntityStub implements ExtendEntityInterface
{
    /** @var array */
    private $serializedData;

    /**
     * @param array $serializedData
     */
    public function __construct(array $serializedData = [])
    {
        $this->serializedData = $serializedData;
    }

    /**
     * @return array
     */
    public function getSerializedData(): array
    {
        return $this->serializedData;
    }
}
