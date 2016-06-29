<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api\Processor\Metadata;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\MetadataProcessorTestCase;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Metadata\AddSerializedFieldsMetadata;

class AddSerializedFieldsMetadataTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var AddSerializedFieldsMetadata */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');

        $this->processor = new AddSerializedFieldsMetadata($this->doctrineHelper, $this->extendConfigProvider);
    }

    public function testProcessWhenNoMetadata()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNoConfig()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getResult()->getFields());
    }

    public function testProcessForEmptyConfig()
    {
        $config = [
            'exclusion_policy' => 'all',
        ];

        $this->doctrineHelper->expects($this->never())
            ->method('isManageableEntityClass');

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getResult()->getFields());
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'          => null,
                'serialized_data' => [
                    'exclude' => true
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getResult()->getFields());
    }

    public function testProcessForNonConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'          => null,
                'serialized_data' => [
                    'exclude' => true
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult(new EntityMetadata());
        $this->processor->process($this->context);

        $this->assertCount(0, $this->context->getResult()->getFields());
    }

    public function testProcessForConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'                  => null,
                'serialized_data'         => [
                    'exclude' => true
                ],
                'serializedField1'        => [
                    'exclude' => true
                ],
                'serializedField2'        => null,
                'notSavedSerializedField' => null,
            ]
        ];

        $metadata = new EntityMetadata();
        $field2 = new FieldMetadata();
        $field2->setName('serializedField2');
        $field2->setDataType('string');
        $metadata->addField($field2);

        $this->extendConfigProvider->addEntityConfig(self::TEST_CLASS_NAME);
        $this->extendConfigProvider->addFieldConfig(
            self::TEST_CLASS_NAME,
            'serializedField1',
            'int',
            ['is_serialized' => true]
        );
        $this->extendConfigProvider->addFieldConfig(
            self::TEST_CLASS_NAME,
            'serializedField2',
            'int',
            ['is_serialized' => true]
        );
        $this->extendConfigProvider->addFieldConfig(
            self::TEST_CLASS_NAME,
            'notSavedSerializedField',
            'int',
            ['is_serialized' => true, 'is_extend' => true, 'state' => ExtendScope::STATE_NEW]
        );
        $this->extendConfigProvider->addFieldConfig(
            self::TEST_CLASS_NAME,
            'field4',
            'int'
        );

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setConfig($this->createConfigObject($config));
        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        $expectedField1 = new FieldMetadata();
        $expectedField1->setName('serializedField1');
        $expectedField1->setDataType('int');
        $expectedField2 = new FieldMetadata();
        $expectedField2->setName('serializedField2');
        $expectedField2->setDataType('string');
        $this->assertEquals(
            [
                'serializedField1' => $expectedField1,
                'serializedField2' => $expectedField2,
            ],
            $this->context->getResult()->getFields()
        );
    }
}
