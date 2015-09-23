<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Tools\GeneratorExtensions\SerializedDataGeneratorExtension;

class SerializedEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
    const SERIALIZED_DATA_FIELD = SerializedDataGeneratorExtension::SERIALIZED_DATA_FIELD;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
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
                    'column'   => self::SERIALIZED_DATA_FIELD,
                    'type'     => 'array',
                    'nullable' => true,
                ];

                $serializedFields = $extendConfigProvider->filter(
                    function (ConfigInterface $field) {
                        return $field->is('is_serialized');
                    },
                    $entityConfig->getId()->getClassName()
                );

                if (!empty($serializedFields)) {
                    $indexes              = $entityConfig->get('index', false, []);
                    $serializedProperties = [];
                    foreach ($serializedFields as $fieldConfig) {
                        $fieldName = $fieldConfig->getId()->getFieldName();

                        if (isset($schema['property'][$fieldName])) {
                            $serializedProperties[$fieldName] = $schema['property'][$fieldName];
                            unset($schema['property'][$fieldName]);
                        }
                        unset($schema['doctrine'][$entityClassName]['fields'][$fieldName]);
                        unset($indexes[$fieldName]);
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
