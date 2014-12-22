<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigrationQuery;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

class SerializedDataMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $table;

    /** @var Schema */
    protected $schema;

    /**  @var SerializedDataMigrationQuery */
    protected $query;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));

        $this->helper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->table = $this->getMockBuilder('Doctrine\DBAL\Schema\Table')
            ->disableOriginalConstructor()
            ->getMock();

        $this->schema = new Schema();
        $this->schema->createTable('test_table');

        $this->query = new SerializedDataMigrationQuery($this->schema, $this->helper);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testExecute($row, $data, $expectedLoggerMessages)
    {
        $logger = new ArrayLogger();
        $this->query->setConnection($this->connection);

        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($row));

        $this->helper->expects($this->any())
            ->method('getTableNameByEntityClass')
            ->will($this->returnValue('test_table'));

        $this->table->expects($this->any())
            ->method('hasColumn')
            ->will($this->returnValue(false));

        $this->connection->expects($this->any())
            ->method('convertToPHPValue')
            ->will($this->returnValue($data));

        $this->query->execute($logger);

        $messages = implode(' ', $logger->getMessages());
        $this->assertSame($expectedLoggerMessages, $messages);
    }

    public function dataProvider()
    {
        return [
            [
                '$rows'                   => [['class_name' => 'Test\Entity\Entity1', 'data' => []]],
                '$data'                   => ['extend' => ['is_extend' => true, 'state' => 'Active']],
                '$expectedLoggerMessages' => "SELECT class_name, data FROM oro_entity_config WHERE mode = ? "
                    . "Parameters: [1] = default "
                    . "ALTER TABLE test_table ADD serialized_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'"
            ]
        ];
    }
}
