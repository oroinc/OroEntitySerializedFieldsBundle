<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Column options Guesser for serialized field configs.
 */
class SerializedColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
        if ($extendFieldConfig && $extendFieldConfig->is('is_serialized')) {
            $options = [
                'frontend_type' => Property::TYPE_HTML,
                'type'          => 'twig',
                'template'      => '@OroEntitySerializedFields/Datagrid/Property/serialized.html.twig',
                'context'       => [
                    'field_name' => $property,
                    'field_type' => $type,
                ],
            ];
        }

        return isset($options)
            ? new ColumnGuess($options, ColumnGuess::HIGH_CONFIDENCE)
            : null;
    }

    /**
     * @param string $scope
     * @param string $class
     * @param string $property
     *
     * @return ConfigInterface
     */
    protected function getFieldConfig($scope, $class, $property)
    {
        $configProvider = $this->configManager->getProvider($scope);

        return $configProvider->hasConfig($class, $property)
            ? $configProvider->getConfig($class, $property)
            : null;
    }
}
