<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\PropertyInfo;

use Oro\Bundle\EntityExtendBundle\Extend\ReflectionExtractor;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess\TestClassMagicGet;
use PHPUnit\Framework\TestCase;

class ReflectionExtractorTest extends TestCase
{
    private ReflectionExtractor $reflectionExtractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->reflectionExtractor = new ReflectionExtractor();
    }

    public function testCanReadPropertyInClass(): void
    {
        self::assertNotNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassMagicGet::class,
                'magicProperty'
            )
        );
        self::assertNotNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassMagicGet::class,
                'constantMagicProperty'
            )
        );
        self::assertNotNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassMagicGet::class,
                'non_existing_property'
            )
        );
        self::assertNotNull(
            $this->reflectionExtractor->getReadInfo(TestClassMagicGet::class, 'accident_magic_get_property')
        );
    }

    public function testCanWritePropertyInClass(): void
    {
        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassMagicGet::class,
            'property_one'
        );
        self::assertEmpty($writeInfo->getErrors());

        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassMagicGet::class,
            'property_two'
        );
        self::assertEmpty($writeInfo->getErrors());

        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassMagicGet::class,
            'non_existing_property'
        );
        self::assertNotNull($writeInfo);

        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassMagicGet::class,
            'accident_magic_get_property'
        );
        self::assertEmpty($writeInfo->getErrors());
    }
}
