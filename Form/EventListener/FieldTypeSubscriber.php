<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class FieldTypeSubscriber implements EventSubscriberInterface
{
    /**
     * @var Session
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA  => 'preSet',
            FormEvents::POST_SUBMIT   => ['postSubmit', 20],
        ];
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

/*        $form->add(
            'is_serialized',
            'choice',
            [
                'choices'  => [
                    'regular'       => 'oro.entity_extend.form.storage_type.regular',
                    'serializable'  => 'oro.entity_extend.form.storage_type.serializable'
                ],
                'required'  => true,
                'label'     => 'oro.entity_extend.form.storage_type.label',
                'mapped'    => false,
                'block'     => 'general',
                'tooltip'   => 'oro.entity_extend.field.storage_type.tooltip',
            ]
        );*/
    }
}
