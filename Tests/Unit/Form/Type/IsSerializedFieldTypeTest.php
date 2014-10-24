<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\FormView;

class IsSerializedFieldTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var IsSerializedFieldType */
    protected $type;

    public function setUp()
    {
        $this->type = new IsSerializedFieldType();
    }

    public function tearDown()
    {
        unset($this->type);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->setDefaultOptions($resolver);
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

        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formView = new FormView();
        $this->type->finishView($formView, $form, array());

        $this->assertEquals($expectedElements, $formView->vars['serializableTypes']);
    }
}
