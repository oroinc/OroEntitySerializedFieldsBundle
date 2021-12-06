<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntitySerializedFieldsBundle\Entity\EntitySerializedFieldsHolder;
use Oro\Bundle\EntitySerializedFieldsBundle\Normalizer\CompoundSerializedFieldsNormalizer;
use Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Validator\Constraints\Stub\ExtendEntityStub;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedData;
use Oro\Bundle\EntitySerializedFieldsBundle\Validator\Constraints\ExtendEntitySerializedDataValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ExtendEntitySerializedDataValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'test_field';

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var FieldConfigConstraintsFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigConstraintsFactory;

    /** @var ExtendEntitySerializedDataValidator */
    private $constraintValidator;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->fieldConfigConstraintsFactory = $this->createMock(FieldConfigConstraintsFactory::class);

        $fieldHelper = $this->createMock(FieldHelper::class);
        $fieldHelper->expects(self::any())
            ->method('getEntityFields')
            ->with(ExtendEntityStub::class, EntityFieldProvider::OPTION_WITH_HIDDEN_FIELDS)
            ->willReturn([['name' => self::FIELD_NAME]]);

        $this->constraintValidator = new ExtendEntitySerializedDataValidator(
            $this->configProvider,
            $fieldHelper,
            $this->fieldConfigConstraintsFactory
        );
        $this->constraintValidator->addConstraints('integer', [['Type' => ['type' => 'integer']]]);
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

        $validator = $this->createMock(ValidatorInterface::class);

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

        $this->mockEntitySerializedFieldsHolder(ExtendEntityStub::class, array_keys($serializedData));
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

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())
            ->method('getValidator');

        $serializedData = [self::FIELD_NAME => 'value'];

        $this->mockEntitySerializedFieldsHolder(ExtendEntityStub::class, array_keys($serializedData));
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

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())
            ->method('getValidator');

        $serializedData = [self::FIELD_NAME => 'value'];

        $this->mockEntitySerializedFieldsHolder(ExtendEntityStub::class, array_keys($serializedData));
        $this->constraintValidator->initialize($context);
        $this->constraintValidator->validate(new ExtendEntityStub($serializedData), new ExtendEntitySerializedData());
    }

    private function mockConfigProvider(
        string $type,
        array $values,
        bool $hasConfig = true,
        bool $getConfig = true
    ): void {
        $this->configProvider->expects($getConfig ? self::once() : self::never())
            ->method('hasConfig')
            ->with(ExtendEntityStub::class, self::FIELD_NAME)
            ->willReturn($hasConfig);

        $this->configProvider->expects($hasConfig ? self::once() : self::never())
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

    private function mockEntitySerializedFieldsHolder(string $className, array $fields): void
    {
        $fieldConfigs = [];
        foreach ($fields as $field) {
            $fieldConfigId = $this->createMock(FieldConfigId::class);
            $fieldConfigId->method('getFieldName')
                ->willReturn($field);
            $fieldConfigId->method('getFieldType')
                ->willReturn('text');
            $fieldConfig = $this->createMock(ConfigInterface::class);
            $fieldConfig->method('get')
                ->with('is_serialized')
                ->willReturn(true);
            $fieldConfig->method('getId')
                ->willReturn($fieldConfigId);

            $fieldConfigs[] = $fieldConfig;
        }

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->method('getConfigs')
            ->with('extend', $className, true)
            ->willReturn($fieldConfigs);
        $normalizer = $this->createMock(CompoundSerializedFieldsNormalizer::class);
        $normalizer->method('normalize')
            ->willReturnCallback(function ($fieldType, $value) {
                return $value;
            });
        $normalizer->method('denormalize')
            ->willReturnCallback(function ($fieldType, $value) {
                return $value;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->withConsecutive(
                ['oro_entity_config.config_manager'],
                ['oro_serialized_fields.normalizer.fields_compound_normalizer']
            )->willReturnOnConsecutiveCalls($configManager, $normalizer);

        EntitySerializedFieldsHolder::initialize($container);
    }
}
