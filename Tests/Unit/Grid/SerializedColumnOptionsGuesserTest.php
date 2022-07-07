<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntitySerializedFieldsBundle\Grid\SerializedColumnOptionsGuesser;
use Symfony\Component\Form\Guess\Guess;

class SerializedColumnOptionsGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SerializedColumnOptionsGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->guesser = new SerializedColumnOptionsGuesser($this->configManager);
    }

    public function testGuessFormatterNoGuess()
    {
        $this->prepareFieldConfig();
        $guess = $this->guesser->guessFormatter('TestClass', 'testProp', 'string');
        $this->assertNull($guess);
    }

    public function testGuessFormatterNoConfig()
    {
        $class = 'TestClass';
        $property = 'testProp';
        $type = 'integer';

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(false);

        $guess = $this->guesser->guessFormatter($class, $property, $type);
        $this->assertNull($guess);
    }

    public function testGuessFormatter()
    {
        $class = 'TestClass';
        $property = 'testProp';
        $type = 'string';

        $this->prepareFieldConfig(['is_serialized' => true]);
        $guess = $this->guesser->guessFormatter($class, $property, $type);
        $this->assertEquals(
            [
                'frontend_type' => Property::TYPE_HTML,
                'type'          => 'twig',
                'template'      => '@OroEntitySerializedFields/Datagrid/Property/serialized.html.twig',
                'context'       => [
                    'field_name' => $property,
                    'field_type' => $type,
                ],
            ],
            $guess->getOptions()
        );
        $this->assertEquals(Guess::HIGH_CONFIDENCE, $guess->getConfidence());
    }

    private function prepareFieldConfig(array $values = []): void
    {
        $class = 'TestClass';
        $property = 'testProp';

        $config = new Config(new FieldConfigId('extend', $class, $property, 'integer'));
        $config->setValues($values);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->willReturn(true);
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($class, $property)
            ->willReturn($config);
    }
}
