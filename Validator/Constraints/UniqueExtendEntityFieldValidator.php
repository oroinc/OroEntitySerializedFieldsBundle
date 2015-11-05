<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Validates field name uniqueness.
 * @see \Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator
 */
class UniqueExtendEntityFieldValidator extends ConstraintValidator
{
    const ALIAS = 'oro_serialized_fields.validator.unique_extend_entity_field';

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

        $fieldName = $value->getFieldName();

        // A special case for `serialized_data` field.
        if ($this->normalizeFieldName($fieldName) === $this->normalizeFieldName('serialized_data')) {
            $this->addViolation($constraint->message, $fieldName, 'serialized_data');

            return;
        }
    }

    /**
     * @param string $message
     * @param string $newFieldName
     * @param string $existingFieldName
     */
    protected function addViolation($message, $newFieldName, $existingFieldName)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context
            ->buildViolation(
                $message,
                ['{{ value }}' => $newFieldName, '{{ field }}' => $existingFieldName]
            )
            ->atPath('fieldName')
            ->addViolation();
    }

    /**
     * Normalizes a field name.
     * The normalized name is lower cased and unessential symbols, like _, are removed.
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function normalizeFieldName($fieldName)
    {
        return strtolower(Inflector::classify($fieldName));
    }
}
