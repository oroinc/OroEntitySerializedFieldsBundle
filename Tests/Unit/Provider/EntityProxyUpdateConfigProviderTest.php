<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Oro\Bundle\EntitySerializedFieldsBundle\Provider\EntityProxyUpdateConfigProvider;

class EntityProxyUpdateConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testIsEntityProxyUpdateAllowed(): void
    {
        $provider = new EntityProxyUpdateConfigProvider();
        self::assertTrue($provider->isEntityProxyUpdateAllowed());
    }
}
