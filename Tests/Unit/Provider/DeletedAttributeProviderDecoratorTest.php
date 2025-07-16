<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\DeletedAttributeProviderDecorator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeletedAttributeProviderDecoratorTest extends TestCase
{
    private DeletedAttributeProviderInterface&MockObject $deletedAttributeProvider;
    private DeletedAttributeProviderDecorator $decorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->deletedAttributeProvider = $this->createMock(DeletedAttributeProviderInterface::class);

        $this->decorator = new DeletedAttributeProviderDecorator($this->deletedAttributeProvider);
    }

    public function testGetAttributesByIds(): void
    {
        $ids = [1, 2];
        $serializedAttribute = $this->getAttribute(true);
        $fieldColumnAttribute = $this->getAttribute(false);
        $this->deletedAttributeProvider->expects($this->once())
            ->method('getAttributesByIds')
            ->with($ids)
            ->willReturn([
                $serializedAttribute,
                $fieldColumnAttribute
            ]);

        $attributes = $this->decorator->getAttributesByIds($ids);
        $this->assertCount(1, $attributes);
        $this->assertEquals($fieldColumnAttribute, array_pop($attributes));
    }

    public function testRemoveAttributeValues(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = [];
        $this->deletedAttributeProvider->expects($this->once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, $names);

        $this->decorator->removeAttributeValues($attributeFamily, $names);
    }

    private function getAttribute(bool $isSerialized): FieldConfigModel
    {
        $attribute = new FieldConfigModel();
        $attribute->fromArray('extend', ['is_serialized' => $isSerialized]);

        return $attribute;
    }
}
