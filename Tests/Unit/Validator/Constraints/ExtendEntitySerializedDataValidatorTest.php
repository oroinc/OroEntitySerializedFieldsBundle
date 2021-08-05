<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints\Stub\ExtendEntityStub;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedData;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedDataValidator;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExtendEntitySerializedDataValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'test_field';

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var ExtendEntitySerializedDataValidator */
    private $constraintValidator;

    /** @var FieldConfigConstraintsFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigConstraintsFactory;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->fieldHelper->expects(self::any())
            ->method('getEntityFields')
            ->with(ExtendEntityStub::class, EntityFieldProvider::OPTION_WITH_HIDDEN_FIELDS)
            ->willReturn([['name' => self::FIELD_NAME]]);

        $this->constraintValidator = new ExtendEntitySerializedDataValidator($this->configProvider, $this->fieldHelper);
        $this->constraintValidator->addConstraints('integer', [['Type' => ['type' => 'integer']]]);

        $this->fieldConfigConstraintsFactory = $this->createMock(FieldConfigConstraintsFactory::class);
    }

    public function testValidate(): void
    {
        $type = 'integer';
        $values = [
            'is_extend' => true,
            'is_serialized' => true,
            'is_deleted' => false,
            'state' => ExtendScope::STATE_ACTIVE,
        ];
        $this->mockConfigProvider($type, $values);

        $serializedData = [self::FIELD_NAME => 'value1', 'some_other_field' => 'value2'];

        $constraintGreaterThan10 = new Constraints\GreaterThan(10);

        /** @var ContextualValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $contextualValidator */
        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $contextualValidator->expects(self::once())
            ->method('atPath')
            ->with(self::FIELD_NAME)
            ->willReturnSelf();
        $contextualValidator->expects(self::once())
            ->method('validate')
            ->with(
                $serializedData[self::FIELD_NAME],
                [
                    new Constraints\Type(['type' => 'integer']),
                    $constraintGreaterThan10,
                ]
            );

        /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator */
        $validator = $this->createMock(ValidatorInterface::class);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('getValidator')
            ->willReturn($validator);

        $validator->expects(self::once())
            ->method('inContext')
            ->with($context)
            ->willReturn($contextualValidator);

        $this->fieldConfigConstraintsFactory->expects(self::once())
            ->method('create')
            ->with($this->getConfig($type, $values))
            ->willReturn([$constraintGreaterThan10]);
        $this->constraintValidator->setFieldConfigConstraintsFactory($this->fieldConfigConstraintsFactory);

        $this->constraintValidator->initialize($context);
        $this->constraintValidator->validate(new ExtendEntityStub($serializedData), new ExtendEntitySerializedData());
    }

    public function testValidateUnsupportedEntity(): void
    {
        $this->mockConfigProvider(
            'integer',
            [
                'is_extend' => true,
                'is_serialized' => true,
                'is_deleted' => false,
                'state' => ExtendScope::STATE_ACTIVE,
            ],
            false,
            false
        );

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())
            ->method('getValidator');

        $this->constraintValidator->initialize($context);
        $this->constraintValidator->validate(new \stdClass(), new ExtendEntitySerializedData());
    }

    /**
     * @dataProvider fieldConfigDataProvider
     */
    public function testValidateFieldConfig(array $values, bool $expectedCall = true): void
    {
        $this->mockConfigProvider('integer', $values, $expectedCall);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())
            ->method('getValidator');

        $serializedData = [self::FIELD_NAME => 'value'];

        $this->constraintValidator->initialize($context);
        $this->constraintValidator->validate(new ExtendEntityStub($serializedData), new ExtendEntitySerializedData());
    }

    public function fieldConfigDataProvider(): array
    {
        return [
            'not extend' => [
                [
                    'is_extend' => false,
                    'is_serialized' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_ACTIVE,
                ]
            ],
            'not serialized' => [
                [
                    'is_extend' => true,
                    'is_serialized' => false,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_ACTIVE,
                ]
            ],
            'deleted' => [
                [
                    'is_extend' => true,
                    'is_serialized' => true,
                    'is_deleted' => true,
                    'state' => ExtendScope::STATE_ACTIVE,
                ]
            ],
            'new state' => [
                [
                    'is_extend' => true,
                    'is_serialized' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_NEW,
                ]
            ],
            'deleted state' => [
                [
                    'is_extend' => true,
                    'is_serialized' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_DELETE,
                ]
            ],
            'no config' => [
                [
                    'is_extend' => true,
                    'is_serialized' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_ACTIVE,
                ],
                false
            ],
        ];
    }

    public function testValidateNoConstraints(): void
    {
        $this->mockConfigProvider(
            'string',
            [
                'is_extend' => true,
                'is_serialized' => true,
                'is_deleted' => false,
                'state' => ExtendScope::STATE_ACTIVE,
            ]
        );

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())
            ->method('getValidator');

        $serializedData = [self::FIELD_NAME => 'value'];

        $this->constraintValidator->initialize($context);
        $this->constraintValidator->validate(new ExtendEntityStub($serializedData), new ExtendEntitySerializedData());
    }

    private function mockConfigProvider(
        string $type,
        array $values,
        bool $hasConfig = true,
        bool $getConfig = true
    ): void {
        $this->configProvider
            ->expects($getConfig ? self::once() : self::never())
            ->method('hasConfig')
            ->with(ExtendEntityStub::class, self::FIELD_NAME)
            ->willReturn($hasConfig);

        $this->configProvider
            ->expects($hasConfig ? self::once() : self::never())
            ->method('getConfig')
            ->with(ExtendEntityStub::class, self::FIELD_NAME)
            ->willReturn(
                $this->getConfig($type, $values)
            );
    }

    private function getConfig(string $type, array $values): Config
    {
        return new Config(
            new FieldConfigId('extend', ExtendEntityStub::class, self::FIELD_NAME, $type),
            $values
        );
    }
}
