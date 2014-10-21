<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

use Oro\Bundle\EntityExtendBundle\Grid\ExtendColumnOptionsGuesser;

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
                [
                    Property::DISABLED_KEY => true
                ],
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
            return new ColumnGuess(
                [
                    Property::TYPE_KEY     => Property::TYPE_STRING,
                    Property::DISABLED_KEY => true
                ],
                ColumnGuess::HIGH_CONFIDENCE
            );
        }

        return null;
    }
}
