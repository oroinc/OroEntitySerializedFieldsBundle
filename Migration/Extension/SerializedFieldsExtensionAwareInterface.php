<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depends on a SerializedFieldsExtension.
 */
interface SerializedFieldsExtensionAwareInterface
{
    /**
     * Sets the SerializedFieldsExtension
     */
    public function setSerializedFieldsExtension(SerializedFieldsExtension $serializedFieldsExtension);
}
