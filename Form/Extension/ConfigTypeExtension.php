<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ConfigTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configModel = $options['config_model'];
        if ($configModel && $configModel instanceof FieldConfigModel) {
            $extendScopeConfig = $configModel->toArray('extend');
            $builder->add(
                'is_serialized',
                'oro_serialized_fields_is_serialized_type',
                [
                    'disabled' => true,
                    'data'     => isset($extendScopeConfig['is_serialized'])
                        ? $extendScopeConfig['is_serialized']
                        : false
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($view->offsetExists('is_serialized')) {
            $fieldsOrder = ['fieldName', 'is_serialized', 'type'];
            $fields = [];
            foreach ($fieldsOrder as $field) {
                if ($view->offsetExists($field)) {
                    $fields[$field] = $view->offsetGet($field);
                    $view->offsetUnset($field);
                }
            }

            $view->children = $fields + $view->children;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_entity_config_type';
    }
}
