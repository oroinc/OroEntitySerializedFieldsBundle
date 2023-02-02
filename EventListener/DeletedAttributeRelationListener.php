<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\DeletedAttributeRelationListener
as BaseDeletedAttributeRelationListener;

/**
 * Overridden original listener class to produce MQ messages with entity serialized data's field names as they are .
 * @see \Oro\Bundle\EntityConfigBundle\EventListener\DeletedAttributeRelationListener
 */
class DeletedAttributeRelationListener extends BaseDeletedAttributeRelationListener
{
    /**
     * {@inheritdoc}
     */
    protected function getAttributeName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }
}
