<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntitySerializedFieldsBundle\EventListener\SerializedDataPostUpMigrationListener;

class SerializedDataPostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        $metadataHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $listener = new SerializedDataPostUpMigrationListener($metadataHelper);
        $event = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Event\PostMigrationEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('addMigration');
        $listener->onPostUp($event);
    }
}
