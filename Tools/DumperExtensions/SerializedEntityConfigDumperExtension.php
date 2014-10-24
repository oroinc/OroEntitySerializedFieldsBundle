<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AbstractEntityConfigDumperExtension;

class SerializedEntityConfigDumperExtension extends AbstractEntityConfigDumperExtension
{
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
        return $actionType === ExtendConfigDumper::ACTION_PRE_UPDATE;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate()
    {
        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->configManager->getProvider('extend');

        $entityConfigs = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if ($entityConfig->is('is_extend')) {
                $serializedFields = $extendConfigProvider->filter(
                    function (ConfigInterface $field) {
                        return $field->is('is_serialized');
                    },
                    $entityConfig->getId()->getClassName()
                );

                /**
                 * Because of serialized field(s) data stored in special table column "serialized_data"
                 * we should not generate doctrine property for such field(s).
                 * Also we can't index such field(s), so they should not pass into "index" configuration.
                 */
                if ($serializedFields) {
                    $schema = $entityConfig->get('schema');
                    $index  = $entityConfig->get('index');
                    foreach ($serializedFields as $serializedField) {
                        $fieldName = $serializedField->getId()->getFieldName();

                        unset($schema['property'][$fieldName]);
                        unset($schema['doctrine'][$schema['entity']]['fields'][$fieldName]);
                        unset($index[$fieldName]);
                    }

                    $entityConfig->set('schema', $schema);
                    $entityConfig->set('index', $index ? : []);

                    $this->configManager->persist($entityConfig);
                }
            }
        }
    }
}
