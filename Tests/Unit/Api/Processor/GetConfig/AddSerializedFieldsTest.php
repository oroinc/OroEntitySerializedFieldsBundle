<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\ConfigProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Api\Processor\GetConfig\AddSerializedFields;

class AddSerializedFieldsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var AddSerializedFields */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new AddSerializedFields($this->doctrineHelper, $this->configManager);
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

        $this->doctrineHelper->expects(self::once())
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

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            $config,
            $this->context->getResult()
        );
    }

    public function testSerializedFieldsShouldNotBeAddedIfSerialisedDataFieldIsExcluded()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'serialized_data' => [
                    'exclude' => true
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::never())
            ->method('getConfigs');

        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'serialized_data' => [
                        'exclude' => true
                    ]
                ]
            ],
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
                ]
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $fieldConfigs = [
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField1', 'int'),
                ['is_serialized' => true]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField2', 'int'),
                ['is_serialized' => true]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField3', 'string'),
                ['is_serialized' => true]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField4', 'string'),
                ['is_serialized' => true]
            )
        ];
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn($fieldConfigs);

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
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testCustomFieldsExclusionPolicyForFieldsOfCustomEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'           => null,
                'serialized_data'  => null,
                'serializedField1' => null
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                new Config(
                    new EntityConfigId('extend', self::TEST_CLASS_NAME),
                    ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
                )
            );

        $fieldConfigs = [
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField1', 'int'),
                ['is_serialized' => true, 'is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField2', 'int'),
                ['is_serialized' => true, 'is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
            )
        ];
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn($fieldConfigs);

        $this->context->setRequestedExclusionPolicy(ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'           => null,
                    'serialized_data'  => [
                        'exclude' => true
                    ],
                    'serializedField1' => [
                        'data_type'  => 'int',
                        'depends_on' => ['serialized_data']
                    ],
                    'serializedField2' => [
                        'data_type'  => 'int',
                        'depends_on' => ['serialized_data']
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testCustomFieldsExclusionPolicyForFieldsOfExtendSystemEntity()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1'           => null,
                'serialized_data'  => null,
                'serializedField1' => null,
                'serializedField3' => null
            ]
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn(
                new Config(
                    new EntityConfigId('extend', self::TEST_CLASS_NAME),
                    ['is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
                )
            );

        $fieldConfigs = [
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField1', 'int'),
                ['is_serialized' => true, 'is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField2', 'int'),
                ['is_serialized' => true, 'is_extend' => true, 'owner' => ExtendScope::OWNER_SYSTEM]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField3', 'string'),
                ['is_serialized' => true, 'is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
            ),
            new Config(
                new FieldConfigId('extend', self::TEST_CLASS_NAME, 'serializedField4', 'string'),
                ['is_serialized' => true, 'is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM]
            )
        ];
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', self::TEST_CLASS_NAME)
            ->willReturn($fieldConfigs);

        $this->context->setRequestedExclusionPolicy(ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS);
        $this->context->setResult($this->createConfigObject($config));
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'field1'           => null,
                    'serialized_data'  => [
                        'exclude' => true
                    ],
                    'serializedField1' => [
                        'data_type'  => 'int',
                        'depends_on' => ['serialized_data']
                    ],
                    'serializedField2' => [
                        'data_type'  => 'int',
                        'depends_on' => ['serialized_data']
                    ],
                    'serializedField3' => [
                        'data_type'  => 'string',
                        'depends_on' => ['serialized_data']
                    ]
                ]
            ],
            $this->context->getResult()
        );
    }
}
