<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Grid\FieldsHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Grid\SerializedFieldsExtension;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class SerializedFieldsExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS_NAME = 'SomeEntityClassName';
    private const ENTITY_ALIAS = 'entityAlias';

    /** @var FieldsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldsHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var DatagridGuesser|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridGuesser;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var SelectedFieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $selectedFieldsProvider;

    /** @var SerializedFieldsExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->datagridGuesser = $this->createMock(DatagridGuesser::class);
        $this->fieldsHelper = $this->createMock(FieldsHelper::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->selectedFieldsProvider = $this->createMock(SelectedFieldsProviderInterface::class);

        $this->extension = new SerializedFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            $this->datagridGuesser,
            $this->fieldsHelper,
            $this->doctrineHelper,
            $this->selectedFieldsProvider
        );
        $this->extension->setDbalTypes([
            'bigint' => 'bigint',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
        ]);
    }

    public function getFieldsDataProvider(): array
    {
        $notSerializedField1 = new FieldConfigId('scope', self::ENTITY_CLASS_NAME, 'notSerializedField1', 'bigint');
        $notSerializedField2 = new FieldConfigId('scope', self::ENTITY_CLASS_NAME, 'notSerializedField2', 'boolean');
        $serializedField1 = new FieldConfigId('scope', self::ENTITY_CLASS_NAME, 'serializedField1', 'date');
        $serializedField2 = new FieldConfigId('scope', self::ENTITY_CLASS_NAME, 'serializedField2', 'datetime');

        return [
            'only not serialized fields' => [
                'fields' => [
                    $notSerializedField1,
                    $notSerializedField2,
                ],
                'fieldsData' => [
                    [self::ENTITY_CLASS_NAME, 'notSerializedField1'],
                    [self::ENTITY_CLASS_NAME, 'notSerializedField2'],
                ],
                'configs' => [
                    new Config($notSerializedField1, ['is_serialized' => false]),
                    new Config($notSerializedField2, ['is_serialized' => false])
                ],
                'expectedExpressions' => [
                    'entityAlias.notSerializedField1',
                    'entityAlias.notSerializedField2',
                ],
            ],
            'serialized and not serialized fields' => [
                'fields' => [
                    $notSerializedField1,
                    $notSerializedField2,
                    $serializedField1,
                    $serializedField2
                ],
                'fieldsData' => [
                    [self::ENTITY_CLASS_NAME, 'notSerializedField1'],
                    [self::ENTITY_CLASS_NAME, 'notSerializedField2'],
                    [self::ENTITY_CLASS_NAME, 'serializedField1'],
                    [self::ENTITY_CLASS_NAME, 'serializedField2']
                ],
                'configs' => [
                    new Config($notSerializedField1, ['is_serialized' => false]),
                    new Config($notSerializedField2, ['is_serialized' => false]),
                    new Config($serializedField1, ['is_serialized' => true]),
                    new Config($serializedField2, ['is_serialized' => true]),
                ],
                'expectedExpressions' => [
                    "CAST(JSON_EXTRACT(entityAlias.serialized_data,'serializedField1') as date) AS serializedField1",
                    "CAST(JSON_EXTRACT(entityAlias.serialized_data,'serializedField2') as datetime) " .
                    "AS serializedField2",
                    'entityAlias.notSerializedField1',
                    'entityAlias.notSerializedField2',
                ],
            ],
            'only serialized fields' => [
                'fields' => [
                    $serializedField1,
                    $serializedField2,
                ],
                'fieldsData' => [
                    [self::ENTITY_CLASS_NAME, 'serializedField1'],
                    [self::ENTITY_CLASS_NAME, 'serializedField2'],
                ],
                'configs' => [
                    new Config($serializedField1, ['is_serialized' => true]),
                    new Config($serializedField2, ['is_serialized' => true])
                ],
                'expectedData' => [
                    "CAST(JSON_EXTRACT(entityAlias.serialized_data,'serializedField1') as date) AS serializedField1",
                    "CAST(JSON_EXTRACT(entityAlias.serialized_data,'serializedField2') as datetime) AS serializedField2"
                ]
            ],
        ];
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testBuildExpression(array $fields, array $fieldsData, array $configs, array $expectedData)
    {
        $datagridConfig = DatagridConfiguration::create(['extended_entity_name' => \stdClass::class]);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);

        $extendConfigProvider->expects($this->exactly(count($fields)))
            ->method('getConfig')
            ->withConsecutive(...$fieldsData)
            ->willReturnOnConsecutiveCalls(...$configs);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(self::ENTITY_CLASS_NAME);

        $this->selectedFieldsProvider->expects($this->any())
            ->method('getSelectedFields')
            ->willReturn(['notSerializedField1', 'notSerializedField2']);

        $this->extension->setParameters($this->createMock(ParameterBag::class));
        $this->extension->buildExpression($fields, $datagridConfig, self::ENTITY_ALIAS);

        $this->assertEquals($expectedData, $datagridConfig->offsetGetByPath('[source][query][select]'));
    }
}
