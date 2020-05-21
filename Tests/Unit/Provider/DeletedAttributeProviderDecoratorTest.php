<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\DeletedAttributeProviderDecorator;

class DeletedAttributeProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DeletedAttributeProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $deletedAttributeProvider;

    /**
     * @var DeletedAttributeProviderDecorator
     */
    protected $decorator;

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
        /** @var EntityManagerInterface $entityManager */
        $attributeFamily = new AttributeFamily();
        $names = [];
        $this->deletedAttributeProvider->expects($this->once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, $names);

        $this->decorator->removeAttributeValues($attributeFamily, $names);
    }

    /**
     * @param bool $isSerialized
     * @return FieldConfigModel
     */
    protected function getAttribute($isSerialized)
    {
        $attribute = new FieldConfigModel();
        $attribute->fromArray('extend', ['is_serialized' => $isSerialized]);

        return $attribute;
    }
}
