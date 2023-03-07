<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Fixtures;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess\TestClassMagicGet;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\SerializedFieldsTrait;

class TestClassWithSerializedFieldsTrait extends TestClassMagicGet
{
    use SerializedFieldsTrait;

    private $serialized_data;
}
