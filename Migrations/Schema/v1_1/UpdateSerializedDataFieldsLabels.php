<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateSerializedDataFieldsLabels implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateSerializedDataFieldsLabelsQuery($schema));
    }
}
