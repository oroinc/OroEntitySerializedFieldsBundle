<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension;

/**
 * This interface should be implemented by migrations that depend on {@see SerializedFieldsExtension}.
 */
interface SerializedFieldsExtensionAwareInterface
{
    public function setSerializedFieldsExtension(SerializedFieldsExtension $serializedFieldsExtension);
}
