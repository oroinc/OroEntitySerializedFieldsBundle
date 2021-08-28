<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedData;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\ExtendFieldValidationLoader;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ExtendFieldValidationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $formConfigProvider;

    /** @var FieldConfigConstraintsFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigConstraintsFactory;

    /** @var ExtendFieldValidationLoader */
    private $loader;

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
