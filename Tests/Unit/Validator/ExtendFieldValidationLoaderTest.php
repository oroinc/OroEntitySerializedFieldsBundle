<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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

    /** @var ExtendFieldValidationLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);

        $this->loader = new ExtendFieldValidationLoader($this->extendConfigProvider, $this->formConfigProvider);
    }

    public function testLoadClassMetadata(): void
    {
        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->atLeastOnce())
            ->method('getClassName')
            ->willReturn(ExtendEntityInterface::class);

        $metadata->expects($this->once())
            ->method('addConstraint')
            ->with(new ExtendEntitySerializedData());

        $this->loader->loadClassMetadata($metadata);
    }

    public function testLoadClassMetadataNotExtendEntity(): void
    {
        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->atLeastOnce())
            ->method('getClassName')
            ->willReturn(\stdClass::class);

        $metadata->expects($this->never())
            ->method('addConstraint')
            ->with(new ExtendEntitySerializedData());

        $this->loader->loadClassMetadata($metadata);
    }
}
