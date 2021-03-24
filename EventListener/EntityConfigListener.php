<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreSetRequireUpdateEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\EntityProxyUpdateConfigProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * The entity config listener to manage serialized fields.
 */
class EntityConfigListener
{
    private EntityProxyUpdateConfigProviderInterface $entityProxyUpdateConfigProvider;
    private EntityGenerator $entityGenerator;
    private Session $session;

    /** @var array [entity class => true/false, ...] */
    private array $hasChangedSerializedFields = [];

    public function __construct(
        EntityProxyUpdateConfigProviderInterface $entityProxyUpdateConfigProvider,
        EntityGenerator $entityGenerator,
        Session $session
    ) {
        $this->entityProxyUpdateConfigProvider = $entityProxyUpdateConfigProvider;
        $this->entityGenerator = $entityGenerator;
        $this->session = $session;
    }

    public function createField(FieldConfigEvent $event): void
    {
        $className = $event->getClassName();
        $configManager = $event->getConfigManager();
        $fieldConfig = $configManager->getFieldConfig('extend', $className, $event->getFieldName());

        if ($this->session->isStarted()) {
            $sessionKey = sprintf(
                FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED,
                $configManager->getConfigModelId($className)
            );
            if ($this->session->has($sessionKey) && $this->session->get($sessionKey)) {
                $fieldConfig->set('is_serialized', true);
            }
        }
        if ($fieldConfig->is('is_serialized')
            && $this->entityProxyUpdateConfigProvider->isEntityProxyUpdateAllowed()
        ) {
            $this->hasChangedSerializedFields[$className] = true;
            $fieldConfig->set('state', ExtendScope::STATE_ACTIVE);
        }
        $configManager->persist($fieldConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preFlush(PreFlushConfigEvent $event): void
    {
        $config = $event->getConfig('extend');
        if (null === $config) {
            return;
        }

        $configManager = $event->getConfigManager();
        $changeSet = $configManager->getConfigChangeSet($config);
        if (!$changeSet) {
            return;
        }

        if (!$event->isFieldConfig()
            || !$config->is('is_serialized')
            || !$this->entityProxyUpdateConfigProvider->isEntityProxyUpdateAllowed()
        ) {
            return;
        }

        $className = $event->getClassName();

        /**
         * Case with creating new serialized field (fired from field persist):
         *  - field's "state" attribute should be "Active"
         *  - owning entity "state" attribute should NOT be changed
         */
        if (!$config->is('state', ExtendScope::STATE_DELETE)
            && !$config->is('state', ExtendScope::STATE_ACTIVE)
        ) {
            $this->hasChangedSerializedFields[$className] = true;
            $config->set('state', ExtendScope::STATE_ACTIVE);
            $configManager->persist($config);
            $configManager->calculateConfigChangeSet($config);
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
        if ($schema
            && $this->updateEntitySchema($schema, $config->getId()->getFieldName(), $config->is('is_deleted'))
        ) {
            $entityConfig->set('schema', $schema);
            $configManager->persist($entityConfig);
            $configManager->calculateConfigChangeSet($entityConfig);
        }
    }

    public function postFlush(PostFlushConfigEvent $event): void
    {
        if (!$this->hasChangedSerializedFields) {
            return;
        }

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

            if ($this->getFieldConfig($model, $configManager)->is('is_serialized')) {
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

    private function getFieldConfig(FieldConfigModel $model, ConfigManager $configManager): ConfigInterface
    {
        $configId = $configManager->getConfigIdByModel($model, 'extend');

        return $configManager->getFieldConfig('extend', $configId->getClassName(), $configId->getFieldName());
    }

    private function updateEntitySchema(array &$schema, string $fieldName, bool $isDeletedField): bool
    {
        $hasChanges = false;
        if (isset($schema['serialized_property'][$fieldName])) {
            if ($isDeletedField) {
                if (!isset($schema['serialized_property'][$fieldName]['private'])
                    || !$schema['serialized_property'][$fieldName]['private']
                ) {
                    $schema['serialized_property'][$fieldName]['private'] = true;
                    $hasChanges = true;
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

    public function preSetRequireUpdate(PreSetRequireUpdateEvent $event): void
    {
        $config = $event->getConfig('extend');
        if (null === $config) {
            return;
        }

        if (!$this->entityProxyUpdateConfigProvider->isEntityProxyUpdateAllowed()) {
            return;
        }

        if ($this->isUpdateNotRequired($config, $event->getClassName(), $event->isEntityConfig())) {
            $event->setUpdateRequired(false);
        }
    }

    private function isUpdateNotRequired(ConfigInterface $config, string $entityClass, bool $isEntityConfig): bool
    {
        if ($isEntityConfig) {
            return isset($this->hasChangedSerializedFields[$entityClass]);
        }

        return $config->is('is_serialized');
    }
}
