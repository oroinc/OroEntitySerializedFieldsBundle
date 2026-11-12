<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\EntityExtend;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProvider;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendEntityMetadataProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\EntityExtend\SerializedEntityFieldExtension;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer;
use PHPUnit\Framework\TestCase;

class SerializedEntityFieldExtensionTest extends TestCase
{
    private SerializedEntityFieldExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new SerializedEntityFieldExtension(
            $this->createMock(CompoundSerializedFieldsNormalizer::class)
        );
    }

    public function testEnumMethodMapIsBuiltOncePerClass(): void
    {
        $metadataProvider = $this->createCountingMetadataProvider();

        $this->extension->methodExists($this->createTransport($metadataProvider, 'getMyEnum'));
        $this->extension->methodExists($this->createTransport($metadataProvider, 'setMyEnum'));

        self::assertSame(1, $metadataProvider->fieldsMetadataCalls);
    }

    private function createTransport(
        ExtendEntityMetadataProviderInterface $metadataProvider,
        string $name
    ): EntityFieldProcessTransport {
        $transport = new EntityFieldProcessTransport();
        $transport->setClass('Extend\Entity\TestEntity')
            ->setEntityMetadataProvider($metadataProvider)
            ->setName($name);

        return $transport;
    }

    private function createCountingMetadataProvider(): ExtendEntityMetadataProviderInterface
    {
        return new class () implements ExtendEntityMetadataProviderInterface {
            public int $fieldsMetadataCalls = 0;

            #[\Override]
            public function getExtendEntityMetadata(string $class): ?ConfigInterface
            {
                return new Config(new EntityConfigId('extend', $class), []);
            }

            #[\Override]
            public function getExtendEntityFieldsMetadata(string $class): array
            {
                $this->fieldsMetadataCalls++;

                return [
                    'my_enum' => [
                        ExtendEntityMetadataProvider::FIELD_NAME => 'my_enum',
                        ExtendEntityMetadataProvider::FIELD_TYPE => 'enum',
                        ExtendEntityMetadataProvider::IS_EXTEND => true,
                        ExtendEntityMetadataProvider::IS_NULLABLE => true,
                        ExtendEntityMetadataProvider::IS_SERIALIZED => true,
                    ],
                ];
            }
        };
    }
}
