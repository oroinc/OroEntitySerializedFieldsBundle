<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Tools\DumperExtensions;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntitySerializedFieldsBundle\Tools\DumperExtensions\SerializedEntityConfigDumperExtension;

class SerializedEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SerializedEntityConfigDumperExtension */
    private $extension;

    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(null);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->extension = new SerializedEntityConfigDumperExtension(
            $this->configManager
        );
    }

    private function getSerializedDataFieldDoctrineConfig(): array
    {
        return [
            'column'   => 'serialized_data',
            'type'     => 'json',
            'nullable' => true
        ];
    }

    public function testShouldSupportPostUpdateAction()
    {
        self::assertTrue($this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE));
    }

    public function testShouldNotSupportPreUpdateAction()
    {
        self::assertFalse($this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE));
    }

    public function testPostUpdateShouldSkipExtendedEntityIfSchemaIsNotPrepared()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $entityConfig = $extendConfigProvider->addEntityConfig(
            'Test\Entity',
            [
                'is_extend' => true
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $this->configManager->expects(self::never())
            ->method('persist');

        $this->extension->postUpdate();

        self::assertFalse($entityConfig->has('schema'));
        self::assertFalse($entityConfig->has('index'));
    }

    public function testPostUpdateForSerializedDataFieldEvenIfEntityDoesNotHaveSerializedFields()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $entityClassName = 'Test\Entity';
        $entityConfig = $extendConfigProvider->addEntityConfig(
            $entityClassName,
            [
                'is_extend' => true,
                'schema'    => [
                    'entity' => $entityClassName
                ]
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));

        $this->extension->postUpdate();

        self::assertEquals(
            [
                'entity'   => $entityClassName,
                'property' => [
                    'serialized_data' => []
                ],
                'doctrine' => [
                    $entityClassName => [
                        'fields' => [
                            'serialized_data' => $this->getSerializedDataFieldDoctrineConfig()
                        ]
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
        self::assertFalse($entityConfig->has('index'));
    }

    public function testPostUpdateShouldClearUpSchemaForSerializedFieldsIfAllOfThemAreDeleted()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $entityClassName = 'Test\Entity';
        $regularFieldName = 'testField';
        $entityConfig = $extendConfigProvider->addEntityConfig(
            $entityClassName,
            [
                'is_extend' => true,
                'schema'    => [
                    'entity'   => $entityClassName,
                    'property' => [
                        $regularFieldName => []
                    ],
                    'doctrine' => [
                        $entityClassName => [
                            'fields' => [
                                $regularFieldName => [
                                    'column' => 'test_field',
                                    'type'   => 'string'
                                ]
                            ]
                        ]
                    ]
                ],
                'index'     => [
                    $regularFieldName => []
                ]
            ]
        );
        $extendConfigProvider->addFieldConfig(
            $entityClassName,
            $regularFieldName,
            'string'
        );

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));

        $this->extension->postUpdate();

        self::assertEquals(
            [
                'entity'   => $entityClassName,
                'property' => [
                    $regularFieldName => [],
                    'serialized_data' => []
                ],
                'doctrine' => [
                    $entityClassName => [
                        'fields' => [
                            $regularFieldName => [
                                'column' => 'test_field',
                                'type'   => 'string'
                            ],
                            'serialized_data' => $this->getSerializedDataFieldDoctrineConfig()
                        ]
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
        self::assertEquals(
            [
                $regularFieldName => []
            ],
            $entityConfig->get('index')
        );
    }

    public function testPostUpdateForNewSerializedField()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $entityClassName = 'Test\Entity';
        $serializedFieldName = 'testField';
        $entityConfig = $extendConfigProvider->addEntityConfig(
            $entityClassName,
            [
                'is_extend' => true,
                'schema'    => [
                    'entity'   => $entityClassName,
                    'property' => [
                        $serializedFieldName => []
                    ],
                    'doctrine' => [
                        $entityClassName => [
                            'fields' => [
                                $serializedFieldName => [
                                    'column' => 'test_field',
                                    'type'   => 'string'
                                ]
                            ]
                        ]
                    ]
                ],
                'index'     => [
                    $serializedFieldName => [],
                    'anotherField'       => []
                ]
            ]
        );
        $extendConfigProvider->addFieldConfig(
            $entityClassName,
            $serializedFieldName,
            'string',
            [
                'is_serialized' => true
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));

        $this->extension->postUpdate();

        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'property'            => [
                    'serialized_data' => []
                ],
                'serialized_property' => [
                    $serializedFieldName => []
                ],
                'doctrine'            => [
                    $entityClassName => [
                        'fields' => [
                            'serialized_data' => $this->getSerializedDataFieldDoctrineConfig()
                        ]
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
        self::assertEquals(
            [
                'anotherField' => []
            ],
            $entityConfig->get('index')
        );
    }

    public function testPostUpdateForRestoredSerializedField()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $entityClassName = 'Test\Entity';
        $serializedFieldName = 'testField';
        $entityConfig = $extendConfigProvider->addEntityConfig(
            $entityClassName,
            [
                'is_extend' => true,
                'schema'    => [
                    'entity' => $entityClassName
                ],
                'index'     => [
                    'anotherField' => []
                ]
            ]
        );
        $extendConfigProvider->addFieldConfig(
            $entityClassName,
            $serializedFieldName,
            'string',
            [
                'is_serialized' => true
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));

        $this->extension->postUpdate();

        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'property'            => [
                    'serialized_data' => []
                ],
                'serialized_property' => [
                    $serializedFieldName => []
                ],
                'doctrine'            => [
                    $entityClassName => [
                        'fields' => [
                            'serialized_data' => $this->getSerializedDataFieldDoctrineConfig()
                        ]
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
        self::assertEquals(
            [
                'anotherField' => []
            ],
            $entityConfig->get('index')
        );
    }

    public function testPostUpdateForDeletedSerializedField()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $entityClassName = 'Test\Entity';
        $serializedFieldName = 'testField';
        $entityConfig = $extendConfigProvider->addEntityConfig(
            $entityClassName,
            [
                'is_extend' => true,
                'schema'    => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $serializedFieldName => []
                    ]
                ],
                'index'     => [
                    'anotherField' => []
                ]
            ]
        );
        $extendConfigProvider->addFieldConfig(
            $entityClassName,
            $serializedFieldName,
            'string',
            [
                'is_serialized' => true,
                'is_deleted'    => true
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));

        $this->extension->postUpdate();

        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'property'            => [
                    'serialized_data' => []
                ],
                'serialized_property' => [
                    $serializedFieldName => [
                        'private' => true
                    ]
                ],
                'doctrine'            => [
                    $entityClassName => [
                        'fields' => [
                            'serialized_data' => $this->getSerializedDataFieldDoctrineConfig()
                        ]
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
        self::assertEquals(
            [
                'anotherField' => []
            ],
            $entityConfig->get('index')
        );
    }
}
