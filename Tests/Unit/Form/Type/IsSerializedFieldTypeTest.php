<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IsSerializedFieldTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var SerializedFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $serializedFieldProvider;

    /** @var IsSerializedFieldType */
    private $type;

    protected function setUp(): void
    {
        $this->serializedFieldProvider = $this->createMock(SerializedFieldProvider::class);

        $this->type = new IsSerializedFieldType($this->serializedFieldProvider);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testType()
    {
        $this->assertSame('oro_serialized_fields_is_serialized_type', $this->type->getName());
        $this->assertSame(ChoiceType::class, $this->type->getParent());
        $this->assertInstanceOf(AbstractType::class, $this->type);
    }

    public function testFinishView()
    {
        $expectedElements = [
            'string',
            'integer',
            'smallint',
            'bigint',
            'boolean',
            'decimal',
            'date',
            'datetime',
            'text',
            'float',
            'money',
            'percent'
        ];

        $this->serializedFieldProvider->expects($this->once())
            ->method('getSerializableTypes')
            ->willReturn($expectedElements);

        $form = $this->createMock(Form::class);
        $formView = new FormView();
        $this->type->finishView($formView, $form, []);

        $this->assertEquals($expectedElements, $formView->vars['serializableTypes']);
    }
}
