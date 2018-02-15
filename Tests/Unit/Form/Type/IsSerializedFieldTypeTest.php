<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\FormView;

class IsSerializedFieldTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SerializedFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $seriliazedFieldProvider;

    /** @var IsSerializedFieldType */
    protected $type;

    public function setUp()
    {
        $this->seriliazedFieldProvider = $this->getMockBuilder(SerializedFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new IsSerializedFieldType($this->seriliazedFieldProvider);
    }

    public function tearDown()
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
        $this->assertSame('choice', $this->type->getParent());
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
