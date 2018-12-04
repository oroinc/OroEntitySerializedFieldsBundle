<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreSetRequireUpdateEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * The entity config listener to manage serialized fields.
 */
class EntityConfigListener
{
    /** @var EntityGenerator $entityGenerator */
    protected $entityGenerator;

    /** @var Session */
    protected $session;

    /** @var array [entity class => true/false, ...] */
    private $hasChangedSerializedFields = [];

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
    public function createField(FieldConfigEvent $event)
    {
        $className = $event->getClassName();
        $fieldName = $event->getFieldName();
        $configManager = $event->getConfigManager();

        $fieldConfig = $configManager->getFieldConfig('extend', $className, $fieldName);

        $isSerialized = false;
        if ($this->session->isStarted()) {
            $sessionKey = sprintf(
                FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED,
                $configManager->getConfigModelId($className)
            );

            $isSerialized = $this->session->get($sessionKey, false);
        }

        if ($fieldConfig->is('is_serialized')) {
            $this->hasChangedSerializedFields[$className] = true;
        } elseif (!array_key_exists($className, $this->hasChangedSerializedFields)) {
            $this->hasChangedSerializedFields[$className] = false;
        }

        $fieldConfig->set('is_serialized', $isSerialized);
        if ($isSerialized) {
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }

        $configManager->persist($fieldConfig);
    }

    /**
     * This method is preserved for backwards compatibility
     *
     * @param PreFlushConfigEvent $event
     */
    public function initializeEntity(PreFlushConfigEvent $event)
    {
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('extend');
        if (null === $config) {
            return;
        }

        $configManager = $event->getConfigManager();
        $changeSet     = $configManager->getConfigChangeSet($config);
        if (empty($changeSet)) {
            $event->stopPropagation();

            return;
        }

        $className = $event->getClassName();
        if ($event->isFieldConfig() && $config->is('is_serialized')) { // serialized field config
            /**
             * Case with creating new serialized field (fired from field persist):
             *  - field's "state" attribute should be "Active"
             *  - owning entity "state" attribute should NOT be changed
             */
            if (!$config->is('state', ExtendScope::STATE_DELETE)) {
                if (!$config->is('state', ExtendScope::STATE_ACTIVE)) {
                    $this->hasChangedSerializedFields[$className] = true;
                    $config->set('state', ExtendScope::STATE_ACTIVE);
                    $configManager->persist($config);
                    $configManager->calculateConfigChangeSet($config);
                }
            }

            /**
             * Case with deletion of serialized field:
             *  - field's "is_deleted" attribute should be set to "true"
             *  - owning entity "state" attribute should NOT be changed
             */
            if ($config->is('state', ExtendScope::STATE_DELETE)) {
                $this->hasChangedSerializedFields[$className] = true;
                $config->set('is_deleted', true);
                $configManager->persist($config);
                $configManager->calculateConfigChangeSet($config);
            }

            /**
             * update owning entity "schema" to be ready to refresh extended entity proxy immediately
             */
            $entityConfig = $configManager->getEntityConfig('extend', $className);
            $schema = $entityConfig->get('schema', false, []);
            if (!empty($schema)
                && $this->updateEntitySchema($schema, $config->getId()->getFieldName(), $config->is('is_deleted'))
            ) {
                $entityConfig->set('schema', $schema);
                $configManager->persist($entityConfig);
                $configManager->calculateConfigChangeSet($entityConfig);
            }
        }
    }

    /**
     * In case of flushing new serialized field, proxies for owning entity should be regenerated.
     *
     * @param PostFlushConfigEvent $event
     */
    public function postFlush(PostFlushConfigEvent $event)
    {
        $toUpdate = [];
        $configManager = $event->getConfigManager();
        foreach ($event->getModels() as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }

            $className = $model->getEntity()->getClassName();
            if (isset($toUpdate[$className]) || !isset($this->hasChangedSerializedFields[$className])) {
                continue;
            }

            /** @var FieldConfigId $configId */
            $configId = $configManager->getConfigIdByModel($model, 'extend');
            $fieldConfig = $configManager->getFieldConfig('extend', $className, $configId->getFieldName());
            if ($fieldConfig->is('is_serialized')) {
                $toUpdate[$className] = $configManager->getEntityConfig('extend', $className);
            }
        }
        foreach ($toUpdate as $className => $entityConfig) {
            $schema = $entityConfig->get('schema');
            if ($schema) {
                $this->entityGenerator->generateSchemaFiles($schema);
            }
        }

        $this->hasChangedSerializedFields = [];
    }

    /**
     * @param array  $schema
     * @param string $fieldName
     * @param bool   $isDeletedField
     *
     * @return bool
     */
    protected function updateEntitySchema(array &$schema, $fieldName, $isDeletedField)
    {
        $hasChanges = false;
        if (isset($schema['serialized_property'][$fieldName])) {
            if ($isDeletedField) {
                if (!isset($schema['serialized_property'][$fieldName]['private'])
                    || !$schema['serialized_property'][$fieldName]['private']
                ) {
                    $schema['serialized_property'][$fieldName]['private'] = true;
                    $hasChanges                                           = true;
                }
            } elseif (isset($schema['serialized_property'][$fieldName]['private'])) {
                unset($schema['serialized_property'][$fieldName]['private']);
                $hasChanges = true;
            }
        } else {
            $schema['serialized_property'][$fieldName] = [];
            if ($isDeletedField) {
                $schema['serialized_property'][$fieldName]['private'] = true;
            }
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * @param PreSetRequireUpdateEvent $event
     */
    public function preSetRequireUpdate(PreSetRequireUpdateEvent $event)
    {
        $config = $event->getConfig('extend');

        $className = $event->getClassName();
        if (($event->isEntityConfig() && isset($this->hasChangedSerializedFields[$className])) // entity config
            || (!$event->isEntityConfig() && $config->is('is_serialized')) // field config
        ) {
            $event->setUpdateRequired(false);
        }
    }
}
