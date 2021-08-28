<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\DeletedAttributeProviderDecorator;

class DeletedAttributeProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeletedAttributeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $deletedAttributeProvider;

    /** @var DeletedAttributeProviderDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->deletedAttributeProvider = $this->createMock(DeletedAttributeProviderInterface::class);

        $this->decorator = new DeletedAttributeProviderDecorator($this->deletedAttributeProvider);
    }

    public function testGetAttributesByIds()
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

    public function testRemoveAttributeValues()
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
