<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The validation constraint to validate field name uniqueness.
 */
class UniqueExtendEntityField extends Constraint
{
    /** @var string */
    public $message = 'This name is reserved to store values of serialized fields.';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
