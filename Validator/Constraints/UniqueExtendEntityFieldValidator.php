<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

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
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context->buildViolation($constraint->message)
            ->atPath($constraint->path)
            ->addViolation();
    }
}
