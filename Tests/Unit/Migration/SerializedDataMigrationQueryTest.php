<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\SerializedDataMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

class SerializedDataMigrationQueryTest extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var EntityMetadataHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $helper;

    /** @var Schema */
    protected $schema;

    /**  @var SerializedDataMigrationQuery */
    protected $query;

    protected function setUp(): void
    {
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));

        $this->helper = $this->getMockBuilder(EntityMetadataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->schema = new Schema();

        $this->query = new SerializedDataMigrationQuery($this->schema, $this->helper);
    }

    /**
     * @dataProvider dataProvider
     * @param array $row
     * @param array $data
     * @param string $expectedLoggerMessages
     */
    public function testExecute(array $row, array $data, $expectedLoggerMessages)
    {
        $logger = new ArrayLogger();
        $this->query->setConnection($this->connection);

        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue($row));

        $this->helper->expects($this->any())
            ->method('getTableNameByEntityClass')
            ->will($this->returnValue('test_table'));

        $this->connection->expects($this->any())
            ->method('convertToPHPValue')
            ->will($this->returnValue($data));

        $this->schema->createTable('test_table');

        $this->query->execute($logger);

        $messages = implode(' ', $logger->getMessages());
        $this->assertSame($expectedLoggerMessages, $messages);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                '$rows' => [['class_name' => 'Test\Entity\Entity1', 'data' => []]],
                '$data' => ['extend' => ['is_extend' => true, 'state' => 'Active']],
                '$expectedLoggerMessages' => "SELECT class_name, data FROM oro_entity_config WHERE mode = ? "
                    ."Parameters: [1] = default "
                    ."ALTER TABLE test_table ADD serialized_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'",
            ],
        ];
    }

    public function testExecuteCheckColumnNotExists()
    {
        $logger = new ArrayLogger();
        $this->query->setConnection($this->connection);

        $tableName = 'entity_1';
        $entityClass = 'Test\Entity\Entity1';

        $this->connection->expects(static::once())
            ->method('fetchAll')
            ->willReturn([['class_name' => $entityClass, 'data' => []]]);

        $this->schema->createTable($tableName);

        $this->connection->expects(static::once())
            ->method('convertToPHPValue')
            ->willReturn([
                'extend' => [
                    'schema' => ['doctrine' => [$entityClass => ['table' => $tableName]]],
                    'is_extend' => true,
                    'state' => 'Active',
                ],
            ]);

        $expectedQuery = "ALTER TABLE entity_1 ADD serialized_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'";
        $this->connection->expects(static::once())
            ->method('query')
            ->with($expectedQuery);

        $this->query->execute($logger);
    }

    public function testExecuteCheckColumnExists()
    {
        $logger = new ArrayLogger();
        $this->query->setConnection($this->connection);

        $tableName = 'entity_1';
        $entityClass = 'Test\Entity\Entity1';

        $this->connection->expects(static::once())
            ->method('fetchAll')
            ->willReturn([['class_name' => $entityClass, 'data' => []]]);

        $this->schema->createTable($tableName);
        $table = $this->schema->getTable($tableName);
        $table->addColumn('serialized_data', 'array');

        $this->connection->expects(static::once())
            ->method('convertToPHPValue')
            ->willReturn([
                'extend' => [
                    'schema' => ['doctrine' => [$entityClass => ['table' => $tableName]]],
                    'is_extend' => true,
                    'state' => 'Active',
                ],
            ]);

        $expectedQuery = "ALTER TABLE entity_1 CHANGE serialized_data serialized_data "
            ."LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'";

        $this->connection->expects(static::once())
            ->method('query')
            ->with($expectedQuery);

        $this->query->execute($logger);
    }
}
