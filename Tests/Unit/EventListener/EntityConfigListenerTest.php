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
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\EntityProxyUpdateConfigProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityConfigListenerTest extends TestCase
{
    private EntityProxyUpdateConfigProviderInterface&MockObject $entityProxyUpdateConfigProvider;
    private EntityGenerator&MockObject $entityGenerator;
    private RequestStack&MockObject $requestStack;
    private ConfigManager&MockObject $configManager;
    private ConfigProviderMock $extendConfigProvider;
    private EntityConfigListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityProxyUpdateConfigProvider = $this->createMock(EntityProxyUpdateConfigProviderInterface::class);
        $this->entityGenerator = $this->createMock(EntityGenerator::class);
        $this->requestStack = $this->createMock(RequestStack::class);
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
            $this->entityProxyUpdateConfigProvider,
            $this->entityGenerator,
            $this->requestStack
        );
    }

    private function setHasChangedSerializedFields(array $value): void
    {
        ReflectionUtil::setPropertyValue($this->listener, 'hasChangedSerializedFields', $value);
    }

    private function getHasChangedSerializedFields(): array
    {
        return ReflectionUtil::getPropertyValue($this->listener, 'hasChangedSerializedFields');
    }

    private function addEntityConfig(string $className, array $values = []): Config
    {
        return $this->extendConfigProvider->addEntityConfig($className, $values);
    }

    private function addFieldConfig(string $className, string $fieldName, array $values = []): Config
    {
        return $this->extendConfigProvider->addFieldConfig($className, $fieldName, 'string', $values);
    }

    public function testCreateSerializedFieldWhenEntityProxyUpdateNotAllowed(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';
        $entityConfigModelId = 123;
        $sessionKey = sprintf(FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED, $entityConfigModelId);

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['state' => ExtendScope::STATE_NEW]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::once())
            ->method('isEntityProxyUpdateAllowed')
            ->willReturn(false);
        $sessionMock = $this->createMock(Session::class);
        $this->requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock->expects($this->once())
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);
        $sessionMock->expects(self::once())
            ->method('isStarted')
            ->willReturn(true);
        $sessionMock->expects(self::once())
            ->method('has')
            ->with($sessionKey)
            ->willReturn(true);
        $sessionMock->expects(self::once())
            ->method('get')
            ->with($sessionKey)
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

        self::assertEquals(ExtendScope::STATE_NEW, $fieldConfig->get('state'));
        self::assertTrue($fieldConfig->get('is_serialized'));
        self::assertSame([], $this->getHasChangedSerializedFields());
    }

    public function testCreateFieldWhenSessionIsNotStarted(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['state' => ExtendScope::STATE_NEW]
        );
        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $sessionMock = $this->createMock(Session::class);
        $this->requestStack->expects(self::never())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('hasSession')
            ->willReturn(false);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);
        $sessionMock->expects(self::never())
            ->method('isStarted')
            ->willReturn(false);
        $sessionMock->expects(self::never())
            ->method('has');
        $sessionMock->expects(self::never())
            ->method('get');
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($fieldConfig));

        $this->listener->createField(
            new FieldConfigEvent($entityClassName, $fieldName, $this->configManager)
        );

        self::assertEquals(ExtendScope::STATE_NEW, $fieldConfig->get('state'));
        self::assertFalse($fieldConfig->has('is_serialized'));
        self::assertSame([], $this->getHasChangedSerializedFields());
    }

    public function testCreateNotSerializedFieldWhenSessionIsStarted(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';
        $entityConfigModelId = 123;
        $sessionKey = sprintf(FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED, $entityConfigModelId);

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['state' => ExtendScope::STATE_NEW]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $sessionMock = $this->createMock(Session::class);
        $this->requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock->expects($this->once())
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);
        $sessionMock->expects(self::once())
            ->method('isStarted')
            ->willReturn(true);
        $sessionMock->expects(self::once())
            ->method('has')
            ->with($sessionKey)
            ->willReturn(true);
        $sessionMock->expects(self::once())
            ->method('get')
            ->with($sessionKey)
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
        self::assertFalse($fieldConfig->has('is_serialized'));
        self::assertSame([], $this->getHasChangedSerializedFields());
    }

    public function testCreateSerializedFieldWhenSessionIsStarted(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';
        $entityConfigModelId = 123;
        $sessionKey = sprintf(FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED, $entityConfigModelId);

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['state' => ExtendScope::STATE_NEW]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::once())
            ->method('isEntityProxyUpdateAllowed')
            ->willReturn(true);
        $sessionMock = $this->createMock(Session::class);
        $this->requestStack->expects(self::once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects(self::once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $requestMock->expects($this->once())
            ->method('hasSession')
            ->willReturn(true);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($requestMock);
        $sessionMock->expects(self::once())
            ->method('isStarted')
            ->willReturn(true);
        $sessionMock->expects(self::once())
            ->method('has')
            ->with($sessionKey)
            ->willReturn(true);
        $sessionMock->expects(self::once())
            ->method('get')
            ->with($sessionKey)
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
        self::assertSame([$entityClassName => true], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushForNotExtendableEntity(): void
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig($entityClassName);

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $this->configManager->expects(self::never())
            ->method('getConfigChangeSet');
        $this->configManager->expects(self::never())
            ->method('persist');

        $event = new PreFlushConfigEvent(['entity' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);
    }

    public function testPreFlushWhenExtendedEntityConfigIsNotChanged(): void
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig($entityClassName);

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $this->configManager->expects(self::once())
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($entityConfig))
            ->willReturn([]);
        $this->configManager->expects(self::never())
            ->method('persist');

        $event = new PreFlushConfigEvent(['extend' => $entityConfig], $this->configManager);
        $this->listener->preFlush($event);
    }

    public function testPreFlushForEntityConfigWhenNoChangedSerializedFields(): void
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
    }

    public function testPreFlushForEntityConfigAndNotSerializedField(): void
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
    }

    public function testPreFlushForSerializedFieldShouldAlwaysUpdateSchema(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_UPDATE]
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
            ->method('calculateConfigChangeSet');
        $this->configManager->expects(self::once())
            ->method('persist');

        $event = new PreFlushConfigEvent(['extend' => $fieldConfig], $this->configManager);
        $this->listener->preFlush($event);

        self::assertEquals(ExtendScope::STATE_UPDATE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertSame(['Test\Entity' => true], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushShouldNotModifyEntityConfigState(): void
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

        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
    }

    public function testPreFlushShouldDoNothingIfEntityConfigStateIsNotChangedForEntityConfigAndSerializedField(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_DELETE]
        );
        $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['is_serialized' => true]
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

        self::assertEquals(ExtendScope::STATE_DELETE, $entityConfig->get('state'));
    }

    public function testPreFlushNewSerializedField(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_UPDATE]
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

        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertEquals(ExtendScope::STATE_ACTIVE, $fieldConfig->get('state'));
        self::assertSame([$entityClassName => true], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushDeletedSerializedField(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_UPDATE]
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

        self::assertEquals(ExtendScope::STATE_ACTIVE, $entityConfig->get('state'));
        self::assertTrue($fieldConfig->get('is_deleted'));
        self::assertSame([$entityClassName => true], $this->getHasChangedSerializedFields());
    }

    public function testPreFlushShouldUpdateEntitySchemaForNewSerializedField(): void
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

    public function testPreFlushShouldUpdateEntitySchemaForDeletedSerializedField(): void
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

    public function testPreFlushShouldUpdateEntitySchemaForRestoredSerializedField(): void
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

    public function testPreFlushShouldUpdateEntitySchemaForExistingSerializedField(): void
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

    public function testPreFlushShouldUpdateEntitySchemaForDeletedSerializedFieldThatAlreadyExistsInSchema(): void
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

    public function testPostFlushWithoutRememberedFieldConfig(): void
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

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $this->configManager->expects(self::never())
            ->method('getConfigIdByModel');
        $this->entityGenerator->expects(self::never())
            ->method('generateSchemaFiles');

        $this->listener->postFlush(
            new PostFlushConfigEvent([$fieldModel], $this->configManager)
        );
    }

    public function testPostFlushForChangedSerializedField(): void
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

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
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

    public function testPostFlushForChangedNotSerializedField(): void
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
                'is_serialized' => false,
                'state'         => ExtendScope::STATE_ACTIVE
            ]
        );

        $fieldModel = new FieldConfigModel();
        $fieldModel->setEntity(new EntityConfigModel($entityClassName));

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $this->configManager->expects(self::never())
            ->method('getConfigIdByModel');
        $this->entityGenerator->expects(self::never())
            ->method('generateSchemaFiles');

        $this->listener->postFlush(
            new PostFlushConfigEvent([$fieldModel], $this->configManager)
        );
    }

    public function testPostFlushWhenNoChangedFields(): void
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
            ['state' => ExtendScope::STATE_ACTIVE]
        );

        $fieldModel = new FieldConfigModel();
        $fieldModel->setEntity(new EntityConfigModel($entityClassName));

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
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

    public function testPostFlushForUnsupportedEntity(): void
    {
        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');
        $this->configManager->expects(self::never())
            ->method('getConfigIdByModel');
        $this->entityGenerator->expects(self::never())
            ->method('generateSchemaFiles');

        $this->listener->postFlush(
            new PostFlushConfigEvent([new \stdClass()], $this->configManager)
        );
    }

    public function testPreSetRequireUpdateEntityConfigNewSerializedField(): void
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

        $this->entityProxyUpdateConfigProvider->expects(self::exactly(2))
            ->method('isEntityProxyUpdateAllowed')
            ->willReturn(true);

        $this->listener->createField(
            new FieldConfigEvent($entityClassName, $fieldName, $this->configManager)
        );

        $event = new PreSetRequireUpdateEvent(['extend' => $entityConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertFalse($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateEntityConfigNoNewSerializedField(): void
    {
        $entityClassName = 'Test\Entity';

        $entityConfig = $this->addEntityConfig(
            $entityClassName,
            ['state' => ExtendScope::STATE_UPDATE]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::once())
            ->method('isEntityProxyUpdateAllowed')
            ->willReturn(true);

        $event = new PreSetRequireUpdateEvent(['extend' => $entityConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateFieldConfigNotSerializedField(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            ['state' => ExtendScope::STATE_NEW]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::once())
            ->method('isEntityProxyUpdateAllowed')
            ->willReturn(true);

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateFieldConfigSerializedFieldConfigStateNotDelete(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state'         => ExtendScope::STATE_NEW,
                'is_serialized' => true,
            ]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertFalse($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateFieldConfigSerializedFieldConfigStateDelete(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state'         => ExtendScope::STATE_DELETE,
                'is_serialized' => true,
            ]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertFalse($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateEmptyConfig(): void
    {
        $this->entityProxyUpdateConfigProvider->expects(self::never())
            ->method('isEntityProxyUpdateAllowed');

        $event = new PreSetRequireUpdateEvent([], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }

    public function testPreSetRequireUpdateWhenEntityProxyUpdateNotAllowed(): void
    {
        $entityClassName = 'Test\Entity';
        $fieldName = 'testField';

        $fieldConfig = $this->addFieldConfig(
            $entityClassName,
            $fieldName,
            [
                'state'         => ExtendScope::STATE_DELETE,
                'is_serialized' => false,
            ]
        );

        $this->entityProxyUpdateConfigProvider->expects(self::once())
            ->method('isEntityProxyUpdateAllowed')
            ->willReturn(false);

        $event = new PreSetRequireUpdateEvent(['extend' => $fieldConfig], $this->configManager);
        self::assertTrue($event->isUpdateRequired());

        $this->listener->preSetRequireUpdate($event);
        self::assertTrue($event->isUpdateRequired());
    }
}
