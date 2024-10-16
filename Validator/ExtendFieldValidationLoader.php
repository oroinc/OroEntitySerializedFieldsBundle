<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Validator\ExtendFieldValidationLoader as BaseLoader;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedData;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Extends original metadata loader and adds logic applicable to serialized fields.
 */
class ExtendFieldValidationLoader extends BaseLoader
{
    #[\Override]
    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        if ($metadata->getClassName() === ExtendEntityInterface::class) {
            $metadata->addConstraint(new ExtendEntitySerializedData());
        }

        return parent::loadClassMetadata($metadata);
    }

    #[\Override]
    protected function isApplicable(ConfigInterface $extendConfig): bool
    {
        return parent::isApplicable($extendConfig)
            && $extendConfig->is('is_serialized', false);
    }
}
