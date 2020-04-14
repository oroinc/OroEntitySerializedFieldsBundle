<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormView;

class IsSerializedFieldTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SerializedFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $seriliazedFieldProvider;

    /** @var IsSerializedFieldType */
    protected $type;

    protected function setUp(): void
    {
        $this->seriliazedFieldProvider = $this->getMockBuilder(SerializedFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new IsSerializedFieldType($this->seriliazedFieldProvider);
    }

    protected function tearDown(): void
    {
        unset($this->type);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testType()
    {
        $this->assertSame('oro_serialized_fields_is_serialized_type', $this->type->getName());
        $this->assertSame(ChoiceType::class, $this->type->getParent());
        $this->assertInstanceOf('Symfony\Component\Form\AbstractType', $this->type);
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

        $this->seriliazedFieldProvider
            ->expects($this->once())
            ->method('getSerializableTypes')
            ->willReturn($expectedElements);

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formView = new FormView();
        $this->type->finishView($formView, $form, array());

        $this->assertEquals($expectedElements, $formView->vars['serializableTypes']);
    }
}
