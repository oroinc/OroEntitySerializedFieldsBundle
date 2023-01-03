<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Normalizer;

use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\DatetimeSerializedFieldNormalizer;
use PHPUnit\Framework\TestCase;

class DatetimeSerializedFieldNormalizerTest extends TestCase
{
    /**
     * @dataProvider denormalizedDateProvider
     */
    public function testDateTimeNormalization(
        bool $isProperDate,
        string|int|null $denormalizedDate = null,
        string $exceptionMessage = null
    ) {
        $datetimeNormalizer = new DatetimeSerializedFieldNormalizer();

        if (!$isProperDate) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertEquals(
            $denormalizedDate,
            $datetimeNormalizer->denormalize($datetimeNormalizer->normalize($denormalizedDate))
        );
    }

    /**
     * @dataProvider normalizedDateProvider
     */
    public function testDateTimeDenormalization(
        bool $isProperDate,
        \DateTime|string|null $normalizedDate = null,
        string $exceptionMessage = null
    ) {
        $datetimeNormalizer = new DatetimeSerializedFieldNormalizer();

        if (!$isProperDate) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertEquals(
            $normalizedDate,
            $datetimeNormalizer->normalize($datetimeNormalizer->denormalize($normalizedDate))
        );
    }

    public function denormalizedDateProvider(): array
    {
        return [
            'proper_date1' =>  [true, '1970-01-01T02:30:00+01:00'],
            'null_value' => [true],
            'false_date' => [false, 'now', "Can't convert 'now' date string into 'DateTime' object"],
            'false_value_type' => [false, 10, 'Given value must be a string']
        ];
    }

    public function normalizedDateProvider(): array
    {
        return [
            'proper_date1' =>  [
                true,
                \DateTime::createFromFormat(\DateTimeInterface::ATOM, '1970-01-01T02:30:00+01:00')
            ],
            'null_value' => [true],
            'false_value_type1' => [
                false,
                'simple string',
                sprintf("Given value must be instance of '%s'", \DateTimeInterface::class)
            ],
            'false_value_type2' => [
                false,
                '1970-01-01T02:30:00+01:00',
                sprintf("Given value must be instance of '%s'", \DateTimeInterface::class)
            ],
        ];
    }
}
