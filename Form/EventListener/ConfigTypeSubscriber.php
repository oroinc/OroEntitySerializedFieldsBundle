<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class ConfigTypeSubscriber implements EventSubscriberInterface
{

    /** @var Session $session */
    protected $session;

    /** @var ConfigManager $configManager */
    protected $configManager;


    public function __construct(Session $session, ConfigManager $configManager)
    {
        $this->session          = $session;
        $this->configManager    = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT   => ['postSubmit', -20],
        ];
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
}
