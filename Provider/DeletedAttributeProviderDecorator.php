<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;

class DeletedAttributeProviderDecorator implements DeletedAttributeProviderInterface
{
    /**
     * @var DeletedAttributeProviderInterface
     */
    protected $deletedAttributeProvider;

    public function __construct(DeletedAttributeProviderInterface $deletedAttributeProvider)
    {
        $this->deletedAttributeProvider = $deletedAttributeProvider;
    }

    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIds(array $ids)
    {
        $attributes = $this->deletedAttributeProvider->getAttributesByIds($ids);

        return array_filter($attributes, function (FieldConfigModel $attribute) {
            return empty($attribute->toArray('extend')['is_serialized']);
        });
    }

    public function removeAttributeValues(AttributeFamily $attributeFamily, array $names)
    {
        $this->deletedAttributeProvider->removeAttributeValues($attributeFamily, $names);
    }
}
