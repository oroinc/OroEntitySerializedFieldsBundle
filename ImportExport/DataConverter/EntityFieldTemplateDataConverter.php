<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\ImportExport\DataConverter;

use Oro\Bundle\EntityConfigBundle\ImportExport\DataConverter\EntityFieldTemplateDataConverter as BaseConverter;

/**
 * Data converter that converts entity field data to the format that is used to deserialize the entity from the array.
 * Adds the is_serialized header to the list of main headers.
 */
class EntityFieldTemplateDataConverter extends BaseConverter
{
    /**
     * {@inheritDoc}
     */
    protected function getMainHeaders(): array
    {
        return ['fieldName', 'is_serialized', 'type'];
    }
}
