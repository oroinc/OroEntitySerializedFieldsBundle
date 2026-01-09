<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class OroEntitySerializedFieldsBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_3';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $queries->addQuery(new UpdateCustomFieldsWithStorageType());
        }
    }
}
