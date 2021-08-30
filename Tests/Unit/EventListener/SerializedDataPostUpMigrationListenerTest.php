<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\EventListener\SerializedDataPostUpMigrationListener;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class SerializedDataPostUpMigrationListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnPostUp()
    {
        $metadataHelper = $this->createMock(EntityMetadataHelper::class);
        $listener = new SerializedDataPostUpMigrationListener($metadataHelper);
        $event = $this->createMock(PostMigrationEvent::class);
        $event->expects($this->once())
            ->method('addMigration');
        $listener->onPostUp($event);
    }
}
