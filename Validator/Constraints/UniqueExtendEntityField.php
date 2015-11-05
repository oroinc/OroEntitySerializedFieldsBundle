<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueExtendEntityField extends Constraint
{
    /** @var string */
    public $message = 'This name is reserved to store values of serialized fields.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UniqueExtendEntityFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
