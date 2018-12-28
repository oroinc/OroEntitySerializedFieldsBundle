<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader as BaseLoader;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedData;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Extends original metadata loader and adds logic applicable to serialized fields.
 */
class ExtendFieldValidationLoader extends BaseLoader
{
    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        if ($metadata->getClassName() === ExtendEntityInterface::class) {
            $metadata->addConstraint(new ExtendEntitySerializedData());
        }

        return parent::loadClassMetadata($metadata);
    }

    /**
     * {@inheritdoc}
     */
    protected function isApplicable($className, $fieldName)
    {
        return parent::isApplicable($className, $fieldName)
            && $this->extendConfigProvider->getConfig($className, $fieldName)->is('is_serialized', false);
    }
}
