<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigration;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SerializedDataMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testUp()
    {
        $metadataHelper = $this->createMock(EntityMetadataHelper::class);
        $schema = $this->createMock(ExtendSchema::class);
        $queries = $this->createMock(QueryBag::class);

        $queries->expects($this->once())
            ->method('addQuery')
            ->with(new SerializedDataMigrationQuery($schema, $metadataHelper));

        $migration = new SerializedDataMigration($metadataHelper);
        $migration->up($schema, $queries);
    }
}
