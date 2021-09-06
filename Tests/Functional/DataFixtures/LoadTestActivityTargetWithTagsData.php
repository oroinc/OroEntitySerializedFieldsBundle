<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\AbstractFixtureWithTags;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;

class LoadTestActivityTargetWithTagsData extends AbstractFixtureWithTags
{
    public const ACTIVITY_TARGET_1 = 'test_activity_target_1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $testActivityTarget = new TestActivityTarget();
        $testActivityTarget->setSerializedAttribute($this->getTextWithTags());

        $manager->persist($testActivityTarget);
        $manager->flush();

        $this->setReference(self::ACTIVITY_TARGET_1, $testActivityTarget);
    }
}
