<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Normalizer;

use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\SerializedFieldNormalizerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class CompoundSerializedFieldsNormalizerTest extends TestCase
{
    private ServiceLocator $locator;

    protected function setUp(): void
    {
        $this->locator = $this->createMock(ServiceLocator::class);
    }

    /**
     * @dataProvider nonChangeableValuesProvider
     */
    public function testNormalizationWithNoAvailableTypeNormalizer(mixed $value, string $fieldType)
    {
        $this->locator->expects($this->exactly(2))
            ->method('has')
            ->with($fieldType)
            ->willReturn(false);
        $this->locator->expects($this->never())
            ->method('get');

        $compoundNormalizer = new CompoundSerializedFieldsNormalizer($this->locator);

        $this->assertEquals(
            $value,
            $compoundNormalizer->denormalize($fieldType, $compoundNormalizer->normalize($fieldType, $value))
        );
    }

    /**
     * @dataProvider changeableValuesProvider
     */
    public function testNormalizationWithAvailableTypeNormalizer(
        string $fieldType,
        string $denormalizedValue,
        object $normalizedValue,
        SerializedFieldNormalizerInterface $normalizerMock
    ) {
        $this->locator->expects($this->exactly(4))
            ->method('has')
            ->with($fieldType)
            ->willReturn(true);
        $this->locator->expects($this->exactly(4))
            ->method('get')
            ->with($fieldType)
            ->willReturn($normalizerMock);

        $compoundNormalizer = new CompoundSerializedFieldsNormalizer($this->locator);

        $this->assertEquals($normalizedValue, $compoundNormalizer->normalize($fieldType, $denormalizedValue));
        $this->assertEquals($denormalizedValue, $compoundNormalizer->denormalize($fieldType, $normalizedValue));
        $this->assertEquals(
            $denormalizedValue,
            $compoundNormalizer->denormalize(
                $fieldType,
                $compoundNormalizer->normalize($fieldType, $denormalizedValue)
            )
        );
    }

    /**
     * @dataProvider normalizationMethodsProvider
     */
    public function testLocatorNormalizerTypeException($method)
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            "Serialized field typed normalizer must implement '%s' interface",
            SerializedFieldNormalizerInterface::class
        ));

        $this->locator->expects($this->once())
            ->method('has')
            ->willReturn(true);
        $this->locator->expects($this->once())
            ->method('get')
            ->willReturn(new \stdClass());

        $compoundNormalizer = new CompoundSerializedFieldsNormalizer($this->locator);
        $compoundNormalizer->{$method}('type', 'value');
    }

    public function normalizationMethodsProvider(): array
    {
        return ['normalizeMethod' => ['normalize'], 'denormalizeMethod' => ['denormalize']];
    }

    public function nonChangeableValuesProvider(): array
    {
        return [
            'bool' => [true, 'bool'],
            'float_number' => [1000.00, 'float_number'],
            'string' => ['test string', 'string'],
            'object' => [new \stdClass(), 'object']
        ];
    }

    public function changeableValuesProvider(): array
    {
        $dateObject = new \DateTime('now');
        $dateString = $dateObject->format(\DateTimeInterface::ATOM);
        $dateNormalizerMock = $this->createMock(SerializedFieldNormalizerInterface::class);
        $dateNormalizerMock->expects($this->any())
            ->method('normalize')
            ->with($dateString)
            ->willReturn($dateObject);
        $dateNormalizerMock->expects($this->any())
            ->method('denormalize')
            ->with($dateObject)
            ->willReturn($dateString);

        $stdObject = new \stdClass();
        $stdObject->stringKey = 'string value';
        $stdObject->floatKey = 100.01;
        $stdObjectJson = json_encode($stdObject);
        $stdObjectNormalizerMock = $this->createMock(SerializedFieldNormalizerInterface::class);
        $stdObjectNormalizerMock->expects($this->any())
            ->method('normalize')
            ->with($stdObjectJson)
            ->willReturn($stdObject);
        $stdObjectNormalizerMock->expects($this->any())
            ->method('denormalize')
            ->with($stdObject)
            ->willReturn($stdObjectJson);

        return [
            'date' => ['date', $dateString, $dateObject, $dateNormalizerMock],
            'std_object' => ['std_object', $stdObjectJson, $stdObject, $stdObjectNormalizerMock]
        ];
    }
}
