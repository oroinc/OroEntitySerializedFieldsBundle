<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class FieldTypeExtension extends AbstractTypeExtension
{
    const SESSION_ID_FIELD_SERIALIZED = '_extendbundle_create_entity_%s_is_serialized';

    /** @var Session */
    protected $session;

    /** @var FormFactoryInterface */
    protected $factory;

    /**
     * @param Session              $session
     * @param FormFactoryInterface $factory
     */
    public function __construct(Session $session, FormFactoryInterface $factory)
    {
        $this->session = $session;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
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
                sprintf(self::SESSION_ID_FIELD_SERIALIZED, $configModel->getEntity()->getId()),
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
            $this->factory->createNamed('is_serialized', 'oro_serialized_fields_is_serialized_type')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_entity_extend_field_type';
    }
}
