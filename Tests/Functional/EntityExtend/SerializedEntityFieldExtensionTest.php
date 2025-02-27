<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\EntityExtend\Extension;

use Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend\Extension\EntityExtendTransportTrait;
use Oro\Bundle\EntitySerializedFieldsBundle\EntityExtend\SerializedEntityFieldExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SerializedEntityFieldExtensionTest extends WebTestCase
{
    use EntityExtendTransportTrait;

    private SerializedEntityFieldExtension $serializedExtension;

    public function setUp(): void
    {
        self::bootKernel();
        $normalizer = self::getContainer()
            ->get('oro_serialized_fields.normalizer.fields_compound_normalizer');
        $this->serializedExtension = new SerializedEntityFieldExtension($normalizer);
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testIsset(string $class, string $name, mixed $expected): void
    {
        $transport = $this->createTransport($class);
        $transport->setName($name);
        $this->serializedExtension->isset($transport);

        self::assertSame($expected, $transport->getResult());
    }

    public function propertiesDataProvider(): array
    {
        return [
            'serialized attribute isset' => [
                'class' => TestExtendedEntity::class,
                'name' => 'serialized_attribute',
                'result' => true
            ],
            'serialized data field isset' => [
                'class' => TestExtendedEntity::class,
                'name' => 'serialized_data',
                'result' => true
            ],
            'serialized property is undefined' => [
                'class' => TestExtendedEntity::class,
                'name' => 'undefined_serialized_property',
                'result' => null
            ],
            'skipped real property check' => [
                'class' => TestExtendedEntity::class,
                'name' => 'id',
                'result' => null
            ],
            'skipped extended not serialized property' => [
                'class' => TestExtendedEntity::class,
                'name' => 'name',
                'result' => null
            ],
        ];
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testPropertyExists(string $class, string $name, mixed $expected): void
    {
        $transport = $this->createTransport($class);
        $transport->setName($name);
        $this->serializedExtension->isset($transport);

        self::assertSame($expected, $transport->getResult());
    }

    /**
     * @dataProvider methodsDataProvider
     */
    public function testMethodExists(string $class, string $name, mixed $expected): void
    {
        $transport = $this->createTransport($class);
        $transport->setName($name);
        $this->serializedExtension->methodExists($transport);

        self::assertSame($expected, $transport->getResult());
    }

    public function methodsDataProvider(): array
    {
        return [
            'get serialized data exists' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getSerializedData',
                'result' => true
            ],
            'set serialized data exists' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getSerializedData',
                'result' => true
            ],
            'undefined serialized method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getUndefinedSerializedData',
                'result' => null
            ],
            'not serialized method' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getName',
                'result' => null
            ],
        ];
    }

    /**
     * @dataProvider callDataProvider
     */
    public function testCall(
        string|object $classOrObject,
        string $name,
        mixed $expected,
        bool $isProcessed
    ): void {
        $transport = $this->createTransport($classOrObject);
        $transport->setName($name);
        $this->serializedExtension->call($transport);

        self::assertSame($expected, $transport->getResult());
        self::assertSame($isProcessed, $transport->isProcessed());
    }

    public function callDataProvider(): array
    {
        $testExtendEntity1 = new TestExtendedEntity();
        $testExtendEntity1->getStorage()->offsetSet('serialized_data', ['test1']);
        $testExtendEntity2 = new TestExtendedEntity();

        return [
            'real method call' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getId',
                'result' => null,
                'isProcessed' => false,
            ],
            'extended not serialized method call' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getName',
                'result' => null,
                'isProcessed' => false,
            ],
            'undefined method call' => [
                'class' => TestExtendedEntity::class,
                'name' => 'getUndefinedMethod',
                'result' => null,
                'isProcessed' => false,
            ],
            'call get serialized data' => [
                'class' => $testExtendEntity1,
                'name' => 'getSerializedData',
                'result' => ['test1'],
                'isProcessed' => true,
            ],
            'call set serialized data' => [
                'class' => $testExtendEntity2,
                'name' => 'setSerializedData',
                'result' => $testExtendEntity2,
                'isProcessed' => true,
            ],
        ];
    }

    public function testSetSerializedDataProperty(): void
    {
        $testSetVale = ['test val1'];
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('serialized_data');
        $transport->setValue($testSetVale);
        $this->serializedExtension->set($transport);

        self::assertTrue($transport->isProcessed());
        self::assertSame($testSetVale, $transport->getStorage()->offsetGet('serialized_data'));
        self::assertSame([], $transport->getStorage()->offsetGet('serialized_normalized'));
    }

    public function testSetNotSerializedOrUndefined(): void
    {
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('undefined_property');
        $transport->setValue('testVal1');
        $this->serializedExtension->set($transport);

        self::assertFalse($transport->isProcessed());

        // extended property set
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('getName');
        $transport->setValue('testVal2');
        $this->serializedExtension->set($transport);

        self::assertFalse($transport->isProcessed());
    }

    public function testSetSerializedPropertyNotNull(): void
    {
        $testSetVale = ['serialized_attr_val1'];
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('serialized_attribute');
        $transport->setValue($testSetVale);
        $this->serializedExtension->set($transport);

        self::assertTrue($transport->isProcessed());
        self::assertSame(
            ['serialized_attribute' => $testSetVale],
            $transport->getStorage()->offsetGet('serialized_data')
        );
        self::assertSame(
            ['serialized_attribute' => $testSetVale],
            $transport->getStorage()->offsetGet('serialized_normalized')
        );
    }

    public function testSetSerializedPropertyNull(): void
    {
        $testSetVale = null;
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('serialized_attribute');
        $transport->setValue($testSetVale);
        $this->serializedExtension->set($transport);

        self::assertTrue($transport->isProcessed());
        self::assertSame([], $transport->getStorage()->offsetGet('serialized_data'));
        self::assertSame([], $transport->getStorage()->offsetGet('serialized_normalized'));
    }

    public function testGetSerializedData(): void
    {
        $testSetVale = ['serialized_attribute' => 'testVal1'];
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('serialized_data');
        $transport->getStorage()->offsetSet('serialized_data', $testSetVale);
        $this->serializedExtension->get($transport);

        self::assertTrue($transport->isProcessed());
        self::assertSame($testSetVale, $transport->getResult());
    }

    public function testGetSerializedProperty(): void
    {
        $testSetVale = ['serialized_attribute' => 'testVal1'];
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('serialized_data');
        $transport->getStorage()->offsetSet('serialized_data', $testSetVale);
        $this->serializedExtension->get($transport);

        self::assertTrue($transport->isProcessed());
        self::assertSame($testSetVale, $transport->getResult());
    }

    public function testGetNotSerializedProperty(): void
    {
        $transport = $this->createTransport(new TestExtendedEntity());
        $transport->setName('getName');
        $this->serializedExtension->get($transport);

        self::assertFalse($transport->isProcessed());
    }
}
