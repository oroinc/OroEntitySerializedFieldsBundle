<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\AddSerializedFields;

class AddSerializedFieldsTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Test\Class';

    public function testForNotCompletedDefinition()
    {
        $context = new ConfigContext();
        $context->setClassName(self::TEST_ENTITY_CLASS);
        $context->setResult($this->createConfigObject([]));

        $extendConfigProvider = $this->getConfigProviderMock();

        $processor = new AddSerializedFields($extendConfigProvider);
        $processor->process($context);

        $this->assertEquals(
            [],
            $context->getResult()->toArray()
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

        $context = new ConfigContext();
        $context->setClassName(self::TEST_ENTITY_CLASS);
        $context->setResult($this->createConfigObject($config));

        $extendConfigProvider = $this->getConfigProviderMock();

        $processor = new AddSerializedFields($extendConfigProvider);
        $processor->process($context);

        $this->assertEquals(
            $config,
            $context->getResult()->toArray()
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

        $context = new ConfigContext();
        $context->setClassName(self::TEST_ENTITY_CLASS);
        $context->setResult($this->createConfigObject($config));

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
            $context->getResult()->toArray()
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

    /**
     * @param array $config
     *
     * @return EntityDefinitionConfig
     */
    protected function createConfigObject(array $config)
    {
        $loaderFactory = new ConfigLoaderFactory();

        return $loaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }
}
