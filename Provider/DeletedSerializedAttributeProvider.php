<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProvider;

class DeletedSerializedAttributeProvider extends DeletedAttributeProvider
{
    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIds(array $ids)
    {
        $attributes = parent::getAttributesByIds($ids);

        return array_filter($attributes, function (FieldConfigModel $attribute) {
            return isset($attribute->toArray('extend')['is_serialized'])
                ? $attribute->toArray('extend')['is_serialized'] === true
                : false;
        });
    }
}
