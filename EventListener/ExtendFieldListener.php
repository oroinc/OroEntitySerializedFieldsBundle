<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Event\AfterFlushFieldEvent;
use Oro\Bundle\EntityExtendBundle\Event\BeforePersistFieldEvent;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendFieldListener
{
    /** @var ExtendConfigDumper $dumper */
    protected $dumper;

    public function __construct(ExtendConfigDumper $dumper)
    {
        $this->dumper = $dumper;
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
            $fieldConfigModel->fromArray('extend', $extendScope, $fieldConfigModel->getIndexedValues()->toArray());
            //$event->getEntityConfig()->set('state', ExtendScope::STATE_ACTIVE);
        }
    }

    /**
     * @param AfterFlushFieldEvent $event
     */
    public function afterFlush(AfterFlushFieldEvent $event)
    {
        $fieldConfigModel = $event->getConfigModel();
        $extendScope = $fieldConfigModel->toArray('extend');
        if (isset($extendScope['is_serialized']) && $extendScope['is_serialized']) {
            $this->dumper->dump();
        }
    }
} 