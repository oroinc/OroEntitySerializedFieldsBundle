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
    public function testExecute($row, $data, $expectedLoggerData)
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

        foreach ($expectedLoggerData as $id => $expectedMessage) {
            $message = $logger->getMessages()[$id];
            if (!$expectedMessage[1]) {
                $this->assertSame($expectedMessage[0], $message);
            } else {
                $this->assertSame(0, strpos($message, $expectedMessage[0]));
            }
        }
    }

    public function dataProvider()
    {
        return [
            [
                'rows' => [
                    [
                        'id'         => 1,
                        'class_name' => 'Test\Entity\Entity1',
                        'data'       => []
                    ]
                ],
                'data' => [
                    'extend' => [
                        'is_extend' => true,
                        'state'     => 'Active'
                    ]
                ],
                [
                    ["SELECT id, class_name, data FROM oro_entity_config WHERE mode = 'default'", false],
                    [
                        "ALTER TABLE test_table ADD serialized_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'",
                        false
                    ],
                    [
                        "DELETE FROM oro_entity_config_field WHERE entity_id = :entityId AND field_name = :fieldName",
                        false
                    ],
                    ["Parameters:", false],
                    ["[entityId] = 1", false],
                    ["[fieldName] = serialized_data", false],
                    [
                        "INSERT INTO oro_entity_config_field  (entity_id, field_name, type, created, updated, mode" .
                        ", data)  values (:entity_id, :field_name, :type, :created, :updated, :mode, :data)",
                        false
                    ],
                    ["Parameters:", false],
                    ["[entity_id] = 1", false],
                    ["[field_name] = serialized_data", false],
                    ["[type] = array", false],
                    ["[created] = ", true],
                    ["[updated] = ", true],
                    ["[mode] = hidden", false],
                    [
                        "[data] = a:5:{s:6:\"entity\";a:1:{s:5:\"label\";s:4:\"data\";}s:6:\"extend\";a:2:{s:5:" .
                        "\"owner\";s:6:\"Custom\";s:9:\"is_extend\";b:0;}s:8:\"datagrid\";a:1:{s:10:\"is_visible\";" .
                        "b:0;}s:5:\"merge\";a:1:{s:7:\"display\";b:0;}s:9:\"dataaudit\";a:1:{s:9:\"auditable\";b:0;}}",
                        true
                    ],
                ]
            ]
        ];
    }
}
