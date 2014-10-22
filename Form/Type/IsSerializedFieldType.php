<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\TranslationBundle\Translation\Translator;

class IsSerializedFieldType extends AbstractType
{
    const ORIGINAL_FIELD_NAMES_ATTRIBUTE = 'original_field_names';
    const TYPE_LABEL_PREFIX              = 'oro.entity_extend.form.data_type.';
    const GROUP_TYPE_PREFIX              = 'oro.entity_extend.form.data_type_group.';
    const GROUP_FIELDS                   = 'fields';

    protected $types = [
        self::GROUP_FIELDS       => [
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
            'percent',
            'image',
            'enum',
            'multiEnum',
        ]
    ];

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator    = $translator;
    }

    /**
     * @param array $reverseRelationTypes
     *
     * @return array
     */
    protected function getFieldTypeChoices()
    {
        $fieldTypes = $relationTypes = [];

        foreach ($this->types[self::GROUP_FIELDS] as $type) {
            $fieldTypes[$type] = $this->translator->trans(self::TYPE_LABEL_PREFIX . $type);
        }

        uasort($fieldTypes, 'strcasecmp');
        uasort($relationTypes, 'strcasecmp');

        $result = [
            $this->translator->trans(self::GROUP_TYPE_PREFIX . self::GROUP_FIELDS)    => $fieldTypes,
        ];

        return $result;
    }

    /**
     * {@inheritdoc}
     */
/*    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
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
        );
    }*/

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        # $b = $this->getFieldTypeChoices();
        $resolver
            ->setDefaults(
                [
                    'choices'  => [
                        1   => 'oro.entity_serialized_fields.form.is_serialized.yes',
                        0   => 'oro.entity_serialized_fields.form.is_serialized.no'
                    ],
                    'auto_initialize' => false,
                    'required'  => true,
                    'label'     => 'oro.entity_serialized_fields.form.is_serialized.label',
                    'mapped'    => false,
                    'block'     => 'general',
                    'tooltip'   => 'oro.entity_serialized_fields.field.is_serialized.tooltip'
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_serialized_fields_is_serialized_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
/*    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['regularTypes']      = $form->get('type')->getConfig()->getOption('choices');
        $view->vars['serializableTypes'] = $this->getFieldTypeChoices(array(), $this->serializableTypes);
    }*/
}
