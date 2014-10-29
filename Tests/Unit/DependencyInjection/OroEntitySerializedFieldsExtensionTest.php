<?php
namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\OroEntitySerializedFieldsExtension;

class OroEntitySerializedFieldsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroEntitySerializedFieldsExtension
     */
    protected $extension;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroEntitySerializedFieldsExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }

    /**
     * @dataProvider loadServiceDataProvider
     *
     * @param string $service
     * @param string $class
     * @param array  $arguments
     * @param array  $tags
     * @param string $scope
     */
    public function testLoadServices($service, $class, array $arguments, array $tags, $scope)
    {
        $this->extension->load(array(), $this->container);
        $definition = $this->container->getDefinition($service);

        $this->assertEquals($class, $definition->getClass());
        $this->assertTrue($this->container->hasParameter(trim($class, '%')));

        $this->assertEquals($arguments, $definition->getArguments());
        $this->assertEquals($tags, $definition->getTags());
        $this->assertEquals($scope, $definition->getScope());
    }

    /**
     * @dataProvider loadParameterDataProvider
     *
     * @param string $parameter
     */
    public function testLoadParameters($parameter)
    {
        $this->extension->load(array(), $this->container);
        $this->assertTrue($this->container->hasParameter($parameter));
    }

    public function loadParameterDataProvider()
    {
        return [
            'form.extension.field_extension' => [
                'oro_serialized_fields.form.extension.field_extension.class'
            ],
            'form.type.is_serialized_field' => [
                'oro_serialized_fields.form.type.is_serialized_field.class'
            ]
        ];
    }

    public function loadServiceDataProvider()
    {
        return [
          'form.extension.field_extension' => [
              'service'   => 'oro_serialized_fields.form.extension.field_extension',
              'class'     => '%oro_serialized_fields.form.extension.field_extension.class%',
              'arguments' => [
                  new Reference('session'),
                  ['fieldName', 'is_serialized', 'type']
              ],
              'tags'      => [
                  'form.type_extension' => array(
                      ['alias' => 'oro_entity_extend_field_type']
                  )
              ],
              'scope'     => 'container'
          ],
          'form.type.is_serialized_field' => [
              'service'   => 'oro_serialized_fields.form.type.is_serialized_field',
              'class'     => '%oro_serialized_fields.form.type.is_serialized_field.class%',
              'arguments' => [],
              'tags'      => [
                  'form.type' => array(
                      ['alias' => 'oro_serialized_fields_is_serialized_type']
                  )
              ],
              'scope'     => 'container'
          ]
        ];
    }
}
