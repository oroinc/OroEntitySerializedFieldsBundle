<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\UpdateCustomFieldsWithStorageType;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntitySerializedFieldsBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new UpdateCustomFieldsWithStorageType());
    }
}
