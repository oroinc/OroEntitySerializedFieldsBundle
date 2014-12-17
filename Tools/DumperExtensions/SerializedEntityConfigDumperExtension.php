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
         * Because of serialized field(s) data stored in special table column "serialized_data"
         * we should:
         *  -- add doctrine property 'serialized_field' into entity class.
         *  -- not generate doctrine property for serialized field(s).
         * Also we can't index such field(s), so they should not pass into "index" configuration.
         */
        $entityConfigs = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if ($entityConfig->is('is_extend')) {
                $schema = $entityConfig->get('schema');

                if (empty($schema['entity'])) {
                    continue;
                }
                $schema['property'][self::SERIALIZED_DATA_FIELD] = self::SERIALIZED_DATA_FIELD;
                $schema['doctrine'][$schema['entity']]['fields'][self::SERIALIZED_DATA_FIELD] = [
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

                if ($serializedFields) {
                    $index  = $entityConfig->get('index');
                    foreach ($serializedFields as $serializedField) {
                        $fieldName = $serializedField->getId()->getFieldName();

                        unset($schema['property'][$fieldName]);
                        unset($schema['doctrine'][$schema['entity']]['fields'][$fieldName]);
                        unset($index[$fieldName]);
                    }
                    $entityConfig->set('index', $index ? : []);
                }

                $entityConfig->set('schema', $schema);

                $this->configManager->persist($entityConfig);
            }
        }
    }
}
