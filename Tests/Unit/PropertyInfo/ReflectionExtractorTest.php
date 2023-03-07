<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\PropertyInfo;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\ReflectionExtractor;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\PropertyAccess\TestClassMagicGet;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Fixtures\TestClassWithSerializedFieldsTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReflectionExtractorTest extends TestCase
{
    private ReflectionExtractor $reflectionExtractor;

    private ConfigManager $configManager;

    protected function setUp(): void
    {
        $this->reflectionExtractor = new ReflectionExtractor();

        $fieldOneConfigId = $this->createMock(FieldConfigId::class);
        $fieldOneConfigId->method('getFieldName')
            ->willReturn('property_one');
        $fieldOneConfigId->method('getFieldType')
            ->willReturn('text');
        $fieldOneConfig = $this->createMock(ConfigInterface::class);
        $fieldOneConfig->method('get')
            ->with('is_serialized')
            ->willReturn(true);
        $fieldOneConfig->method('getId')
            ->willReturn($fieldOneConfigId);

        $fieldTwoConfigId = $this->createMock(FieldConfigId::class);
        $fieldTwoConfigId->method('getFieldName')
            ->willReturn('property_two');
        $fieldTwoConfigId->method('getFieldType')
            ->willReturn('text');
        $fieldTwoConfig = $this->createMock(ConfigInterface::class);
        $fieldTwoConfig->method('get')
            ->with('is_serialized')
            ->willReturn(true);
        $fieldTwoConfig->method('getId')
            ->willReturn($fieldTwoConfigId);

        $fieldConfigs = [$fieldOneConfig, $fieldTwoConfig];

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager
            ->method('getConfigs')
            ->with('extend', TestClassWithSerializedFieldsTrait::class, true)
            ->willReturn($fieldConfigs);

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
    }
}
