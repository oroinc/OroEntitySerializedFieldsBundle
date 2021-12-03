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
    private $connection;

    /** @var EntityMetadataHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var Schema */
    private $schema;

    /** @var SerializedDataMigrationQuery */
    private $query;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->helper = $this->createMock(EntityMetadataHelper::class);

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
            ->method('fetchAllAssociative')
            ->willReturn($row);

        $this->helper->expects($this->any())
            ->method('getTableNameByEntityClass')
            ->willReturn('test_table');

        $this->connection->expects($this->any())
            ->method('convertToPHPValue')
            ->willReturn($data);

        $this->schema->createTable('test_table');

        $this->query->execute($logger);

        $messages = implode(' ', $logger->getMessages());
        $this->assertSame($expectedLoggerMessages, $messages);
    }

    public function dataProvider(): array
    {
        return [
            [
                '$rows' => [['class_name' => 'Test\Entity\Entity1', 'data' => []]],
                '$data' => ['extend' => ['is_extend' => true, 'state' => 'Active']],
                '$expectedLoggerMessages' => 'SELECT class_name, data FROM oro_entity_config WHERE mode = ?'
                    . ' Parameters: [1] = default'
                    . ' ALTER TABLE test_table ADD serialized_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\''
            ],
        ];
    }

    public function testExecuteCheckColumnNotExists()
    {
        $logger = new ArrayLogger();
        $this->query->setConnection($this->connection);

        $tableName = 'entity_1';
        $entityClass = 'Test\Entity\Entity1';

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([['class_name' => $entityClass, 'data' => []]]);

        $this->schema->createTable($tableName);

        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->willReturn([
                'extend' => [
                    'schema' => ['doctrine' => [$entityClass => ['table' => $tableName]]],
                    'is_extend' => true,
                    'state' => 'Active',
                ],
            ]);

        $expectedQuery = "ALTER TABLE entity_1 ADD serialized_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'";
        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with($expectedQuery);

        $this->query->execute($logger);
    }

    public function testExecuteCheckColumnExists()
    {
        $logger = new ArrayLogger();
        $this->query->setConnection($this->connection);

        $tableName = 'entity_1';
        $entityClass = 'Test\Entity\Entity1';

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn([['class_name' => $entityClass, 'data' => []]]);

        $this->schema->createTable($tableName);
        $table = $this->schema->getTable($tableName);
        $table->addColumn('serialized_data', 'array');

        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->willReturn([
                'extend' => [
                    'schema' => ['doctrine' => [$entityClass => ['table' => $tableName]]],
                    'is_extend' => true,
                    'state' => 'Active',
                ],
            ]);

        $expectedQuery ='ALTER TABLE entity_1 CHANGE serialized_data serialized_data '
            . 'LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'';

        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with($expectedQuery);

        $this->query->execute($logger);
    }
}
