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
        if ($actionType === ExtendConfigDumper::ACTION_PRE_UPDATE) {
            return true;
        }

        return false;
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
                /** @var ConfigInterface[] $entityFields */
                $entityFields = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
                $entityFields = array_filter(
                    $entityFields,
                    function (ConfigInterface $entityField) {
                        return $entityField->get('is_serialized', false, false);
                    }
                );

                if ($entityFields) {
                    $schema = $entityConfig->get('schema');
                    $index  = $entityConfig->get('index');
                    foreach ($entityFields as $entityField) {
                        unset($schema['property'][$entityField->getId()->getFieldName()]);
                        unset($schema['doctrine'][$schema['entity']]['fields'][$entityField->getId()->getFieldName()]);
                        unset($index[$entityField->getId()->getFieldName()]);
                    }

                    $entityConfig->set('schema', $schema);
                    $entityConfig->set('index', $index ? : []);
                    $this->configManager->persist($entityConfig);
                }
            }
        }
    }
}
