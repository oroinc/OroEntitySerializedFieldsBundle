<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
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
    private $originalEntityConfig;

    /** @var Config|null */
    private $originalFieldConfig;

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
        $className     = $event->getClassName();
        $configManager = $event->getConfigManager();

        if ($this->session->isStarted()) {
            $sessionKey = sprintf(
                FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED,
                $configManager->getConfigModelId($className)
            );

            $isSerialized = $this->session->get($sessionKey, false);
        } else {
            $isSerialized = false;
        }

        $fieldConfig = $configManager->getProvider('extend')->getConfig($className, $event->getFieldName());

        $this->originalFieldConfig = clone $fieldConfig;

        $fieldConfig->set('is_serialized', $isSerialized);
        if ($isSerialized) {
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }

        $configManager->persist($fieldConfig);
    }

    /**
     * Starts before all events.
     * The main aim of method to store original entity state for future events.
     *
     * @param PreFlushConfigEvent $event
     */
    public function initializeEntity(PreFlushConfigEvent $event)
    {
        $className = $event->getClassName();
        if ($this->originalEntityConfig === null) {
            $entityConfig = $event->getConfigManager()->getProvider('extend')->getConfig($className);

            $this->originalEntityConfig = clone $entityConfig;
        }
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

        if ($event->isEntityConfig()) { // entity config
            /**
             * Case with creating new serialized field (fired from entity persist):
             *  - owning entity "state" attribute should NOT be changed
             */
            if ($this->originalEntityConfig !== null
                && $this->originalFieldConfig !== null
                && $this->originalFieldConfig->is('is_serialized')
            ) {
                $this->revertEntityState($configManager, $event->getClassName());
            }
        } elseif ($config->is('is_serialized')) { // serialized field config
            /**
             * Case with creating new serialized field (fired from field persist):
             *  - field's "state" attribute should be "Active"
             *  - owning entity "state" attribute should NOT be changed
             */
            if ($this->originalEntityConfig !== null && !$config->is('state', ExtendScope::STATE_DELETE)) {
                $this->revertEntityState($configManager, $event->getClassName());
                if (!$config->is('state', ExtendScope::STATE_ACTIVE)) {
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
                $config->set('is_deleted', true);

                $this->originalFieldConfig = clone $config;

                $configManager->persist($config);
                $configManager->calculateConfigChangeSet($config);
            }

            /**
             * update owning entity "schema" to be ready to refresh extended entity proxy immediately
             * also {@see flushConfig}
             */
            $entityConfig = $this->getEntityConfig($configManager, $event->getClassName());
            $schema       = $entityConfig->get('schema', false, []);
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
        if (null === $this->originalFieldConfig) {
            return;
        }

        $configManager = $event->getConfigManager();
        foreach ($event->getModels() as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }

            /** @var FieldConfigId $configId */
            $configId    = $configManager->getConfigIdByModel($model, 'extend');
            $fieldConfig = $configManager->getProvider('extend')->getConfig(
                $configId->getClassName(),
                $configId->getFieldName()
            );

            if ($fieldConfig->is('is_serialized')) {
                $entityConfig = $this->getEntityConfig($configManager, $configId->getClassName());
                $schema       = $entityConfig->get('schema');
                if ($schema) {
                    $this->entityGenerator->generateSchemaFiles($schema);
                }
            }
        }
    }

    /**
     * @param ConfigManager $configManager
     * @param string        $className
     *
     * @return ConfigInterface
     */
    protected function getEntityConfig(ConfigManager $configManager, $className)
    {
        return $configManager->getProvider('extend')->getConfig($className);
    }

    /**
     * Reverts entity state to it's original value
     *
     * @param ConfigManager $configManager
     * @param string        $className
     */
    protected function revertEntityState(ConfigManager $configManager, $className)
    {
        $entityConfig = $this->getEntityConfig($configManager, $className);
        if ($entityConfig->get('state') !== $this->originalEntityConfig->get('state')) {
            $entityConfig->set('state', $this->originalEntityConfig->get('state'));

            $configManager->persist($entityConfig);
            $configManager->calculateConfigChangeSet($entityConfig);
        }
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
}
