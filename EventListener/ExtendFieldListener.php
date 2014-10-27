<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Event\AfterFlushFieldEvent;
use Oro\Bundle\EntityExtendBundle\Event\BeforePersistFieldEvent;
use Oro\Bundle\EntityExtendBundle\Event\CollectFieldOptionsEvent;
use Oro\Bundle\EntityExtendBundle\Event\BeforeDeletePersistFieldEvent;

use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;

class ExtendFieldListener implements EventSubscriberInterface
{
    /** @var ExtendConfigDumper $dumper */
    protected $dumper;

    /** @var Session */
    protected $session;

    /** @var string */
    protected $originalEntityState = null;

    /**
     * @param ExtendConfigDumper $dumper
     * @param Session            $session
     */
    public function __construct(ExtendConfigDumper $dumper, Session $session)
    {
        $this->dumper  = $dumper;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_PERSIST_CONFIG     => ['persistConfig', -100],
        ];
    }

    /**
     * @param BeforePersistFieldEvent $event
     */
    public function beforePersist(BeforePersistFieldEvent $event)
    {
        $fieldConfigModel = $event->getFieldConfigModel();

        $extendScope = $fieldConfigModel->toArray('extend');
        if (isset($extendScope['is_serialized']) && $extendScope['is_serialized']) {
            $extendScope['state'] = ExtendScope::STATE_ACTIVE;
            $indexes              = $fieldConfigModel->getIndexedValues()->toArray();
            array_walk(
                $indexes,
                function (ConfigModelIndexValue &$index) {
                    if ($index->getScope() == 'extend' && $index->getCode() == 'state') {
                        $index->setValue(ExtendScope::STATE_ACTIVE);
                    }
                }
            );
            $fieldConfigModel->fromArray('extend', $extendScope, $indexes);

            $event->getEntityConfig()->set('state', $event->getOriginalExtendEntityConfig()->get('state'));
        }
    }

    /**
     * @param AfterFlushFieldEvent $event
     */
    public function afterFlush(AfterFlushFieldEvent $event)
    {
        $fieldConfigModel = $event->getConfigModel();
        $extendScope      = $fieldConfigModel->toArray('extend');
        if (isset($extendScope['is_serialized']) && $extendScope['is_serialized']) {
            $this->dumper->dump($event->getClassName(), false);
        }
    }

    /**
     * @param CollectFieldOptionsEvent $event
     */
    public function collectOptions(CollectFieldOptionsEvent $event)
    {
        $options      = $event->getOptions();
        $fieldModel   = $event->getFieldConfigModel();
        $isSerialized = $this->session->get(
            sprintf(FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED, $fieldModel->getEntity()->getId())
        );
        $options['extend']['is_serialized'] = $isSerialized;

        $event->setOptions($options);
    }

    /**
     * @param BeforeDeletePersistFieldEvent $event
     */
    public function beforeDeletePersist(BeforeDeletePersistFieldEvent $event)
    {
        $fieldConfig = $event->getFieldConfig();

        if ($fieldConfig->get('is_serialized')) {
            $fieldConfig->set('is_deleted', true);
            $event->getEntityConfig()->set('upgradeable', $event->getOriginalExtendEntityConfig()->get('upgradeable'));
            $this->originalEntityState = $event->getOriginalExtendEntityConfig()->get('state');
        }
    }

    /**
     * Restore entity state on delete serialized field
     *
     * @param PersistConfigEvent $event
     */
    public function persistConfig(PersistConfigEvent $event)
    {
        $eventConfig   = $event->getConfig();
        $eventConfigId = $eventConfig->getId();
        $scope         = $eventConfigId->getScope();

        if (!$eventConfigId instanceof FieldConfigId) {
            return;
        }

        $change       = $event->getConfigManager()->getConfigChangeSet($eventConfig);
        $stateChanged = isset($change['state']);
        $isCustom     = $eventConfig->is('owner', ExtendScope::OWNER_CUSTOM);

        if ($isCustom && 'extend' == $scope && $stateChanged && $eventConfig->get('is_deleted')) {
            $configManager = $event->getConfigManager();
            $className     = $eventConfig->getId()->getClassName();
            $entityConfig  = $configManager->getProvider($scope)->getConfig($className);

            $entityConfig->set('state', $this->originalEntityState);
            $configManager->persist($entityConfig);
        }
    }
}
