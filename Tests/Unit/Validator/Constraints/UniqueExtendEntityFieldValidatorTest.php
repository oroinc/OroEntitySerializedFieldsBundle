<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\UniqueExtendEntityField;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueExtendEntityFieldValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        $validationHelper = $this->createMock(FieldNameValidationHelper::class);
        $validationHelper->expects($this->any())
            ->method('normalizeFieldName')
            ->willReturnCallback(function (string $fieldName): string {
                return str_replace('_', '', ucwords($fieldName, '_'));
            });

        return new UniqueExtendEntityFieldValidator($validationHelper);
    }

    public function testValidatesForSerializedDataFieldInCamelCase()
    {
        $entity = new EntityConfigModel('Test\Entity');
        $field = new FieldConfigModel('serializedData');
        $entity->addField($field);

        $constraint = new UniqueExtendEntityField();

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'serializedData', '{{ field }}' => 'serialized_data'])
            ->atPath('property.path.fieldName')
            ->assertRaised();
    }

    public function testValidatesForSerializedDataFieldInSnakeCase()
    {
        $entity = new EntityConfigModel('Test\Entity');
        $field = new FieldConfigModel('serialized_data');
        $entity->addField($field);

        $constraint = new UniqueExtendEntityField();

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'serialized_data', '{{ field }}' => 'serialized_data'])
            ->atPath('property.path.fieldName')
            ->assertRaised();
    }

    public function testValidateForNotSerializedData()
    {
        $entity = new EntityConfigModel('Test\Entity');
        $field = new FieldConfigModel('anotherField');
        $entity->addField($field);

        $constraint = new UniqueExtendEntityField();
        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }
}
