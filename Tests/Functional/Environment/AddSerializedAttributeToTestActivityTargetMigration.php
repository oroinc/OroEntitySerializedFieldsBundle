<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds serialized attribute to TestActivityTarget entity to use in functional tests.
 */
class AddSerializedAttributeToTestActivityTargetMigration implements
    Migration,
    SerializedFieldsExtensionAwareInterface
{
    /** @var SerializedFieldsExtension */
    private $serializedFieldsExtension;

    /**
     * {@inheritdoc}
     */
    public function setSerializedFieldsExtension(SerializedFieldsExtension $serializedFieldsExtension)
    {
        $this->serializedFieldsExtension = $serializedFieldsExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->serializedFieldsExtension->addSerializedField(
            $schema->getTable('test_activity_target'),
            'serialized_attribute',
            'string',
            [
                'extend'    => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                ],
                'attribute' => [
                    'is_attribute' => true
                ]
            ]
        );
    }
}
