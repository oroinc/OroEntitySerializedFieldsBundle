<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntitySerializedFieldsBundle\Migrations\Schema\v1_0\UpdateCustomFieldsWithStorageType;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroEntitySerializedFieldsBundleInstaller implements Installation, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $queries->addQuery(new UpdateCustomFieldsWithStorageType($schema));
        }
    }
}
