<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates values inside serialized data of any extended entity.
 */
class ExtendEntitySerializedData extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_serialized_fields.validator.extend_entity_serialized_data';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
