<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntitySerializedFieldsBundle\Duplicator\Filter\SerializedDataFilter;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SerializedDataFilterTest extends TestCase
{
    private SerializedDataFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->filter = new SerializedDataFilter();
    }

    public function testApplyEmptyArrayValue(): void
    {
        $object = new \stdClass();
        $object->serialized_data = [];

        // Source entity needed; early return happens before ClassUtils::getClass
        $this->filter->setSourceEntity(new \stdClass());

        $this->filter->apply($object, 'serialized_data', $this->getCopierMock([]));

        self::assertTrue(is_array($object->serialized_data));
        self::assertEmpty($object->serialized_data);
        self::assertEmpty($object->serialized_normalized);
    }

    public function testApplyArrayValue(): void
    {
        $object = new \stdClass();
        $object->serialized_data = ['property' => 'value'];

        $this->filter->setSourceEntity(new \stdClass());

        $this->filter->apply(
            $object,
            'serialized_data',
            $this->getCopierMock([\stdClass::class => ['property' => ['type' => 'enum']]])
        );

        self::assertArrayHasKey('property', $object->serialized_data);
        self::assertArrayHasKey('property', $object->serialized_normalized);
    }

    private function getCopierMock(array $fieldConfig): \Closure
    {
        $normalizerMock = $this->createMock(CompoundSerializedFieldsNormalizer::class);
        $normalizerMock->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(static fn (mixed $value): mixed => $value);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects(self::any())
            ->method('get')
            ->withConsecutive(['oro_serialized_fields.normalizer.fields_compound_normalizer'])
            ->willReturn($normalizerMock);

        $configManagerMock = $this->createMock(ConfigManager::class);
        $configManagerMock->expects(self::any())
            ->method('getConfigs')
            ->willReturn([]);

        $class = new \ReflectionClass(EntitySerializedFieldsHolder::class);
        $class->setStaticPropertyValue('container', $containerMock);
        $class->setStaticPropertyValue('fieldsNormalizer', $normalizerMock);
        $class->setStaticPropertyValue('configManager', $configManagerMock);
        $class->setStaticPropertyValue('serializedFields', $fieldConfig);

        return static fn (mixed $data): object => (object)$data;
    }
}
