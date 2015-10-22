<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Validates field name uniqueness.
 * @see \Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator
 */
class UniqueExtendEntityFieldValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, %s given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $newFieldName = strtolower(Inflector::classify(($value->getFieldName())));

        // Need hardcoded check for `serialized_data` field.
        if ($newFieldName === strtolower(Inflector::classify('serialized_data'))) {
            $this->addViolation($constraint);

            return;
        }
    }

    /**
     * @param Constraint $constraint
     */
    protected function addViolation(Constraint $constraint)
    {
        $this->context->addViolationAt($constraint->path, $constraint->message);
    }
}
