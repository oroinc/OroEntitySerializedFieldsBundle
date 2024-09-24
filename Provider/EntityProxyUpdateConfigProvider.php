<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

/**
 * Represents the default configuration of the routine to update entity proxies.
 */
class EntityProxyUpdateConfigProvider implements EntityProxyUpdateConfigProviderInterface
{
    #[\Override]
    public function isEntityProxyUpdateAllowed(): bool
    {
        return true;
    }
}
