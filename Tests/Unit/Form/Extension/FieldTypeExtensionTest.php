<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension\FieldTypeExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class FieldTypeExtensionTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private FieldTypeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->extension = new FieldTypeExtension(
            $this->requestStack,
            ['fieldName', 'is_serialized', 'type']
        );

        $this->assertEquals(
            '_extendbundle_create_entity_%s_is_serialized',
            FieldTypeExtension::SESSION_ID_FIELD_SERIALIZED
        );
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([FieldType::class], FieldTypeExtension::getExtendedTypes());
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('add')
            ->with('is_serialized', IsSerializedFieldType::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'postSubmit']);

        $this->extension->buildForm($builder, []);
    }

    public function testPostSubmit(): void
    {
        $formConfig = $this->createMock(Form::class);
        $formConfig->expects($this->once())
            ->method('getData')
            ->willReturn(1);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('get')
            ->with('is_serialized')
            ->willReturn($formConfig);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('has')
            ->with('is_serialized')
            ->willReturn(true);

        $entityConfigModel = $this->createMock(EntityConfigModel::class);
        $entityConfigModel->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $fieldConfigModel = new FieldConfigModel('test_field', 'string');
        $fieldConfigModel->setEntity($entityConfigModel);

        $sessionMock = $this->createMock(Session::class);
        $this->requestStack->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);
        $sessionMock->expects($this->once())
            ->method('set')
            ->with('_extendbundle_create_entity_1_is_serialized', true);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($fieldConfigModel);

        $this->extension->postSubmit($event);
    }
}
