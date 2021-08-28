<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\ConfigSerializedFieldExclusionProvider;

class ConfigSerializedFieldExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var ConfigSerializedFieldExclusionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->provider = new ConfigSerializedFieldExclusionProvider($this->configProvider);
    }

    public function testIsIgnoredEntity()
    {
        $this->assertEquals(false, $this->provider->isIgnoredEntity('field'));
    }

    public function testIsIgnoredRelation()
    {
        $this->assertEquals(false, $this->provider->isIgnoredRelation($this->metadata, 'field'));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsIgnoredField(Config $fieldConfig)
    {
        $fieldName = $fieldConfig->getId()->getFieldName();

        $this->metadata->expects($this->once())
            ->method('hasField')
            ->willReturn(true);

        $this->metadata->expects($this->never())
            ->method('getName');

        $this->configProvider->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->provider->isIgnoredField($this->metadata, $fieldName));
    }

    public function testIsIgnoredSerializedField()
    {
        $fieldConfig  = $this->getFieldConfig(
            'Test\Entity\Entity2',
            'custom_name',
            [ 'is_serialized' => true ]
        );
        $className = $fieldConfig->getId()->getClassName();
        $fieldName = $fieldConfig->getId()->getFieldName();

        $this->metadata->expects($this->once())
            ->method('hasField')
            ->willReturn(false);

        $this->metadata->expects($this->once())
            ->method('getName')
            ->willReturn($className);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($fieldConfig);

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->assertTrue($this->provider->isIgnoredField($this->metadata, $fieldName));
    }

    public function dataProvider(): array
    {
        return [
            'Field config without is_serializable property' => [
                $this->getFieldConfig(
                    'Test\Entity\Entity1',
                    'custom_description'
                )
            ],
            'With is_serializable = false' => [
                $this->getFieldConfig(
                    'Test\Entity\Entity3',
                    'custom_address',
                    ['is_serialized' => false]
                )
            ]
        ];
    }

    private function getFieldConfig(string $entityClassName, string $fieldName, array $values = []): Config
    {
        $extend = [
            'is_extend' => true,
            'owner'     => ExtendScope::OWNER_CUSTOM,
            'state'     => ExtendScope::STATE_ACTIVE
        ];
        $fieldConfig = new Config(new FieldConfigId('extend', $entityClassName, $fieldName));
        $fieldConfig->setValues(array_merge($extend, $values));

        return $fieldConfig;
    }
}
