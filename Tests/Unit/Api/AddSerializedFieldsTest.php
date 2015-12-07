<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\AddSerializedFields;

class AddSerializedFieldsTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Test\Class';

    public function testNoConfig()
    {
        $context = new ConfigContext();
        $context->setClassName(self::TEST_ENTITY_CLASS);

        $extendConfigProvider = $this->getConfigProviderMock();

        $processor = new AddSerializedFields($extendConfigProvider);
        $processor->process($context);

        $this->assertNull($context->getResult());
    }

    public function testForNonConfigurableEntity()
    {
        $config = [
            ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
            ConfigUtil::FIELDS           => [
                'field1'          => [
                    ConfigUtil::DEFINITION => null
                ],
                'serialized_data' => [
                    ConfigUtil::DEFINITION => [
                        ConfigUtil::EXCLUDE => true
                    ]
                ],
            ]
        ];

        $context = new ConfigContext();
        $context->setClassName(self::TEST_ENTITY_CLASS);
        $context->setResult($config);

        $extendConfigProvider = $this->getConfigProviderMock();

        $processor = new AddSerializedFields($extendConfigProvider);
        $processor->process($context);

        $this->assertEquals(
            $config,
            $context->getResult()
        );
    }

    public function testForConfigurableEntity()
    {
        $config = [
            ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
            ConfigUtil::FIELDS           => [
                'field1'           => [
                    ConfigUtil::DEFINITION => null
                ],
                'serialized_data'  => [
                    ConfigUtil::DEFINITION => [
                        ConfigUtil::EXCLUDE => true
                    ]
                ],
                'serializedField1' => [
                    ConfigUtil::DEFINITION => [
                        ConfigUtil::EXCLUDE => true
                    ]
                ]
            ]
        ];

        $context = new ConfigContext();
        $context->setClassName(self::TEST_ENTITY_CLASS);
        $context->setResult($config);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->addEntityConfig(self::TEST_ENTITY_CLASS);
        $extendConfigProvider->addFieldConfig(
            self::TEST_ENTITY_CLASS,
            'serializedField1',
            'int',
            ['is_serialized' => true]
        );
        $extendConfigProvider->addFieldConfig(
            self::TEST_ENTITY_CLASS,
            'serializedField2',
            'int',
            ['is_serialized' => true]
        );

        $processor = new AddSerializedFields($extendConfigProvider);
        $processor->process($context);

        $this->assertEquals(
            [
                ConfigUtil::EXCLUSION_POLICY => ConfigUtil::EXCLUSION_POLICY_ALL,
                ConfigUtil::FIELDS           => [
                    'field1'           => [
                        ConfigUtil::DEFINITION => null
                    ],
                    'serialized_data'  => [
                        ConfigUtil::DEFINITION => null
                    ],
                    'serializedField1' => [
                        ConfigUtil::DEFINITION => [
                            ConfigUtil::EXCLUDE => true
                        ]
                    ],
                    'serializedField2' => [
                        ConfigUtil::DEFINITION => null
                    ]
                ]
            ],
            $context->getResult()
        );
    }

    /**
     * @return ConfigProviderMock
     */
    protected function getConfigProviderMock()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        return new ConfigProviderMock($configManager, 'extend');
    }
}
