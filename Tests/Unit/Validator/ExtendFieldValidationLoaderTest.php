<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedData;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\ExtendFieldValidationLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ExtendFieldValidationLoaderTest extends TestCase
{
    private ConfigProvider&MockObject $extendConfigProvider;
    private ConfigProvider&MockObject $formConfigProvider;
    private FieldConfigConstraintsFactory&MockObject $fieldConfigConstraintsFactory;
    private ExtendFieldValidationLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);
        $this->fieldConfigConstraintsFactory = $this->createMock(FieldConfigConstraintsFactory::class);

        $this->loader = new ExtendFieldValidationLoader(
            $this->extendConfigProvider,
            $this->formConfigProvider,
            $this->fieldConfigConstraintsFactory
        );
    }

    public function testLoadClassMetadata(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::atLeastOnce())
            ->method('getClassName')
            ->willReturn(ExtendEntityInterface::class);

        $metadata->expects(self::once())
            ->method('addConstraint')
            ->with(new ExtendEntitySerializedData());

        $this->loader->loadClassMetadata($metadata);
    }

    public function testLoadClassMetadataNotExtendEntity(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::atLeastOnce())
            ->method('getClassName')
            ->willReturn(\stdClass::class);

        $metadata->expects(self::never())
            ->method('addConstraint')
            ->with(new ExtendEntitySerializedData());

        $this->loader->loadClassMetadata($metadata);
    }
}
