<?php
namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }
}
