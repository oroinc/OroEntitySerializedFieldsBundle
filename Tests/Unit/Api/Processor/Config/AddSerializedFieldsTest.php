<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api\Processor\Config;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config\AddSerializedFields;

class AddSerializedFieldsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var AddSerializedFields */
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

        $this->processor = new AddSerializedFields($this->doctrineHelper, $this->extendConfigProvider);
    }

    public function testForNotCompletedDefinition()
    {
        $this->context->setResult($this->createConfigObject([]));
        $this->processor->process($this->context);

        $this->assertConfig(
            [],
            $this->context->getResult()
        );
    }

    public function testForNotManageableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'          => null,
                'serialized_data' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testForNonConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'          => null,
                'serialized_data' => null
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testForConfigurableEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'                  => null,
                'serialized_data'         => null,
                'serializedField1'        => [
                    'exclude' => true
                ],
                'renamedSerializedField3' => [
                    'property_path' => 'serializedField3'
                ],
                'renamedSerializedField4' => [
                    'property_path' => 'serializedField4',
                    'data_type'     => 'int',
                    'depends_on'    => ['serialized_data', 'another_field']
                ],
            ]
        ];

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

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
            'serializedField3',
            'string',
            ['is_serialized' => true]
        );
        $this->extendConfigProvider->addFieldConfig(
            self::TEST_CLASS_NAME,
            'serializedField4',
            'string',
            ['is_serialized' => true]
        );

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'                  => null,
                    'serialized_data'         => [
                        'exclude' => true
                    ],
                    'serializedField1'        => [
                        'exclude'    => true,
                        'data_type'  => 'int',
                        'depends_on' => ['serialized_data']
                    ],
                    'serializedField2'        => [
                        'data_type'  => 'int',
                        'depends_on' => ['serialized_data']
                    ],
                    'renamedSerializedField3' => [
                        'property_path' => 'serializedField3',
                        'data_type'     => 'string',
                        'depends_on'    => ['serialized_data']
                    ],
                    'renamedSerializedField4' => [
                        'property_path' => 'serializedField4',
                        'data_type'     => 'int',
                        'depends_on'    => ['serialized_data', 'another_field']
                    ],
                ]
            ],
            $this->context->getResult()
        );
    }
}
