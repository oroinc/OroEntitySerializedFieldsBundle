<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\DumperExtensions;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * The extension for the entity config dumper that do the following:
 * * adds "serialized_data" field that are used to store values of serialized fields
 * * updates entity configuration related to serialized fields
 */
class SerializedEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    const SERIALIZED_DATA_FIELD = 'serialized_data';

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType)
    {
        return $actionType === ExtendConfigDumper::ACTION_POST_UPDATE;
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate()
    {
        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $platform = $this->configManager->getEntityManager()->getConnection()->getDatabasePlatform();
        $isPg = $platform instanceof PostgreSQL94Platform;

        /**
         * Because of values of all serialized fields are stored in "serialized_data" column we should:
         *  - not generate doctrine property for serialized fields.
         *  - generate special getters and setters for serialized fields.
         *  - serialized fields can't be indexed, so they should be removed from "index" configuration.
         */
        $entityConfigs = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if ($entityConfig->is('is_extend')) {
                $schema = $entityConfig->get('schema');
                if (empty($schema['entity'])) {
                    continue;
                }

                $entityClassName = $schema['entity'];
                $schema['property'][self::SERIALIZED_DATA_FIELD] = [];
                $schema['doctrine'][$entityClassName]['fields'][self::SERIALIZED_DATA_FIELD] = [
                    'column' => self::SERIALIZED_DATA_FIELD,
                    'type' => Types::JSON,
                    'nullable' => true,
                ];
                if ($isPg) {
                    $schema['doctrine'][$entityClassName]['fields'][self::SERIALIZED_DATA_FIELD]['options'] = [
                        'jsonb' => true
                    ];
                }

                $serializedFields = $extendConfigProvider->filter(
                    function (ConfigInterface $field) {
                        return $field->is('is_serialized');
                    },
                    $entityConfig->getId()->getClassName(),
                    true
                );

                if (!empty($serializedFields)) {
                    $indexes = $entityConfig->get('index', false, []);
                    $serializedProperties = [];
                    foreach ($serializedFields as $fieldConfig) {
                        $fieldName = $fieldConfig->getId()->getFieldName();

                        $serializedProperties[$fieldName] = [];
                        if ($fieldConfig->is('is_deleted')) {
                            $serializedProperties[$fieldName]['private'] = true;
                        }

                        unset(
                            $schema['property'][$fieldName],
                            $schema['doctrine'][$entityClassName]['fields'][$fieldName],
                            $indexes[$fieldName]
                        );
                    }
                    if (empty($serializedProperties)) {
                        unset($schema['serialized_property']);
                    } else {
                        $schema['serialized_property'] = $serializedProperties;
                    }
                    $entityConfig->set('index', $indexes);
                }

                $entityConfig->set('schema', $schema);

                $this->configManager->persist($entityConfig);
            }
        }
    }
}
