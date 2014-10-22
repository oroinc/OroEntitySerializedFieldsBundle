<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\EventListener;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class FieldTypeSubscriber extends AbstractTypeExtension
{
    /** @var Session */
    protected $session;

    /**
     * @param Session              $session
     * @param FormFactoryInterface $factory
     */
    public function __construct(Session $session, FormFactoryInterface $factory)
    {
        $this->session = $session;
        $this->factory = $factory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSet']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $isSerialized = $form->get('is_serialized')->getData();

        /** @var FieldConfigModel $configModel */
        $configModel = $event->getData();

        if ($form->isValid()) {
            $this->session->set(
                sprintf('_extendbundle_create_entity_%s_is_serialized', $configModel->getEntity()->getId()),
                $isSerialized
            );
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $form = $event->getForm();

        $form->add(
            $this->factory->createNamed(
                'is_serialized',
                'oro_serialized_fields_is_serialized_type'
            )
        );

        /*$form->add(
            'is_serialized',
            'choice',
            [
                'choices'  => [
                    0 => 'Yes',
                    1 => 'No'
                ],
                'required'  => true,
                'label'     => 'oro.entity_serialized_fields.form.is_serialized.label',
                'mapped'    => false,
                'block'     => 'general',
                'tooltip'   => 'oro.entity_extend.field.storage_type.tooltip',
            ]
        );*/
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return 'oro_entity_extend_field_type';
    }
}
