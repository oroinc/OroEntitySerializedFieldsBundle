<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntitySerializedFieldsBundle\Exception\NoSuchPropertyException;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer as FieldsNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple storage to keep serialized data's fields for extended entity classes.
 */
final class EntitySerializedFieldsHolder
{
    private static array $serializedFields = [];

    private static ?ContainerInterface $container;

    private static ?ConfigManager $configManager;

    private static ?FieldsNormalizer $fieldsNormalizer;

    /**
     * @param string $class
     * @return array|null
     */
    public static function getEntityFields(string $class): ?array
    {
        $class = ClassUtils::getRealClass($class);

        if (!isset(self::$serializedFields[$class])) {
            $configManager = self::getConfigManager();
            $fieldConfigs = $configManager->getConfigs('extend', $class, true);
            $compactFieldConfigs = [];

            foreach ($fieldConfigs as $fieldConfig) {
                if ($fieldConfig->get('is_serialized')) {
                    $fieldConfigId = $fieldConfig->getId();
                    $compactFieldConfigs[$fieldConfigId->getFieldName()] = ['type' => $fieldConfigId->getFieldType()];
                }
            }

            self::$serializedFields[$class] = $compactFieldConfigs;
        }

        return array_keys(self::$serializedFields[$class]);
    }

    /**
     * @param ContainerInterface $container
     */
    public static function initialize(ContainerInterface $container)
    {
        self::$container = $container;
        self::$configManager = self::$fieldsNormalizer = null;
        self::$serializedFields = [];
    }

    /**
     * @param string $class
     * @param string $field
     * @param $value
     * @return mixed
     */
    public static function normalize(string $class, string $field, $value)
    {
        $class = ClassUtils::getRealClass($class);

        return self::getFieldsNormalizer()->normalize(self::getFieldType($class, $field), $value);
    }

    /**
     * @param string $class
     * @param string $field
     * @param $value
     * @return mixed
     */
    public static function denormalize(string $class, string $field, $value)
    {
        $class = ClassUtils::getRealClass($class);

        return self::getFieldsNormalizer()->denormalize(self::getFieldType($class, $field), $value);
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

    /**
     * @return FieldsNormalizer|null
     */
    private static function getFieldsNormalizer(): ?FieldsNormalizer
    {
        if (self::$fieldsNormalizer === null) {
            self::$fieldsNormalizer =
                self::$container->get('oro_serialized_fields.normalizer.fields_compound_normalizer');
        }

        return self::$fieldsNormalizer;
    }

    /**
     * @param string $class
     * @param string $field
     * @return string
     * @throws NoSuchPropertyException
     */
    private static function getFieldType(string $class, string $field): string
    {
        if (!isset(self::$serializedFields[$class][$field])) {
            throw new NoSuchPropertyException(
                sprintf(
                    'There is no "%s" field in "%s" entity',
                    $field,
                    $class
                )
            );
        }

        return self::$serializedFields[$class][$field]['type'];
    }
}
