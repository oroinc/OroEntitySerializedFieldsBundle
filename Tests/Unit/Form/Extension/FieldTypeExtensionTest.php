<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\FormEvents;

class FieldTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var  FieldTypeExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $session;

    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new FieldTypeExtension(
            $this->session,
            ['fieldName', 'is_serialized', 'type']
        );

        $extension = $this->extension;
        $this->assertEquals(
            '_extendbundle_create_entity_%s_is_serialized',
            $extension::SESSION_ID_FIELD_SERIALIZED
        );
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([FieldType::class], FieldTypeExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject */
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->at(0))
            ->method('add')
            ->with('is_serialized', IsSerializedFieldType::class);
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'postSubmit']);

        $this->extension->buildForm($builder, []);
    }

    public function testPostSubmit()
    {
        $event = $this->getFormEventMock();

        $this->session
            ->expects($this->once())
            ->method('set')
            ->with('_extendbundle_create_entity_1_is_serialized', true);

        $this->extension->postSubmit($event);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFormEventMock()
    {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');

        $formConfig = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $formConfig
            ->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(1));
        $form
            ->expects($this->once())
            ->method('get')
            ->with('is_serialized')
            ->will($this->returnValue($formConfig));
        $form
            ->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form
            ->expects($this->once())
            ->method('has')
            ->with('is_serialized')
            ->willReturn(true);

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $entityConfigModel = $this->createMock('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel');
        $entityConfigModel
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $fieldConfigModel = new FieldConfigModel('test_field', 'string');
        $fieldConfigModel->setEntity($entityConfigModel);

        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($fieldConfigModel));

        return $event;
    }
}
