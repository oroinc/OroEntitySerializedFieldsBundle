<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ConfigTypeExtension extends AbstractTypeExtension
{
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

        $isSerialized = $this->session->get(
            sprintf('_extendbundle_create_entity_%s_is_serialized', $configModel->getEntity()->getId())
        );

        if (!$isSerialized) {
            return false;
        }

        // Update field config
        $className = $configModel->getEntity()->getClassName();
        $fieldName = $configModel->getFieldName();

        $configProvider = $this->configManager->getProvider('extend');
        $config         = $configProvider->getConfig($className, $fieldName);
        $config->set('is_serialized', true);
        $configProvider->persist($config);
        $configProvider->flush();
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
