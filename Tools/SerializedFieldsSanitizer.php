<?php

namespace Oro\Bundle\EntitySerializedFieldsBundle\Tools;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntitySerializedFieldsBundle\Provider\SerializedFieldsProvider;
use Oro\Bundle\SecurityBundle\Tools\AbstractFieldsSanitizer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\DBAL\Types\ArrayType;

/**
 * Sanitizes serialized fields of specified type.
 *
 * THIS CLASS MUST BE USED WITH CAUTION AS IT CAN MAKE MASS MODIFICATION OF USER CONTENT!
 */
class SerializedFieldsSanitizer extends AbstractFieldsSanitizer
{
    private const SERIALIZED_DATA = 'serialized_data';

    private SerializedFieldsProvider $serializedFieldsProvider;

    private ArrayType $arrayType;

    public function __construct(
        ManagerRegistry $managerRegistry,
        HtmlTagHelper $htmlTagHelper,
        SerializedFieldsProvider $serializedFieldsProvider
    ) {
        parent::__construct($managerRegistry, $htmlTagHelper);

        $this->managerRegistry = $managerRegistry;
        $this->serializedFieldsProvider = $serializedFieldsProvider;
        $this->arrayType = new ArrayType();
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    protected function getFieldsToSanitize(ClassMetadataInfo $classMetadata, string $fieldTypeToSanitize): array
    {
        return $this->serializedFieldsProvider->getSerializedFields($classMetadata->getName(), $fieldTypeToSanitize);
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $fields
     */
    protected function getRowsToSanitizeQueryBuilder(ClassMetadataInfo $classMetadata, array $fields): QueryBuilder
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($classMetadata->getName());

        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $expr = $entityManager->getExpressionBuilder();
        $qbFieldName = QueryBuilderUtil::sprintf('entity.%s', self::SERIALIZED_DATA);

        return $entityManager
            ->getRepository($classMetadata->getName())
            ->createQueryBuilder('entity')
            ->select(
                QueryBuilderUtil::sprintf('entity.%s as id', $classMetadata->getSingleIdentifierFieldName()),
                $qbFieldName
            )
            ->where(
                $expr->isNotNull($qbFieldName),
                $expr->neq($qbFieldName, ':empty'),
                $expr->neq($qbFieldName, ':null')
            )
            ->setParameter('empty', '', Types::STRING)
            ->setParameter('null', $this->arrayType->convertToDatabaseValue(null, $platform), Types::STRING);
    }

    private function getSerializedData($value): array
    {
        return (array)$this->arrayType->convertToPHPValue(
            $value,
            $this->managerRegistry->getConnection()->getDatabasePlatform()
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $fields
     */
    protected function sanitizeEntityRow(
        ClassMetadataInfo $classMetadata,
        array $row,
        array $fields,
        int $mode,
        array $modeArguments,
        bool $applyChanges
    ): array {
        $className = $classMetadata->getName();
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass($className);
        $affectedFields = [];

        if (!empty($row[self::SERIALIZED_DATA])) {
            $row[self::SERIALIZED_DATA] = $this->getSerializedData($row[self::SERIALIZED_DATA]);
            $affectedSerializedFields = $this
                ->sanitizeSerializedData($row[self::SERIALIZED_DATA], $fields, $mode, $modeArguments);
            if ($affectedSerializedFields) {
                $affectedFields = array_merge($affectedFields, $affectedSerializedFields);
            }
        }

        if ($applyChanges && $affectedFields) {
            $placeholder = QueryBuilderUtil::sprintf(':value_%s', self::SERIALIZED_DATA);

            $entityManager
                ->createQueryBuilder()
                ->update($className, 'entity')
                ->set(QueryBuilderUtil::sprintf('entity.%s', self::SERIALIZED_DATA), $placeholder)
                ->setParameter($placeholder, $row[self::SERIALIZED_DATA], Types::ARRAY)
                ->where(
                    $entityManager->getExpressionBuilder()->eq(
                        QueryBuilderUtil::sprintf('entity.%s', $classMetadata->getSingleIdentifierFieldName()),
                        ':entity_id'
                    )
                )
                ->setParameter('entity_id', $row['id'])
                ->getQuery()
                ->execute();
        }

        return $affectedFields;
    }

    /**
     * @param array $serializedData
     * @param string[] $fields
     * @param int $mode Sanitization mode, a MODE_* constant from {@see AbstractFieldsSanitizer}
     * @param array $modeArguments Extra arguments specific for the mode of the chosen sanitization method.
     *
     * @return string[] Affected fields.
     */
    private function sanitizeSerializedData(
        array &$serializedData,
        array $fields,
        int $mode,
        array $modeArguments
    ): array {
        $affectedFields = [];
        foreach ($fields as $fieldName) {
            if (empty($serializedData[$fieldName])) {
                // Skips if field is empty.
                continue;
            }

            $sanitizedValue = $this->sanitizeText((string)$serializedData[$fieldName], $mode, $modeArguments);
            if ($sanitizedValue === $serializedData[$fieldName]) {
                // Skips field if data is not changed after sanitizing.
                continue;
            }

            // Put changed content back to serialized data.
            $serializedData[$fieldName] = $sanitizedValue;

            $affectedFields[] = $fieldName;
        }

        return $affectedFields;
    }
}
