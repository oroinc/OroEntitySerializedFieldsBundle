<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates field name uniqueness.
 * @see \Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator
 */
class UniqueExtendEntityFieldValidator extends ConstraintValidator
{
    private FieldNameValidationHelper $validationHelper;

    public function __construct(FieldNameValidationHelper $validationHelper)
    {
        $this->validationHelper = $validationHelper;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(sprintf(
                '%s supported only, %s given',
                FieldConfigModel::class,
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }

        // a special case for "serialized_data" field.
        $fieldName = $value->getFieldName();
        if ($this->isReservedFieldName($fieldName)) {
            $this->context
                ->buildViolation(
                    $constraint->message,
                    ['{{ value }}' => $fieldName, '{{ field }}' => 'serialized_data']
                )
                ->atPath('fieldName')
                ->addViolation();
        }
    }

    private function isReservedFieldName(string $fieldName): string
    {
        $normalizedFieldName = $this->validationHelper->normalizeFieldName($fieldName);

        return $this->validationHelper->normalizeFieldName('serialized_data') === $normalizedFieldName;
    }
}
