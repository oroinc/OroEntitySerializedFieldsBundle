<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

class ConfigTypeExtension extends AbstractTypeExtension
{
    const SESSION_ID_FIELD_SERIALIZED = '_extendbundle_create_entity_%s_is_serialized';

    /** @var Session $session */
    protected $session;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /**
     * @param Session       $session
     * @param ConfigManager $configManager
     */
    public function __construct(Session $session, ConfigManager $configManager)
    {
        $this->session          = $session;
        $this->configManager    = $configManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit'], -20);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var FieldConfigModel $configModel */
        $configModel = $form->getConfig()->getOption('config_model');

        if ($configModel instanceof EntityConfigModel) {
            return false;
        }

        $isSerialized = $this->session->get(
            sprintf(self::SESSION_ID_FIELD_SERIALIZED, $configModel->getEntity()->getId())
        );

        if (!$isSerialized) {
            return false;
        }

        $fieldOptions = [
            'extend' => [
                'is_serialized' => true,
            ]
        ];
        $this->updateFieldConfigs($this->configManager, $configModel, $fieldOptions);
    }

    /**
     * @param ConfigManager    $configManager
     * @param FieldConfigModel $fieldModel
     * @param array            $options
     */
    protected function updateFieldConfigs(ConfigManager $configManager, FieldConfigModel $fieldModel, $options)
    {
        $className = $fieldModel->getEntity()->getClassName();
        $fieldName = $fieldModel->getFieldName();
        foreach ($options as $scope => $scopeValues) {
            $configProvider = $configManager->getProvider($scope);
            $config         = $configProvider->getConfig($className, $fieldName);
            $hasChanges     = false;
            foreach ($scopeValues as $code => $val) {
                if (!$config->is($code, $val)) {
                    $config->set($code, $val);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $configProvider->persist($config);
                $indexedValues = $configProvider->getPropertyConfig()->getIndexedValues($config->getId());
                $fieldModel->fromArray($config->getId()->getScope(), $config->all(), $indexedValues);

                $configProvider->flush();
            }
        }
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return 'oro_entity_config_type';
    }
}
