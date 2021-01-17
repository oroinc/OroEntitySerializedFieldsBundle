<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadActivityTargets;

class SerializedAttributeValueProviderTest extends WebTestCase
{
    /**
     * @var AttributeValueProviderInterface
     */
    protected $provider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var FieldConfigModelRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            LoadAttributeFamilyData::class,
            LoadActivityTargets::class,
        ]);
        $this->provider = $this->getContainer()->get('oro_serialized_fields.provider.serialized_attribute_value');
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->repository = $this->doctrineHelper->getEntityRepositoryForClass(FieldConfigModel::class);
    }

    public function testRemoveAttributeValues()
    {
        $attributeFamily = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        $attributeName = $this->getSerializedAttributeName();
        $testActivityTargetManager = $this->doctrineHelper->getEntityManagerForClass(TestActivityTarget::class);
        $testActivityTarget = $this->loadTestActivityTarget(
            $attributeFamily,
            $testActivityTargetManager,
            $attributeName
        );

        $getter = 'get' . $attributeName;
        $this->assertNotEmpty($testActivityTarget->$getter());

        $this->provider->removeAttributeValues(
            $attributeFamily,
            [$attributeName]
        );

        $this->assertEmpty($testActivityTarget->$getter());
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param EntityManagerInterface $manager
     * @param string $attributeName
     * @return TestActivityTarget
     */
    protected function loadTestActivityTarget(
        AttributeFamily $attributeFamily,
        EntityManagerInterface $manager,
        $attributeName
    ) {
        $testActivityTarget = $this->getReference('activity_target_one');
        $testActivityTarget->setAttributeFamily($attributeFamily);

        $setter = 'set' . $attributeName;
        $testActivityTarget->$setter('some string');

        $manager->persist($testActivityTarget);
        $manager->flush();

        return $testActivityTarget;
    }

    /**
     * @return string
     */
    protected function getSerializedAttributeName()
    {
        return ucfirst((new InflectorFactory())->build()->camelize('serialized_attribute'));
    }
}
