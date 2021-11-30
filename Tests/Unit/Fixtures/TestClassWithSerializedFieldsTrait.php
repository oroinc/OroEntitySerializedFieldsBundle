<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Fixtures;

use Oro\Bundle\EntitySerializedFieldsBundle\Entity\SerializedFieldsTrait;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClassMagicGet;

class TestClassWithSerializedFieldsTrait extends TestClassMagicGet
{
    use SerializedFieldsTrait;

    private $serialized_data;
}
