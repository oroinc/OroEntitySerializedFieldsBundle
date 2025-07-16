<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IsSerializedFieldTypeTest extends TestCase
{
    private SerializedFieldProvider&MockObject $serializedFieldProvider;
    private IsSerializedFieldType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->serializedFieldProvider = $this->createMock(SerializedFieldProvider::class);

        $this->type = new IsSerializedFieldType($this->serializedFieldProvider);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testType(): void
    {
        $this->assertSame('oro_serialized_fields_is_serialized_type', $this->type->getName());
        $this->assertSame(ChoiceType::class, $this->type->getParent());
        $this->assertInstanceOf(AbstractType::class, $this->type);
    }

    public function testFinishView(): void
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
