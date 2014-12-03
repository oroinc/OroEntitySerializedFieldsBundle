<?php
namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator;

use Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader as BaseLoader;

class ExtendFieldValidationLoader extends BaseLoader
{
    /**
     * {@inheritdoc}
     */
    protected function isApplicable($className, $fieldName)
    {
        return parent::isApplicable($className, $fieldName)
            && $this->extendConfigProvider->getConfig($className, $fieldName)->is('is_serialized', false);
    }
}
