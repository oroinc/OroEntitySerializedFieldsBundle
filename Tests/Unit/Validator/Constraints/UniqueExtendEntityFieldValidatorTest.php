<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\UniqueExtendEntityField;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;

class UniqueExtendEntityFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $this->validator = new UniqueExtendEntityFieldValidator();
    }

    /**
     * @dataProvider validateProvider
     *
     * @param string $fieldName
     * @param bool   $isViolationExpected
     */
    public function testValidate($fieldName, $isViolationExpected)
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName);
        $entity->addField($field);

        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($context);

        $constraint = new UniqueExtendEntityField();

        if ($isViolationExpected) {
            $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $context->expects($this->once())
                ->method('buildViolation')
                ->with($constraint->message)
                ->willReturn($violation);
            $violation->expects($this->once())
                ->method('atPath')
                ->with('fieldName')
                ->willReturnSelf();
            $violation->expects($this->once())
                ->method('addViolation');
        } else {
            $context->expects($this->never())
                ->method('buildViolation');
        }

        $this->validator->validate($field, $constraint);
    }

    public function validateProvider()
    {
        return [
            ['anotherField', false],
            ['serialized_data', true],
            ['serializedData', true],
        ];
    }
}
