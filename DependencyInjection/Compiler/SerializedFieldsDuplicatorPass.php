<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers a default duplicator rule that unwraps the `serialized_data`
 * sub-bucket of `extendEntityStorage`, allowing user-supplied DeepCopy
 * filters to reach individual serialized fields by name/type.
 */
class SerializedFieldsDuplicatorPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('oro_action.factory.duplicator_factory')) {
            return;
        }

        $container->getDefinition('oro_action.factory.duplicator_factory')
            ->addMethodCall('addRule', [
                ['serialized_data_storage'],
                ['propertyName', ['serialized_data']],
            ]);
    }
}
