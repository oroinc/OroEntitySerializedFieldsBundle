<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Event\AfterFlushFieldEvent;
use Oro\Bundle\EntityExtendBundle\Event\BeforePersistFieldEvent;
use Oro\Bundle\EntityExtendBundle\Event\CollectFieldOptionsEvent;

use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;

class ExtendFieldListener
{
    /** @var ExtendConfigDumper $dumper */
    protected $dumper;

    /** @var Session */
    protected $session;

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
        if ($isSerialized) {
            $options['extend']['is_serialized'] = true;
        }
        $event->setOptions($options);
    }
}
