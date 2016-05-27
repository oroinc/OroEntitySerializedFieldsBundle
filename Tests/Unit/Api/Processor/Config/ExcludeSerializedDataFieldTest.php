<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api\Processor\Config;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config\ExcludeSerializedDataField;

class ExcludeSerializedDataFieldTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ExcludeSerializedDataField */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ExcludeSerializedDataField($this->doctrineHelper);
    }

    public function testProcessForNotManageableEntity()
    {
        $config = [];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoSerializedDataFieldInConfig()
    {
        $config = [];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertFalse($this->context->getResult()->hasField('serialized_data'));
    }

    public function testProcessWhenSerializedDataFieldIsAlreadyExcluded()
    {
        $config = [
            'fields' => [
                'serialized_data' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertTrue($this->context->getResult()->getField('serialized_data')->isExcluded());
    }

    public function testProcessWhenSerializedDataFieldIsNotExcludedYet()
    {
        $config = [
            'fields' => [
                'serialized_data' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertTrue($this->context->getResult()->getField('serialized_data')->isExcluded());
    }
}
