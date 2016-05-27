<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api\Processor\Config;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\Config\AddSerializedFields;

class AddSerializedFieldsTest extends ConfigProcessorTestCase
{
    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var AddSerializedFields */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');

        $this->processor = new AddSerializedFields($this->extendConfigProvider);
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

    public function testForNonConfigurableEntity()
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
                'field1'           => null,
                'serialized_data'  => [
                    'exclude' => true
                ],
                'serializedField1' => [
                    'exclude' => true
                ]
            ]
        ];

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

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'           => null,
                    'serialized_data'  => null,
                    'serializedField1' => [
                        'exclude' => true
                    ],
                    'serializedField2' => null
                ]
            ],
            $this->context->getResult()
        );
    }
}
