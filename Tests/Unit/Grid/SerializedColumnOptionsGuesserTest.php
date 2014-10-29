<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\EntitySerializedFieldsBundle\Grid\SerializedColumnOptionsGuesser;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;

class ExtendColumnOptionsGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var SerializedColumnOptionsGuesser */
    protected $guesser;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new SerializedColumnOptionsGuesser($this->configManager);
    }

    public function testGuessFormatterNoGuess()
    {
        $this->getFieldConfig();
        $guess = $this->guesser->guessFormatter('TestClass', 'testProp', 'string');
        $this->assertNull($guess);
    }

    public function testGuessFormatterNoConfig()
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'integer';

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(false));

        $guess = $this->guesser->guessFormatter($class, $property, $type);
        $this->assertNull($guess);
    }

    public function testGuessFormatter()
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'string';

        $this->getFieldConfig(['is_serialized' => true]);
        $guess = $this->guesser->guessFormatter($class, $property, $type);
        $this->assertEquals(
            [
                'frontend_type' => Property::TYPE_HTML,
                'type'          => 'twig',
                'template'      => 'OroEntitySerializedFieldsBundle:Datagrid:Property/serialized.html.twig',
                'context'       => [
                    'field_name' => $property,
                    'field_type' => $type,
                ],
            ],
            $guess->getOptions()
        );
        $this->assertEquals(ColumnGuess::HIGH_CONFIDENCE, $guess->getConfidence());
    }

    public function testGuessFilterNoGuess()
    {
        $this->getFieldConfig();
        $guess = $this->guesser->guessFilter('TestClass', 'testProp', 'string');
        $this->assertNull($guess);
    }

    public function testGuessFilterNoConfig()
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'integer';

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(false));

        $guess = $this->guesser->guessFilter($class, $property, $type);
        $this->assertNull($guess);
    }

    public function testGuessFilter()
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'string';

        $this->getFieldConfig(['is_serialized' => true]);
        $guess = $this->guesser->guessFilter($class, $property, $type);
        $this->assertEquals(
            [
                'type'      => Property::TYPE_STRING,
                'disabled'  => true
            ],
            $guess->getOptions()
        );
        $this->assertEquals(ColumnGuess::HIGH_CONFIDENCE, $guess->getConfidence());
    }

    public function testGuessSorterNoGuess()
    {
        $this->getFieldConfig();
        $guess = $this->guesser->guessSorter('TestClass', 'testProp', 'string');
        $this->assertNull($guess);
    }

    public function testGuessSorterNoConfig()
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'integer';

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(false));

        $guess = $this->guesser->guessSorter($class, $property, $type);
        $this->assertNull($guess);
    }

    public function testGuessSorter()
    {
        $class    = 'TestClass';
        $property = 'testProp';
        $type     = 'string';

        $this->getFieldConfig(['is_serialized' => true]);
        $guess = $this->guesser->guessSorter($class, $property, $type);
        $this->assertEquals(
            [
                'disabled' => true
            ],
            $guess->getOptions()
        );
        $this->assertEquals(ColumnGuess::HIGH_CONFIDENCE, $guess->getConfidence());
    }

    protected function getFieldConfig($values = array())
    {
        $class    = 'TestClass';
        $property = 'testProp';

        $config = new Config(new FieldConfigId('extend', $class, $property, 'integer'));
        $config->setValues($values);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class, $property)
            ->will($this->returnValue(true));
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($class, $property)
            ->will($this->returnValue($config));
    }
}
