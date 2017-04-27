<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\ConfigSerializedFieldExclusionProvider;
use Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\DataFixtures\LoadSerializedAttributeData;
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

        $this->loadFixtures([
            LoadActivityTargets::class,
            LoadSerializedAttributeData::class,
        ]);

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->provider = $this->getContainer()->get('oro_serialized_fields.exclusion_provider.serialized_field');
    }

    public function testIsIgnoredRegularField()
    {
        $metadata = $this->doctrineHelper->getEntityMetadata(TestActivityTarget::class);
        $fieldName = LoadSerializedAttributeData::REGULAR_ATTRIBUTE_1;

        $this->assertFalse($this->provider->isIgnoredField($metadata, $fieldName));
    }

    public function testIsIgnoredSerializedField()
    {
        $metadata = $this->doctrineHelper->getEntityMetadata(TestActivityTarget::class);
        $fieldName = LoadSerializedAttributeData::SERIALIZED_ATTRIBUTE;

        $this->assertTrue($this->provider->isIgnoredField($metadata, $fieldName));
    }
}
