<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Grid\ExtendColumnOptionsGuesser;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class SerializedColumnOptionsGuesser extends ExtendColumnOptionsGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
        if ($extendFieldConfig->is('is_serialized')) {
            $options = [
                'frontend_type' => Property::TYPE_HTML,
                'type'          => 'twig',
                'template'      => 'OroEntitySerializedFieldsBundle:Datagrid:Property/serialized.html.twig',
                'context'       => [
                    'field_name' => $property
                ],
            ];
        }

        return isset($options)
            ? new ColumnGuess($options, ColumnGuess::HIGH_CONFIDENCE)
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessSorter($class, $property, $type)
    {
        $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
        if ($extendFieldConfig->is('is_serialized')) {
            return new ColumnGuess(
                [Property::DISABLED_KEY => true],
                ColumnGuess::HIGH_CONFIDENCE
            );
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessFilter($class, $property, $type)
    {
        $extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
        if ($extendFieldConfig->is('is_serialized')) {
            $options = [
                'type' => 'string',
                DatagridGuesser::FILTER => [
                    'data_name' => $property,
                    'enabled'   => false
                ],
            ];
        }

        /*$extendFieldConfig = $this->getFieldConfig('extend', $class, $property);
        if ($extendFieldConfig->is('is_serialized')) {
            $options = [
                'type'       => 'null',
            ];
        }*/

        return isset($options)
            ? new ColumnGuess($options, ColumnGuess::HIGH_CONFIDENCE)
            : null;
    }
}
