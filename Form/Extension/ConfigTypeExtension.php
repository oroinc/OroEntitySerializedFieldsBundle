<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntitySerializedFieldsBundle\Form\Type\IsSerializedFieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ConfigTypeExtension extends AbstractTypeExtension
{
    /**
     * Array of field's names in preferred order
     *
     * @var array
     */
    protected $fieldOrder;

    /**
     * @param array $fieldOrder
     */
    public function __construct($fieldOrder = [])
    {
        $this->fieldOrder = $fieldOrder;
    }

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
                IsSerializedFieldType::class,
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
        return [ConfigType::class];
    }
}
