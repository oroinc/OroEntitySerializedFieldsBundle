<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension;

/**
 * This trait can be used by migrations that implement {@see SerializedFieldsExtensionAwareInterface}.
 */
trait SerializedFieldsExtensionAwareTrait
{
    private SerializedFieldsExtension $serializedFieldsExtension;

    public function setSerializedFieldsExtension(SerializedFieldsExtension $serializedFieldsExtension): void
    {
        $this->serializedFieldsExtension = $serializedFieldsExtension;
    }
}
