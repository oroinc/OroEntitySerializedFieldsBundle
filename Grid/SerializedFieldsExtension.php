<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Grid;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;

class SerializedFieldsExtension extends DynamicFieldsExtension
{
    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 260;
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));

        /** @var QueryBuilder $qb */
        $qb        = $datasource->getQueryBuilder();
        $fromParts = $qb->getDQLPart('from');
        $alias     = false;

        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            if ($this->entityClassResolver->getEntityClass($fromPart->getFrom()) == $entityClassName) {
                $alias = $fromPart->getAlias();
            }
        }

        if ($alias === false) {
            // add entity if it not exists in from clause
            $alias = 'o';
            $qb->from($entityClassName, $alias);
        }

        /**
         * Exclude serialized fields from query
         */
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $extendConfig = $extendConfigProvider->getConfig($entityClassName);
        if ($extendConfig->is('is_extend')) {
            /** @var FieldConfigId[] $fields */
            $fields = $this->getFields($config);
            $fields = array_filter(
                $fields,
                function (FieldConfigId $field) use ($extendConfigProvider, $entityClassName) {
                    return $extendConfigProvider
                        ->getConfig($entityClassName, $field->getFieldName())
                        ->is('is_serialized');
                }
            );
            array_walk(
                $fields,
                function (&$value) use ($alias) {
                    $value = sprintf('%s.%s', $alias, $value->getFieldName());
                }
            );

            /** @var Select[] $selects */
            $selects = $qb->getDQLPart('select');
            $selects = array_filter(
                $selects,
                function (Select $select) use ($fields) {
                    return !in_array($select->getParts()[0], $fields);
                }
            );

            $qb->resetDQLPart('select');
            foreach ($selects as $select) {
                $qb->addSelect($select->getParts());
            }

            $qb->addSelect(sprintf('%s.%s', $alias, 'serialized_data'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        //leave empty. we do not need any processing for serialized fields
    }
}
