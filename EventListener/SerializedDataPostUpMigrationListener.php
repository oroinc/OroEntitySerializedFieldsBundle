<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigration;

class SerializedDataPostUpMigrationListener
{
    /**  @var EntityMetadataHelper */
    protected $metadataHelper;

    /**
     * @param EntityMetadataHelper $metadataHelper
     */
    public function __construct(EntityMetadataHelper $metadataHelper)
    {
        $this->metadataHelper = $metadataHelper;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new SerializedDataMigration($this->metadataHelper)
        );
    }
}
