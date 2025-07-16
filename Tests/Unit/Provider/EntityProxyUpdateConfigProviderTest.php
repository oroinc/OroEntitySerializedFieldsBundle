<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Oro\Bundle\EntitySerializedFieldsBundle\Provider\EntityProxyUpdateConfigProvider;
use PHPUnit\Framework\TestCase;

class EntityProxyUpdateConfigProviderTest extends TestCase
{
    public function testIsEntityProxyUpdateAllowed(): void
    {
        $provider = new EntityProxyUpdateConfigProvider();
        self::assertTrue($provider->isEntityProxyUpdateAllowed());
    }
}
