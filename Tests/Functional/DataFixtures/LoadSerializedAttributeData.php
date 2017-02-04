<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class LoadSerializedAttributeData extends LoadAttributeData
{
    const SERIALIZED_ATTRIBUTE = 'serialized_attribute';

    /**
     * @var array
     */
    protected static $attributes = [
        self::SERIALIZED_ATTRIBUTE => [
            'extend' => [
                'owner' => ExtendScope::OWNER_CUSTOM,
                'state' => ExtendScope::STATE_ACTIVE,
                'is_serialized' => true,
            ],
        ],
    ];
}
