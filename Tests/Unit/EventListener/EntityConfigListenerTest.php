<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreSetRequireUpdateEvent;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;
use Oro\Bundle\EntitySerializedFieldsBundle\EventListener\EntityConfigListener;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|EntityGenerator */
    private $entityGenerator;

    /** @var MockObject|Session */
    private $session;

    /** @var MockObject|ConfigManager */
    private $configManager;

    /** @var ConfigProviderMock */
    private $extendConfigProvider;

    /** @var EntityConfigListener */
    private $listener;

    /** @var \ReflectionProperty */
    private $hasChangedSerializedFieldsProperty;

    protected function setUp(): void
    {
        $this->entityGenerator = $this->createMock(EntityGenerator::class);
        $this->session = $this->createMock(Session::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');

        $this->configManager->expects(self::any())
            ->method('getEntityConfig')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className) {
                return $this->extendConfigProvider->getConfig($className);
            });
        $this->configManager->expects(self::any())
            ->method('getFieldConfig')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className, $fieldName) {
                return $this->extendConfigProvider->getConfig($className, $fieldName);
            });

        $this->listener = new EntityConfigListener(
            $this->entityGenerator,
            $this->session
        );

        $this->hasChangedSerializedFieldsProperty = new \ReflectionProperty(
            EntityConfigListener::class,
            'hasChangedSerializedFields'
        );
        $this->hasChangedSerializedFieldsProperty->setAccessible(true);
    }

    /**
     * Sets the hasChangedSerializedFields private property value on $this->listener
     */
    private function setHasChangedSerializedFields(array $value): void
    {
        $this->hasChangedSerializedFieldsProperty->setValue($this->listener, $value);
    }

    /**
     * Reads the hasChangedSerializedFields private property value off $this->listener
     */
    private function getHasChangedSerializedFields(): array
    {
        return $this->hasChangedSerializedFieldsProperty->getValue($this->listener);
    }

    /**
     * @param string $className
     * @param array  $values
     *
     * @return Config
     */
    private function addEntityConfig($className, $values = [])
    {
        return $this->extendConfigProvider->addEntityConfig($className, $values);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $values
     *
     * @return Config
     */
    private function addFieldConfig($className, $fieldName, $values = [])
    {
        return $this->extendConfigProvider->addFieldConfig($className, $fieldName, 'string', $values);
    }

    public function testCreateFieldWhenSessionIsNotStarted()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state' => ExtendScope::STATE_NEW
            ]
        );

        $this->session->expects(self::once())
            ->method('isStarted')
            ->willReturn(false);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($fieldConfig));

        $this->listener->createField(
            new FieldConfigEvent($entityClassName, $fieldName, $this->configManager)
        );

        self::assertEquals(ExtendScope::STATE_NEW, $fieldConfig->get('state'));
        self::assertFalse($fieldConfig->get('is_serialized'));
        self::assertEquals([$entityClassName => false], $this->getHasChangedSerializedFields());
    }

    public function testCreateNotSerializedFieldWhenSessionIsStarted()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';
        $entityConfigModelId = 123;
        $sessionKey = sprintf(FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED, $entityConfigModelId);

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state' => ExtendScope::STATE_NEW
            ]
        );

        $this->session->expects(self::once())
            ->method('isStarted')
            ->willReturn(true);
        $this->session->expects(self::once())
            ->method('get')
            ->with($sessionKey, self::isFalse())
            ->willReturn(false);
        $this->configManager->expects(self::once())
            ->method('getConfigModelId')
            ->with($entityClassName)
            ->willReturn($entityConfigModelId);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($fieldConfig));

        $this->listener->createField(
            new FieldConfigEvent($entityClassName, $fieldName, $this->configManager)
        );

        self::assertEquals(ExtendScope::STATE_NEW, $fieldConfig->get('state'));
        self::assertFalse($fieldConfig->get('is_serialized'));
        self::assertEquals([$entityClassName => false], $this->getHasChangedSerializedFields());
    }

    public function testCreateSerializedFieldWhenSessionIsStarted()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';
        $entityConfigModelId = 123;
        $sessionKey = sprintf(FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED, $entityConfigModelId);

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state' => ExtendScope::STATE_NEW
            ]
        );

        $this->session->expects(self::once())
            ->method('isStarted')
            ->willReturn(true);
        $this->session->expects(self::once())
            ->method('get')
            ->with($sessionKey, self::isFalse())
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getConfigModelId')
            ->with($entityClassName)
            ->willReturn($entityConfigModelId);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($fieldConfig));

        $this->listener->createField(
            new FieldConfigEvent($entityClassName, $fieldName, $this->configManager)
        );

        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertTrue($fieldConfig->get('is_serialized'));
        self::assertEquals([$entityClassName => false], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushForNotExtendableEntity()
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig($entityClassName);

        $this->configManager->expects(self::never())
            ->method('getConfigChangeSet');
        $this->configManager->expects(self::never())
            ->method('persist');

        $event = new PreFlushConfigEvent(['entity' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
    }

    public function testPreFlushShouldStopEventPropagationWhenExtendedEntityConfigIsNotChanged()
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig($entityClassName);

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($entityConfig))
            ->willReturn([]);
        $this->configManager->expects(self::never())
            ->method('persist');

        $event = new PreFlushConfigEvent(['extend' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertTrue($event->isPropagationStopped());
    }

    public function testPreFlushForEntityConfigWhenNoChangedSerializedFields()
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig($entityClassName);

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($entityConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::never())
            ->method('persist');

        $event = new PreFlushConfigEvent(['extend' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
    }

    public function testPreFlushForEntityConfigAndNotSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig($entityClassName);
        $this->addFieldConfig($entityClassName, $fieldName);

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($entityConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::never())
            ->method('persist');

        $this->setHasChangedSerializedFields([$entityClassName => true]);
        $event = new PreFlushConfigEvent(['extend' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
    }

    public function testPreFlushShouldNotModifyEntityConfigState()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state' => ExtendScope::STATE_UPDATE
            ]
        );
        $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($entityConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::never())
            ->method('persist');
        $this->configManager->expects(self::never())
            ->method('calculateConfigChangeSet');

        $this->setHasChangedSerializedFields([$entityClassName => true]);
        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
    }

    public function testPreFlushShouldDoNothingIfEntityConfigStateIsNotChangedForEntityConfigAndSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state' => ExtendScope::STATE_DELETE
            ]
        );
        $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($entityConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::never())
            ->method('persist');
        $this->configManager->expects(self::never())
            ->method('calculateConfigChangeSet');

        $this->setHasChangedSerializedFields([$entityClassName => true]);
        $event = new PreFlushConfigEvent(['extend' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_DELETE, $entityConfig->get('state'));
    }

    public function testPreFlushNewSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state' => ExtendScope::STATE_UPDATE
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_NEW
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($fieldConfig));
        $this->configManager->expects(self::once())
            ->method('calculateConfigChangeSet')
            ->with(self::identicalTo($fieldConfig));

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertEquals([$entityClassName => true], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushDeletedSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state' => ExtendScope::STATE_UPDATE
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_DELETE
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($fieldConfig));
        $this->configManager->expects(self::once())
            ->method('calculateConfigChangeSet')
            ->with(self::identicalTo($fieldConfig));

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertTrue($fieldConfig->get('is_deleted'));
        self::assertEquals([$entityClassName => true], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushShouldUpdateEntitySchemaForNewSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity' => $entityClassName
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));
        $this->configManager->expects(self::once())
            ->method('calculateConfigChangeSet')
            ->with(self::identicalTo($entityConfig));

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'serialized_property' => [
                    $fieldName => []
                ]
            ],
            $entityConfig->get('schema')
        );
    }

    public function testPreFlushShouldUpdateEntitySchemaForDeletedSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity' => $entityClassName
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'is_deleted'    => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));
        $this->configManager->expects(self::once())
            ->method('calculateConfigChangeSet')
            ->with(self::identicalTo($entityConfig));

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'serialized_property' => [
                    $fieldName => [
                        'private' => true
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
    }

    public function testPreFlushShouldUpdateEntitySchemaForRestoredSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => [
                            'private' => true
                        ]
                    ]
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));
        $this->configManager->expects(self::once())
            ->method('calculateConfigChangeSet')
            ->with(self::identicalTo($entityConfig));

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'serialized_property' => [
                    $fieldName => []
                ]
            ],
            $entityConfig->get('schema')
        );
    }

    public function testPreFlushShouldUpdateEntitySchemaForExistingSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => []
                    ]
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::never())
            ->method('persist');
        $this->configManager->expects(self::never())
            ->method('calculateConfigChangeSet');

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'serialized_property' => [
                    $fieldName => []
                ]
            ],
            $entityConfig->get('schema')
        );
    }

    public function testPreFlushShouldUpdateEntitySchemaForDeletedSerializedFieldThatAlreadyExistsInSchema()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => []
                    ]
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'is_deleted'    => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($fieldConfig))
            ->willReturn(['old', 'new']);
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entityConfig));
        $this->configManager->expects(self::once())
            ->method('calculateConfigChangeSet')
            ->with(self::identicalTo($entityConfig));

        $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertFalse($event->isPropagationStopped());
        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertEquals(
            [
                'entity'              => $entityClassName,
                'serialized_property' => [
                    $fieldName => [
                        'private' => true
                    ]
                ]
            ],
            $entityConfig->get('schema')
        );
    }

    public function testPostFlushWithoutRememberedFieldConfig()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => []
                    ]
                ]
            ]
        );
        $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $fieldModel = new FieldConfigModel();
        $fieldModel->setEntity(new EntityConfigModel($entityClassName));

        $this->configManager->expects(self::never())
            ->method('getConfigIdByModel');
        $this->entityGenerator->expects(self::never())
            ->method('generateSchemaFiles');

        $this->listener->postFlush(
            new PostFlushConfigEvent([$fieldModel], $this->configManager)
        );
    }

    public function testPostFlushForChangedSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => []
                    ]
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $fieldModel = new FieldConfigModel();
        $fieldModel->setEntity(new EntityConfigModel($entityClassName));

        $this->configManager->expects(self::once())
            ->method('getConfigIdByModel')
            ->with(self::identicalTo($fieldModel), 'extend')
            ->willReturn($fieldConfig->getId());
        $this->entityGenerator->expects(self::once())
            ->method('generateSchemaFiles')
            ->with($entityConfig->get('schema'));

        $this->setHasChangedSerializedFields([$entityClassName => true]);
        $this->listener->postFlush(
            new PostFlushConfigEvent([$fieldModel], $this->configManager)
        );
    }

    public function testPostFlushForChangedNotSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => []
                    ]
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'is_serialized' => true,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $fieldModel = new FieldConfigModel();
        $fieldModel->setEntity(new EntityConfigModel($entityClassName));

        $this->configManager->expects(self::once())
            ->method('getConfigIdByModel')
            ->with(self::identicalTo($fieldModel), 'extend')
            ->willReturn($fieldConfig->getId());
        $this->entityGenerator->expects(self::once())
            ->method('generateSchemaFiles')
            ->with($entityConfig->get('schema'));

        $this->setHasChangedSerializedFields([$entityClassName => false]);
        $this->listener->postFlush(
            new PostFlushConfigEvent([$fieldModel], $this->configManager)
        );
    }

    public function testPostFlushWhenNoChangedFields()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $this->addEntityConfig(
            $entityClassName,
            [
                'state'  => ExtendScope::STATE_ACTIVE,
                'schema' => [
                    'entity'              => $entityClassName,
                    'serialized_property' => [
                        $fieldName => []
                    ]
                ]
            ]
        );
        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state' => ExtendScope::STATE_ACTIVE
            ]
        );

        $fieldModel = new FieldConfigModel();
        $fieldModel->setEntity(new EntityConfigModel($entityClassName));

        $this->configManager->expects(self::once())
            ->method('getConfigIdByModel')
            ->with(self::identicalTo($fieldModel), 'extend')
            ->willReturn($fieldConfig->getId());
        $this->entityGenerator->expects(self::never())
            ->method('generateSchemaFiles');

        $this->setHasChangedSerializedFields([$entityClassName => true]);
        $this->listener->postFlush(
            new PostFlushConfigEvent([$fieldModel], $this->configManager)
        );
    }

    public function testPostFlushForUnsupportedEntity()
    {
        $this->configManager->expects(self::never())
            ->method('getConfigIdByModel');
        $this->entityGenerator->expects(self::never())
            ->method('generateSchemaFiles');

        $this->listener->postFlush(
            new PostFlushConfigEvent([new \stdClass()], $this->configManager)
        );
    }

    public function testPreSetRequireUpdateEntityConfigNewSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_UPDATE]
        );

        $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['is_serialized' => true]
        );

        $this->listener->createField(
            new FieldConfigEvent($entityClassName, $fieldName, $this->configManager)
        );

        $event = new PreSetRequireUpdateEvent(['extend' => $entityConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertFalse($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateEntityConfigNoNewSerializedField()
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_UPDATE]
        );

        $event = new PreSetRequireUpdateEvent(['extend' => $entityConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateFieldConfigNotSerializedField()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['state' => ExtendScope::STATE_NEW]
        );

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateFieldConfigSerializedFieldConfigStateNotDelete()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state' => ExtendScope::STATE_NEW,
                'is_serialized' => true,
            ]
        );

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertFalse($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateFieldConfigSerializedFieldConfigStateDelete()
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state' => ExtendScope::STATE_DELETE,
                'is_serialized' => true,
            ]
        );

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertFalse($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateEmptyConfig()
    {
        $event = new PreSetRequireUpdateEvent([], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }
}
