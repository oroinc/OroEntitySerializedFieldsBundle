<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Environment;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Adds serialized attribute to TestActivityTarget entity to use in functional tests.
 */
class TestEntitiesMigrationListener
{
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new AddSerializedAttributeToTestActivityTargetMigration());
    }
}
