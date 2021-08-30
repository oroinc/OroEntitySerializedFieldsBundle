<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\DeletedSerializedAttributeProvider;

class DeletedSerializedAttributeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigModelManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configModelManager;

    /** @var AttributeValueProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeValueProvider;

    /** @var DeletedSerializedAttributeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configModelManager = $this->createMock(ConfigModelManager::class);
        $this->attributeValueProvider = $this->createMock(AttributeValueProviderInterface::class);

        $this->provider = new DeletedSerializedAttributeProvider(
            $this->configModelManager,
            $this->attributeValueProvider
        );
    }

    public function testGetAttributesByIds()
    {
        $ids = [1, 2];
        $serializedAttribute = $this->getAttribute(true);
        $fieldColumnAttribute = $this->getAttribute(false);

        $repository = $this->createMock(FieldConfigModelRepository::class);
        $repository->expects($this->once())
            ->method('getAttributesByIds')
            ->with($ids)
            ->willReturn([
                $serializedAttribute,
                $fieldColumnAttribute
            ]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(FieldConfigModel::class)
            ->willReturn($repository);
        $this->configModelManager->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->configModelManager->expects($this->once())
            ->method('checkDatabase')
            ->willReturn(true);

        $attributes = $this->provider->getAttributesByIds($ids);
        $this->assertCount(1, $attributes);
        $this->assertEquals($serializedAttribute, array_pop($attributes));
    }

    private function getAttribute(bool $isSerialized): FieldConfigModel
    {
        $attribute = new FieldConfigModel();
        $attribute->fromArray('extend', ['is_serialized' => $isSerialized]);

        return $attribute;
    }
}
