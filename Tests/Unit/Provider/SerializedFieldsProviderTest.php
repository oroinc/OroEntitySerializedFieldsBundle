<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\SerializedFieldsProvider;

class SerializedFieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private EntityConfigManager $entityConfigManager;

    private SerializedFieldsProvider $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new SerializedFieldsProvider($this->entityConfigManager);
    }

    /**
     * @dataProvider getSerializedFieldsDataProvider
     *
     * @param ConfigInterface[] $fieldConfigs
     * @param string $type
     * @param array $expected
     */
    public function testGetSerializedFields(
        array $fieldConfigs,
        string $type,
        array $expected
    ): void {
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getConfigs')
            ->with('extend', \stdClass::class, true)
            ->willReturn($fieldConfigs);

        self::assertEquals($expected, $this->provider->getSerializedFields(\stdClass::class, $type));
    }

    public function getSerializedFieldsDataProvider(): array
    {
        $configId = new FieldConfigId('extend', \stdClass::class, 'sample_field', 'sample_type1');

        return [
            'no field configs' => [
                'fieldConfigs' => [],
                'type' => '',
                'expected' => [],
            ],
            'not serialized config' => [
                'fieldConfigs' => [new Config($this->createMock(ConfigIdInterface::class), [])],
                'type' => '',
                'expected' => [],
            ],
            'type mismatch' => [
                'fieldConfigs' => [new Config($configId, ['is_serialized' => true])],
                'type' => 'sample_type2',
                'expected' => [],
            ],
            'type match' => [
                'fieldConfigs' => [new Config($configId, ['is_serialized' => true])],
                'type' => 'sample_type1',
                'expected' => ['sample_field'],
            ],
            'no type' => [
                'fieldConfigs' => [new Config($configId, ['is_serialized' => true])],
                'type' => '',
                'expected' => ['sample_field'],
            ],
        ];
    }
}
