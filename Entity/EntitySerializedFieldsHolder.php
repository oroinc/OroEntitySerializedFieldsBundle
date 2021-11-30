<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple storage to keep serialized data's fields for extended entity classes.
 */
final class EntitySerializedFieldsHolder
{
    private static array $serializedFields = [];

    private static ?ContainerInterface $container = null;

    private static ?ConfigManager $configManager = null;

    /**
     * @param string $class
     * @return array|null
     */
    public static function getEntityFields(string $class): ?array
    {
        $class = ClassUtils::getRealClass($class);
        if (!isset(self::$serializedFields[$class])) {
            $configManager = self::getConfigManager();
            $config = $configManager->getEntityConfig('extend', $class);
            $schema = $config->get('schema');
            $serializedConfig =  $schema['serialized_property'] ?? null;

            self::$serializedFields[$class] = is_array($serializedConfig)
                ? array_keys($serializedConfig)
                : [];
        }

        return self::$serializedFields[$class];
    }

    /**
     * @param ContainerInterface $configManager
     */
    public static function initialize(ContainerInterface $container)
    {
        self::$container = $container;
    }

    /**
     * @return ConfigManager|null
     */
    private static function getConfigManager(): ?ConfigManager
    {
        if (self::$configManager === null) {
            self::$configManager = self::$container->get('oro_entity_config.config_manager');
        }

        return self::$configManager;
    }
}
