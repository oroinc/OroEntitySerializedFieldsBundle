<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\ConfigSerializedFieldExclusionProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

class ConfigSerializedFieldExclusionProviderTest extends WebTestCase
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ConfigSerializedFieldExclusionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([LoadActivityTargets::class]);

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->provider = $this->getContainer()->get('oro_serialized_fields.exclusion_provider.serialized_field');
    }

    public function testIsIgnoredRegularField()
    {
        $metadata = $this->doctrineHelper->getEntityMetadata(TestActivityTarget::class);

        $this->assertFalse($this->provider->isIgnoredField($metadata, 'regular_attribute_1'));
    }

    public function testIsIgnoredSerializedField()
    {
        $metadata = $this->doctrineHelper->getEntityMetadata(TestActivityTarget::class);

        $this->assertTrue($this->provider->isIgnoredField($metadata, 'serialized_attribute'));
    }
}
