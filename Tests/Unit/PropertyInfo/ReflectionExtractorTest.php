<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\PropertyInfo;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Bundle\EntitySerializedFieldsBundle\PropertyInfo\ReflectionExtractor;
use Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Fixtures\TestClassWithSerializedFieldsTrait;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\TestClassMagicGet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReflectionExtractorTest extends TestCase
{
    private const DEFINED_PROPERTIES = ['property_one', 'property_two'];
    private ReflectionExtractor $reflectionExtractor;

    private ConfigManager $configManager;

    protected function setUp(): void
    {
        $this->reflectionExtractor = new ReflectionExtractor();

        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')
            ->with('schema')
            ->willReturn([
                'serialized_property' => array_flip(self::DEFINED_PROPERTIES),
            ]);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager
            ->method('getEntityConfig')
            ->with('extend', TestClassWithSerializedFieldsTrait::class)
            ->willReturn($config);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('oro_entity_config.config_manager')
            ->willReturn($this->configManager);
        EntitySerializedFieldsHolder::initialize($container);
    }

    public function testCanReadPropertyInClass()
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

    public function testCanWritePropertyInClass()
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

    public function testCanReadPropertyInClassWithSerializedFields()
    {
        self::assertNotNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassWithSerializedFieldsTrait::class,
                'property_one'
            )
        );
        self::assertNotNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassWithSerializedFieldsTrait::class,
                'property_two'
            )
        );
        self::assertNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassWithSerializedFieldsTrait::class,
                'magicProperty'
            )
        );
        self::assertNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassWithSerializedFieldsTrait::class,
                'constantMagicProperty'
            )
        );
        self::assertNull(
            $this->reflectionExtractor->getReadInfo(
                TestClassWithSerializedFieldsTrait::class,
                'non_existing_property'
            )
        );
    }

    public function testCanWritePropertyInClassWithSerializedFields()
    {
        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassWithSerializedFieldsTrait::class,
            'property_one'
        );
        self::assertEmpty($writeInfo->getErrors());

        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassWithSerializedFieldsTrait::class,
            'property_two'
        );
        self::assertEmpty($writeInfo->getErrors());

        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassWithSerializedFieldsTrait::class,
            'non_existing_property'
        );
        self::assertNotEmpty($writeInfo->getErrors());

        $writeInfo = $this->reflectionExtractor->getWriteInfo(
            TestClassWithSerializedFieldsTrait::class,
            'accident_magic_get_property'
        );
        self::assertNotEmpty($writeInfo->getErrors());
    }
}
