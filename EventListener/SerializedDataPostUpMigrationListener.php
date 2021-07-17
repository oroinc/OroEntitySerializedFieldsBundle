<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class SerializedDataPostUpMigrationListener
{
    /**  @var EntityMetadataHelper */
    protected $metadataHelper;

    public function __construct(EntityMetadataHelper $metadataHelper)
    {
        $this->metadataHelper = $metadataHelper;
    }

    /**
     * POST UP event handler
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new SerializedDataMigration($this->metadataHelper)
        );
    }
}
