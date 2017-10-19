<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroEntitySerializedFieldsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $this->configureTestEnvironment($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureTestEnvironment(ContainerBuilder $container)
    {
        // oro_serialized_fields.tests.migration_listener
        $testMigrationListenerDef = new Definition(
            'Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\Environment\TestEntitiesMigrationListener'
        );
        $testMigrationListenerDef->addTag(
            'kernel.event_listener',
            ['event' => 'oro_migration.post_up', 'method' => 'onPostUp']
        );
        $container->setDefinition('oro_serialized_fields.tests.migration_listener', $testMigrationListenerDef);
    }
}
