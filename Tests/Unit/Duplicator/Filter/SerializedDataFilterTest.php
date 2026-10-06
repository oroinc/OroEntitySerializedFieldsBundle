<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Duplicator\Filter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntitySerializedFieldsBundle\Duplicator\Filter\SerializedDataFilter;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Bundle\EntitySerializedFieldsBundle\Exception\NoSuchPropertyException;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityFields;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;
use Oro\Component\Duplicator\PropertyBag;
use Oro\Component\Duplicator\PropertyBagInterface;
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

    public function testApplyNullValue(): void
    {
        $object = new \stdClass();
        $object->serialized_data = null;

        // Source entity needed; early return happens before ClassUtils::getClass
        $this->filter->setSourceEntity(new \stdClass());

        $this->filter->apply($object, 'serialized_data', $this->getCopierMock([]));

        self::assertTrue($object->serialized_data === null);
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

    public function testEntityClassTakenFromPropertyBagOwner(): void
    {
        $copier = $this->getCopierMock([TestExtendedEntity::class => ['field' => ['type' => 'string']]]);
        $this->filter->setSourceEntity(new TestEntityFields());
        $bag = new PropertyBag(TestExtendedEntity::class, ['serialized_data' => ['field' => 'value']]);

        $this->filter->apply($bag, 'serialized_data', $copier);

        self::assertSame(['field' => 'value'], $bag->serialized_data);
        self::assertSame(['field' => 'value'], $bag->serialized_normalized);
    }

    public function testSourceEntityClassIsNotUsedForPropertyBag(): void
    {
        $copier = $this->getCopierMock([TestEntityFields::class => ['field' => ['type' => 'string']]]);
        $this->filter->setSourceEntity(new TestEntityFields());
        $bag = new PropertyBag(TestExtendedEntity::class, ['serialized_data' => ['field' => 'value']]);

        $this->expectException(NoSuchPropertyException::class);
        $this->filter->apply($bag, 'serialized_data', $copier);
    }

    public function testPlainObjectBagWithoutSourceEntityIsSkipped(): void
    {
        $object = new \stdClass();
        $object->serialized_data = ['field' => 'value'];

        $this->filter->apply($object, 'serialized_data', static function (): never {
            self::fail('The copier must not be called');
        });

        self::assertSame(['field' => 'value'], $object->serialized_data);
        self::assertObjectNotHasProperty('serialized_normalized', $object);
    }

    public function testCopierReceivesBagWithSerializedEntries(): void
    {
        $this->getCopierMock([TestExtendedEntity::class => ['field' => ['type' => 'string']]]);
        $bag = new PropertyBag(TestExtendedEntity::class, ['serialized_data' => ['field' => 'value']]);

        $received = null;
        $this->filter->apply($bag, 'serialized_data', function (PropertyBagInterface $bag) use (&$received) {
            $received = $bag;

            return clone $bag;
        });

        self::assertInstanceOf(PropertyBag::class, $received);
        self::assertSame(TestExtendedEntity::class, $received->getOwnerClass());
        self::assertSame(['field' => 'value'], $received->toArray());
    }

    public function testSourceBagRetainedWhileFilterLives(): void
    {
        $this->getCopierMock([TestExtendedEntity::class => ['field' => ['type' => 'string']]]);
        $bag = new PropertyBag(TestExtendedEntity::class, ['serialized_data' => ['field' => 'value']]);

        $weakRef = null;
        $this->filter->apply($bag, 'serialized_data', function (PropertyBagInterface $bag) use (&$weakRef) {
            $weakRef = \WeakReference::create($bag);

            return clone $bag;
        });

        gc_collect_cycles();
        self::assertNotNull($weakRef->get(), 'the source bag must live as long as the filter');

        $this->filter = new SerializedDataFilter();
        gc_collect_cycles();
        self::assertNull($weakRef->get(), 'the source bag must be released with the filter');
    }

    private function getCopierMock(array $fieldConfig): \Closure
    {
        $normalizerMock = $this->createMock(CompoundSerializedFieldsNormalizer::class);
        $normalizerMock->expects(self::any())
            ->method('normalize')
            ->willReturnCallback(static fn (string $type, mixed $value): mixed => $value);

        $normalizerMock->expects(self::any())
            ->method('denormalize')
            ->willReturnCallback(static fn (string $type, mixed $value): mixed => $value);

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

        return static fn (PropertyBagInterface $bag): object => clone $bag;
    }
}
