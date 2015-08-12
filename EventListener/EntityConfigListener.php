<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\FlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;

use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;

class EntityConfigListener
{
    /** @var EntityGenerator $entityGenerator */
    protected $entityGenerator;

    /** @var Session */
    protected $session;

    /** @var Config|null */
    private $originalEntityConfig = null;

    /** @var Config|null */
    private $originalFieldConfig = null;

    /**
     * @param EntityGenerator $entityGenerator
     * @param Session         $session
     */
    public function __construct(EntityGenerator $entityGenerator, Session $session)
    {
        $this->entityGenerator = $entityGenerator;
        $this->session         = $session;
    }

    /**
     * @param FieldConfigEvent $event
     */
    public function newFieldConfig(FieldConfigEvent $event)
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = $event->getConfigManager()->getProvider('extend');

        $entityClassName = $event->getClassName();
        $entityModelId   = $event->getConfigManager()->getConfigEntityModel($entityClassName)->getId();

        if ($this->session->isStarted()) {
            $sessionKey      = sprintf(
                FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED,
                $entityModelId
            );

            $isSerialized = $this->session->get($sessionKey, false);
        } else {
            $isSerialized = false;
        }

        $fieldConfig = $configProvider->getConfig($event->getClassName(), $event->getFieldName());

        $this->originalFieldConfig = clone $fieldConfig;

        $fieldConfig->set('is_serialized', $isSerialized);
        if ($isSerialized) {
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }

        $configProvider->persist($fieldConfig);
        $configProvider->getConfigManager()->calculateConfigChangeSet($fieldConfig);
    }

    /**
     * @param PersistConfigEvent $event
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function persistConfig(PersistConfigEvent $event)
    {
        $eventConfig   = $event->getConfig();
        $eventConfigId = $event->getConfigId();

        $change = $event->getConfigManager()->getConfigChangeSet($eventConfig);
        if (empty($change)) {
            $event->stopPropagation();

            return;
        }

        /**
         * Case with creating new serialized field (fired from entity persist):
         *  - owning entity "state" attribute should NOT be changed
         */
        if ($eventConfigId instanceof EntityConfigId
            && $this->originalEntityConfig !== null
            && $this->originalFieldConfig !== null
            && $this->originalFieldConfig->is('is_serialized')
        ) {
            $this->revertEntityState($event);
        }

        /**
         * Case with creating new serialized field (fired from field persist):
         *  - field's "state" attribute should be "Active"
         *  - owning entity "state" attribute should NOT be changed
         */
        if ($eventConfigId instanceof FieldConfigId
            && $this->originalEntityConfig !== null
            && $eventConfig->is('is_serialized')
            && !$eventConfig->is('state', ExtendScope::STATE_DELETE)
        ) {
            $this->revertEntityState($event);
            if (!$eventConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                $eventConfig->set('state', ExtendScope::STATE_ACTIVE);

                $event->getConfigManager()->persist($eventConfig);
                $event->getConfigManager()->calculateConfigChangeSet($eventConfig);
            }
        }

        /**
         * Case with deletion of serialized field:
         *  - field's "is_deleted" attribute should be set to "true"
         *  - owning entity "state" attribute should NOT be changed
         */
        if ($eventConfigId instanceof FieldConfigId
            && $eventConfig->is('state', ExtendScope::STATE_DELETE)
            && $eventConfig->is('is_serialized')
        ) {
            $eventConfig->set('is_deleted', true);

            $this->originalFieldConfig = clone $eventConfig;

            $event->getConfigManager()->persist($eventConfig);
            $event->getConfigManager()->calculateConfigChangeSet($eventConfig);
        }
    }

    /**
     * Starts before all events.
     * The main aim of method to store original entity state for future events.
     *
     * @param PersistConfigEvent $event
     */
    public function updateEntityConfig(PersistConfigEvent $event)
    {
        /** @var ConfigProvider $configProvider */
        $configProvider = $event->getConfigManager()->getProvider('extend');

        $entityClassName = $event->getConfigId()->getClassName();
        $entityConfig    = $configProvider->getConfig($entityClassName);

        if ($this->originalEntityConfig == null) {
            $this->originalEntityConfig = clone $entityConfig;
        }
    }

    /**
     * In case of flushing new serialized field, proxies for owning entity should be regenerated.
     *
     * @param FlushConfigEvent $event
     */
    public function flushConfig(FlushConfigEvent $event)
    {
        $models        = $event->getModels();
        $configManager = $event->getConfigManager();
        foreach ($models as $model) {
            if (!$model instanceof FieldConfigModel || $this->originalFieldConfig === null) {
                continue;
            }

            /** @var FieldConfigId $configId */
            $configId    = $configManager->getConfigIdByModel($model, 'extend');
            $fieldConfig = $configManager->getProvider('extend')->getConfig(
                $configId->getClassName(),
                $configId->getFieldName()
            );

            if ($fieldConfig->is('is_serialized')) {
                $entityConfig = $configManager->getProvider('extend')->getConfig($configId->getClassName());
                $schema       = $entityConfig->get('schema');
                if ($schema) {
                    $this->entityGenerator->generateSchemaFiles($schema);
                }
            }
        }
    }

    /**
     * Reverts entity state to it's original value
     *
     * @param PersistConfigEvent $event
     */
    protected function revertEntityState(PersistConfigEvent $event)
    {
        $entityConfig = $this->getEntityConfig($event);
        if ($entityConfig->get('state') != $this->originalEntityConfig->get('state')) {
            $entityConfig->set('state', $this->originalEntityConfig->get('state'));

            $event->getConfigManager()->persist($entityConfig);
            $event->getConfigManager()->calculateConfigChangeSet($entityConfig);
        }
    }

    /**
     * @param PersistConfigEvent $event
     *
     * @return ConfigInterface
     */
    protected function getEntityConfig(PersistConfigEvent $event)
    {
        $className    = $event->getConfigId()->getClassName();
        $entityConfig = $event->getConfigManager()->getProvider('extend')->getConfig($className);

        return $entityConfig;
    }
}
