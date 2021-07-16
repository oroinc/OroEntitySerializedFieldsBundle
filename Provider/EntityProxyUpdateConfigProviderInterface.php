<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

/**
 * Represents a configuration of the routine to update entity proxies.
 */
interface EntityProxyUpdateConfigProviderInterface
{
    /**
     * Indicates whether the update of entity proxies is allowed on the fly
     * or should be done by the "Schema Update" process.
     */
    public function isEntityProxyUpdateAllowed(): bool;
}
