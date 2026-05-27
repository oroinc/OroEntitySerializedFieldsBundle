<?php

declare(strict_types=1);

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntitySerializedFieldsBundle\DependencyInjection\Compiler\SerializedFieldsDuplicatorPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SerializedFieldsDuplicatorPassTest extends TestCase
{
    private SerializedFieldsDuplicatorPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new SerializedFieldsDuplicatorPass();
    }

    public function testProcessWithoutFactoryService(): void
    {
        // Must not throw when the factory service is absent
        $this->compiler->process(new ContainerBuilder());
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->register('oro_action.factory.duplicator_factory');

        $this->compiler->process($container);

        self::assertSame(
            [
                ['addRule', [['serialized_data_storage'], ['propertyName', ['serialized_data']]]],
            ],
            $container->getDefinition('oro_action.factory.duplicator_factory')->getMethodCalls()
        );
    }
}
