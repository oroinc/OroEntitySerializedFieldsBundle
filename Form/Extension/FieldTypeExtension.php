<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\Session;

class FieldTypeExtension extends AbstractTypeExtension
{
    const SESSION_ID_FIELD_SERIALIZED = '_extendbundle_create_entity_%s_is_serialized';

    /** @var Session */
    protected $session;

    /**
     * Array of field's names in preferred order
     *
     * @var array
     */
    protected $fieldOrder;

    /**
     * @param Session $session
     * @param array   $fieldOrder
     */
    public function __construct(Session $session, $fieldOrder = [])
    {
        $this->session    = $session;
        $this->fieldOrder = $fieldOrder;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'is_serialized',
            IsSerializedFieldType::class
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        if (!$form->has('is_serialized')) {
            return;
        }
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
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $fields      = [];
        $fieldsOrder = $this->fieldOrder;
        foreach ($fieldsOrder as $field) {
            if ($view->offsetExists($field)) {
                $fields[$field] = $view->offsetGet($field);
                $view->offsetUnset($field);
            }
        }

        $view->children = $fields + $view->children;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FieldType::class];
    }
}
