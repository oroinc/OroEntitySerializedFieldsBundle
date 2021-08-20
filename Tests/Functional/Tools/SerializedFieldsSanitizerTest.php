<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Tools;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\DataFixtures\LoadTestActivityTargetWithTagsData;
use Oro\Bundle\EntitySerializedFieldsBundle\Tools\SerializedFieldsSanitizer;
use Oro\Bundle\SecurityBundle\Tools\AbstractFieldsSanitizer;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class SerializedFieldsSanitizerTest extends WebTestCase
{
    private SerializedFieldsSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadTestActivityTargetWithTagsData::class]);

        $this->sanitizer = self::getContainer()->get('oro_serialized_fields.tools.serialized_fields_sanitizer');
    }

    public function testSanitizeByFieldTypeWhenNoEntityManager(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Entity manager for class %s was not found', \stdClass::class));

        $result = $this->sanitizer->sanitizeByFieldType(
            \stdClass::class,
            'sampleField',
            AbstractFieldsSanitizer::MODE_STRIP_TAGS,
            [],
            false
        );
        iterator_to_array($result);
    }

    public function testSanitizeByFieldTypeWhenNoField(): void
    {
        $result = $this->sanitizer->sanitizeByFieldType(
            TestActivityTarget::class,
            'missing',
            AbstractFieldsSanitizer::MODE_STRIP_TAGS,
            [],
            false
        );

        self::assertEquals([], iterator_to_array($result));
    }

    /**
     * @dataProvider modeDataProvider
     *
     * @param int $mode
     */
    public function testSanitizeByFieldTypeWhenNotApplyChanges(int $mode): void
    {
        $result = $this->sanitizer->sanitizeByFieldType(TestActivityTarget::class, Types::STRING, $mode, [], false);

        $activityTarget = $this->getReference(LoadTestActivityTargetWithTagsData::ACTIVITY_TARGET_1);
        self::assertEquals([$activityTarget->getId() => ['serialized_attribute']], iterator_to_array($result));
    }

    public function modeDataProvider(): array
    {
        return [
            ['mode' => AbstractFieldsSanitizer::MODE_STRIP_TAGS],
            ['mode' => AbstractFieldsSanitizer::MODE_SANITIZE],
        ];
    }

    /**
     * @dataProvider sanitizeByFieldTypeWhenApplyChangesDataProvider
     *
     * @param int $mode
     */
    public function testSanitizeByFieldTypeWhenApplyChanges(int $mode, string $expected): void
    {
        $result = $this->sanitizer->sanitizeByFieldType(TestActivityTarget::class, Types::STRING, $mode, [], true);

        $activityTarget = $this->getReference(LoadTestActivityTargetWithTagsData::ACTIVITY_TARGET_1);
        self::assertEquals([$activityTarget->getId() => ['serialized_attribute']], iterator_to_array($result));

        self::assertEquals(
            $expected,
            $activityTarget->getSerializedAttribute()
        );
    }

    public function sanitizeByFieldTypeWhenApplyChangesDataProvider(): array
    {
        return [
            ['mode' => AbstractFieldsSanitizer::MODE_STRIP_TAGS, 'expected' => 'Name with'],
            ['mode' => AbstractFieldsSanitizer::MODE_SANITIZE, 'expected' => 'Name with Le&gt;'],
        ];
    }
}
