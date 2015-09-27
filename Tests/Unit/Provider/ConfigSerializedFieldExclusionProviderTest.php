<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\ConfigSerializedFieldExclusionProvider;

class ConfigSerializedFieldExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigSerializedFieldExclusionProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

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
     *
     * @param Config  $fieldConfig  Field config config
     * @param bool    $expected     Expected result
     */
    public function testIsIgnoredField($fieldConfig, $expected)
    {
        $className = $fieldConfig->getId()->getClassName();
        $fieldName = $fieldConfig->getId()->getFieldName();

        $this->metadata->expects($this->once())
            ->method('hasField')
            ->willReturn(true);

        $this->metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($className));

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($fieldConfig));

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));

        $this->assertEquals($expected, $this->provider->isIgnoredField($this->metadata, $fieldName));
    }

    public function dataProvider()
    {
        // Field config without is_serializable property
        $config1 = $this->getFieldConfig(
            'Test\Entity\Entity1',
            'custom_description'
        );

        // With is_serializable = true
        $config2  = $this->getFieldConfig(
            'Test\Entity\Entity2',
            'custom_name',
            [ 'is_serialized' => true ]
        );

        // With is_serializable = false
        $config3  = $this->getFieldConfig(
            'Test\Entity\Entity3',
            'custom_address',
            [ 'is_serialized' => false ]
        );
        return [
            [$config1, false],
            [$config2, true],
            [$config3, false],
        ];
    }

    protected function getFieldConfig($entityClassName, $fieldName, $values = array())
    {
        $extend = [
            'is_extend' => true,
            'owner'     => ExtendScope::OWNER_CUSTOM,
            'state'     => ExtendScope::STATE_ACTIVE
        ];
        $fieldConfigId = new FieldConfigId('extend', $entityClassName, $fieldName);
        $fieldConfig   = new Config($fieldConfigId);
        $fieldConfig->setValues(array_merge($extend, $values));

        return $fieldConfig;
    }
}
