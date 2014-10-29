<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Migration;

use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigration;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigrationQuery;

class SerializedDataMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $metadataHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $migration = new SerializedDataMigration($metadataHelper);

        $schema = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema')
            ->disableOriginalConstructor()
            ->getMock();

        $queries = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\QueryBag')
            ->disableOriginalConstructor()
            ->getMock();

        $queries->expects($this->at(0))
            ->method('addQuery')
            ->with(
                new SerializedDataMigrationQuery(
                    $schema,
                    $metadataHelper
                )
            );

        $migration->up($schema, $queries);
    }
}
