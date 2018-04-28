<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class IsSerializedFieldType extends AbstractType
{
    /**
     * @var SerializedFieldProvider
     */
    private $serializedFieldProvider;

    public function __construct(SerializedFieldProvider $serializedFieldProvider)
    {
        $this->serializedFieldProvider = $serializedFieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'         => [
                    'oro.entity_serialized_fields.form.is_serialized.database' => 0,
                    'oro.entity_serialized_fields.form.is_serialized.serialized' => 1,
                ],
                'auto_initialize' => false,
                'required'        => true,
                'constraints'     => [new Assert\NotNull()],
                'label'           => 'oro.entity_serialized_fields.form.is_serialized.label',
                'data'            => 0,
                'mapped'          => false,
                'block'           => 'general',
                'tooltip'         => 'oro.entity_serialized_fields.field.is_serialized.tooltip'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_serialized_fields_is_serialized_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['serializableTypes'] = $this->serializedFieldProvider->getSerializableTypes();
    }
}
