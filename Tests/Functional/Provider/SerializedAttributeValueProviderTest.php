<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

class SerializedAttributeValueProviderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadAttributeFamilyData::class,
            LoadActivityTargets::class,
        ]);
    }

    public function testRemoveAttributeValues()
    {
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $testActivityTargetManager = $doctrineHelper->getEntityManagerForClass(TestActivityTarget::class);
        $testActivityTarget = $this->loadTestActivityTarget(
            $attributeFamily,
            $testActivityTargetManager,
            'serialized_attribute'
        );

        $this->assertNotEmpty($testActivityTarget->serialized_attribute);

        /** @var AttributeValueProviderInterface $provider */
        $provider = $this->getContainer()->get('oro_serialized_fields.provider.serialized_attribute_value');
        $provider->removeAttributeValues(
            $attributeFamily,
            ['serialized_attribute']
        );

        $this->assertEmpty($testActivityTarget->serialized_attribute);
    }

    private function loadTestActivityTarget(
        AttributeFamily $attributeFamily,
        EntityManagerInterface $manager,
        string $attributeName
    ): TestActivityTarget {
        $testActivityTarget = $this->getReference('activity_target_one');
        $testActivityTarget->setAttributeFamily($attributeFamily);
        $testActivityTarget->{$attributeName} = 'some string';

        $manager->persist($testActivityTarget);
        $manager->flush();

        return $testActivityTarget;
    }
}
