<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Provider;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;

/**
 * Provides given entity attributes' values clean up.
 */
class SerializedAttributeValueProvider implements AttributeValueProviderInterface
{
    const BATCH_SIZE = 1000;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function removeAttributeValues(AttributeFamily $attributeFamily, array $names)
    {
        $manager = $this->doctrineHelper->getEntityManagerForClass($attributeFamily->getEntityClass());

        $queryBuilder = $manager->createQueryBuilder();
        $queryBuilder
            ->select('partial entity.{id, serialized_data}')
            ->from($attributeFamily->getEntityClass(), 'entity')
            ->where($queryBuilder->expr()->eq('entity.attributeFamily', ':attributeFamily'))
            ->setParameter('attributeFamily', $attributeFamily);

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        foreach ($iterator as $entity) {
            $itemsCount++;
            foreach ($names as $name) {
                if (!method_exists($entity, '__set')) {
                    continue;
                }

                $entity->{$name} = null;
                $manager->persist($entity);
            }
            if (0 === $itemsCount % self::BATCH_SIZE) {
                $manager->flush();
                $manager->clear();
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $manager->flush();
            $manager->clear();
        }
    }
}
